<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'slug', 'email', 'contact_number', 'address',
        'description', 'tagline', 'introduction',
        'logo_path', 'cover_path',
        'business_type', 'business_type_other', 'business_address',
        'owner_name', 'owner_phone', 'owner_id_path',
        'status', 'approved_at', 'approved_by', 'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    // ============================================
    // BOOT
    // ============================================
    // Tenant IS its own store. No main branch to auto-create.
    protected static function booted(): void
    {
        // Intentionally empty.
    }

    // ============================================
    // APPROVAL
    // ============================================
    public function approve(?int $approvedBy = null): void
    {
        $this->update([
            'status'      => 'active',
            'approved_at' => now(),
            'approved_by' => $approvedBy,
        ]);
    }

    // ============================================
    // RELATIONSHIPS
    // ============================================
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class)->orderBy('name');
    }

    public function users(): HasMany            { return $this->hasMany(User::class); }
    public function products(): HasMany         { return $this->hasMany(Product::class); }
    public function categories(): HasMany       { return $this->hasMany(Category::class); }
    public function orders(): HasMany           { return $this->hasMany(Order::class); }
    public function inventories(): HasMany      { return $this->hasMany(Inventory::class); }
    public function inventoryLogs(): HasMany    { return $this->hasMany(InventoryLog::class); }
    public function purchases(): HasMany        { return $this->hasMany(Purchase::class); }
    public function reviews(): HasMany          { return $this->hasMany(Review::class); }
    public function rewardItems(): HasMany      { return $this->hasMany(RewardItem::class); }
    public function customerPoints(): HasMany   { return $this->hasMany(CustomerPoint::class); }

    public function loyaltySettings()
    {
        return $this->hasOne(LoyaltySetting::class);
    }

    public function owner()
    {
        return $this->users()->where('role', 'owner')->first();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ============================================
    // ACCESSORS
    // ============================================
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            if (Str::startsWith($this->logo_path, ['http://', 'https://'])) {
                return $this->logo_path;
            }
            if (Storage::disk('public')->exists($this->logo_path)) {
                return Storage::url($this->logo_path);
            }
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name)
             . '&size=256&background=f59e0b&color=0c0a09&bold=true&font-size=0.35';
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (! $this->cover_path) return null;
        if (Str::startsWith($this->cover_path, ['http://', 'https://'])) return $this->cover_path;
        if (Storage::disk('public')->exists($this->cover_path)) return Storage::url($this->cover_path);
        return null;
    }

    public function getTaglineAttribute(): string
    {
        return $this->attributes['tagline'] ?? $this->description ?? 'A cozy spot serving freshly brewed coffee.';
    }

    // ============================================
    // HELPERS
    // ============================================
    public function isApproved(): bool  { return $this->status === 'active'; }
    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isRejected(): bool  { return $this->status === 'rejected'; }
    public function isSuspended(): bool { return $this->status === 'suspended'; }

    public function scopePending($q)  { return $q->where('status', 'pending'); }
    public function scopeActive($q)   { return $q->where('status', 'active'); }
    public function scopeRejected($q) { return $q->where('status', 'rejected'); }
}