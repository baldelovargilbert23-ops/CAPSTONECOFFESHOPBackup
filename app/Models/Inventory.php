<?php

namespace App\Models;

use App\Traits\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Inventory extends Model
{
    use HasFactory, BranchScope;

    protected $fillable = [
        'tenant_id', 'branch_id', 'product_id',
        'quantity', 'min_stock', 'is_active',
    ];

    protected $casts = [
        'quantity'  => 'decimal:4',
        'min_stock' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================
    public function tenant(): BelongsTo   { return $this->belongsTo(Tenant::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function product(): BelongsTo  { return $this->belongsTo(Product::class); }

    // ============================================
    // SCOPES
    // ============================================
    public function scopeActive($q) { return $q->where('is_active', true); }

    public function scopeLow($q)
    {
        return $q->whereColumn('quantity', '<=', 'min_stock')->where('quantity', '>', 0);
    }

    public function scopeOut($q) { return $q->where('quantity', '<=', 0); }

    /**
     * The tenant's own store (branch_id = null).
     */
    public function scopeOwnStore($q) { return $q->whereNull('branch_id'); }

    /**
     * Any specific branch.
     */
    public function scopeInBranch($q, int $branchId) { return $q->where('branch_id', $branchId); }

    // ============================================
    // ATOMIC STOCK OPERATIONS
    // ============================================
    public function decrementStock(float $qty, string $reason = 'sale', ?int $userId = null): void
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        DB::transaction(function () use ($qty, $reason, $userId) {
            $row = static::withoutGlobalScopes()
                ->whereKey($this->id)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                throw new \RuntimeException("Inventory row #{$this->id} not found.");
            }

            if ($row->quantity < $qty) {
                throw new \RuntimeException("Insufficient stock for product #{$row->product_id}");
            }

            $row->decrement('quantity', $qty);

            InventoryLog::withoutGlobalScopes()->create([
                'tenant_id'  => $row->tenant_id,
                'branch_id'  => $row->branch_id,
                'product_id' => $row->product_id,
                'user_id'    => $userId,
                'type'       => 'OUT',
                'quantity'   => $qty,
                'reference'  => $reason,
            ]);
        });
    }

    public function incrementStock(float $qty, string $reason = 'restock', ?int $userId = null): void
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        DB::transaction(function () use ($qty, $reason, $userId) {
            $row = static::withoutGlobalScopes()
                ->whereKey($this->id)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                throw new \RuntimeException("Inventory row #{$this->id} not found.");
            }

            $row->increment('quantity', $qty);

            InventoryLog::withoutGlobalScopes()->create([
                'tenant_id'  => $row->tenant_id,
                'branch_id'  => $row->branch_id,
                'product_id' => $row->product_id,
                'user_id'    => $userId,
                'type'       => 'IN',
                'quantity'   => $qty,
                'reference'  => $reason,
            ]);
        });
    }

    // ============================================
    // HELPERS
    // ============================================
    public function isLow(): bool  { return $this->quantity > 0 && $this->quantity <= $this->min_stock; }
    public function isOut(): bool  { return $this->quantity <= 0; }
    public function isOk(): bool   { return $this->quantity > $this->min_stock; }

    /**
     * Find or create an inventory row for a specific branch + product.
     * $branchId = null → the tenant's own store.
     */
    public static function forBranchProduct(int $tenantId, ?int $branchId, int $productId): self
    {
        $query = static::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId);

        if ($branchId === null) {
            $query->whereNull('branch_id');
        } else {
            $query->where('branch_id', $branchId);
        }

        $existing = $query->first();
        if ($existing) return $existing;

        return static::create([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'product_id' => $productId,
            'quantity'  => 0,
            'min_stock' => 5,
            'is_active' => true,
        ]);
    }
} 
