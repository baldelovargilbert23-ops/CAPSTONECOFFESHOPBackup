<?php

namespace App\Livewire\Pages\ShopOwner;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Support\StockService;
use App\Support\UnitConverter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.shop_owner')] class extends Component
{
    use WithFileUploads;

    public string $currentTab = 'products';

    // ─── Modals ────────────────────────────────────────
    public bool $showAddModal      = false;
    public bool $showEditModal     = false;
    public bool $showCategoryModal = false;
    public bool $showDeleteModal   = false;
    public int  $deleteTargetId    = 0;
    public string $deleteTargetName = '';

    // ─── Add form ──────────────────────────────────────
    public $name, $price, $cost, $sku, $photo;
    public $category_id;
    public $type = 'finished';
    public $unit = 'g';
    public $initial_stock = 0;
    public $min_stock = null;
    /** finished products only: 'recipe' = made from ingredients, 'ready' = ready-made item with its own stock */
    public string $productMode = 'recipe';

    // ─── Draft recipe ──────────────────────────────────
    public array $draftRecipe = [];
    public ?int $draftIngredientId = null;
    public $draftQuantity = 0;
    public string $draftUnit = 'g';

    // ─── Category form ─────────────────────────────────
    public $newCategoryName;
    public $newCategoryType = 'finished';

    // ─── Tenant data ───────────────────────────────────
    #[Locked]
    public $tenantId;

    // ─── Edit modal ────────────────────────────────────
    public $editingId = null;
    public $edit_name, $edit_price, $edit_cost, $edit_sku, $edit_category_id, $edit_photo;
    public $edit_type = 'finished';
    public $edit_unit = 'g';
    public $edit_stock = 0;
    public $edit_min_stock = null;
    public $edit_original_type = 'finished';
    public $edit_original_unit = 'pcs';

    // ─── Recipe editor (inside edit modal) ─────────────
    public ?int $recipeIngredientId = null;
    public $recipeQuantity = 0;
    public string $recipeUnit = 'g';

    // ─── Filters ───────────────────────────────────────
    public $search = '';
    public $categoryFilter = null;
    public $stockFilter = 'all';

    protected function rules()
    {
        $priceRule = $this->type === 'finished'
            ? 'required|numeric|min:0|max:100000'
            : 'nullable|numeric|min:0|max:100000';

        return [
            'name'          => 'required|string|max:255|min:2',
            'price'         => $priceRule,
            'cost'          => 'nullable|numeric|min:0|max:100000',
            'category_id'   => [
                'required',
                \Illuminate\Validation\Rule::exists('categories', 'id')
                    ->where('tenant_id', $this->tenantId)
                    ->where('type', $this->type),
            ],
            'sku'           => [
                'nullable', 'string', 'max:50',
                \Illuminate\Validation\Rule::unique('products', 'sku')->where('tenant_id', $this->tenantId),
            ],
            'photo'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'type'          => 'required|in:ingredient,finished',
            'unit'          => 'required|in:g,kg,ml,L,pcs',
            'productMode'   => 'required|in:recipe,ready',
            'initial_stock' => 'nullable|numeric|min:0|max:10000000',
            'min_stock'     => 'nullable|numeric|min:0|max:10000000',
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        $this->tenantId = $user->tenant_id;
    }

    // ================================================================
    // TAB
    // ================================================================
    public function switchTab(string $tab): void
    {
        if (! in_array($tab, ['ingredients', 'products'], true)) return;
        $this->currentTab = $tab;
        $this->search = '';
        $this->categoryFilter = null;
        $this->stockFilter = 'all';
    }

    /** Used by the setup guide: jump to a tab and open the add form. */
    public function quickAdd(string $tab): void
    {
        $this->switchTab($tab);
        $this->openAddModal();
    }

    // ================================================================
    // MODAL CONTROL
    // ================================================================
    public function openAddModal(): void
    {
        $this->reset(['name', 'price', 'cost', 'sku', 'photo', 'category_id', 'initial_stock', 'min_stock', 'productMode']);
        $this->reset(['draftRecipe', 'draftIngredientId', 'draftQuantity']);
        $this->draftUnit = 'g';
        $this->unit = 'g';
        $this->type = $this->currentTab === 'ingredients' ? 'ingredient' : 'finished';
        $this->min_stock = $this->type === 'ingredient' ? UnitConverter::defaultMinStock('g') : 5;
        $this->resetValidation();
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->reset(['draftRecipe', 'draftIngredientId', 'draftQuantity']);
        $this->resetValidation();
    }

    public function openCategoryModal(): void
    {
        $this->newCategoryName = '';
        $this->newCategoryType = $this->currentTab === 'ingredients' ? 'ingredient' : 'finished';
        $this->resetValidation();
        $this->showCategoryModal = true;
    }

    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
        $this->resetValidation();
    }

    public function confirmDelete(int $id): void
    {
        $product = Product::forLocation($this->tenantId, null)->find($id);
        if (! $product) return;

        $this->deleteTargetId   = $id;
        $this->deleteTargetName = $product->name;
        $this->showDeleteModal  = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal  = false;
        $this->deleteTargetId   = 0;
        $this->deleteTargetName = '';
    }

    // ================================================================
    // DRAFT RECIPE
    // ================================================================
    public function addDraftIngredient(): void
    {
        $this->validate([
            'draftIngredientId' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('products', 'id')
                    ->where('tenant_id', $this->tenantId)
                    ->whereNull('branch_id')
                    ->where('type', 'ingredient'),
            ],
            'draftQuantity'     => 'required|numeric|min:0.0001|max:100000',
            'draftUnit'         => 'required|in:g,kg,ml,L,pcs',
        ]);

        $ing = Product::forLocation($this->tenantId, null)
            ->where('type', 'ingredient')
            ->find($this->draftIngredientId);

        if (! $ing) {
            $this->addError('draftIngredientId', 'That is not a valid ingredient.');
            return;
        }

        $ingUnit = $ing->unit ?: 'g';

        if (! UnitConverter::compatible($ingUnit, $this->draftUnit)) {
            $allowed = implode(' / ', UnitConverter::compatibleUnits($ingUnit));
            $this->addError('draftUnit', "{$ing->name} is stocked in {$ingUnit}. Use {$allowed}.");
            return;
        }

        foreach ($this->draftRecipe as $line) {
            if ((int) $line['ingredient_id'] === (int) $this->draftIngredientId) {
                $this->addError('draftIngredientId', 'Already in the recipe.');
                return;
            }
        }

        $costPerRecipeUnit = UnitConverter::convert(1, $this->draftUnit, $ingUnit);

        $this->draftRecipe[] = [
            'ingredient_id'   => (int) $this->draftIngredientId,
            'ingredient_name' => $ing->name,
            'ingredient_cost' => (float) ($ing->cost ?? 0) * (float) $costPerRecipeUnit,
            'ingredient_unit' => $ingUnit,
            'quantity'        => (float) $this->draftQuantity,
            'unit'            => $this->draftUnit,
        ];

        $this->draftIngredientId = null;
        $this->draftQuantity     = 0;
        $this->draftUnit         = 'g';
        $this->resetValidation();
    }

    public function removeDraftIngredient(int $index): void
    {
        if (isset($this->draftRecipe[$index])) {
            unset($this->draftRecipe[$index]);
            $this->draftRecipe = array_values($this->draftRecipe);
        }
    }

    public function updatedDraftIngredientId($value): void
    {
        if (! $value) return;
        $ing = Product::forLocation($this->tenantId, null)->where('type', 'ingredient')->find($value);
        if ($ing && $ing->unit) $this->draftUnit = $ing->unit;
    }

    public function updatedRecipeIngredientId($value): void
    {
        if (! $value) return;
        $ing = Product::forLocation($this->tenantId, null)->where('type', 'ingredient')->find($value);
        if ($ing && $ing->unit) $this->recipeUnit = $ing->unit;
    }

    public function updatedUnit($value): void
    {
        if ($this->type === 'ingredient') {
            $this->min_stock = UnitConverter::defaultMinStock($value);
        }
    }

    public function updatedProductMode($value): void
    {
        $this->min_stock = 5;
    }

    /** Total cost of one unit. Recipe mode: sum of converted ingredient costs. Ready-made: its buying cost. */
    #[Computed]
    public function draftRecipeCost(): float
    {
        if ($this->type === 'finished' && $this->productMode === 'ready') {
            return round((float) ($this->cost ?? 0), 2);
        }

        $total = 0;
        foreach ($this->draftRecipe as $line) {
            $qtyInIngUnit = UnitConverter::convert(
                (float) ($line['quantity'] ?? 0),
                $line['unit'] ?? null,
                $line['ingredient_unit'] ?? null
            ) ?? (float) ($line['quantity'] ?? 0);

            $total += ((float) ($line['ingredient_cost'] ?? 0)) * $qtyInIngUnit;
        }
        return round($total, 2);
    }

    #[Computed]
    public function draftMargin(): float
    {
        return round((float) ($this->price ?? 0) - $this->draftRecipeCost, 2);
    }

    #[Computed]
    public function draftMarginPct(): float
    {
        $price = (float) ($this->price ?? 0);
        if ($price <= 0) return 0;
        return round(($this->draftMargin / $price) * 100, 1);
    }

    // ================================================================
    // COMPUTED — kept for blade compatibility
    // ================================================================

    /**
     * Backwards-compatible: the tenant IS the shop, so there is no
     * separate main branch. Return null to keep the blade happy.
     */
    #[Computed]
    public function ownerBranch()
    {
        return null;
    }

    #[Computed]
    public function shopLabel(): string
    {
        return Auth::user()->tenant->name ?? 'Your Shop';
    }

    #[Computed]
    public function categories()
    {
        return Category::where('tenant_id', $this->tenantId)
            ->where('type', $this->type)
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
    public function allCategories()
    {
        return Category::where('tenant_id', $this->tenantId)
            ->orderBy('type')
            ->orderBy('cat_name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        $products = Product::forLocation($this->tenantId, null)
            ->with('category')
            ->with(['recipe.ingredient:id,name,cost,unit'])
            ->withCount('recipe')
            ->orderBy('name')
            ->get();

        // Own-store inventory (branch_id = null)
        $ownStore = Inventory::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->whereNull('branch_id')
            ->where('is_active', true)
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('MAX(min_stock) as min_qty')
            )
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $availableFinishedStock = StockService::availableQuantities(
            $products->where('type', 'finished'),
            null,
            (int) $this->tenantId
        );

        foreach ($products as $product) {
            $row      = $ownStore->get($product->id);
            $rawStock = (float) ($row->total_qty ?? 0);
            $minStock = (float) ($row->min_qty ?? 5);

            $product->setAttribute('stock', $rawStock);
            $product->setAttribute('min_stock', $minStock);

            $hasRecipe    = $product->type === 'finished' && $product->recipe->isNotEmpty();
            $recipeIssue  = false;
            $recipeCost   = 0.0;

            // Recipe cost: ingredient cost is "per ingredient unit", so convert the recipe amount first.
            foreach ($product->recipe as $line) {
                $ing = $line->ingredient;
                if (! $ing) continue;

                $lineUnit = $line->unit ?: $ing->unit;
                $qtyInIng = UnitConverter::convert((float) $line->quantity, $lineUnit, $ing->unit);

                if ($qtyInIng === null) {
                    $recipeIssue = true;
                    $qtyInIng    = (float) $line->quantity;
                }

                $recipeCost += ((float) ($ing->cost ?? 0)) * $qtyInIng;
            }

            // Ready-made items use their own buying cost.
            $unitCost = $hasRecipe ? $recipeCost : (float) ($product->cost ?? 0);

            $product->setAttribute('unit_cost', round($unitCost, 2));
            $product->setAttribute('recipe_cost', round($recipeCost, 2));
            $product->setAttribute('margin', round((float) $product->price - $unitCost, 2));
            $product->setAttribute(
                'margin_pct',
                $product->price > 0 ? round(($product->margin / (float) $product->price) * 100, 1) : 0
            );
            $product->setAttribute('recipe_issue', $recipeIssue);
            $product->setAttribute('limiting', null);

            if ($hasRecipe) {
                // Prefer StockService (matches branch parity while staying tenant-scoped)
                $canMake = (int) ($availableFinishedStock[$product->id] ?? 0);

                // Fallback: manual limiting-ingredient calc when StockService returns nothing
                if ($canMake === 0 && $availableFinishedStock === []) {
                    $min      = PHP_INT_MAX;
                    $limiting = null;

                    foreach ($product->recipe as $line) {
                        $ing = $line->ingredient;
                        if (! $ing || (float) $line->quantity <= 0) continue;

                        $lineUnit = $line->unit ?: $ing->unit;

                        if (! UnitConverter::compatible($ing->unit, $lineUnit)) {
                            $possible = 0;
                        } else {
                            $stockQty  = (float) ($ownStore->get($line->ingredient_id)?->total_qty ?? 0);
                            $stockBase = UnitConverter::toBase($stockQty, $ing->unit);
                            $needBase  = UnitConverter::toBase((float) $line->quantity, $lineUnit);
                            $possible  = $needBase > 0 ? (int) floor(round($stockBase / $needBase, 6)) : 0;
                        }

                        if ($possible < $min) {
                            $min      = $possible;
                            $limiting = $ing->name;
                        }
                    }

                    $canMake = $min === PHP_INT_MAX ? 0 : max(0, $min);
                    $product->setAttribute('limiting', $limiting);
                }

                $product->setAttribute('can_make', $canMake);
                $product->setAttribute('display_stock', (float) $canMake);
                $product->setAttribute('is_derived_stock', true);
            } else {
                $product->setAttribute('can_make', null);
                $product->setAttribute('display_stock', $rawStock);
                $product->setAttribute('is_derived_stock', false);
            }
        }

        return $products;
    }

    #[Computed]
    public function ingredients()
    {
        return Product::forLocation($this->tenantId, null)
            ->where('type', 'ingredient')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'cost', 'unit']);
    }

    #[Computed]
    public function stats()
    {
        $products = $this->products->where('type', $this->currentTab === 'ingredients' ? 'ingredient' : 'finished');

        return [
            'total'           => $products->count(),
            'in_stock'        => $products->filter(fn ($p) => $p->display_stock > $p->min_stock)->count(),
            'low'             => $products->filter(fn ($p) => $p->display_stock > 0 && $p->display_stock <= $p->min_stock)->count(),
            'out'             => $products->where('display_stock', '<=', 0)->count(),
            'inventory_value' => $products->sum(fn ($p) => ($p->cost ?? 0) * $p->stock),
        ];
    }

    #[Computed]
    public function filteredProducts()
    {
        $type = $this->currentTab === 'ingredients' ? 'ingredient' : 'finished';

        return $this->products
            ->where('type', $type)
            ->when($this->search, fn ($c) => $c->filter(fn ($p) =>
                stripos($p->name, $this->search) !== false ||
                stripos($p->sku, $this->search) !== false ||
                stripos($p->category->cat_name ?? '', $this->search) !== false
            ))
            ->when($this->categoryFilter, fn ($c) => $c->where('category_id', $this->categoryFilter))
            ->when($this->stockFilter === 'in_stock', fn ($c) => $c->filter(fn ($p) => $p->display_stock > $p->min_stock))
            ->when($this->stockFilter === 'low',      fn ($c) => $c->filter(fn ($p) => $p->display_stock > 0 && $p->display_stock <= $p->min_stock))
            ->when($this->stockFilter === 'out',      fn ($c) => $c->where('display_stock', '<=', 0));
    }

    #[Computed]
    public function editingRecipe()
    {
        if (! $this->editingId) return collect();
        if (! Product::forLocation($this->tenantId, null)->whereKey($this->editingId)->exists()) return collect();

        return ProductIngredient::where('product_id', $this->editingId)
            ->with('ingredient:id,name,sku,unit,cost')
            ->orderBy('id')
            ->get();
    }

    // ================================================================
    // HELPERS
    // ================================================================
    public function updatedName($value)
    {
        if (! empty($value)) {
            $tenantCode = strtoupper(substr(Auth::user()->tenant->name ?? 'TN', 0, 3));
            $this->sku = $tenantCode . '-' . strtoupper(substr($value, 0, 3)) . '-' . strtoupper(substr(uniqid(), -4));
        }
    }

    public function updatedType($value)
    {
        if (! empty($this->category_id)) {
            $valid = Category::where('id', $this->category_id)
                ->where('tenant_id', $this->tenantId)
                ->where('type', $value)
                ->exists();
            if (! $valid) $this->category_id = null;
        }
    }

    public function updatedEditType($value)
    {
        if (! empty($this->edit_category_id)) {
            $valid = Category::where('id', $this->edit_category_id)
                ->where('tenant_id', $this->tenantId)
                ->where('type', $value)
                ->exists();
            if (! $valid) $this->edit_category_id = null;
        }
    }

    // ================================================================
    // CATEGORIES
    // ================================================================
    public function addCategory()
    {
        $this->validate([
            'newCategoryName' => 'required|string|max:50|min:2',
            'newCategoryType' => 'required|in:ingredient,finished',
        ]);

        $dupe = Category::where('tenant_id', $this->tenantId)
            ->where('type', $this->newCategoryType)
            ->where('cat_name', trim($this->newCategoryName))
            ->exists();

        if ($dupe) {
            $this->addError('newCategoryName', 'That category already exists for this type.');
            return;
        }

        Category::create([
            'cat_name'  => trim($this->newCategoryName),
            'type'      => $this->newCategoryType,
            'tenant_id' => $this->tenantId,
        ]);

        $this->newCategoryName = '';
        unset($this->categories, $this->editCategories, $this->allCategories);
        session()->flash('modal_success', 'Category added.');
    }

    public function deleteCategory($id)
    {
        $category = Category::where('id', $id)->where('tenant_id', $this->tenantId)->first();
        if (! $category) return;

        $hasProducts = Product::where('category_id', $id)->where('tenant_id', $this->tenantId)->exists();
        if ($hasProducts) {
            session()->flash('modal_error', 'Cannot delete — this category has products in it.');
            return;
        }

        $category->delete();
        unset($this->categories, $this->editCategories, $this->allCategories);
        session()->flash('modal_success', 'Category removed.');
    }

    // ================================================================
    // PRODUCTS
    // ================================================================
    public function addProduct()
    {
        $this->validate();

        $isIngredient = $this->type === 'ingredient';
        $isReady      = ! $isIngredient && $this->productMode === 'ready';
        $tracksStock  = $isIngredient || $isReady;

        $unit         = $isIngredient ? $this->unit : 'pcs';
        $price        = $isIngredient ? 0 : ($this->price ?? 0);
        $initialStock = $tracksStock ? (float) ($this->initial_stock ?? 0) : 0.0;
        $draftRecipe  = (! $isIngredient && ! $isReady) ? $this->validatedDraftRecipeLines() : [];
        if ($draftRecipe === null) {
            return;
        }

        $this->draftRecipe = $draftRecipe;
        $imagePath = $this->photo
            ? $this->photo->store('products/' . $this->tenantId, 'public')
            : null;

        if (! empty($draftRecipe)) {
            $ingredientIds = collect($draftRecipe)->pluck('ingredient_id')->map(fn ($id) => (int) $id)->unique();
            $validIngredientCount = Product::forLocation($this->tenantId, null)
                ->where('type', 'ingredient')
                ->whereIn('id', $ingredientIds)
                ->count();

            if ($ingredientIds->count() !== count($draftRecipe) || $validIngredientCount !== $ingredientIds->count()) {
                $this->addError('draftRecipe', 'This recipe must use ingredients from your main store catalog.');
                return;
            }
        }

        $minStock = ($this->min_stock !== null && $this->min_stock !== '')
            ? (float) $this->min_stock
            : ($isIngredient ? UnitConverter::defaultMinStock($unit) : 5.0);

        // Recipe products store their calculated cost; ingredients/ready-made store what was typed.
        $cost = (! $isIngredient && ! $isReady && ! empty($draftRecipe))
            ? $this->draftRecipeCost
            : (float) ($this->cost ?? 0);

        $type = $this->type;

        DB::transaction(function () use ($imagePath, $initialStock, $price, $minStock, $draftRecipe, $type, $unit, $cost) {
            $product = Product::create([
                'tenant_id'   => $this->tenantId,
                'branch_id'   => null,
                'category_id' => $this->category_id,
                'type'        => $type,
                'unit'        => $unit,
                'name'        => trim($this->name),
                'price'       => $price,
                'cost'        => $cost,
                'sku'         => filled($this->sku) ? strtoupper(trim($this->sku)) : null,
                'image'       => $imagePath,
                'is_active'   => true,
            ]);

            if ($type === 'finished' && ! empty($draftRecipe)) {
                foreach ($draftRecipe as $line) {
                    ProductIngredient::create([
                        'product_id'    => $product->id,
                        'ingredient_id' => $line['ingredient_id'],
                        'quantity'      => $line['quantity'],
                        'unit'          => $line['unit'],
                    ]);
                }
            }

            Inventory::withoutGlobalScopes()->firstOrCreate(
                [
                    'tenant_id'  => $this->tenantId,
                    'branch_id'  => null,
                    'product_id' => $product->id,
                ],
                [
                    'quantity'  => $initialStock,
                    'min_stock' => $minStock,
                    'is_active' => true,
                ]
            );
        });

        $this->closeAddModal();
        unset($this->products, $this->stats, $this->filteredProducts, $this->draftRecipeCost, $this->draftMargin, $this->draftMarginPct);

        session()->flash(
            'success',
            $isIngredient
                ? 'Ingredient added to your main store.'
                : ($isReady
                    ? 'Ready-made product added with its own stock.'
                    : 'Product added' . (! empty($draftRecipe) ? ' with ' . count($draftRecipe) . ' recipe line(s).' : '. Add a recipe so stock is calculated from ingredients.'))
        );
    }

    /** Validate client-controlled recipe state and rebuild display/cost values from trusted rows. */
    protected function validatedDraftRecipeLines(): ?array
    {
        if (empty($this->draftRecipe)) {
            return [];
        }

        $this->validate([
            'draftRecipe' => 'required|array|max:50',
            'draftRecipe.*' => 'required|array:ingredient_id,ingredient_name,ingredient_cost,ingredient_unit,quantity,unit',
            'draftRecipe.*.ingredient_id' => [
                'required',
                'integer',
                'distinct',
                \Illuminate\Validation\Rule::exists('products', 'id')
                    ->where('tenant_id', $this->tenantId)
                    ->whereNull('branch_id')
                    ->where('type', 'ingredient'),
            ],
            'draftRecipe.*.quantity' => 'required|numeric|gt:0|max:100000',
            'draftRecipe.*.unit' => 'required|in:g,kg,ml,L,pcs',
        ]);

        $normalized = [];
        foreach ($this->draftRecipe as $index => $line) {
            $ingredient = Product::forLocation($this->tenantId, null)
                ->where('type', 'ingredient')
                ->find((int) $line['ingredient_id']);

            if (! $ingredient) {
                $this->addError("draftRecipe.{$index}.ingredient_id", 'Choose an ingredient from your main store.');
                return null;
            }

            $stockUnit = $ingredient->unit ?: 'g';
            if (! UnitConverter::compatible($stockUnit, $line['unit'])) {
                $allowed = implode(' / ', UnitConverter::compatibleUnits($stockUnit));
                $this->addError("draftRecipe.{$index}.unit", "{$ingredient->name} requires {$allowed}.");
                return null;
            }

            $costPerRecipeUnit = UnitConverter::convert(1, $line['unit'], $stockUnit);
            $normalized[] = [
                'ingredient_id' => $ingredient->id,
                'ingredient_name' => $ingredient->name,
                'ingredient_cost' => (float) ($ingredient->cost ?? 0) * (float) $costPerRecipeUnit,
                'ingredient_unit' => $stockUnit,
                'quantity' => (float) $line['quantity'],
                'unit' => $line['unit'],
            ];
        }

        return $normalized;
    }

    public function editProduct($id)
    {
        $product = Product::forLocation($this->tenantId, null)->findOrFail($id);

        $inventory = Inventory::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->whereNull('branch_id')
            ->where('product_id', $id)
            ->first();

        $this->editingId           = $id;
        $this->edit_name           = $product->name;
        $this->edit_price          = $product->price;
        $this->edit_cost           = $product->cost;
        $this->edit_sku            = $product->sku;
        $this->edit_category_id    = $product->category_id;
        $this->edit_type           = $product->type ?? 'finished';
        $this->edit_unit           = $product->unit ?? 'g';
        $this->edit_original_type  = $product->type ?? 'finished';
        $this->edit_original_unit  = $product->unit ?? 'g';
        $this->edit_stock          = (float) ($inventory->quantity ?? 0);
        $this->edit_min_stock      = (float) ($inventory->min_stock ?? ($product->type === 'ingredient' ? UnitConverter::defaultMinStock($product->unit) : 5));
        $this->edit_photo          = null;

        $this->recipeIngredientId = null;
        $this->recipeQuantity     = 0;
        $this->recipeUnit         = 'g';

        unset($this->editingRecipe, $this->editCategories);
        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal      = false;
        $this->editingId          = null;
        $this->edit_photo         = null;
        $this->recipeIngredientId = null;
        $this->recipeQuantity     = 0;
        $this->recipeUnit         = 'g';
        $this->resetValidation();
    }

    public function updateProduct()
    {
        $editPriceRule = $this->edit_type === 'finished'
            ? 'required|numeric|min:0|max:100000'
            : 'nullable|numeric|min:0|max:100000';

        $this->validate([
            'edit_name'        => 'required|string|max:255',
            'edit_price'       => $editPriceRule,
            'edit_cost'        => 'nullable|numeric|min:0|max:100000',
            'edit_category_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('categories', 'id')
                    ->where('tenant_id', $this->tenantId)
                    ->where('type', $this->edit_type),
            ],
            'edit_type'        => 'required|in:ingredient,finished',
            'edit_unit'        => 'required|in:g,kg,ml,L,pcs',
            'edit_photo'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'edit_stock'       => 'nullable|numeric|min:0|max:10000000',
            'edit_min_stock'   => 'nullable|numeric|min:0|max:10000000',
        ]);

        $product = Product::forLocation($this->tenantId, null)->findOrFail($this->editingId);

        // Guards when switching type
        if ($this->edit_original_type === 'ingredient' && $this->edit_type === 'finished'
            && ProductIngredient::where('ingredient_id', $product->id)->exists()) {
            session()->flash('modal_error', 'This ingredient is used in recipes, so it cannot become a product.');
            return;
        }
        if ($this->edit_original_type === 'finished' && $this->edit_type === 'ingredient'
            && ProductIngredient::where('product_id', $product->id)->exists()) {
            session()->flash('modal_error', 'Remove this product\'s recipe lines before turning it into an ingredient.');
            return;
        }

        // The unit of an existing ingredient is locked (its stock and recipes depend on it).
        $newUnit = $this->edit_type === 'finished'
            ? 'pcs'
            : ($this->edit_original_type === 'ingredient' ? $this->edit_original_unit : $this->edit_unit);

        $updateData = [
            'name'        => trim($this->edit_name),
            'price'       => $this->edit_type === 'ingredient' ? 0 : ($this->edit_price ?? 0),
            'cost'        => $this->edit_cost ?? 0,
            'category_id' => $this->edit_category_id,
            'type'        => $this->edit_type,
            'unit'        => $newUnit,
        ];

        if ($this->edit_photo) {
            $imageUsedElsewhere = $product->image && Product::where('image', $product->image)
                ->where('id', '!=', $product->id)->exists();
            if ($product->image && ! $imageUsedElsewhere && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $updateData['image'] = $this->edit_photo->store('products/' . $this->tenantId, 'public');
        }

        $product->update($updateData);

        // Stock: ingredients and ready-made items hold their own stock.
        // Products WITH a recipe get their stock calculated, so we leave the stock row alone.
        $hasRecipeNow = $this->edit_type === 'finished'
            && ProductIngredient::where('product_id', $product->id)->exists();

        $inventory = Inventory::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->whereNull('branch_id')
            ->where('product_id', $product->id)
            ->first();

        $minStock = ($this->edit_min_stock !== null && $this->edit_min_stock !== '')
            ? (float) $this->edit_min_stock
            : ($this->edit_type === 'ingredient' ? UnitConverter::defaultMinStock($newUnit) : 5.0);

        if ($inventory) {
            $payload = ['min_stock' => $minStock];
            if (! $hasRecipeNow) {
                $payload['quantity'] = max(0, (float) ($this->edit_stock ?? 0));
            }
            $inventory->update($payload);
        } else {
            Inventory::withoutGlobalScopes()->create([
                'tenant_id'  => $this->tenantId,
                'branch_id'  => null,
                'product_id' => $product->id,
                'quantity'   => $hasRecipeNow ? 0 : max(0, (float) ($this->edit_stock ?? 0)),
                'min_stock'  => $minStock,
                'is_active'  => true,
            ]);
        }

        $this->closeEditModal();
        unset($this->products, $this->stats, $this->filteredProducts);
        session()->flash('success', 'Product updated.');
    }

    public function deleteProduct()
    {
        $id = $this->deleteTargetId;
        $product = Product::forLocation($this->tenantId, null)->find($id);

        if (! $product) { $this->closeDeleteModal(); return; }

        // Block ingredient delete if used in any recipe
        if ($product->type === 'ingredient') {
            $usedIn = ProductIngredient::where('ingredient_id', $id)->count();
            if ($usedIn > 0) {
                $this->closeDeleteModal();
                session()->flash('error', "Cannot delete — this ingredient is used in {$usedIn} recipe(s).");
                return;
            }
        }

        $usedByBranchRecipe = $product->type === 'ingredient'
            && ProductIngredient::where('ingredient_id', $id)->exists();

        if (! $usedByBranchRecipe) {
            $imageUsedElsewhere = $product->image && Product::where('image', $product->image)
                ->where('id', '!=', $product->id)->exists();
            if ($product->image && ! $imageUsedElsewhere && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $product->delete();
            session()->flash('success', $product->type === 'ingredient' ? 'Ingredient removed.' : 'Product removed.');
        } else {
            session()->flash('error', 'Remove this ingredient from the recipes before deleting it.');
        }

        $this->closeDeleteModal();
        unset($this->products, $this->stats, $this->filteredProducts);
    }

    // ================================================================
    // RECIPE (edit modal)
    // ================================================================
    public function addIngredientToRecipe(): void
    {
        if (! $this->editingId) return;

        $product = Product::forLocation($this->tenantId, null)
            ->whereKey($this->editingId)
            ->where('type', 'finished')
            ->first();
        if (! $product) return;

        $this->validate([
            'recipeIngredientId' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('products', 'id')
                    ->where('tenant_id', $this->tenantId)
                    ->whereNull('branch_id')
                    ->where('type', 'ingredient'),
            ],
            'recipeQuantity'     => 'required|numeric|min:0.0001|max:100000',
            'recipeUnit'         => 'required|in:g,kg,ml,L,pcs',
        ]);

        $ing = Product::forLocation($this->tenantId, null)
            ->where('type', 'ingredient')
            ->find($this->recipeIngredientId);

        if (! $ing) {
            $this->addError('recipeIngredientId', 'That is not a valid ingredient.');
            return;
        }

        $ingUnit = $ing->unit ?: 'g';

        if (! UnitConverter::compatible($ingUnit, $this->recipeUnit)) {
            $allowed = implode(' / ', UnitConverter::compatibleUnits($ingUnit));
            $this->addError('recipeUnit', "{$ing->name} is stocked in {$ingUnit}. Use {$allowed}.");
            return;
        }

        $exists = ProductIngredient::where('product_id', $this->editingId)
            ->where('ingredient_id', $this->recipeIngredientId)
            ->exists();

        if ($exists) {
            $this->addError('recipeIngredientId', 'This ingredient is already in the recipe.');
            return;
        }

        ProductIngredient::create([
            'product_id'    => $this->editingId,
            'ingredient_id' => $this->recipeIngredientId,
            'quantity'      => $this->recipeQuantity,
            'unit'          => $this->recipeUnit,
        ]);

        $this->recipeIngredientId = null;
        $this->recipeQuantity     = 0;
        $this->recipeUnit         = 'g';

        unset($this->editingRecipe, $this->products, $this->filteredProducts);
        session()->flash('modal_success', 'Ingredient added to recipe.');
    }

    public function removeIngredientFromRecipe(int $lineId): void
    {
        if (! Product::forLocation($this->tenantId, null)->whereKey($this->editingId)->exists()) return;

        $line = ProductIngredient::where('product_id', $this->editingId)->where('id', $lineId)->first();
        if (! $line) return;

        $line->delete();
        unset($this->editingRecipe, $this->products, $this->filteredProducts);
        session()->flash('modal_success', 'Ingredient removed.');
    }

    // NOTE: no render() method on purpose. In a single-file component the template below is used.
};
?>
<div class="shop-owner-theme relative min-h-screen transition-colors duration-300 font-sans overflow-x-hidden"
     style="background-color: var(--bg-primary); color: var(--text-primary);">

    @once
    <style>
        :root {
            --bg-primary: #f5f0e8; --bg-secondary: #faf6ef; --bg-elevated: #ffffff; --bg-inner: #f5f0e8;
            --text-primary: #1a3a3a; --text-muted: #6b7f7a;
            --accent: #c8965f; --accent-warm: #d4a574; --accent-bright: #b8874a;
            --forest: #1a3a3a; --border-soft: rgba(26, 58, 58, 0.10);
            --ok: #16a34a; --warn: #d97706; --bad: #dc2626; --info: #2563eb; --violet: #7c3aed; --cyan: #0891b2;
        }
        .dark {
            --bg-primary: #0a1f1a; --bg-secondary: #0f2922; --bg-elevated: #14332c; --bg-inner: #0a1f1a;
            --text-primary: #f0ebe0; --text-muted: #8a9a94;
            --accent: #d4a574; --accent-warm: #e0b585; --accent-bright: #c8965f;
            --forest: #f0ebe0; --border-soft: rgba(212, 165, 116, 0.12);
            --ok: #4ade80; --warn: #fbbf24; --bad: #f87171; --info: #60a5fa; --violet: #a78bfa; --cyan: #22d3ee;
        }

        .shop-owner-theme .card { background-color: var(--bg-elevated); border: 1px solid var(--border-soft); border-radius: 1rem; }
        .shop-owner-theme .inner { background-color: var(--bg-inner); border: 1px solid var(--border-soft); border-radius: 0.75rem; }
        .shop-owner-theme .muted { color: var(--text-muted); }

        .shop-owner-theme .pill-accent { background: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent); }
        .shop-owner-theme .pill-ok     { background: color-mix(in srgb, var(--ok) 12%, transparent);     color: var(--ok);     border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent); }
        .shop-owner-theme .pill-warn   { background: color-mix(in srgb, var(--warn) 12%, transparent);   color: var(--warn);   border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent); }
        .shop-owner-theme .pill-bad    { background: color-mix(in srgb, var(--bad) 12%, transparent);    color: var(--bad);    border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent); }
        .shop-owner-theme .pill-violet { background: color-mix(in srgb, var(--violet) 12%, transparent); color: var(--violet); border: 1px solid color-mix(in srgb, var(--violet) 30%, transparent); }
        .shop-owner-theme .pill-info   { background: color-mix(in srgb, var(--info) 12%, transparent);   color: var(--info);   border: 1px solid color-mix(in srgb, var(--info) 30%, transparent); }

        .shop-owner-theme .btn-accent {
            background-image: linear-gradient(135deg,
                color-mix(in srgb, var(--accent) 95%, white 5%) 0%,
                color-mix(in srgb, var(--accent-bright) 90%, black 10%) 100%);
            color: #fff;
        }
        .shop-owner-theme .btn-violet {
            background-image: linear-gradient(135deg,
                color-mix(in srgb, var(--violet) 95%, white 5%) 0%,
                color-mix(in srgb, var(--violet) 80%, black 20%) 100%);
            color: #fff;
        }
        .shop-owner-theme .btn-accent, .shop-owner-theme .btn-violet { transition: filter 0.15s, transform 0.1s; }
        .shop-owner-theme .btn-accent:hover, .shop-owner-theme .btn-violet:hover { filter: brightness(1.06); }
        .shop-owner-theme .btn-accent:active, .shop-owner-theme .btn-violet:active { transform: scale(0.98); }
        .shop-owner-theme button:disabled { opacity: 0.55; cursor: not-allowed; }
        .shop-owner-theme button:focus-visible, .shop-owner-theme select:focus-visible, .shop-owner-theme input:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

        .shop-owner-theme .form-input {
            background-color: var(--bg-inner); border: 1px solid var(--border-soft); color: var(--text-primary);
            outline: none; transition: border-color 0.15s, box-shadow 0.15s;
        }
        .shop-owner-theme .form-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent); }
        .shop-owner-theme .field-label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.3rem; }
        .shop-owner-theme .field-hint  { font-size: 11px; color: var(--text-muted); margin-top: 0.3rem; }
        .shop-owner-theme .field-error { font-size: 11px; color: var(--bad); margin-top: 0.3rem; display: block; }
        .shop-owner-theme .dropzone { background-color: var(--bg-inner); border: 2px dashed var(--border-soft); transition: border-color 0.15s; }
        .shop-owner-theme .dropzone:hover { border-color: var(--accent); }

        .shop-owner-theme .seg-btn { background-color: var(--bg-inner); color: var(--text-muted); border: 1px solid var(--border-soft); transition: all 0.15s; }
        .shop-owner-theme .seg-btn.on-accent { background-color: var(--accent); color: #fff; border-color: transparent; }
        .shop-owner-theme .seg-btn.on-violet { background-color: var(--violet); color: #fff; border-color: transparent; }

        .shop-owner-theme .stat-value { font-family: 'JetBrains Mono', ui-monospace, monospace; letter-spacing: -0.02em; }
        .shop-owner-theme .stat-card { cursor: pointer; transition: transform 0.15s, border-color 0.15s; }
        .shop-owner-theme .stat-card:hover { transform: translateY(-2px); border-color: var(--accent); }
        .shop-owner-theme .stat-card.is-active { border-color: var(--accent); box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 25%, transparent); }

        .shop-owner-theme .item-card { transition: border-color 0.15s, box-shadow 0.15s; }
        .shop-owner-theme .item-card:hover { border-color: color-mix(in srgb, var(--accent) 40%, transparent); box-shadow: 0 2px 10px color-mix(in srgb, var(--accent) 8%, transparent); }
        .shop-owner-theme .action-btn { transition: background-color 0.15s, opacity 0.15s; }
        .shop-owner-theme .action-btn:hover { background-color: color-mix(in srgb, var(--accent) 10%, transparent); }

        .shop-owner-theme .modal-backdrop { animation: fadeIn 0.15s ease-out; backdrop-filter: blur(2px); }
        .shop-owner-theme .modal-panel    { animation: slideUp 0.2s ease-out; }
        .shop-owner-theme .toast          { animation: slideUp 0.2s ease-out; }
        @keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes spin    { to { transform: rotate(360deg); } }
        .shop-owner-theme .spinner { width: 12px; height: 12px; border-radius: 9999px; border: 2px solid currentColor; border-right-color: transparent; animation: spin 0.7s linear infinite; display: inline-block; }
    </style>
    @endonce

    @php
        $fmt        = fn ($q, $u = null) => \App\Support\UnitConverter::format((float) $q, $u);
        $isIngTab   = $currentTab === 'ingredients';
        $tabType    = $isIngTab ? 'ingredient' : 'finished';
        $tabColor   = $isIngTab ? 'var(--violet)' : 'var(--accent)';
        $tabIcon    = $isIngTab ? 'fa-wheat-awn' : 'fa-mug-hot';
        $tabNoun    = $isIngTab ? 'Ingredient' : 'Product';
        $hasFilters = $search !== '' || $categoryFilter || $stockFilter !== 'all';
        $ingCount   = $this->products->where('type', 'ingredient')->count();
        $prodCount  = $this->products->where('type', 'finished')->count();

        // ── Setup guide progress ──
        $catFinishedCount = $this->allCategories->where('type', 'finished')->count();
        $catIngCount      = $this->allCategories->where('type', 'ingredient')->count();
        $stockedIng       = $this->products->where('type', 'ingredient')->filter(fn ($p) => $p->stock > 0)->count();
        $sellable         = $this->products->where('type', 'finished')->filter(fn ($p) => $p->display_stock > 0)->count();

        $guideSteps = [
            [
                'done'   => $catFinishedCount > 0 && $catIngCount > 0,
                'title'  => 'Create categories',
                'desc'   => 'Make at least one category for Ingredients (e.g. Coffee, Dairy) and one for Products (e.g. Drinks, Pastries).',
                'action' => 'openCategoryModal',
                'btn'    => 'Open categories',
            ],
            [
                'done'   => $ingCount > 0 && $stockedIng > 0,
                'title'  => 'Add ingredients with unit, cost and stock',
                'desc'   => 'Pick the unit you buy in (kg, L, pcs…), the cost PER that unit, and your starting stock. Example: Coffee Beans · kg · ₱800/kg · 10 kg.',
                'action' => "quickAdd('ingredients')",
                'btn'    => 'Add ingredient',
            ],
            [
                'done'   => $prodCount > 0,
                'title'  => 'Add products',
                'desc'   => 'Made from ingredients? Choose "Made from ingredients" and add a recipe (e.g. 18 g beans). Bought ready to sell (bottled water, pastries)? Choose "Ready-made" and set its own stock.',
                'action' => "quickAdd('products')",
                'btn'    => 'Add product',
            ],
            [
                'done'   => $sellable > 0,
                'title'  => 'Check your numbers',
                'desc'   => 'Recipe products show "Can make" (calculated from ingredient stock). Ready-made products show their own stock. Both must be above 0 to sell.',
                'action' => "switchTab('products')",
                'btn'    => 'View products',
            ],
        ];
        $guideDone = collect($guideSteps)->where('done', true)->count();
        $guideAll  = $guideDone === count($guideSteps);
    @endphp

    {{-- TOASTS --}}
    <div class="fixed top-3 left-1/2 -translate-x-1/2 z-[70] w-[calc(100%-1.5rem)] max-w-md space-y-2 pointer-events-none">
        @if(session()->has('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 x-transition.opacity role="status"
                 class="toast pointer-events-auto p-3 rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg pill-ok"
                 style="background-color: var(--bg-elevated);">
                <i class="fa-solid fa-circle-check"></i>
                <span class="flex-1">{{ session('success') }}</span>
                <button type="button" @click="show = false" class="opacity-60 hover:opacity-100"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
        @if(session()->has('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                 x-transition.opacity role="alert"
                 class="toast pointer-events-auto p-3 rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg pill-bad"
                 style="background-color: var(--bg-elevated);">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span class="flex-1">{{ session('error') }}</span>
                <button type="button" @click="show = false" class="opacity-60 hover:opacity-100"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
    </div>

    <main class="shop-owner-theme relative z-10 p-3 sm:p-5 max-w-6xl mx-auto space-y-4">

        {{-- HEADER --}}
        <header class="card p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
                         style="background-color: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent);">
                        <i class="fa-solid fa-store text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-xl font-black truncate">Main Store Catalog</h1>
                        <p class="text-xs font-mono muted truncate">
                            {{ Auth::user()->tenant->name ?? 'Tenant' }} • Main Store
                        </p>
                        <p class="text-[11px] muted mt-1">Your central catalog. Branch managers maintain each branch's own products and ingredients.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" wire:click="openCategoryModal"
                            class="flex-1 sm:flex-none px-3 py-2.5 rounded-xl inner text-xs font-bold flex items-center justify-center gap-2">
                        <i class="fa-solid fa-tags" style="color: var(--accent);"></i>
                        Categories
                        <span class="text-[11px] font-mono muted">({{ $this->allCategories->count() }})</span>
                    </button>

                    <button type="button" wire:click="openAddModal"
                            class="flex-1 sm:flex-none {{ $isIngTab ? 'btn-violet' : 'btn-accent' }} px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-sm">
                        <i class="fa-solid fa-plus"></i>
                        Add {{ $tabNoun }}
                    </button>
                </div>
            </div>
        </header>

        {{-- SETUP GUIDE --}}
        <section class="card p-4" x-data="{ open: {{ $guideAll ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-3 text-left">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $guideAll ? 'pill-ok' : 'pill-info' }}">
                        <i class="fa-solid {{ $guideAll ? 'fa-circle-check' : 'fa-list-check' }}"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-black truncate">{{ $guideAll ? 'Setup complete' : 'Setup guide — what to do first' }}</p>
                        <p class="text-[11px] muted">{{ $guideDone }} of {{ count($guideSteps) }} steps done</p>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down text-xs muted transition-transform" :class="open ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="open" x-cloak class="mt-4 space-y-2">
                @foreach($guideSteps as $i => $step)
                    <div class="inner p-3 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-black flex-shrink-0 {{ $step['done'] ? 'pill-ok' : 'pill-accent' }}">
                            @if($step['done']) <i class="fa-solid fa-check"></i> @else {{ $i + 1 }} @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold">{{ $step['title'] }}</p>
                            <p class="text-[11px] muted mt-0.5">{{ $step['desc'] }}</p>
                        </div>
                        <button type="button" wire:click="{{ $step['action'] }}"
                                class="px-3 py-1.5 rounded-lg text-[11px] font-bold whitespace-nowrap cursor-pointer {{ $step['done'] ? 'inner' : 'btn-accent' }}">
                            {{ $step['btn'] }}
                        </button>
                    </div>
                @endforeach

                <div class="p-3 rounded-xl text-[11px] pill-info leading-relaxed">
                    <strong><i class="fa-solid fa-scale-balanced"></i> How units work:</strong>
                    stock is kept in the ingredient's own unit, and recipes may use any compatible unit
                    (g ↔ kg, ml ↔ L). Example: <strong>10 kg</strong> of beans = 10,000 g. One drink using
                    <strong>18 g</strong> leaves <strong>9.982 kg</strong> and the page shows you can still make
                    <strong>555 drinks</strong>. You can't mix kinds (grams can't be used for a liquid in ml).
                </div>
            </div>
        </section>

        {{-- TABS --}}
        <nav class="grid grid-cols-2 gap-3" aria-label="Catalog sections">
            <button type="button" wire:click="switchTab('ingredients')"
                    aria-current="{{ $isIngTab ? 'page' : 'false' }}"
                    class="card p-3 sm:p-4 text-left transition cursor-pointer"
                    style="{{ $isIngTab ? 'border-color: var(--violet); box-shadow: 0 0 0 2px color-mix(in srgb, var(--violet) 25%, transparent);' : '' }}">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background-color: color-mix(in srgb, var(--violet) {{ $isIngTab ? '18%' : '10%' }}, transparent); color: var(--violet);">
                        <i class="fa-solid fa-wheat-awn"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-black truncate flex items-center gap-2"
                           style="color: {{ $isIngTab ? 'var(--violet)' : 'var(--text-primary)' }};">
                            Ingredients
                            <span class="text-[11px] font-mono px-1.5 rounded pill-violet">{{ $ingCount }}</span>
                        </p>
                        <p class="text-[11px] muted truncate hidden sm:block">Raw materials you buy</p>
                    </div>
                </div>
            </button>

            <button type="button" wire:click="switchTab('products')"
                    aria-current="{{ ! $isIngTab ? 'page' : 'false' }}"
                    class="card p-3 sm:p-4 text-left transition cursor-pointer"
                    style="{{ ! $isIngTab ? 'border-color: var(--accent); box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 25%, transparent);' : '' }}">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background-color: color-mix(in srgb, var(--accent) {{ ! $isIngTab ? '18%' : '10%' }}, transparent); color: var(--accent);">
                        <i class="fa-solid fa-mug-hot"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-black truncate flex items-center gap-2"
                           style="color: {{ ! $isIngTab ? 'var(--accent)' : 'var(--text-primary)' }};">
                            Products
                            <span class="text-[11px] font-mono px-1.5 rounded pill-accent">{{ $prodCount }}</span>
                        </p>
                        <p class="text-[11px] muted truncate hidden sm:block">What you sell at the POS</p>
                    </div>
                </div>
            </button>
        </nav>

        {{-- STATS --}}
        <section class="grid grid-cols-2 sm:grid-cols-4 gap-3" aria-label="Stock summary">
            <button type="button" wire:click="$set('stockFilter', 'all')"
                    class="stat-card card p-3 text-left {{ $stockFilter === 'all' ? 'is-active' : '' }}">
                <p class="text-[11px] font-bold uppercase tracking-wider muted">All</p>
                <p class="text-2xl font-black stat-value mt-0.5">{{ $this->stats['total'] }}</p>
                <p class="text-[11px] muted mt-0.5">{{ $isIngTab ? 'Ingredients' : 'Products' }}</p>
            </button>
            <button type="button" wire:click="$set('stockFilter', 'in_stock')"
                    class="stat-card card p-3 text-left {{ $stockFilter === 'in_stock' ? 'is-active' : '' }}">
                <p class="text-[11px] font-bold uppercase tracking-wider muted">In Stock</p>
                <p class="text-2xl font-black stat-value mt-0.5" style="color: var(--ok);">{{ $this->stats['in_stock'] }}</p>
                <p class="text-[11px] muted mt-0.5">Healthy</p>
            </button>
            <button type="button" wire:click="$set('stockFilter', 'low')"
                    class="stat-card card p-3 text-left {{ $stockFilter === 'low' ? 'is-active' : '' }}">
                <p class="text-[11px] font-bold uppercase tracking-wider muted">Low</p>
                <p class="text-2xl font-black stat-value mt-0.5" style="color: var(--warn);">{{ $this->stats['low'] }}</p>
                <p class="text-[11px] muted mt-0.5">Restock soon</p>
            </button>
            <button type="button" wire:click="$set('stockFilter', 'out')"
                    class="stat-card card p-3 text-left {{ $stockFilter === 'out' ? 'is-active' : '' }}">
                <p class="text-[11px] font-bold uppercase tracking-wider muted">Out</p>
                <p class="text-2xl font-black stat-value mt-0.5" style="color: var(--bad);">{{ $this->stats['out'] }}</p>
                <p class="text-[11px] muted mt-0.5">Needs action</p>
            </button>
        </section>

        {{-- SEARCH & FILTERS --}}
        <section class="card p-3 flex flex-col sm:flex-row gap-2 sm:items-center">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs muted"></i>
                <input type="search" wire:model.live.debounce.250ms="search"
                       placeholder="Search name, SKU or category…"
                       class="form-input w-full pl-8 pr-3 py-2.5 rounded-xl text-xs">
            </div>
            <div class="flex items-center gap-2">
                <select wire:model.live="categoryFilter"
                        class="form-input flex-1 sm:flex-none sm:w-48 px-3 py-2.5 rounded-xl text-xs">
                    <option value="">All categories</option>
                    @foreach($this->allCategories->where('type', $tabType) as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->cat_name }}</option>
                    @endforeach
                </select>
                @if($hasFilters)
                    <button type="button"
                            wire:click="$set('search', ''); $set('categoryFilter', null); $set('stockFilter', 'all')"
                            class="px-3 py-2.5 rounded-xl inner text-xs font-bold flex items-center gap-1.5 whitespace-nowrap">
                        <i class="fa-solid fa-rotate-left text-[10px]"></i> Clear
                    </button>
                @endif
            </div>
        </section>

        <p class="text-[11px] muted px-1 -mt-2">
            Showing {{ $this->filteredProducts->count() }} of {{ $this->stats['total'] }}
            {{ strtolower($isIngTab ? 'ingredients' : 'products') }}
            @if($isIngTab && $this->stats['inventory_value'] > 0)
                • Stock value ₱{{ number_format($this->stats['inventory_value'], 2) }}
            @endif
        </p>

        {{-- LIST --}}
        @if($this->filteredProducts->isEmpty())
            <div class="card p-8 sm:p-10 text-center">
                @if($this->stats['total'] === 0)
                    <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4"
                         style="background-color: color-mix(in srgb, {{ $tabColor }} 12%, transparent); color: {{ $tabColor }};">
                        <i class="fa-solid {{ $tabIcon }} text-2xl"></i>
                    </div>
                    <h3 class="text-base font-bold mb-1">No {{ $isIngTab ? 'ingredients' : 'products' }} yet</h3>
                    <p class="text-xs muted mb-5 max-w-sm mx-auto">
                        @if($isIngTab)
                            Start by adding raw materials — coffee beans, milk, cups, syrups.
                        @else
                            Add drinks made from ingredients (with a recipe) or ready-made items like bottled water.
                        @endif
                    </p>
                    <button type="button" wire:click="openAddModal"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold inline-flex items-center gap-2 {{ $isIngTab ? 'btn-violet' : 'btn-accent' }}">
                        <i class="fa-solid fa-plus"></i>
                        Add First {{ $tabNoun }}
                    </button>
                @else
                    <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4"
                         style="background-color: color-mix(in srgb, var(--text-muted) 12%, transparent); color: var(--text-muted);">
                        <i class="fa-solid fa-magnifying-glass text-2xl"></i>
                    </div>
                    <h3 class="text-base font-bold mb-1">No matches</h3>
                    <p class="text-xs muted mb-5">Nothing fits those filters. Try clearing them.</p>
                    <button type="button"
                            wire:click="$set('search', ''); $set('categoryFilter', null); $set('stockFilter', 'all')"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold inline-flex items-center gap-2 inner">
                        <i class="fa-solid fa-rotate-left"></i>
                        Reset Filters
                    </button>
                @endif
            </div>
        @else
            <div class="space-y-2.5">
                @foreach($this->filteredProducts as $product)
                    @php
                        $isIngredient = $product->type === 'ingredient';
                        $hasRecipe    = ! $isIngredient && $product->recipe_count > 0;
                        $isReady      = ! $isIngredient && ! $hasRecipe;
                        $recipeCount  = $product->recipe_count ?? 0;

                        $displayStock = (float) ($product->display_stock ?? $product->stock);
                        $isDerived    = (bool) ($product->is_derived_stock ?? false);
                        $minStock     = (float) ($product->min_stock ?? 5);
                        $unit         = $product->unit ?? ($isIngredient ? 'g' : 'pcs');

                        $stockState = $displayStock <= 0 ? 'bad' : ($displayStock <= $minStock ? 'warn' : 'ok');
                        $stockLabel = $displayStock <= 0 ? 'OUT' : ($displayStock <= $minStock ? 'LOW' : 'OK');

                        $baseHint = $isIngredient ? \App\Support\UnitConverter::baseLabel($displayStock, $unit) : null;
                        $showCost = $hasRecipe || ($isReady && (float) $product->cost > 0);
                    @endphp

                    <article class="card item-card overflow-hidden" wire:key="product-{{ $product->id }}">
                        <div class="p-3 sm:p-4 flex items-start gap-3">
                            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl inner flex items-center justify-center flex-shrink-0 overflow-hidden">
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                         loading="lazy" class="w-full h-full object-cover">
                                @else
                                    <i class="fa-solid {{ $isIngredient ? 'fa-wheat-awn' : 'fa-mug-hot' }} text-xl opacity-40"></i>
                                @endif
                            </div>

                            <div class="flex-grow min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-bold truncate max-w-full">{{ $product->name }}</h3>
                                    @if($hasRecipe)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold pill-ok whitespace-nowrap">
                                            <i class="fa-solid fa-flask text-[9px]"></i> {{ $recipeCount }} {{ $recipeCount === 1 ? 'item' : 'items' }}
                                        </span>
                                    @elseif($isReady)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold pill-info whitespace-nowrap"
                                              title="No recipe — this product tracks its own stock">
                                            <i class="fa-solid fa-box text-[9px]"></i> READY-MADE
                                        </span>
                                    @endif
                                    @if($product->recipe_issue)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold pill-bad whitespace-nowrap"
                                              title="A recipe line uses a unit that doesn't match the ingredient (e.g. grams for a liquid)">
                                            UNIT MISMATCH
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[11px] font-mono muted truncate mt-0.5">
                                    {{ $product->category->cat_name ?? '—' }} • {{ $product->sku }}
                                </p>

                                @if($showCost)
                                    <div class="mt-2 flex items-center gap-x-3 gap-y-1 flex-wrap text-[11px]">
                                        <span class="muted">{{ $hasRecipe ? 'Recipe cost' : 'Cost' }}
                                            <strong style="color: var(--text-primary);">₱{{ number_format($product->unit_cost, 2) }}</strong>
                                        </span>
                                        <span class="muted">Margin
                                            <strong style="color: {{ $product->margin >= 0 ? 'var(--ok)' : 'var(--bad)' }};">
                                                ₱{{ number_format($product->margin, 2) }} ({{ $product->margin_pct }}%)
                                            </strong>
                                        </span>
                                        @if($product->can_make !== null)
                                            <span class="muted">Can make
                                                <strong style="color: {{ $product->can_make > 0 ? 'var(--accent)' : 'var(--bad)' }};">
                                                    {{ number_format($product->can_make) }} {{ $product->can_make === 1 ? 'unit' : 'units' }}
                                                </strong>
                                                @if($product->limiting && $product->can_make <= 20)
                                                    <span class="opacity-80">· limited by {{ $product->limiting }}</span>
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="text-right flex-shrink-0">
                                @if($isIngredient)
                                    <div class="text-xs font-bold font-mono opacity-80">
                                        ₱{{ number_format($product->cost ?? 0, 2) }}<span class="muted">/{{ $unit }}</span>
                                    </div>
                                @else
                                    <div class="text-sm font-black font-mono" style="color: var(--accent);">
                                        ₱{{ number_format($product->price, 2) }}
                                    </div>
                                @endif
                                <div class="mt-1 inline-flex items-center gap-1 text-[11px] font-mono font-bold px-2 py-0.5 rounded-md pill-{{ $stockState }}"
                                     @if($isDerived) title="Calculated from your main store ingredient stock"
                                     @elseif($baseHint) title="= {{ $baseHint }}" @endif>
                                    @if($isDerived) <i class="fa-solid fa-flask text-[9px]"></i> @endif
                                    <span>{{ $fmt($displayStock) }}{{ $isDerived ? '' : ' ' . $unit }}</span>
                                    <span class="opacity-80">· {{ $stockLabel }}</span>
                                </div>
                                @if($baseHint)
                                    <div class="text-[10px] font-mono muted mt-0.5">= {{ $baseHint }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="px-2 py-1.5 flex items-center gap-1 border-t"
                             style="border-color: var(--border-soft); background-color: var(--bg-inner);">

                            <button type="button" wire:click="editProduct({{ $product->id }})"
                                    class="action-btn flex-1 py-2 rounded-lg text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer">
                                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                {{ $isIngredient ? 'Edit / Stock' : ($isReady ? 'Edit / Stock' : 'Edit') }}
                            </button>

                            @if(! $isIngredient)
                                <button type="button" wire:click="editProduct({{ $product->id }})"
                                        class="action-btn flex-1 py-2 rounded-lg text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer"
                                        style="{{ ! $hasRecipe ? 'color: var(--accent);' : '' }}">
                                    <i class="fa-solid fa-flask text-[11px]"></i>
                                    {{ $hasRecipe ? 'Recipe' : '＋ Add Recipe' }}
                                </button>
                            @endif

                            <button type="button" wire:click="confirmDelete({{ $product->id }})"
                                    aria-label="Delete {{ $product->name }}"
                                    class="action-btn py-2 px-4 rounded-lg text-xs font-bold flex items-center justify-center cursor-pointer"
                                    style="color: var(--bad);">
                                <i class="fa-solid fa-trash-can text-[11px]"></i>
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </main>

    {{-- ADD MODAL --}}
    @if($showAddModal)
        @php
            $draftIng   = $draftIngredientId ? $this->ingredients->firstWhere('id', $draftIngredientId) : null;
            $draftUnits = $draftIng
                ? \App\Support\UnitConverter::compatibleUnits($draftIng->unit)
                : array_keys(\App\Support\UnitConverter::UNITS);
            $isReadyMode = $type === 'finished' && $productMode === 'ready';
            $addBaseHint = ($type === 'ingredient' && (float) $initial_stock > 0)
                ? \App\Support\UnitConverter::baseLabel((float) $initial_stock, $unit)
                : null;
        @endphp
        <div class="shop-owner-theme fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
             role="dialog" aria-modal="true" wire:keydown.escape.window="closeAddModal">
            <div class="modal-backdrop fixed inset-0 bg-black/60" wire:click="closeAddModal"></div>

            <div class="modal-panel card w-full sm:max-w-2xl relative z-10 max-h-[95vh] sm:max-h-[90vh] flex flex-col rounded-t-3xl sm:rounded-2xl overflow-hidden">

                <div class="flex items-center justify-between p-4 sm:p-5 border-b flex-shrink-0" style="border-color: var(--border-soft);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, {{ $type === 'ingredient' ? 'var(--violet)' : 'var(--accent)' }} 12%, transparent); color: {{ $type === 'ingredient' ? 'var(--violet)' : 'var(--accent)' }};">
                            <i class="fa-solid {{ $type === 'ingredient' ? 'fa-wheat-awn' : 'fa-mug-hot' }}"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold">Add New {{ $type === 'ingredient' ? 'Ingredient' : 'Product' }}</h2>
                            <p class="text-[11px] muted">Main Store • managed separately from branch catalogs</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeAddModal" class="p-2 rounded-lg opacity-60 hover:opacity-100"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <form wire:submit.prevent="addProduct" class="flex flex-col flex-1 min-h-0">
                    <div class="p-4 sm:p-5 space-y-4 overflow-y-auto flex-1">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="field-label">Name <span style="color: var(--bad);">*</span></label>
                                <input type="text" wire:model.live.debounce.300ms="name"
                                       placeholder="{{ $type === 'ingredient' ? 'e.g. Coffee Beans' : 'e.g. Iced Coffee' }}"
                                       class="form-input w-full px-3 py-2.5 text-sm rounded-xl" autofocus>
                                @error('name') <span class="field-error">{{ $message }}</span> @enderror
                                @if($sku)
                                    <p class="field-hint font-mono">SKU: {{ strtoupper($sku) }}</p>
                                @endif
                            </div>
                            <div>
                                <label class="field-label">Category <span style="color: var(--bad);">*</span></label>
                                <div class="flex gap-1.5">
                                    <select wire:model="category_id" class="form-input flex-1 px-3 py-2.5 text-sm rounded-xl">
                                        <option value="">— Select —</option>
                                        @foreach($this->categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->cat_name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" wire:click="openCategoryModal"
                                            class="px-3 rounded-xl inner flex-shrink-0 cursor-pointer" title="Manage categories">
                                        <i class="fa-solid fa-tags" style="color: var(--accent);"></i>
                                    </button>
                                </div>
                                @error('category_id') <span class="field-error">{{ $message }}</span> @enderror
                                @if($this->categories->isEmpty())
                                    <p class="field-hint" style="color: var(--warn);">No categories yet — tap the tag button to create one.</p>
                                @endif
                            </div>
                        </div>

                        @if($type === 'ingredient')
                            {{-- INGREDIENT --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="field-label">Unit you buy &amp; stock in <span style="color: var(--bad);">*</span></label>
                                    <select wire:model.live="unit" class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono">
                                        @foreach(\App\Support\UnitConverter::UNITS as $u => $meta)
                                            <option value="{{ $u }}">{{ $meta['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <p class="field-hint">Recipes can use any matching unit (kg ↔ g, L ↔ ml).</p>
                                </div>
                                <div>
                                    <label class="field-label">Cost per {{ $unit ?: 'unit' }} (₱)</label>
                                    <input type="number" step="any" min="0" wire:model="cost" placeholder="0.00"
                                           class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono">
                                    <p class="field-hint">Price of ONE {{ $unit }} — e.g. ₱800 per kg.</p>
                                    @error('cost') <span class="field-error">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="p-3 inner">
                                <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                                    <label class="field-label !mb-0">
                                        <i class="fa-solid fa-boxes-stacked" style="color: var(--accent);"></i> Initial Stock
                                    </label>
                                    <span class="text-[11px] muted">
                                        <i class="fa-solid fa-store"></i> Main Store
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    <input type="number" step="any" min="0" wire:model.live.debounce.300ms="initial_stock" placeholder="0"
                                           class="form-input flex-1 px-3 py-2.5 text-lg rounded-xl font-mono font-black text-center">
                                    <div class="px-4 rounded-xl inner flex items-center justify-center text-sm font-mono font-bold">{{ $unit }}</div>
                                </div>
                                @if($addBaseHint)
                                    <p class="field-hint text-center font-mono">= {{ $addBaseHint }}</p>
                                @endif
                                @error('initial_stock') <span class="field-error text-center">{{ $message }}</span> @enderror

                                <div class="mt-3 pt-3 border-t" style="border-color: var(--border-soft);">
                                    <label class="field-label">Low-stock alert at ({{ $unit }})</label>
                                    <input type="number" step="any" min="0" wire:model="min_stock"
                                           class="form-input w-full px-3 py-2 text-sm rounded-xl font-mono">
                                    <p class="field-hint">You'll see "LOW" when stock drops to this amount or less.</p>
                                    @error('min_stock') <span class="field-error">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @else
                            {{-- PRODUCT --}}
                            <div>
                                <label class="field-label">How is this product made?</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <button type="button" wire:click="$set('productMode', 'recipe')"
                                            class="seg-btn p-3 rounded-xl text-left cursor-pointer {{ $productMode === 'recipe' ? 'on-accent' : '' }}">
                                        <span class="block text-xs font-bold"><i class="fa-solid fa-flask"></i> Made from ingredients</span>
                                        <span class="block text-[11px] opacity-80 mt-0.5">Add a recipe. Stock is calculated from ingredient stock.</span>
                                    </button>
                                    <button type="button" wire:click="$set('productMode', 'ready')"
                                            class="seg-btn p-3 rounded-xl text-left cursor-pointer {{ $productMode === 'ready' ? 'on-accent' : '' }}">
                                        <span class="block text-xs font-bold"><i class="fa-solid fa-box"></i> Ready-made item</span>
                                        <span class="block text-[11px] opacity-80 mt-0.5">No recipe needed (bottled water, pastries). Tracks its own stock.</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="field-label">Selling Price (₱) <span style="color: var(--bad);">*</span></label>
                                    <input type="number" step="0.01" min="0" wire:model.live.debounce.300ms="price" placeholder="0.00"
                                           class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono font-bold">
                                    @error('price') <span class="field-error">{{ $message }}</span> @enderror
                                </div>
                                @if($isReadyMode)
                                    <div>
                                        <label class="field-label">Buying cost per piece (₱)</label>
                                        <input type="number" step="0.01" min="0" wire:model.live.debounce.300ms="cost" placeholder="0.00"
                                               class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono">
                                        @error('cost') <span class="field-error">{{ $message }}</span> @enderror
                                    </div>
                                @endif
                            </div>

                            @if($isReadyMode)
                                <div class="p-3 inner">
                                    <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                                        <label class="field-label !mb-0">
                                            <i class="fa-solid fa-boxes-stacked" style="color: var(--accent);"></i> Initial Stock
                                        </label>
                                        <span class="text-[11px] muted"><i class="fa-solid fa-store"></i> Main Store</span>
                                    </div>
                                    <div class="flex gap-2">
                                        <input type="number" step="1" min="0" wire:model="initial_stock" placeholder="0"
                                               class="form-input flex-1 px-3 py-2.5 text-lg rounded-xl font-mono font-black text-center">
                                        <div class="px-4 rounded-xl inner flex items-center justify-center text-sm font-mono font-bold">pcs</div>
                                    </div>
                                    @error('initial_stock') <span class="field-error text-center">{{ $message }}</span> @enderror

                                    <div class="mt-3 pt-3 border-t" style="border-color: var(--border-soft);">
                                        <label class="field-label">Low-stock alert at (pcs)</label>
                                        <input type="number" step="1" min="0" wire:model="min_stock"
                                               class="form-input w-full px-3 py-2 text-sm rounded-xl font-mono">
                                        @error('min_stock') <span class="field-error">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @else
                                {{-- Recipe builder --}}
                                <div class="inner p-3">
                                    <div class="flex items-center justify-between mb-3">
                                        <p class="field-label !mb-0">
                                            <i class="fa-solid fa-flask" style="color: var(--accent);"></i> Recipe <span class="normal-case font-normal">(amount used per 1 unit sold)</span>
                                        </p>
                                        <span class="text-[11px] font-mono muted">{{ count($draftRecipe) }} line(s)</span>
                                    </div>

                                    @if($this->ingredients->isEmpty())
                                        <div class="p-3 rounded-lg text-xs pill-warn">
                                            <i class="fa-solid fa-info-circle"></i>
                                            No ingredients yet. Close this and add some in the <strong>Ingredients</strong> tab first —
                                            or switch to <strong>Ready-made item</strong> if this product has no recipe.
                                        </div>
                                    @else
                                        @if(! empty($draftRecipe))
                                            <div class="space-y-1.5 mb-3">
                                                @foreach($draftRecipe as $index => $line)
                                                    @php
                                                        $lineQtyInIng = \App\Support\UnitConverter::convert((float) $line['quantity'], $line['unit'], $line['ingredient_unit']) ?? (float) $line['quantity'];
                                                        $lineCost = (float) $line['ingredient_cost'] * $lineQtyInIng;
                                                    @endphp
                                                    <div class="flex items-center gap-2 p-2 rounded-lg" wire:key="draft-{{ $index }}-{{ $line['ingredient_id'] }}"
                                                         style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                                                        <div class="flex-1 min-w-0">
                                                            <span class="text-xs font-bold">{{ $line['ingredient_name'] }}</span>
                                                            <span class="text-[11px] font-mono muted ml-2">{{ $fmt($line['quantity']) }} {{ $line['unit'] }}</span>
                                                            @if($lineCost > 0)
                                                                <span class="text-[11px] font-mono muted ml-2">≈ ₱{{ number_format($lineCost, 2) }}</span>
                                                            @endif
                                                        </div>
                                                        <button type="button" wire:click="removeDraftIngredient({{ $index }})"
                                                                class="p-1.5 rounded-lg opacity-70 hover:opacity-100 cursor-pointer">
                                                            <i class="fa-solid fa-xmark text-xs" style="color: var(--bad);"></i>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-[11px] muted italic mb-3">No ingredients yet. Add them below, or save now and add the recipe later.</p>
                                        @endif

                                        <div class="grid grid-cols-6 sm:grid-cols-12 gap-2 items-end">
                                            <div class="col-span-6 sm:col-span-5">
                                                <label class="field-label">Ingredient</label>
                                                <select wire:model.live="draftIngredientId" class="form-input w-full px-2 py-2.5 text-xs rounded-lg">
                                                    <option value="">— Pick —</option>
                                                    @foreach($this->ingredients as $ing)
                                                        <option value="{{ $ing->id }}">{{ $ing->name }} ({{ $ing->unit }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-span-3 sm:col-span-3">
                                                <label class="field-label">Amount</label>
                                                <input type="number" step="any" min="0" wire:model="draftQuantity" placeholder="0"
                                                       class="form-input w-full px-2 py-2.5 text-xs rounded-lg font-mono text-right">
                                            </div>
                                            <div class="col-span-3 sm:col-span-2">
                                                <label class="field-label">Unit</label>
                                                <select wire:model="draftUnit" class="form-input w-full px-2 py-2.5 text-xs rounded-lg font-mono">
                                                    @foreach($draftUnits as $u)
                                                        <option value="{{ $u }}">{{ $u }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-span-6 sm:col-span-2">
                                                <button type="button" wire:click="addDraftIngredient"
                                                        class="btn-accent w-full py-2.5 rounded-lg text-xs font-bold flex items-center justify-center gap-1 cursor-pointer">
                                                    <i class="fa-solid fa-plus text-[10px]"></i> Add
                                                </button>
                                            </div>
                                        </div>

                                        @if($draftIng)
                                            <p class="field-hint">
                                                {{ $draftIng->name }} is stocked in <strong>{{ $draftIng->unit }}</strong>
                                                (₱{{ number_format((float) $draftIng->cost, 2) }}/{{ $draftIng->unit }}). Amounts convert automatically.
                                            </p>
                                        @endif

                                        @error('draftIngredientId') <span class="field-error">{{ $message }}</span> @enderror
                                        @error('draftQuantity')     <span class="field-error">{{ $message }}</span> @enderror
                                        @error('draftUnit')         <span class="field-error">{{ $message }}</span> @enderror
                                    @endif
                                </div>
                            @endif

                            @if($isReadyMode || ! empty($draftRecipe))
                                <div class="inner p-3 grid grid-cols-3 gap-2 text-center">
                                    <div>
                                        <p class="text-[11px] uppercase muted mb-0.5">Cost</p>
                                        <p class="text-sm font-black font-mono" style="color: var(--bad);">₱{{ number_format($this->draftRecipeCost, 2) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] uppercase muted mb-0.5">Margin</p>
                                        <p class="text-sm font-black font-mono" style="color: {{ $this->draftMargin >= 0 ? 'var(--ok)' : 'var(--bad)' }};">₱{{ number_format($this->draftMargin, 2) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] uppercase muted mb-0.5">Margin %</p>
                                        <p class="text-sm font-black font-mono"
                                           style="color: {{ $this->draftMarginPct >= 50 ? 'var(--ok)' : ($this->draftMarginPct >= 20 ? 'var(--warn)' : 'var(--bad)') }};">
                                            {{ $this->draftMarginPct }}%
                                        </p>
                                    </div>
                                </div>
                            @endif
                        @endif

                        <div>
                            <label class="field-label">Photo <span class="normal-case font-normal">— optional</span></label>
                            <label class="dropzone relative flex items-center justify-center h-28 rounded-xl cursor-pointer overflow-hidden">
                                @if($photo)
                                    <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="absolute inset-0 w-full h-full object-cover">
                                    <button type="button" wire:click.prevent="$set('photo', null)"
                                            class="absolute top-2 right-2 w-7 h-7 rounded-lg bg-black/60 text-white flex items-center justify-center cursor-pointer">
                                        <i class="fa-solid fa-xmark text-xs"></i>
                                    </button>
                                @else
                                    <div class="flex flex-col items-center gap-1">
                                        <i class="fa-solid fa-cloud-arrow-up text-xl" style="color: var(--accent);"></i>
                                        <p class="text-xs font-bold">Click to upload</p>
                                        <p class="text-[11px] muted">JPG, PNG, WebP · Max 2MB</p>
                                    </div>
                                @endif
                                <input type="file" wire:model="photo" class="hidden" accept="image/*">
                            </label>
                            @error('photo') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex gap-2 p-4 sm:p-5 border-t flex-shrink-0" style="border-color: var(--border-soft); background-color: var(--bg-elevated);">
                        <button type="button" wire:click="closeAddModal" class="flex-1 py-3 rounded-xl inner text-xs font-bold cursor-pointer">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="addProduct,photo"
                                class="flex-[2] py-3 rounded-xl text-xs font-bold flex items-center justify-center gap-2 cursor-pointer {{ $type === 'ingredient' ? 'btn-violet' : 'btn-accent' }}">
                            <span wire:loading.remove wire:target="addProduct"><i class="fa-solid fa-check"></i> Save {{ $type === 'ingredient' ? 'Ingredient' : 'Product' }}</span>
                            <span wire:loading wire:target="addProduct" class="flex items-center gap-2"><span class="spinner"></span> Saving…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- EDIT MODAL --}}
    @if($showEditModal)
        @php
            $recipeIng   = $recipeIngredientId ? $this->ingredients->firstWhere('id', $recipeIngredientId) : null;
            $recipeUnits = $recipeIng
                ? \App\Support\UnitConverter::compatibleUnits($recipeIng->unit)
                : array_keys(\App\Support\UnitConverter::UNITS);
            $editHasRecipe  = $edit_type === 'finished' && $this->editingRecipe->isNotEmpty();
            $editUnitShown  = $edit_type === 'ingredient'
                ? ($edit_original_type === 'ingredient' ? $edit_original_unit : $edit_unit)
                : 'pcs';
            $unitLocked     = $edit_original_type === 'ingredient';
            $editBaseHint   = $edit_type === 'ingredient' ? \App\Support\UnitConverter::baseLabel((float) $edit_stock, $editUnitShown) : null;
        @endphp
        <div class="shop-owner-theme fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
             role="dialog" aria-modal="true" wire:keydown.escape.window="closeEditModal">
            <div class="modal-backdrop fixed inset-0 bg-black/60" wire:click="closeEditModal"></div>

            <div class="modal-panel card w-full sm:max-w-2xl relative z-10 max-h-[95vh] sm:max-h-[90vh] flex flex-col rounded-t-3xl sm:rounded-2xl overflow-hidden">

                <div class="flex items-center justify-between p-4 sm:p-5 border-b flex-shrink-0" style="border-color: var(--border-soft);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent);">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold">Edit {{ $edit_type === 'ingredient' ? 'Ingredient' : 'Product' }}</h2>
                            <p class="text-[11px] muted">Main Store stock only</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeEditModal" class="p-2 rounded-lg opacity-60 hover:opacity-100 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="p-4 sm:p-5 space-y-4 overflow-y-auto flex-1">

                    @if(session()->has('modal_success'))
                        <div class="p-2.5 rounded-lg text-xs font-bold pill-ok flex items-center gap-2">
                            <i class="fa-solid fa-circle-check"></i> {{ session('modal_success') }}
                        </div>
                    @endif
                    @if(session()->has('modal_error'))
                        <div class="p-2.5 rounded-lg text-xs font-bold pill-bad flex items-center gap-2">
                            <i class="fa-solid fa-circle-exclamation"></i> {{ session('modal_error') }}
                        </div>
                    @endif

                    <div>
                        <label class="field-label">Type</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="$set('edit_type', 'finished')"
                                    class="seg-btn py-2.5 rounded-xl text-xs font-bold cursor-pointer {{ $edit_type === 'finished' ? 'on-accent' : '' }}">
                                <i class="fa-solid fa-mug-hot"></i> Product
                            </button>
                            <button type="button" wire:click="$set('edit_type', 'ingredient')"
                                    class="seg-btn py-2.5 rounded-xl text-xs font-bold cursor-pointer {{ $edit_type === 'ingredient' ? 'on-violet' : '' }}">
                                <i class="fa-solid fa-wheat-awn"></i> Ingredient
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="field-label">Name</label>
                            <input type="text" wire:model="edit_name" class="form-input w-full px-3 py-2.5 text-sm rounded-xl">
                            @error('edit_name') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="field-label">Category</label>
                            <select wire:model="edit_category_id" class="form-input w-full px-3 py-2.5 text-sm rounded-xl">
                                <option value="">— Select —</option>
                                @foreach($this->editCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->cat_name }}</option>
                                @endforeach
                            </select>
                            @error('edit_category_id') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    @if($edit_type === 'ingredient')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="field-label">Unit</label>
                                @if($unitLocked)
                                    <div class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono opacity-80">
                                        {{ \App\Support\UnitConverter::UNITS[$edit_original_unit]['label'] ?? $edit_original_unit }}
                                    </div>
                                    <p class="field-hint"><i class="fa-solid fa-lock text-[9px]"></i> Locked — stock and recipes depend on it. To change it, add a new ingredient.</p>
                                @else
                                    <select wire:model="edit_unit" class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono">
                                        @foreach(\App\Support\UnitConverter::UNITS as $u => $meta)
                                            <option value="{{ $u }}">{{ $meta['label'] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            <div>
                                <label class="field-label">Cost per {{ $editUnitShown }} (₱)</label>
                                <input type="number" step="any" wire:model="edit_cost" class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono">
                                @error('edit_cost') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="field-label">Stock (Main Store) in {{ $editUnitShown }}</label>
                                <input type="number" step="any" min="0" wire:model.live.debounce.300ms="edit_stock" class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono">
                                @if($editBaseHint) <p class="field-hint font-mono">= {{ $editBaseHint }}</p> @endif
                                @error('edit_stock') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="field-label">Low-stock alert at ({{ $editUnitShown }})</label>
                                <input type="number" step="any" min="0" wire:model="edit_min_stock" class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono">
                                @error('edit_min_stock') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="field-label">Selling Price (₱)</label>
                                <input type="number" step="0.01" wire:model="edit_price" class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono font-bold">
                                @error('edit_price') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                            @if(! $editHasRecipe)
                                <div>
                                    <label class="field-label">Buying cost per piece (₱)</label>
                                    <input type="number" step="0.01" wire:model="edit_cost" class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono">
                                    @error('edit_cost') <span class="field-error">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>

                        @if($editHasRecipe)
                            <div class="p-3 rounded-xl text-[11px] pill-info">
                                <i class="fa-solid fa-flask"></i>
                                This product has a recipe, so its cost and stock are <strong>calculated from ingredients</strong> — no stock to type here.
                            </div>
                        @else
                            <div class="p-3 inner">
                                <p class="field-label"><i class="fa-solid fa-box" style="color: var(--accent);"></i> Ready-made item — its own stock</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="field-label">Stock (pcs)</label>
                                        <input type="number" step="1" min="0" wire:model="edit_stock" class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono">
                                        @error('edit_stock') <span class="field-error">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="field-label">Low-stock alert at (pcs)</label>
                                        <input type="number" step="1" min="0" wire:model="edit_min_stock" class="form-input w-full px-3 py-2.5 text-sm rounded-xl font-mono">
                                    </div>
                                </div>
                                <p class="field-hint">Add recipe lines below only if this product is made from ingredients — then stock is calculated instead.</p>
                            </div>
                        @endif
                    @endif

                    @if($edit_type === 'finished')
                        <div class="inner p-3">
                            <div class="flex items-center justify-between mb-3">
                                <p class="field-label !mb-0">
                                    <i class="fa-solid fa-flask" style="color: var(--accent);"></i> Recipe — ingredients per 1 unit sold
                                </p>
                                <span class="text-[11px] font-mono muted">{{ $this->editingRecipe->count() }} line(s)</span>
                            </div>

                            @if($this->editingRecipe->isNotEmpty())
                                <div class="space-y-1.5 mb-3">
                                    @foreach($this->editingRecipe as $line)
                                        @php
                                            $ingU   = $line->ingredient->unit ?? $line->unit;
                                            $conv   = \App\Support\UnitConverter::convert((float) $line->quantity, $line->unit, $ingU);
                                            $lCost  = ((float) ($line->ingredient->cost ?? 0)) * ($conv ?? (float) $line->quantity);
                                        @endphp
                                        <div class="flex items-center gap-2 p-2 rounded-lg" wire:key="recipe-line-{{ $line->id }}"
                                             style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                                            <div class="flex-1 min-w-0">
                                                <span class="text-xs font-bold">{{ $line->ingredient->name ?? '—' }}</span>
                                                <span class="text-[11px] font-mono muted ml-2">{{ $fmt($line->quantity) }} {{ $line->unit }}</span>
                                                @if($conv === null)
                                                    <span class="text-[10px] font-bold ml-2 px-1.5 rounded pill-bad">unit mismatch ({{ $ingU }})</span>
                                                @elseif($lCost > 0)
                                                    <span class="text-[11px] font-mono muted ml-2">≈ ₱{{ number_format($lCost, 2) }}</span>
                                                @endif
                                            </div>
                                            <button type="button"
                                                    wire:click="removeIngredientFromRecipe({{ $line->id }})"
                                                    wire:confirm="Remove {{ $line->ingredient->name ?? 'this' }} from the recipe?"
                                                    class="p-1.5 rounded-lg opacity-70 hover:opacity-100 cursor-pointer">
                                                <i class="fa-solid fa-xmark text-xs" style="color: var(--bad);"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-[11px] muted italic mb-3">No ingredients yet. Add the first one below (or keep it as a ready-made item).</p>
                            @endif

                            <div class="grid grid-cols-6 sm:grid-cols-12 gap-2 items-end">
                                <div class="col-span-6 sm:col-span-5">
                                    <label class="field-label">Ingredient</label>
                                    <select wire:model.live="recipeIngredientId" class="form-input w-full px-2 py-2.5 text-xs rounded-lg">
                                        <option value="">— Pick —</option>
                                        @foreach($this->ingredients as $ing)
                                            <option value="{{ $ing->id }}">{{ $ing->name }} ({{ $ing->unit }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-3 sm:col-span-3">
                                    <label class="field-label">Amount</label>
                                    <input type="number" step="any" min="0" wire:model="recipeQuantity" placeholder="0"
                                           class="form-input w-full px-2 py-2.5 text-xs rounded-lg font-mono text-right">
                                </div>
                                <div class="col-span-3 sm:col-span-2">
                                    <label class="field-label">Unit</label>
                                    <select wire:model="recipeUnit" class="form-input w-full px-2 py-2.5 text-xs rounded-lg font-mono">
                                        @foreach($recipeUnits as $u)
                                            <option value="{{ $u }}">{{ $u }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-6 sm:col-span-2">
                                    <button type="button" wire:click="addIngredientToRecipe"
                                            class="btn-accent w-full py-2.5 rounded-lg text-xs font-bold flex items-center justify-center gap-1 cursor-pointer">
                                        <i class="fa-solid fa-plus text-[10px]"></i> Add
                                    </button>
                                </div>
                            </div>

                            @if($recipeIng)
                                <p class="field-hint">
                                    {{ $recipeIng->name }} is stocked in <strong>{{ $recipeIng->unit }}</strong>. Amounts convert automatically.
                                </p>
                            @endif

                            @error('recipeIngredientId') <span class="field-error">{{ $message }}</span> @enderror
                            @error('recipeQuantity')     <span class="field-error">{{ $message }}</span> @enderror
                            @error('recipeUnit')         <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="field-label">Replace Photo <span class="normal-case font-normal">— optional</span></label>
                        @if($edit_photo)
                            <img src="{{ $edit_photo->temporaryUrl() }}" alt="New photo preview" class="w-20 h-20 rounded-xl object-cover mb-2">
                        @endif
                        <input type="file" wire:model="edit_photo" accept="image/*"
                               class="form-input w-full px-3 py-2 text-xs rounded-xl file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold">
                        @error('edit_photo') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex gap-2 p-4 sm:p-5 border-t flex-shrink-0" style="border-color: var(--border-soft); background-color: var(--bg-elevated);">
                    <button type="button" wire:click="closeEditModal" class="flex-1 py-3 rounded-xl inner text-xs font-bold cursor-pointer">Cancel</button>
                    <button type="button" wire:click="updateProduct" wire:loading.attr="disabled" wire:target="updateProduct,edit_photo"
                            class="flex-[2] py-3 rounded-xl btn-accent text-xs font-bold flex items-center justify-center gap-2 cursor-pointer">
                        <span wire:loading.remove wire:target="updateProduct"><i class="fa-solid fa-floppy-disk"></i> Save Changes</span>
                        <span wire:loading wire:target="updateProduct" class="flex items-center gap-2"><span class="spinner"></span> Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- DELETE MODAL --}}
    @if($showDeleteModal)
        <div class="shop-owner-theme fixed inset-0 z-[60] flex items-center justify-center p-4"
             role="alertdialog" aria-modal="true" wire:keydown.escape.window="closeDeleteModal">
            <div class="modal-backdrop fixed inset-0 bg-black/60" wire:click="closeDeleteModal"></div>

            <div class="modal-panel card w-full max-w-sm relative z-10 p-5 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                         style="background-color: color-mix(in srgb, var(--bad) 12%, transparent); color: var(--bad);">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold">Delete this item?</h3>
                        <p class="text-[11px] muted">This cannot be undone.</p>
                    </div>
                </div>

                <div class="p-3 inner">
                    <p class="text-[11px] muted mb-1">You're about to delete:</p>
                    <p class="text-sm font-bold break-words">{{ $deleteTargetName }}</p>
                </div>

                <p class="text-xs muted">Main Store inventory rows and recipe links will be removed. If no branch stocks it and no recipe uses it, the product is deleted entirely.</p>

                <div class="flex gap-2 pt-1">
                    <button type="button" wire:click="closeDeleteModal" class="flex-1 py-3 rounded-xl inner text-xs font-bold cursor-pointer">Cancel</button>
                    <button type="button" wire:click="deleteProduct" wire:loading.attr="disabled" wire:target="deleteProduct"
                            class="flex-1 py-3 rounded-xl text-xs font-bold text-white flex items-center justify-center gap-2 cursor-pointer"
                            style="background-color: var(--bad);">
                        <span wire:loading.remove wire:target="deleteProduct"><i class="fa-solid fa-trash-can"></i> Delete</span>
                        <span wire:loading wire:target="deleteProduct" class="flex items-center gap-2"><span class="spinner"></span> Deleting…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- CATEGORY MODAL --}}
    @if($showCategoryModal)
        <div class="shop-owner-theme fixed inset-0 z-[55] flex items-end sm:items-center justify-center p-0 sm:p-4"
             role="dialog" aria-modal="true" wire:keydown.escape.window="closeCategoryModal">
            <div class="modal-backdrop fixed inset-0 bg-black/60" wire:click="closeCategoryModal"></div>

            <div class="modal-panel card w-full sm:max-w-md relative z-10 max-h-[90vh] flex flex-col rounded-t-3xl sm:rounded-2xl overflow-hidden">

                <div class="flex items-center justify-between p-4 sm:p-5 border-b flex-shrink-0" style="border-color: var(--border-soft);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent);">
                            <i class="fa-solid fa-tags"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold">Categories</h2>
                            <p class="text-[11px] muted">{{ $this->allCategories->count() }} total</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeCategoryModal" class="p-2 rounded-lg opacity-60 hover:opacity-100 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="p-4 sm:p-5 space-y-4 overflow-y-auto flex-1">

                    @if(session()->has('modal_success'))
                        <div class="p-2.5 rounded-lg text-xs font-bold pill-ok flex items-center gap-2">
                            <i class="fa-solid fa-circle-check"></i> {{ session('modal_success') }}
                        </div>
                    @endif
                    @if(session()->has('modal_error'))
                        <div class="p-2.5 rounded-lg text-xs font-bold pill-bad flex items-center gap-2">
                            <i class="fa-solid fa-circle-exclamation"></i> {{ session('modal_error') }}
                        </div>
                    @endif

                    <div class="space-y-2">
                        <label class="field-label">New category for</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="$set('newCategoryType', 'finished')"
                                    class="seg-btn py-2.5 rounded-lg text-xs font-bold cursor-pointer {{ $newCategoryType === 'finished' ? 'on-accent' : '' }}">
                                <i class="fa-solid fa-mug-hot text-[10px]"></i> Products
                            </button>
                            <button type="button" wire:click="$set('newCategoryType', 'ingredient')"
                                    class="seg-btn py-2.5 rounded-lg text-xs font-bold cursor-pointer {{ $newCategoryType === 'ingredient' ? 'on-violet' : '' }}">
                                <i class="fa-solid fa-wheat-awn text-[10px]"></i> Ingredients
                            </button>
                        </div>

                        <div class="flex gap-2">
                            <input type="text" wire:model="newCategoryName" placeholder="Category name…"
                                   wire:keydown.enter="addCategory"
                                   class="form-input flex-1 px-3 py-2.5 text-sm rounded-xl">
                            <button type="button" wire:click="addCategory"
                                    class="btn-accent px-4 rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer">
                                <i class="fa-solid fa-plus text-[10px]"></i> Add
                            </button>
                        </div>
                        @error('newCategoryName') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    @foreach(['finished' => ['Products', 'pill-accent'], 'ingredient' => ['Ingredients', 'pill-violet']] as $catType => [$catLabel, $catPill])
                        @php $group = $this->allCategories->where('type', $catType); @endphp
                        <div>
                            <p class="field-label flex items-center gap-2">
                                {{ $catLabel }}
                                <span class="px-1.5 rounded text-[10px] font-mono {{ $catPill }}">{{ $group->count() }}</span>
                            </p>
                            <div class="space-y-1.5">
                                @forelse($group as $cat)
                                    <div class="flex justify-between items-center p-2.5 inner text-xs" wire:key="cat-{{ $cat->id }}">
                                        <span class="truncate font-medium">{{ $cat->cat_name }}</span>
                                        <button type="button" wire:click="deleteCategory({{ $cat->id }})"
                                                wire:confirm="Delete '{{ $cat->cat_name }}'?"
                                                class="opacity-60 hover:opacity-100 p-1.5 cursor-pointer">
                                            <i class="fa-solid fa-trash-can text-[11px]" style="color: var(--bad);"></i>
                                        </button>
                                    </div>
                                @empty
                                    <p class="text-[11px] muted italic px-1">None yet.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>