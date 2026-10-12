<?php

namespace App\Models;

use App\Traits\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, BranchScope;

    protected $fillable = [
        'order_id', 'branch_id',
        'amount', 'method', 'reference_no', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount'  => 'decimal:2',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================
    public function order(): BelongsTo  { return $this->belongsTo(Order::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }

    // ============================================
    // SCOPES
    // ============================================
    public function scopeFromOwnStore($q) { return $q->whereNull('branch_id'); }
    public function scopeFromBranch($q, int $branchId) { return $q->where('branch_id', $branchId); }

    // ============================================
    // HELPERS
    // ============================================
    public function isFromOwnStore(): bool { return is_null($this->branch_id); }

    public function isCash(): bool   { return $this->method === 'cash'; }
    public function isOnline(): bool { return in_array($this->method, ['gcash', 'paymaya', 'grab_pay', 'card']); }

    public function getMethodLabel(): string
    {
        return match ($this->method) {
            'cash'     => '💵 Cash',
            'gcash'    => '📱 GCash',
            'paymaya'  => '📱 PayMaya',
            'grab_pay' => '🛵 GrabPay',
            'card'     => '💳 Card',
            default    => ucfirst($this->method),
        };
    }
}