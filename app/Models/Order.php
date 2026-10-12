<?php

namespace App\Models;

use App\Traits\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory, BranchScope;

    protected $fillable = [
        'tenant_id', 'branch_id', 'user_id',
        'order_number', 'total_amount',
        'status', 'payment_method', 'payment_status',
        'payment_session_id',
        'notes', 'is_reward_order',
        'barista_id', 'preferred_barista_id',
        'claimed_at', 'ready_at',
        'loyalty_awarded_at', 'loyalty_points_awarded',
    ];

    protected $casts = [
        'total_amount'           => 'decimal:2',
        'payment_status'         => 'string',
        'is_reward_order'        => 'boolean',
        'claimed_at'             => 'datetime',
        'ready_at'               => 'datetime',
        'loyalty_awarded_at'     => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================
    public function tenant(): BelongsTo           { return $this->belongsTo(Tenant::class); }
    public function branch(): BelongsTo           { return $this->belongsTo(Branch::class); }
    public function user(): BelongsTo             { return $this->belongsTo(User::class); }
    public function barista(): BelongsTo          { return $this->belongsTo(User::class, 'barista_id'); }
    public function preferredBarista(): BelongsTo { return $this->belongsTo(User::class, 'preferred_barista_id'); }
    public function items(): HasMany              { return $this->hasMany(OrderItem::class); }
    public function payment(): HasOne             { return $this->hasOne(Payment::class); }
    public function payments(): HasMany           { return $this->hasMany(Payment::class); }
    public function rewardRedemption(): HasOne    { return $this->hasOne(RewardRedemption::class); }

    // ============================================
    // SCOPES
    // ============================================
    public function scopeFromOwnStore($q) { return $q->whereNull('branch_id'); }
    public function scopeFromBranch($q, int $branchId) { return $q->where('branch_id', $branchId); }

    // ============================================
    // HELPERS
    // ============================================
    public function isFromOwnStore(): bool
    {
        return is_null($this->branch_id);
    }

    // ============================================
    // ACCESSORS
    // ============================================
    public function getFormattedNumberAttribute(): string
    {
        return '#' . str_pad($this->order_number, 3, '0', STR_PAD_LEFT);
    }

    // ============================================
    // STATUS HELPERS
    // ============================================
    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isPreparing(): bool { return $this->status === 'preparing'; }
    public function isReady(): bool     { return $this->status === 'ready'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending'   => '⏳ Pending',
            'preparing' => '🔥 Preparing',
            'ready'     => '✅ Ready',
            'completed' => '🎉 Completed',
            'cancelled' => '❌ Cancelled',
            default     => $this->status,
        };
    }

    public function isRewardOrder(): bool { return $this->is_reward_order ?? false; }

    public function isClaimed(): bool             { return $this->barista_id !== null; }
    public function isAssignedTo(int $userId): bool { return $this->barista_id === $userId; }
    public function hasPreferredBarista(): bool   { return $this->preferred_barista_id !== null; }

    // ============================================
    // PAYMENT HELPERS
    // ============================================
    public function isPaymentPending(): bool { return $this->payment_status === 'pending'; }
    public function isPaymentPaid(): bool    { return $this->payment_status === 'paid'; }

    public function markPaymentAsPaid(): void
    {
        $this->update(['payment_status' => 'paid']);
    }

    // ============================================
    // LOYALTY HELPERS
    // ============================================
    public function hasLoyaltyBeenAwarded(): bool
    {
        return $this->loyalty_awarded_at !== null;
    }

    public function markLoyaltyAwarded(int $points): void
    {
        $this->forceFill([
            'loyalty_awarded_at'     => now(),
            'loyalty_points_awarded' => $points,
        ])->saveQuietly();
    }
}
