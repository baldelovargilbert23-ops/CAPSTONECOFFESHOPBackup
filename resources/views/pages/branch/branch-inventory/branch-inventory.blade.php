<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Support\UnitConverter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.branch')] class extends Component
{
    // Filters
    public string $search = '';
    public string $typeFilter = 'all';      // all | finished | ingredient
    public string $stockFilter = 'all';     // all | in_stock | low | out
    public string $sortBy = 'name';
    public $categoryFilter = null;

    // Quick adjustment panel
    public $product_id = null;
    public $quantity_to_add = null;
    public string $adjustment_type = 'add';
    public string $adjust_unit = 'pcs';

    // Branch
    #[Locked]
    public $tenantId;
    #[Locked]
    public $branchId;
    public $branch;

    // Edit modal
    public bool $showEditModal = false;
    #[Locked]
    public $editingProductId = null;
    #[Locked]
    public string $edit_type = 'finished';
    #[Locked]
    public string $edit_unit = 'pcs';
    #[Locked]
    public bool $edit_has_recipe = false;
    public $edit_name;
    public $edit_price;
    public $edit_cost;
    public $edit_stock = 0;
    public $edit_min_stock = null;
    public $edit_category_id;

    protected function rules()
    {
        return [
            'product_id'      => [
                'required', 'integer',
                Rule::exists('products', 'id')
                    ->where('tenant_id', $this->tenantId)
                    ->where('branch_id', $this->branchId),
            ],
            'quantity_to_add' => 'required|numeric|gt:0|max:10000000',
            'adjustment_type' => 'required|in:add,subtract',
            'adjust_unit'     => 'required|in:g,kg,ml,L,pcs',
        ];
    }

    protected function messages()
    {
        return [
            'product_id.required'      => 'Please select an item.',
            'quantity_to_add.required' => 'Quantity is required.',
            'quantity_to_add.numeric'  => 'Quantity must be a number.',
            'quantity_to_add.gt'       => 'Quantity must be greater than 0.',
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        $this->tenantId = $user->tenant_id;
        $this->branchId = $user->branch_id;
        $this->branch   = Branch::where('tenant_id', $this->tenantId)->find($this->branchId);
    }

    // ================================================================
    // COMPUTED
    // ================================================================
    #[Computed]
    public function categories()
    {
        return Category::where('tenant_id', $this->tenantId)
            ->orderBy('type')
            ->orderBy('cat_name')
            ->get();
    }

    #[Computed]
    public function editCategories()
    {
        return Category::where('tenant_id', $this->tenantId)
            ->where('type', $this->edit_type)
            ->orderBy('cat_name')
            ->get();
    }

    #[Computed]
    public function stockRequests()
    {
        return Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'stock_request')
            ->with(['items.product', 'user'])
            ->latest()
            ->get();
    }

    /**
     * Every product of this branch with stock figures worked out.
     * - Ingredients / ready-made items: stock = own inventory row, in their own unit.
     * - Products with a recipe: stock = how many can be made from ingredient stock.
     */
    #[Computed]
    public function allProducts()
    {
        $products = Product::forLocation($this->tenantId, $this->branchId)
            ->with(['category', 'recipe.ingredient:id,name,cost,unit'])
            ->withCount('recipe')
            ->orderBy('name')
            ->get();

        // One query for all stock rows (no N+1).
        $store = Inventory::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('is_active', true)
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('MAX(min_stock) as min_qty'))
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        foreach ($products as $product) {
            $row      = $store->get($product->id);
            $rawStock = (float) ($row->total_qty ?? 0);
            $isIng    = $product->type === 'ingredient';
            $unit     = $isIng ? ($product->unit ?: 'g') : 'pcs';
            $minStock = (float) ($row->min_qty ?? ($isIng ? UnitConverter::defaultMinStock($unit) : 5));

            $hasRecipe   = ! $isIng && $product->recipe->isNotEmpty();
            $recipeIssue = false;
            $limiting    = null;
            $display     = $rawStock;
            $unitCost    = (float) ($product->cost ?? 0);

            if ($hasRecipe) {
                $unitCost = 0.0;
                $min      = PHP_INT_MAX;

                foreach ($product->recipe as $line) {
                    $ing = $line->ingredient;
                    if (! $ing || (float) $line->quantity <= 0) {
                        continue;
                    }

                    $ingUnit  = $ing->unit ?: 'g';
                    $lineUnit = $line->unit ?: $ingUnit;

                    if (! UnitConverter::compatible($ingUnit, $lineUnit)) {
                        $recipeIssue = true;
                        $possible    = 0;
                        $unitCost   += (float) ($ing->cost ?? 0) * (float) $line->quantity;
                    } else {
                        // cost: convert recipe amount into the ingredient's own unit
                        $qtyInIng  = UnitConverter::convert((float) $line->quantity, $lineUnit, $ingUnit) ?? (float) $line->quantity;
                        $unitCost += (float) ($ing->cost ?? 0) * $qtyInIng;

                        // capacity: compare both in a common base unit
                        $stockBase = UnitConverter::toBase((float) ($store->get($ing->id)->total_qty ?? 0), $ingUnit);
                        $needBase  = UnitConverter::toBase((float) $line->quantity, $lineUnit);
                        $possible  = $needBase > 0 ? (int) floor(round($stockBase / $needBase, 6)) : 0;
                    }

                    if ($possible < $min) {
                        $min      = $possible;
                        $limiting = $ing->name;
                    }
                }

                $display = $min === PHP_INT_MAX ? 0.0 : (float) max(0, $min);
            }

            $price  = (float) $product->price;
            $margin = $price - $unitCost;

            $product->setAttribute('stock', $rawStock);
            $product->setAttribute('min_stock', $minStock);
            $product->setAttribute('display_stock', $display);
            $product->setAttribute('is_derived_stock', $hasRecipe);
            $product->setAttribute('is_ingredient', $isIng);
            $product->setAttribute('stock_unit', $unit);
            $product->setAttribute('limiting', $limiting);
            $product->setAttribute('recipe_issue', $recipeIssue);
            $product->setAttribute('unit_cost', round($unitCost, 2));
            $product->setAttribute('margin', round($margin, 2));
            $product->setAttribute('margin_pct', $price > 0 ? round($margin / $price * 100, 1) : 0);
            $product->setAttribute('state', $display <= 0 ? 'out' : ($display <= $minStock ? 'low' : 'ok'));
            // Only items that keep their own stock add to inventory value (avoids double-counting recipes).
            $product->setAttribute('stock_value', $hasRecipe ? 0.0 : $unitCost * $rawStock);
        }

        return $products;
    }

    #[Computed]
    public function products()
    {
        $term = trim($this->search);

        $list = $this->allProducts
            ->when($this->typeFilter !== 'all', fn ($c) => $c->where('type', $this->typeFilter))
            ->when($this->categoryFilter, fn ($c) => $c->where('category_id', (int) $this->categoryFilter))
            ->when($term !== '', fn ($c) => $c->filter(fn ($p) =>
                stripos($p->name, $term) !== false
                || stripos((string) $p->sku, $term) !== false
                || stripos($p->category->cat_name ?? '', $term) !== false
            ))
            ->when($this->stockFilter === 'in_stock', fn ($c) => $c->where('state', 'ok'))
            ->when($this->stockFilter === 'low', fn ($c) => $c->where('state', 'low'))
            ->when($this->stockFilter === 'out', fn ($c) => $c->where('state', 'out'));

        $sorted = match ($this->sortBy) {
            'stock_asc'  => $list->sortBy('display_stock'),
            'stock_desc' => $list->sortByDesc('display_stock'),
            'price_asc'  => $list->sortBy('price'),
            'price_desc' => $list->sortByDesc('price'),
            default      => $list->sortBy(fn ($p) => mb_strtolower($p->name)),
        };

        return $sorted->values();
    }

    #[Computed]
    public function stats()
    {
        $all = $this->allProducts;

        return [
            'total'    => $all->count(),
            'in_stock' => $all->where('state', 'ok')->count(),
            'low'      => $all->where('state', 'low')->count(),
            'out'      => $all->where('state', 'out')->count(),
            'value'    => $all->sum('stock_value'),
            'ing'      => $all->where('type', 'ingredient')->count(),
            'fin'      => $all->where('type', 'finished')->count(),
        ];
    }

    // ================================================================
    // HELPERS
    // ================================================================
    protected function branchProduct(int $id): ?Product
    {
        return Product::forLocation($this->tenantId, $this->branchId)->find($id);
    }

    protected function productHasRecipe(Product $product): bool
    {
        return $product->type === 'finished'
            && ProductIngredient::where('product_id', $product->id)->exists();
    }

    protected function unitOf(Product $product): string
    {
        return $product->type === 'ingredient' ? ($product->unit ?: 'g') : 'pcs';
    }

    protected function stepFor(string $unit): float
    {
        return match ($unit) {
            'g', 'ml' => 100.0,
            default   => 1.0,
        };
    }

    protected function refreshData(): void
    {
        unset($this->allProducts, $this->products, $this->stats, $this->stockRequests);
    }

    /**
     * Atomically change a stock row. Returns an error string, or null on success.
     */
    protected function applyDelta(int $productId, float $delta, float $defaultMin = 5.0): ?string
    {
        return DB::transaction(function () use ($productId, $delta, $defaultMin) {
            $inv = Inventory::withoutGlobalScopes()
                ->where('tenant_id', $this->tenantId)
                ->where('branch_id', $this->branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            $current = (float) ($inv->quantity ?? 0);
            $new     = round($current + $delta, 4);

            if ($new < 0) {
                return 'Insufficient stock. Available: ' . UnitConverter::format($current);
            }

            if ($inv) {
                $inv->update(['quantity' => $new]);
            } else {
                Inventory::withoutGlobalScopes()->create([
                    'tenant_id'  => $this->tenantId,
                    'branch_id'  => $this->branchId,
                    'product_id' => $productId,
                    'quantity'   => $new,
                    'min_stock'  => $defaultMin,
                    'is_active'  => true,
                ]);
            }

            return null;
        });
    }

    // ================================================================
    // FILTERS
    // ================================================================
    public function setStockFilter(string $filter): void
    {
        if (in_array($filter, ['all', 'in_stock', 'low', 'out'], true)) {
            $this->stockFilter = $filter;
        }
    }

    public function setTypeFilter(string $type): void
    {
        if (in_array($type, ['all', 'finished', 'ingredient'], true)) {
            $this->typeFilter     = $type;
            $this->categoryFilter = null;
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = null;
        $this->stockFilter = 'all';
        $this->typeFilter = 'all';
    }

    // ================================================================
    // QUICK ADJUST PANEL
    // ================================================================
    public function updatedProductId($value): void
    {
        $this->resetValidation();
        $product = $value ? $this->branchProduct((int) $value) : null;
        $this->adjust_unit = $product ? $this->unitOf($product) : 'pcs';
    }

    public function updateStock(): void
    {
        $this->validate();

        $product = $this->branchProduct((int) $this->product_id);
        if (! $product) {
            $this->addError('product_id', 'Item not found in this branch.');
            return;
        }

        if ($this->productHasRecipe($product)) {
            $this->addError('product_id', "{$product->name} has a recipe — its stock is calculated. Adjust its ingredients instead.");
            return;
        }

        $unit = $this->unitOf($product);

        if (! UnitConverter::compatible($unit, $this->adjust_unit)) {
            $allowed = implode(' / ', UnitConverter::compatibleUnits($unit));
            $this->addError('adjust_unit', "{$product->name} is stocked in {$unit}. Use {$allowed}.");
            return;
        }

        $qty = UnitConverter::convert((float) $this->quantity_to_add, $this->adjust_unit, $unit);
        if ($qty === null || $qty <= 0) {
            $this->addError('quantity_to_add', 'Could not convert that amount.');
            return;
        }

        $isAdd = $this->adjustment_type === 'add';
        $error = $this->applyDelta(
            $product->id,
            $isAdd ? $qty : -$qty,
            $product->type === 'ingredient' ? UnitConverter::defaultMinStock($unit) : 5.0
        );

        if ($error) {
            $this->addError('quantity_to_add', $error . ' ' . $unit);
            return;
        }

        $label = UnitConverter::format((float) $this->quantity_to_add) . ' ' . $this->adjust_unit;
        session()->flash('success', ($isAdd ? "Added {$label} to " : "Removed {$label} from ") . $product->name . '.');

        $this->reset(['product_id', 'quantity_to_add']);
        $this->adjust_unit = 'pcs';
        $this->resetValidation();
        $this->refreshData();
    }

    public function quickAdjust(int $productId, int $direction): void
    {
        $product = $this->branchProduct($productId);
        if (! $product) return;

        if ($this->productHasRecipe($product)) {
            session()->flash('error', 'Stock for recipe products is calculated from ingredients.');
            return;
        }

        $unit  = $this->unitOf($product);
        $delta = $this->stepFor($unit) * ($direction >= 0 ? 1 : -1);

        $error = $this->applyDelta(
            $productId,
            $delta,
            $product->type === 'ingredient' ? UnitConverter::defaultMinStock($unit) : 5.0
        );

        if ($error) {
            session()->flash('error', "{$product->name}: {$error} {$unit}");
        }

        $this->refreshData();
    }

    // ================================================================
    // STOCK REQUESTS
    // ================================================================
    public function approveStockRequest(int $orderId): void
    {
        $result = DB::transaction(function () use ($orderId) {
            // Lock the request so a double click can't add the stock twice.
            $request = Order::where('tenant_id', $this->tenantId)
                ->where('branch_id', $this->branchId)
                ->where('status', 'stock_request')
                ->where('id', $orderId)
                ->with('items')
                ->lockForUpdate()
                ->first();

            if (! $request) {
                return ['error', 'Request not found or already processed.'];
            }

            $added   = 0;
            $skipped = [];

            foreach ($request->items as $item) {
                if (! $item->product_id || (float) $item->quantity <= 0) continue;

                $product = $this->branchProduct((int) $item->product_id);
                if (! $product) continue;

                if ($this->productHasRecipe($product)) {
                    $skipped[] = $product->name;
                    continue;
                }

                $unit = $this->unitOf($product);
                $this->applyDelta(
                    $product->id,
                    (float) $item->quantity,
                    $product->type === 'ingredient' ? UnitConverter::defaultMinStock($unit) : 5.0
                );
                $added++;
            }

            if ($added === 0) {
                return ['error', $skipped
                    ? 'Nothing added — ' . implode(', ', $skipped) . ' use recipes, so restock their ingredients instead.'
                    : 'This request has no valid items for this branch.'];
            }

            $note = "\n✅ Approved by manager at " . now()->format('M d, Y h:i A');
            if ($skipped) {
                $note .= ' (skipped recipe items: ' . implode(', ', $skipped) . ')';
            }

            $request->update([
                'status' => 'stock_request_approved',
                'notes'  => ($request->notes ?? '') . $note,
            ]);

            return ['success', 'Stock request approved! Items added to inventory.'
                . ($skipped ? ' Skipped recipe items: ' . implode(', ', $skipped) . '.' : '')];
        });

        session()->flash($result[0], $result[1]);
        $this->refreshData();
    }

    public function rejectStockRequest(int $orderId): void
    {
        $request = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'stock_request')
            ->where('id', $orderId)
            ->first();

        if (! $request) {
            session()->flash('error', 'Request not found or already processed.');
            return;
        }

        $request->update([
            'status' => 'stock_request_rejected',
            'notes'  => ($request->notes ?? '') . "\n❌ Rejected by manager at " . now()->format('M d, Y h:i A'),
        ]);

        session()->flash('info', 'Request rejected.');
        $this->refreshData();
    }

    // ================================================================
    // EDIT
    // ================================================================
    public function editProduct(int $id): void
    {
        $product = Product::forLocation($this->tenantId, $this->branchId)->findOrFail($id);

        $inventory = Inventory::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('product_id', $id)
            ->first();

        $isIng = $product->type === 'ingredient';
        $unit  = $this->unitOf($product);

        $this->editingProductId = $product->id;
        $this->edit_type        = $product->type ?? 'finished';
        $this->edit_unit        = $unit;
        $this->edit_has_recipe  = $this->productHasRecipe($product);
        $this->edit_name        = $product->name;
        $this->edit_price       = $product->price;
        $this->edit_cost        = $product->cost;
        $this->edit_category_id = $product->category_id;
        $this->edit_stock       = (float) ($inventory->quantity ?? 0);
        $this->edit_min_stock   = (float) ($inventory->min_stock ?? ($isIng ? UnitConverter::defaultMinStock($unit) : 5));

        unset($this->editCategories);
        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal    = false;
        $this->editingProductId = null;
        $this->resetValidation();
    }

    public function updateProduct(): void
    {
        $isIng = $this->edit_type === 'ingredient';

        $this->validate([
            'edit_name'        => 'required|string|min:2|max:255',
            'edit_price'       => $isIng ? 'nullable|numeric|min:0|max:100000' : 'required|numeric|min:0|max:100000',
            'edit_cost'        => 'nullable|numeric|min:0|max:100000',
            'edit_stock'       => 'nullable|numeric|min:0|max:10000000',
            'edit_min_stock'   => 'nullable|numeric|min:0|max:10000000',
            'edit_category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where('tenant_id', $this->tenantId)
                    ->where('type', $this->edit_type),
            ],
        ]);

        $product = Product::forLocation($this->tenantId, $this->branchId)->findOrFail($this->editingProductId);
        $hasRecipe = $this->productHasRecipe($product);

        DB::transaction(function () use ($product, $isIng, $hasRecipe) {
            $data = [
                'name'        => trim($this->edit_name),
                'category_id' => $this->edit_category_id,
                'price'       => $isIng ? 0 : ($this->edit_price ?? 0),
            ];
            // Recipe products keep a calculated cost; don't overwrite it with a typed value.
            if (! $hasRecipe) {
                $data['cost'] = $this->edit_cost ?? 0;
            }
            $product->update($data);

            $minStock = ($this->edit_min_stock !== null && $this->edit_min_stock !== '')
                ? (float) $this->edit_min_stock
                : ($isIng ? UnitConverter::defaultMinStock($this->edit_unit) : 5.0);

            $inv = Inventory::withoutGlobalScopes()
                ->where('tenant_id', $this->tenantId)
                ->where('branch_id', $this->branchId)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $payload = ['min_stock' => $minStock];
            if (! $hasRecipe) {
                $payload['quantity'] = max(0, (float) ($this->edit_stock ?? 0));
            }

            if ($inv) {
                $inv->update($payload);
            } else {
                Inventory::withoutGlobalScopes()->create($payload + [
                    'tenant_id'  => $this->tenantId,
                    'branch_id'  => $this->branchId,
                    'product_id' => $product->id,
                    'quantity'   => $payload['quantity'] ?? 0,
                    'is_active'  => true,
                ]);
            }
        });

        $this->closeEditModal();
        $this->refreshData();
        session()->flash('success', 'Item updated.');
    }

    // ================================================================
    // DELETE
    // ================================================================
    public function deleteProduct(int $id): void
    {
        $product = Product::forLocation($this->tenantId, $this->branchId)->find($id);
        if (! $product) return;

        if ($product->type === 'ingredient') {
            $usedIn = ProductIngredient::where('ingredient_id', $id)->count();
            if ($usedIn > 0) {
                session()->flash('error', "Cannot delete — this ingredient is used in {$usedIn} recipe(s).");
                return;
            }
        }

        try {
            $image = $product->image;

            DB::transaction(function () use ($product) {
                if ($product->type === 'finished') {
                    ProductIngredient::where('product_id', $product->id)->delete();
                }
                Inventory::withoutGlobalScopes()
                    ->where('tenant_id', $this->tenantId)
                    ->where('branch_id', $this->branchId)
                    ->where('product_id', $product->id)
                    ->delete();
                $product->delete();
            });

            if ($image && ! Product::where('image', $image)->exists() && Storage::disk('public')->exists($image)) {
                Storage::disk('public')->delete($image);
            }

            session()->flash('success', 'Item removed from this branch.');
        } catch (QueryException $e) {
            session()->flash('error', 'Cannot delete — this item is referenced by existing records (e.g. past orders).');
        }

        $this->refreshData();
    }

    // NOTE: no render() on purpose — single-file component, the template below is used.
};
?>
<div class="inv-theme relative min-h-screen font-sans transition-colors duration-300" wire:poll.30s.visible>

    <style>
        .inv-theme {
            --bg-primary: #f5f0e8; --bg-elevated: #ffffff; --bg-inner: #f5f0e8;
            --text-primary: #1a3a3a; --text-muted: #6b7f7a;
            --accent: #c8965f; --accent-bright: #b8874a; --forest: #1a3a3a;
            --border-soft: rgba(26, 58, 58, 0.10);
            --ok: #16a34a; --warn: #d97706; --bad: #dc2626; --info: #2563eb; --violet: #7c3aed;
        }
        .dark .inv-theme {
            --bg-primary: #0a1f1a; --bg-elevated: #14332c; --bg-inner: #0a1f1a;
            --text-primary: #f0ebe0; --text-muted: #8a9a94;
            --accent: #d4a574; --accent-bright: #c8965f; --forest: #f0ebe0;
            --border-soft: rgba(212, 165, 116, 0.12);
            --ok: #4ade80; --warn: #fbbf24; --bad: #f87171; --info: #60a5fa; --violet: #a78bfa;
        }
        .inv-theme { background-color: var(--bg-primary); color: var(--text-primary); }
        .inv-theme .card  { background-color: var(--bg-elevated); border: 1px solid var(--border-soft); border-radius: 1rem; }
        .inv-theme .inner { background-color: var(--bg-inner); border: 1px solid var(--border-soft); border-radius: 0.75rem; }
        .inv-theme .muted { color: var(--text-muted); }
        .inv-theme .input-theme { background-color: var(--bg-inner); color: var(--text-primary); border: 1px solid var(--border-soft); outline: none; }
        .inv-theme .input-theme:focus { border-color: var(--accent); box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent); }
        .inv-theme .input-theme:disabled { opacity: .6; cursor: not-allowed; }
        .inv-theme .btn-accent { background-image: linear-gradient(135deg, color-mix(in srgb, var(--accent) 95%, white 5%), color-mix(in srgb, var(--accent-bright) 90%, black 10%)); color: #fff; }
        .inv-theme button:disabled { opacity: .45; cursor: not-allowed; }
        .inv-theme .pill { border-radius: .375rem; font-weight: 700; }
        .inv-theme .pill-ok     { background: color-mix(in srgb, var(--ok) 12%, transparent);   color: var(--ok);   border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent); }
        .inv-theme .pill-low    { background: color-mix(in srgb, var(--warn) 12%, transparent); color: var(--warn); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent); }
        .inv-theme .pill-out    { background: color-mix(in srgb, var(--bad) 12%, transparent);  color: var(--bad);  border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent); }
        .inv-theme .pill-info   { background: color-mix(in srgb, var(--info) 12%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent); }
        .inv-theme .pill-violet { background: color-mix(in srgb, var(--violet) 12%, transparent); color: var(--violet); border: 1px solid color-mix(in srgb, var(--violet) 30%, transparent); }
        .inv-theme .pill-accent { background: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent); }
        .inv-theme .lbl { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color: var(--text-muted); margin-bottom:.25rem; }
        .inv-theme .err { font-size: 10px; color: var(--bad); display:block; margin-top:.2rem; }
    </style>

    @php
        $fmt   = fn ($q) => \App\Support\UnitConverter::format((float) $q);
        $tone  = ['ok' => 'var(--ok)', 'low' => 'var(--warn)', 'out' => 'var(--bad)'];
        $words = ['ok' => 'OK', 'low' => 'LOW', 'out' => 'OUT'];
        $stats = $this->stats;
        $list  = $this->products;

        $adjustable = $this->allProducts->where('is_derived_stock', false);
        $selected   = $product_id ? $this->allProducts->firstWhere('id', (int) $product_id) : null;
        $adjUnits   = $selected
            ? \App\Support\UnitConverter::compatibleUnits($selected->stock_unit)
            : array_keys(\App\Support\UnitConverter::UNITS);
        $hasFilters = $search !== '' || $categoryFilter || $stockFilter !== 'all' || $typeFilter !== 'all';
        $visibleCats = $this->categories->when($typeFilter !== 'all', fn ($c) => $c->where('type', $typeFilter));
    @endphp

    <main class="relative z-10 p-3 sm:p-4 md:p-5 max-w-7xl mx-auto space-y-3.5">

        {{-- HEADER --}}
        <div class="card p-3 sm:p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background-color: var(--forest); color: var(--bg-primary);">
                    <i class="fa-solid fa-boxes-stacked text-sm"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg sm:text-xl font-black tracking-tight">Inventory Control</h1>
                        <span class="pill pill-accent inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-mono">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--ok);"></span>
                            {{ $stats['total'] }} ITEMS
                        </span>
                    </div>
                    <p class="text-xs font-mono truncate muted">{{ $branch->name ?? 'Branch' }} • Live Stock</p>
                </div>
            </div>

            <div class="relative w-full sm:w-72">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs muted"></i>
                <input type="search" wire:model.live.debounce.250ms="search" placeholder="Search name, SKU or category…"
                       class="input-theme w-full pl-8 pr-3 py-2 rounded-xl text-xs">
            </div>
        </div>

        {{-- FLASH --}}
        @foreach(['success' => ['ok', 'fa-circle-check'], 'error' => ['bad', 'fa-circle-exclamation'], 'info' => ['info', 'fa-circle-info']] as $key => [$c, $ic])
            @if(session()->has($key))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" role="status"
                     class="p-3 rounded-xl text-xs font-bold flex items-center justify-between gap-2"
                     style="background-color: color-mix(in srgb, var(--{{ $c }}) 10%, transparent); border: 1px solid color-mix(in srgb, var(--{{ $c }}) 30%, transparent); color: var(--{{ $c }});">
                    <span class="flex items-center gap-2"><i class="fa-solid {{ $ic }}"></i> {{ session($key) }}</span>
                    <button type="button" @click="show = false"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif
        @endforeach

        {{-- STOCK REQUESTS --}}
        @if($this->stockRequests->isNotEmpty())
            <div class="card p-3 sm:p-4 shadow-sm" style="border-color: color-mix(in srgb, var(--bad) 30%, transparent);">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center pill-out"><i class="fa-solid fa-box-open text-sm"></i></div>
                        <div>
                            <h3 class="text-xs font-mono font-bold uppercase tracking-wider">Stock Requests from Cashiers</h3>
                            <p class="text-[10px] font-mono muted">{{ $this->stockRequests->count() }} pending</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    @foreach($this->stockRequests as $req)
                        @php
                            $elapsed = (int) $req->created_at->diffInMinutes(now(), true);
                            $urgency = $elapsed >= 60 ? 'var(--bad)' : ($elapsed >= 15 ? 'var(--warn)' : 'var(--ok)');
                            $note = '';
                            if ($req->notes && preg_match('/\|\s*Note:\s*(.+?)(?:\n|$)/i', $req->notes, $m)) $note = trim($m[1]);
                            $totalLines = $req->items->count();
                        @endphp
                        <div class="inner p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3" wire:key="req-{{ $req->id }}">
                            <div class="min-w-0 flex-1">
                                <div class="space-y-1">
                                    @foreach($req->items as $item)
                                        @php $pUnit = ($item->product?->type === 'ingredient') ? ($item->product->unit ?: 'g') : 'pcs'; @endphp
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="font-bold text-xs truncate">{{ $item->product->name ?? 'Unknown product' }}</p>
                                            <span class="pill pill-out px-1.5 py-0.5 text-[10px] font-mono">+{{ $fmt($item->quantity) }} {{ $pUnit }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="flex items-center gap-3 mt-1 text-[10px] font-mono muted flex-wrap">
                                    <span><i class="fa-solid fa-user-tie text-[9px] mr-1"></i>{{ $req->user->name ?? 'Cashier' }}</span>
                                    <span><i class="fa-solid fa-clock text-[9px] mr-1"></i>{{ $req->created_at->format('h:i A') }}</span>
                                    <span class="font-bold" style="color: {{ $urgency }};">{{ $elapsed >= 60 ? intdiv($elapsed, 60) . 'h ' . ($elapsed % 60) . 'm waiting' : $elapsed . 'm waiting' }}</span>
                                </div>
                                @if($note)<p class="text-[10px] italic mt-1 truncate muted">"{{ $note }}"</p>@endif
                            </div>

                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button type="button" wire:click="approveStockRequest({{ $req->id }})"
                                        wire:loading.attr="disabled" wire:target="approveStockRequest({{ $req->id }})"
                                        wire:confirm="Approve this request and add {{ $totalLines }} item(s) to inventory?"
                                        class="px-3 py-1.5 rounded-lg text-[11px] font-mono font-bold text-white"
                                        style="background-color: var(--ok);">
                                    <i class="fa-solid fa-check text-[10px] mr-1"></i> Approve
                                </button>
                                <button type="button" wire:click="rejectStockRequest({{ $req->id }})"
                                        wire:loading.attr="disabled" wire:target="rejectStockRequest({{ $req->id }})"
                                        wire:confirm="Reject this stock request?"
                                        class="px-3 py-1.5 rounded-lg text-[11px] font-mono font-bold muted"
                                        style="border: 1px solid var(--border-soft);">
                                    <i class="fa-solid fa-xmark text-[10px]"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ALERT --}}
        @if($stats['out'] > 0 || $stats['low'] > 0)
            <div class="p-3 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 pill-low">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <p class="text-xs font-bold">
                        Stock alert:
                        @if($stats['out'] > 0) {{ $stats['out'] }} out @endif
                        @if($stats['out'] > 0 && $stats['low'] > 0) · @endif
                        @if($stats['low'] > 0) {{ $stats['low'] }} running low @endif
                    </p>
                </div>
                <button type="button" wire:click="setStockFilter('{{ $stats['out'] > 0 ? 'out' : 'low' }}')"
                        class="px-3 py-1.5 rounded-xl text-white text-[11px] font-mono font-bold whitespace-nowrap" style="background-color: var(--warn);">
                    <i class="fa-solid fa-eye text-[10px] mr-1"></i> View {{ $stats['out'] > 0 ? 'out of stock' : 'low stock' }}
                </button>
            </div>
        @endif

        {{-- QUICK ADJUSTMENT --}}
        <div class="card p-3 sm:p-4 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <i class="fa-solid fa-arrows-rotate text-xs" style="color: var(--accent);"></i>
                <h3 class="text-xs font-mono font-bold uppercase tracking-wider">Stock Adjustment</h3>
            </div>

            <form wire:submit.prevent="updateStock" class="grid grid-cols-2 sm:grid-cols-12 gap-2 items-start">
                <div class="col-span-2 sm:col-span-4">
                    <select wire:model.live="product_id" class="input-theme w-full px-3 py-2 rounded-xl text-xs">
                        <option value="">Select item…</option>
                        @foreach($adjustable as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} — {{ $fmt($p->stock) }} {{ $p->stock_unit }}</option>
                        @endforeach
                    </select>
                    @error('product_id') <span class="err font-mono">{{ $message }}</span> @enderror
                </div>

                <div class="col-span-2 sm:col-span-2">
                    <select wire:model="adjustment_type" class="input-theme w-full px-3 py-2 rounded-xl text-xs font-mono font-bold">
                        <option value="add">+ Add</option>
                        <option value="subtract">− Remove</option>
                    </select>
                </div>

                <div class="col-span-1 sm:col-span-2">
                    <input type="number" step="any" min="0" wire:model="quantity_to_add" placeholder="Qty"
                           class="input-theme w-full px-3 py-2 rounded-xl text-xs font-mono">
                    @error('quantity_to_add') <span class="err font-mono">{{ $message }}</span> @enderror
                </div>

                <div class="col-span-1 sm:col-span-2">
                    <select wire:model="adjust_unit" class="input-theme w-full px-3 py-2 rounded-xl text-xs font-mono">
                        @foreach($adjUnits as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach
                    </select>
                    @error('adjust_unit') <span class="err font-mono">{{ $message }}</span> @enderror
                </div>

                <div class="col-span-2 sm:col-span-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateStock"
                            class="w-full py-2 rounded-xl text-xs font-mono font-bold flex items-center justify-center gap-1"
                            style="background-color: var(--forest); color: var(--bg-primary);">
                        <i class="fa-solid fa-check text-[10px]"></i> Apply
                    </button>
                </div>
            </form>

            <p class="text-[10px] muted mt-2">
                You can enter any compatible unit (e.g. add <strong>2 kg</strong> to an item stocked in g — it's converted for you).
                Products made from a recipe aren't listed: their stock is calculated from ingredients.
            </p>
        </div>

        {{-- KPI --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-3.5">
            @foreach([
                ['Catalog', $stats['total'], 'var(--accent)', 'fa-cubes'],
                ['In Stock', $stats['in_stock'], 'var(--ok)', 'fa-circle-check'],
                ['Low', $stats['low'], 'var(--warn)', 'fa-triangle-exclamation'],
                ['Out', $stats['out'], 'var(--bad)', 'fa-circle-xmark'],
            ] as [$kl, $kv, $kc, $ki])
                <div class="card p-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider" style="color: {{ $kc }};">{{ $kl }}</span>
                        <div class="text-lg font-black font-mono" style="color: {{ $kc }};">{{ $kv }}</div>
                    </div>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs"
                         style="background-color: color-mix(in srgb, {{ $kc }} 10%, transparent); color: {{ $kc }};"><i class="fa-solid {{ $ki }}"></i></div>
                </div>
            @endforeach

            <div class="p-3 rounded-2xl col-span-2 lg:col-span-1 flex items-center justify-between shadow-sm"
                 style="background-color: var(--forest); color: var(--bg-primary);">
                <div class="min-w-0">
                    <span class="text-[10px] font-mono font-bold uppercase tracking-wider opacity-80">Stock Value (at cost)</span>
                    <div class="text-base font-black font-mono truncate">₱{{ number_format($stats['value'], 2) }}</div>
                </div>
                <i class="fa-solid fa-coins"></i>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="card overflow-hidden">
            <div class="p-3 flex flex-wrap items-center justify-between gap-2 inner" style="border-radius: 0;">
                <div class="flex items-center gap-1.5 flex-wrap">
                    @foreach(['all' => 'All', 'finished' => 'Products (' . $stats['fin'] . ')', 'ingredient' => 'Ingredients (' . $stats['ing'] . ')'] as $k => $l)
                        <button type="button" wire:click="setTypeFilter('{{ $k }}')"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold"
                                style="{{ $typeFilter === $k ? 'background-color: var(--forest); color: var(--bg-primary);' : 'background-color: var(--bg-elevated); color: var(--text-muted); border: 1px solid var(--border-soft);' }}">{{ $l }}</button>
                    @endforeach
                    <span class="mx-1 muted">|</span>
                    @foreach(['all' => 'Any stock', 'in_stock' => 'In stock', 'low' => 'Low', 'out' => 'Out'] as $k => $l)
                        <button type="button" wire:click="setStockFilter('{{ $k }}')"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold"
                                style="{{ $stockFilter === $k ? 'background-color: var(--accent); color: #fff;' : 'background-color: var(--bg-elevated); color: var(--text-muted); border: 1px solid var(--border-soft);' }}">{{ $l }}</button>
                    @endforeach
                </div>

                <div class="flex items-center gap-2">
                    <select wire:model.live="categoryFilter" class="input-theme px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold">
                        <option value="">All categories</option>
                        @foreach($visibleCats as $cat)<option value="{{ $cat->id }}">{{ $cat->cat_name }}</option>@endforeach
                    </select>
                    <select wire:model.live="sortBy" class="input-theme px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold">
                        <option value="name">Name A→Z</option>
                        <option value="stock_asc">Stock ↑ (lowest first)</option>
                        <option value="stock_desc">Stock ↓</option>
                        <option value="price_asc">Price ↑</option>
                        <option value="price_desc">Price ↓</option>
                    </select>
                    @if($hasFilters)
                        <button type="button" wire:click="clearFilters" class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold inner">
                            <i class="fa-solid fa-rotate-left text-[10px]"></i> Clear
                        </button>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="font-mono text-[10px] uppercase tracking-wider"
                           style="background-color: var(--bg-inner); color: var(--text-muted); border-bottom: 1px solid var(--border-soft);">
                        <tr>
                            <th class="px-4 py-2.5">Item & SKU</th>
                            <th class="px-4 py-2.5">Category</th>
                            <th class="px-4 py-2.5">Price / Cost</th>
                            <th class="px-4 py-2.5">Stock</th>
                            <th class="px-4 py-2.5">Quick adjust</th>
                            <th class="px-4 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($list as $product)
                            @php
                                $unit = $product->stock_unit;
                                $step = in_array($unit, ['g', 'ml'], true) ? 100 : 1;
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-soft);" wire:key="inv-{{ $product->id }}">
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-9 h-9 rounded-lg flex-shrink-0 overflow-hidden flex items-center justify-center inner">
                                            @if($product->image)
                                                <img src="{{ Storage::url($product->image) }}" alt="" loading="lazy" class="w-full h-full object-cover">
                                            @else
                                                <i class="fa-solid {{ $product->is_ingredient ? 'fa-wheat-awn' : 'fa-mug-hot' }} text-xs" style="color: var(--accent);"></i>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-xs truncate max-w-[200px]">{{ $product->name }}</div>
                                            <div class="text-[10px] font-mono truncate muted">
                                                {{ $product->is_ingredient ? 'Ingredient' : ($product->is_derived_stock ? 'Recipe product' : 'Ready-made') }} • {{ $product->sku ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="pill {{ $product->is_ingredient ? 'pill-violet' : 'pill-accent' }} inline-flex px-2 py-0.5 text-[10px] font-mono">
                                        {{ $product->category->cat_name ?? 'General' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5 font-mono">
                                    @if($product->is_ingredient)
                                        <div class="font-bold">₱{{ number_format((float) $product->cost, 2) }}<span class="muted">/{{ $unit }}</span></div>
                                    @else
                                        <div class="font-bold">₱{{ number_format((float) $product->price, 2) }}</div>
                                        @if($product->unit_cost > 0)
                                            <div class="text-[10px]" style="color: {{ $product->margin >= 0 ? 'var(--ok)' : 'var(--bad)' }};">
                                                cost ₱{{ number_format($product->unit_cost, 2) }} · {{ $product->margin_pct }}%
                                            </div>
                                        @endif
                                    @endif
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="pill pill-{{ $product->state }} inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-mono"
                                          @if($product->is_derived_stock) title="Calculated from ingredient stock" @endif>
                                        @if($product->is_derived_stock)<i class="fa-solid fa-flask text-[9px]"></i>@endif
                                        {{ $fmt($product->display_stock) }}{{ $product->is_derived_stock ? ' can make' : ' ' . $unit }}
                                        · {{ $words[$product->state] }}
                                    </span>
                                    @if($product->is_ingredient && $product->stock > 0)
                                        <div class="text-[10px] font-mono muted mt-0.5">= {{ \App\Support\UnitConverter::baseLabel($product->stock, $unit) }}</div>
                                    @endif
                                    @if($product->is_derived_stock && $product->limiting && $product->display_stock <= 20)
                                        <div class="text-[10px] muted mt-0.5">limited by {{ $product->limiting }}</div>
                                    @endif
                                    @if($product->recipe_issue)
                                        <div class="text-[10px] font-bold mt-0.5" style="color: var(--bad);">Unit mismatch in recipe</div>
                                    @endif
                                    <div class="text-[10px] font-mono muted mt-0.5">alert ≤ {{ $fmt($product->min_stock) }}</div>
                                </td>

                                <td class="px-4 py-2.5">
                                    @if($product->is_derived_stock)
                                        <span class="text-[10px] muted">Auto (recipe)</span>
                                    @else
                                        <div class="inline-flex items-center rounded-lg overflow-hidden" style="border: 1px solid var(--border-soft);">
                                            <button type="button" wire:click="quickAdjust({{ $product->id }}, -1)" wire:loading.attr="disabled"
                                                    class="px-2 py-1 muted" title="Remove {{ $step }} {{ $unit }}">
                                                <i class="fa-solid fa-minus text-[9px]"></i>
                                            </button>
                                            <span class="px-2.5 py-1 font-mono font-bold text-[10px] min-w-[56px] text-center"
                                                  style="background-color: var(--bg-inner); border-left: 1px solid var(--border-soft); border-right: 1px solid var(--border-soft);">
                                                {{ $step }} {{ $unit }}
                                            </span>
                                            <button type="button" wire:click="quickAdjust({{ $product->id }}, 1)" wire:loading.attr="disabled"
                                                    class="px-2 py-1 muted" title="Add {{ $step }} {{ $unit }}">
                                                <i class="fa-solid fa-plus text-[9px]"></i>
                                            </button>
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" wire:click="editProduct({{ $product->id }})" class="p-1.5 rounded-lg muted" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" wire:click="deleteProduct({{ $product->id }})"
                                                wire:confirm="Remove '{{ $product->name }}' from this branch? This cannot be undone."
                                                class="p-1.5 rounded-lg" style="color: var(--bad);" title="Delete">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <i class="fa-solid fa-box-open text-xl muted mb-2"></i>
                                    <p class="text-xs font-mono muted">{{ $hasFilters ? 'No items match your filters.' : 'No inventory items yet. Add items from the Branch Catalog.' }}</p>
                                    @if($hasFilters)
                                        <button type="button" wire:click="clearFilters" class="mt-3 px-3 py-1.5 rounded-lg text-[11px] font-mono font-bold btn-accent">Clear filters</button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-2.5 text-[10px] font-mono flex items-center justify-between muted" style="border-top: 1px solid var(--border-soft);">
                <span>Showing {{ $list->count() }} of {{ $stats['total'] }} items</span>
                <span class="font-bold flex items-center gap-1" style="color: var(--ok);">
                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--ok);"></span> Auto-refreshing
                </span>
            </div>
        </div>
    </main>

    {{-- EDIT MODAL --}}
    @if($showEditModal)
        @php $editBase = $edit_type === 'ingredient' ? \App\Support\UnitConverter::baseLabel((float) $edit_stock, $edit_unit) : null; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 overflow-y-auto" role="dialog" aria-modal="true"
             wire:keydown.escape.window="closeEditModal">
            <div class="fixed inset-0 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.6);" wire:click="closeEditModal"></div>
            <div class="card w-full max-w-md shadow-2xl relative z-10 overflow-hidden">

                <div class="p-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-soft);">
                    <h3 class="text-sm font-bold flex items-center gap-2">
                        <i class="fa-solid fa-pen-to-square" style="color: var(--accent);"></i>
                        Edit {{ $edit_type === 'ingredient' ? 'Ingredient' : 'Product' }}
                    </h3>
                    <button type="button" wire:click="closeEditModal" class="muted"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="p-4 space-y-3 text-xs max-h-[70vh] overflow-y-auto">
                    <div>
                        <label class="lbl">Name</label>
                        <input type="text" wire:model="edit_name" class="input-theme w-full px-3 py-2 rounded-xl">
                        @error('edit_name') <span class="err">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="lbl">Category</label>
                        <select wire:model="edit_category_id" class="input-theme w-full px-3 py-2 rounded-xl">
                            <option value="">— Select —</option>
                            @foreach($this->editCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->cat_name }}</option>
                            @endforeach
                        </select>
                        @error('edit_category_id') <span class="err">{{ $message }}</span> @enderror
                    </div>

                    @if($edit_type === 'ingredient')
                        <div>
                            <label class="lbl">Cost per {{ $edit_unit }} (₱)</label>
                            <input type="number" step="any" min="0" wire:model="edit_cost" class="input-theme w-full px-3 py-2 rounded-xl font-mono">
                            @error('edit_cost') <span class="err">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="lbl">Selling price (₱)</label>
                                <input type="number" step="0.01" min="0" wire:model="edit_price" class="input-theme w-full px-3 py-2 rounded-xl font-mono font-bold">
                                @error('edit_price') <span class="err">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="lbl">Buying cost (₱)</label>
                                <input type="number" step="0.01" min="0" wire:model="edit_cost" @disabled($edit_has_recipe)
                                       class="input-theme w-full px-3 py-2 rounded-xl font-mono">
                                @error('edit_cost') <span class="err">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    @if($edit_has_recipe)
                        <div class="p-3 rounded-xl text-[11px] pill-info">
                            <i class="fa-solid fa-flask"></i>
                            This product has a recipe, so its cost and stock are <strong>calculated from ingredients</strong>.
                            Edit the recipe in the Branch Catalog.
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="lbl">Stock ({{ $edit_unit }})</label>
                                <input type="number" step="any" min="0" wire:model.live.debounce.300ms="edit_stock" class="input-theme w-full px-3 py-2 rounded-xl font-mono">
                                @if($editBase)<p class="text-[10px] font-mono muted mt-1">= {{ $editBase }}</p>@endif
                                @error('edit_stock') <span class="err">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="lbl">Low-stock alert ({{ $edit_unit }})</label>
                                <input type="number" step="any" min="0" wire:model="edit_min_stock" class="input-theme w-full px-3 py-2 rounded-xl font-mono">
                                @error('edit_min_stock') <span class="err">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    @if($edit_has_recipe)
                        <div>
                            <label class="lbl">Low-stock alert (units you can make)</label>
                            <input type="number" step="1" min="0" wire:model="edit_min_stock" class="input-theme w-full px-3 py-2 rounded-xl font-mono">
                            @error('edit_min_stock') <span class="err">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    @if($edit_type === 'ingredient')
                        <p class="text-[10px] muted"><i class="fa-solid fa-lock text-[9px]"></i> Unit ({{ $edit_unit }}) is locked because stock and recipes depend on it.</p>
                    @endif
                </div>

                <div class="p-3 flex gap-2" style="border-top: 1px solid var(--border-soft);">
                    <button type="button" wire:click="closeEditModal" class="flex-1 py-2.5 text-xs font-mono rounded-xl font-bold inner">Cancel</button>
                    <button type="button" wire:click="updateProduct" wire:loading.attr="disabled" wire:target="updateProduct"
                            class="flex-1 py-2.5 text-xs font-mono rounded-xl font-bold btn-accent">
                        <span wire:loading.remove wire:target="updateProduct">Save changes</span>
                        <span wire:loading wire:target="updateProduct">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>