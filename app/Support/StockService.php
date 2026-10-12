<?php

namespace App\Support;

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Shared branch-aware stock rules for customer checkout and staff POS.
 * Recipe products consume their ingredients; ready-made products consume
 * their own inventory row.
 */
class StockService
{
    /**
     * @param iterable<Product> $products
     * @return array<int, int> Product ID => whole units that can be sold.
     */
    public static function availableQuantities(iterable $products, ?int $branchId, ?int $tenantId = null): array
    {
        $products = collect($products)->filter(fn ($product) => $product instanceof Product)->values();

        if ($products->isEmpty()) {
            return [];
        }

        $products->each(fn (Product $product) => $product->loadMissing('recipe.ingredient'));
        $tenantId ??= (int) $products->first()->tenant_id;

        $productIds = $products->pluck('id')->map(fn ($id) => (int) $id)->all();
        $ingredientIds = $products
            ->flatMap(fn (Product $product) => $product->recipe->pluck('ingredient_id'))
            ->map(fn ($id) => (int) $id)
            ->all();
        $stockIds = array_values(array_unique(array_merge($productIds, $ingredientIds)));

        $inventoryQuery = Inventory::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('product_id', $stockIds);

        if ($branchId === null) {
            $inventoryQuery->whereNull('branch_id');
        } else {
            $inventoryQuery->where('branch_id', $branchId);
        }

        $stock = $inventoryQuery->pluck('quantity', 'product_id')
            ->map(fn ($quantity) => (float) $quantity);

        $available = [];

        foreach ($products as $product) {
            $productId = (int) $product->id;

            $productBranchId = $product->branch_id === null ? null : (int) $product->branch_id;
            if ((int) $product->tenant_id !== $tenantId
                || $productBranchId !== $branchId
                || $product->type !== 'finished'
                || ! $product->is_active) {
                $available[$productId] = 0;
                continue;
            }

            $recipe = $product->recipe;
            if ($recipe->isEmpty()) {
                $available[$productId] = max(0, (int) floor(($stock->get($productId, 0.0) + 1e-9)));
                continue;
            }

            $possible = null;

            foreach ($recipe as $line) {
                $ingredient = $line->ingredient;
                $ingredientBranchId = $ingredient?->branch_id === null ? null : (int) $ingredient->branch_id;
                if (! $ingredient
                    || (int) $ingredient->tenant_id !== $tenantId
                    || $ingredientBranchId !== $branchId
                    || $ingredient->type !== 'ingredient') {
                    $possible = 0;
                    break;
                }

                $perProduct = UnitConverter::convert(
                    (float) $line->quantity,
                    $line->unit ?: $ingredient->unit,
                    $ingredient->unit
                );

                if ($perProduct === null || $perProduct <= 0) {
                    $possible = 0;
                    break;
                }

                $lineAvailable = (int) floor(($stock->get((int) $ingredient->id, 0.0) + 1e-9) / $perProduct);
                $possible = $possible === null ? $lineAvailable : min($possible, $lineAvailable);
            }

            $available[$productId] = max(0, (int) ($possible ?? 0));
        }

        return $available;
    }

    public static function availableQuantity(Product $product, ?int $branchId, ?int $tenantId = null): int
    {
        $tenantId ??= (int) $product->tenant_id;

        return self::availableQuantities([$product], $branchId, $tenantId)[(int) $product->id] ?? 0;
    }

    /**
     * Deduct a complete order in one transaction with inventory rows locked
     * in a stable order, so recipe stock cannot be oversold or double counted.
     *
     * @param array<int, int|float> $items Product ID => quantity sold.
     */
    public static function deductItems(
        array $items,
        ?int $branchId,
        int $tenantId,
        ?string $reference = null,
        ?int $userId = null
    ): void {
        if (empty($items)) {
            return;
        }

        DB::transaction(function () use ($items, $branchId, $tenantId, $reference, $userId) {
            $quantities = [];
            foreach ($items as $productId => $quantity) {
                $productId = (int) $productId;
                $quantity = (float) $quantity;
                if ($productId <= 0 || $quantity <= 0) {
                    throw new RuntimeException('Order quantities must be greater than zero.');
                }
                $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
            }

            ksort($quantities);

            $products = Product::forLocation($tenantId, $branchId)
                ->where('type', 'finished')
                ->where('is_active', true)
                ->whereIn('id', array_keys($quantities))
                ->with(['recipe' => fn ($query) => $query->orderBy('ingredient_id'), 'recipe.ingredient'])
                ->orderBy('id')
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($quantities)) {
                throw new RuntimeException('An item in the order is no longer available.');
            }

            $requirements = [];

            foreach ($quantities as $productId => $quantity) {
                /** @var Product $product */
                $product = $products->get($productId);
                if ($product->recipe->isEmpty()) {
                    self::addRequirement($requirements, $productId, $quantity, $product->name);
                    continue;
                }

                foreach ($product->recipe as $line) {
                    $ingredient = $line->ingredient;
                    $ingredientBranchId = $ingredient?->branch_id === null ? null : (int) $ingredient->branch_id;
                    if (! $ingredient
                        || (int) $ingredient->tenant_id !== $tenantId
                        || $ingredientBranchId !== $branchId
                        || $ingredient->type !== 'ingredient') {
                        throw new RuntimeException("A recipe ingredient for {$product->name} is unavailable.");
                    }

                    $needed = UnitConverter::convert(
                        (float) $line->quantity * $quantity,
                        $line->unit ?: $ingredient->unit,
                        $ingredient->unit
                    );

                    if ($needed === null || $needed <= 0) {
                        throw new RuntimeException("Unit mismatch in the recipe for {$ingredient->name}.");
                    }

                    self::addRequirement($requirements, (int) $ingredient->id, $needed, $ingredient->name);
                }
            }

            ksort($requirements);
            $inventoryQuery = Inventory::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereIn('product_id', array_keys($requirements));

            if ($branchId === null) {
                $inventoryQuery->whereNull('branch_id');
            } else {
                $inventoryQuery->where('branch_id', $branchId);
            }

            $inventory = $inventoryQuery->orderBy('product_id')->lockForUpdate()->get()->keyBy('product_id');

            foreach ($requirements as $productId => $requirement) {
                $row = $inventory->get($productId);
                $available = (float) ($row?->quantity ?? 0);

                if (! $row || $available + 1e-9 < $requirement['quantity']) {
                    throw new RuntimeException(
                        "Not enough {$requirement['name']} in stock. Available: " . UnitConverter::format($available)
                    );
                }
            }

            foreach ($requirements as $productId => $requirement) {
                $row = $inventory->get($productId);
                $row->quantity = round((float) $row->quantity - $requirement['quantity'], 4);
                $row->save();

                InventoryLog::withoutGlobalScopes()->create([
                    'tenant_id'  => $tenantId,
                    'branch_id'  => $branchId,
                    'product_id' => $productId,
                    'user_id'    => $userId,
                    'type'       => 'OUT',
                    'quantity'   => round($requirement['quantity'], 4),
                    'reference'  => $reference,
                    'note'       => 'Sale stock deduction',
                ]);
            }
        });
    }

    public static function deductSale(
        Product $product,
        float $quantity,
        ?int $branchId,
        ?int $tenantId = null,
        ?string $reference = null,
        ?int $userId = null
    ): void {
        $tenantId ??= (int) $product->tenant_id;
        self::deductItems([(int) $product->id => $quantity], $branchId, $tenantId, $reference, $userId);
    }

    /**
     * Restore stock when a reserved order is cancelled. New orders restore
     * the exact ingredient rows recorded at checkout. Older orders without
     * ingredient log rows restore their historical finished-product stock.
     */
    public static function restoreOrder(Order $order, ?int $userId = null): void
    {
        DB::transaction(function () use ($order, $userId) {
            $tenantId = (int) $order->tenant_id;
            $branchId = $order->branch_id === null ? null : (int) $order->branch_id;
            $restoreReference = 'RESTORE-' . $order->order_number;

            $restoredQuery = InventoryLog::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('type', 'IN')
                ->where('reference', $restoreReference);
            if ($branchId === null) {
                $restoredQuery->whereNull('branch_id');
            } else {
                $restoredQuery->where('branch_id', $branchId);
            }
            if ($restoredQuery->exists()) {
                return;
            }

            $logsQuery = InventoryLog::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('type', 'OUT')
                ->where('reference', $order->order_number);

            if ($branchId === null) {
                $logsQuery->whereNull('branch_id');
            } else {
                $logsQuery->where('branch_id', $branchId);
            }

            $logs = $logsQuery->orderBy('product_id')->lockForUpdate()->get();
            $requirements = [];

            if ($logs->isNotEmpty()) {
                foreach ($logs->groupBy('product_id') as $productId => $productLogs) {
                    $product = $productLogs->first()->product;
                    self::addRequirement(
                        $requirements,
                        (int) $productId,
                        (float) $productLogs->sum('quantity'),
                        $product?->name ?? 'item'
                    );
                }
            } else {
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        $product = $item->product;
                        self::addRequirement(
                            $requirements,
                            (int) $item->product_id,
                            (float) $item->quantity,
                            $product?->name ?? 'item'
                        );
                    }
                }
            }

            if (empty($requirements)) {
                return;
            }

            ksort($requirements);
            $inventoryQuery = Inventory::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('product_id', array_keys($requirements));

            if ($branchId === null) {
                $inventoryQuery->whereNull('branch_id');
            } else {
                $inventoryQuery->where('branch_id', $branchId);
            }

            $inventory = $inventoryQuery->orderBy('product_id')->lockForUpdate()->get()->keyBy('product_id');

            foreach ($requirements as $productId => $requirement) {
                $row = $inventory->get($productId);

                if (! $row) {
                    $row = Inventory::forBranchProduct($tenantId, $branchId, (int) $productId);
                    $row = Inventory::withoutGlobalScopes()->whereKey($row->id)->lockForUpdate()->first();
                }

                $row->quantity = round((float) $row->quantity + $requirement['quantity'], 4);
                $row->save();

                InventoryLog::withoutGlobalScopes()->create([
                    'tenant_id'  => $tenantId,
                    'branch_id'  => $branchId,
                    'product_id' => $productId,
                    'user_id'    => $userId,
                    'type'       => 'IN',
                    'quantity'   => round($requirement['quantity'], 4),
                    'reference'  => $restoreReference,
                    'note'       => 'Cancelled order stock restored',
                ]);
            }
        });
    }

    private static function addRequirement(array &$requirements, int $productId, float $quantity, string $name): void
    {
        $requirements[$productId] ??= ['quantity' => 0.0, 'name' => $name];
        $requirements[$productId]['quantity'] += $quantity;
    }
}
