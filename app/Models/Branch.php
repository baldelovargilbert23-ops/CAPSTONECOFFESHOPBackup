<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'name', 'code', 'logo_path', 'email', 'contact_number',
        'address', 'barangay', 'city', 'province', 'description',
        'branch_manager_id', 'manager_name', 'manager_id_path',
        'permit_path', 'location_photo_path',
        'business_permit_path', 'dti_sec_registration_path', 'bir_certificate_path',
        'barangay_clearance_path', 'sanitary_permit_path', 'fire_safety_path',
        'dti_sec_number', 'bir_tin', 'mayors_permit_number',
        'is_active',
        'approval_status', 'approved_at', 'approved_by', 'rejection_reason',
        'created_by', 'settings',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'approved_at' => 'datetime',
        'settings'    => 'array',
    ];

    /**
     * Memoized loyalty-enabled flag for the current request.
     */
    protected ?bool $loyaltyEnabledCache = null;

    // ============================================
    // AUTO-GENERATE UNIQUE BRANCH CODE (per tenant)
    // ============================================
    public static function generateCode(int $tenantId, ?string $prefix = null): string
    {
        return DB::transaction(function () use ($tenantId, $prefix) {
            $prefix = $prefix ?: static::resolvePrefix($tenantId);

            $lastCode = static::where('tenant_id', $tenantId)
                ->where('code', 'like', $prefix . '-%')
                ->lockForUpdate()
                ->orderByDesc('code')
                ->value('code');

            $nextNumber = 1;
            if ($lastCode && preg_match('/' . preg_quote($prefix, '/') . '-(\d+)$/', $lastCode, $m)) {
                $nextNumber = ((int) $m[1]) + 1;
            }

            $candidate = $prefix . '-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);

            while (static::where('tenant_id', $tenantId)->where('code', $candidate)->exists()) {
                $nextNumber++;
                $candidate = $prefix . '-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
            }

            return $candidate;
        });
    }

    protected static function resolvePrefix(int $tenantId): string
    {
        $tenant = Tenant::find($tenantId);

        if ($tenant && ! empty($tenant->code)) {
            $prefix = strtoupper(preg_replace('/[^A-Za-z]/', '', $tenant->code));
        } elseif ($tenant && ! empty($tenant->name)) {
            $prefix = strtoupper(preg_replace('/[^A-Za-z]/', '', $tenant->name));
        } else {
            $prefix = 'BRA';
        }

        return str_pad(substr($prefix, 0, 3), 3, 'X');
    }

    // ============================================
    // BOOT
    // ============================================
    protected static function booted(): void
    {
        static::creating(function (Branch $branch) {
            if (empty($branch->code) && ! empty($branch->tenant_id)) {
                $branch->code = static::generateCode($branch->tenant_id);
            }
        });

        static::deleting(function (Branch $branch) {
            foreach ([
                $branch->logo_path,
                $branch->permit_path,
                $branch->manager_id_path,
                $branch->location_photo_path,
                $branch->business_permit_path,
                $branch->dti_sec_registration_path,
                $branch->bir_certificate_path,
                $branch->barangay_clearance_path,
                $branch->sanitary_permit_path,
                $branch->fire_safety_path,
            ] as $path) {
                if ($path && ! Str::startsWith($path, ['http://', 'https://'])) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
        });
    }

    // ============================================
    // APPROVAL WORKFLOW
    // ============================================
    public function isPending(): bool   { return $this->approval_status === 'pending'; }
    public function isApproved(): bool  { return $this->approval_status === 'approved'; }
    public function isRejected(): bool  { return $this->approval_status === 'rejected'; }

    public function approve(?int $approvedBy = null): void
    {
        $this->update([
            'approval_status'  => 'approved',
            'approved_at'      => now(),
            'approved_by'      => $approvedBy,
            'rejection_reason' => null,
        ]);
    }

    public function reject(string $reason, ?int $rejectedBy = null): void
    {
        $this->update([
            'approval_status'  => 'rejected',
            'rejection_reason' => $reason,
            'approved_by'      => $rejectedBy,
        ]);
    }

    public function canOperate(): bool
    {
        return $this->is_active && $this->isApproved();
    }

    public function scopePending($q)  { return $q->where('approval_status', 'pending'); }
    public function scopeApproved($q) { return $q->where('approval_status', 'approved'); }
    public function scopeRejected($q) { return $q->where('approval_status', 'rejected'); }

    // ============================================
    // LOYALTY
    // ============================================
    /**
     * Branch-level toggle AND tenant-level master switch must both be on.
     * Memoized so repeated calls in one request don't re-hit the DB.
     */
    public function isLoyaltyEnabled(): bool
    {
        if ($this->loyaltyEnabledCache !== null) {
            return $this->loyaltyEnabledCache;
        }

        $branchSetting = $this->loyaltySettings;

        if ($branchSetting && ! $branchSetting->is_enabled) {
            return $this->loyaltyEnabledCache = false;
        }

        $tenantSetting = LoyaltySetting::where('tenant_id', $this->tenant_id)->first();

        return $this->loyaltyEnabledCache = (bool) ($tenantSetting?->is_enabled);
    }

    // ============================================
    // RELATIONSHIPS
    // ============================================
    public function tenant(): BelongsTo  { return $this->belongsTo(Tenant::class); }
    public function users(): HasMany     { return $this->hasMany(User::class); }

    public function manager(): BelongsTo      { return $this->belongsTo(User::class, 'branch_manager_id'); }
    public function approvedBy(): BelongsTo   { return $this->belongsTo(User::class, 'approved_by'); }
    public function createdBy(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }

    public function inventories(): HasMany    { return $this->hasMany(Inventory::class); }
    public function orders(): HasMany         { return $this->hasMany(Order::class); }
    public function payments(): HasMany       { return $this->hasMany(Payment::class); }
    public function purchases(): HasMany      { return $this->hasMany(Purchase::class); }
    public function inventoryLogs(): HasMany  { return $this->hasMany(InventoryLog::class); }
    public function rewardRedemptions(): HasMany { return $this->hasMany(RewardRedemption::class); }
    public function reviews(): HasMany        { return $this->hasMany(Review::class); }

    public function loyaltySettings(): HasOne
    {
        return $this->hasOne(BranchLoyaltySetting::class, 'branch_id');
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

        if ($this->tenant && $this->tenant->logo_path) {
            return $this->tenant->logo_url;
        }

        return 'data:image/svg+xml;utf8,' . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#d2964b" stroke-width="2"><path d="M3 9l1-5h16l1 5M4 9v11h16V9M9 22V12h6v10"/></svg>'
        );
    }

    // ============================================
    // HELPERS
    // ============================================
    public function getStaffCount(): int
    {
        return $this->users()
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->count();
    }
}