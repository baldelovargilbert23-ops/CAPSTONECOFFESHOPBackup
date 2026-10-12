<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'branch_id', 'category_id', 'type', 'unit',
        'name', 'sku', 'price', 'cost',
        'image', 'description', 'is_active',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'cost'      => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================
    public function tenant(): BelongsTo       { return $this->belongsTo(Tenant::class); }
    public function branch(): BelongsTo       { return $this->belongsTo(Branch::class); }
    public function category(): BelongsTo     { return $this->belongsTo(Category::class); }
    public function inventories(): HasMany    { return $this->hasMany(Inventory::class); }
    public function reviews(): HasMany        { return $this->hasMany(Review::class); }
    public function orderItems(): HasMany     { return $this->hasMany(OrderItem::class); }

    /**
     * The recipe LINES (join rows with quantity + unit).
     * Used where you need "18g of Coffee Beans" per drink.
     */
    public function recipe(): HasMany
    {
        return $this->hasMany(ProductIngredient::class, 'product_id');
    }

    /**
     * The INGREDIENT PRODUCTS in this product's recipe.
     * Returns Product models — each carries ->pivot->quantity and ->pivot->unit.
     * Used by analytics/blades that expect `$product->ingredients`.
     */
    public function ingredients()
    {
        return $this->belongsToMany(
            Product::class,
            'product_ingredients',
            'product_id',
            'ingredient_id'
        )->withPivot('quantity', 'unit')->withTimestamps();
    }

    /**
     * For an ingredient product, which finished products use it.
     */
    public function usedIn(): HasMany
    {
        return $this->hasMany(ProductIngredient::class, 'ingredient_id');
    }

    // ============================================
    // SCOPES
    // ============================================
    public function scopeFinished($q)    { return $q->where('type', 'finished'); }
    public function scopeIngredients($q) { return $q->where('type', 'ingredient'); }

    /**
     * Catalog owned by a specific store location. A null branch ID is the tenant's main store.
     */
    public function scopeForLocation($query, int $tenantId, ?int $branchId = null)
    {
        $query->where('tenant_id', $tenantId);

        return $branchId === null
            ? $query->whereNull('branch_id')
            : $query->where('branch_id', $branchId);
    }

    // ============================================
    // HELPERS
    // ============================================

    /**
     * Inventory row for a location.
     * $branchId = null → the tenant's own store.
     */
    public function inventoryForBranch(?int $branchId): ?Inventory
    {
        $productBranchId = $this->branch_id === null ? null : (int) $this->branch_id;
        if ($productBranchId !== $branchId) {
            return null;
        }

        $query = $this->inventories();

        if ($branchId === null) {
            $query->whereNull('branch_id');
        } else {
            $query->where('branch_id', $branchId);
        }

        return $query->first();
    }

    /**
     * Current stock at a location.
     * $branchId = null → the tenant's own store.
     */
    public function getStockForBranch(?int $branchId): float
    {
        return (float) ($this->inventoryForBranch($branchId)?->quantity ?? 0);
    }

    public function hasStockInBranch(?int $branchId, float $quantity = 1): bool
    {
        return $this->getStockForBranch($branchId) >= $quantity;
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) return null;
        if (Str::startsWith($this->image, ['http://', 'https://'])) return $this->image;
        return asset('storage/' . ltrim($this->image, '/'));
    }

    /**
     * Does this product have a recipe? (Only meaningful for finished products.)
     */
    public function hasRecipe(): bool
    {
        return $this->recipe()->exists();
    }

    /**
     * How many units of this product can be made at a location,
     * based on its recipe and the ingredient stock there.
     *
     * $branchId = null → the tenant's own store.
     */
    public function canMakeInBranch(?int $branchId): int
    {
        return \App\Support\StockService::availableQuantity($this, $branchId, (int) $this->tenant_id);
    }
}
