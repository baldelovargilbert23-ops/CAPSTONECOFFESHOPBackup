<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'branch_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('branches')
                    ->cascadeOnDelete();
                $table->index(['tenant_id', 'branch_id', 'type', 'is_active'], 'products_location_catalog_idx');
            });
        }

        // Existing products were shared across all locations. Copy that catalog into
        // each branch once, then point that branch's stock and history at its copies.
        // Main-store products remain branch_id = null.
        foreach (DB::table('branches')->orderBy('id')->get() as $branch) {
            $hasBranchCatalog = DB::table('products')
                ->where('tenant_id', $branch->tenant_id)
                ->where('branch_id', $branch->id)
                ->exists();

            if ($hasBranchCatalog) {
                continue;
            }

            DB::transaction(function () use ($branch) {
                $sourceProducts = DB::table('products')
                    ->where('tenant_id', $branch->tenant_id)
                    ->whereNull('branch_id')
                    ->orderBy('id')
                    ->get();

                if ($sourceProducts->isEmpty()) {
                    return;
                }

                $productMap = [];
                foreach ($sourceProducts as $source) {
                    $productMap[(int) $source->id] = DB::table('products')->insertGetId([
                        'tenant_id'   => $source->tenant_id,
                        'branch_id'   => $branch->id,
                        'category_id' => $source->category_id,
                        'type'        => $source->type,
                        'unit'        => $source->unit,
                        'name'        => $source->name,
                        // SKUs are tenant-unique in the current schema. Let each
                        // branch assign its own SKU instead of copying a collision.
                        'sku'         => null,
                        'price'       => $source->price,
                        'cost'        => $source->cost,
                        'image'       => $source->image,
                        'description' => $source->description,
                        'is_active'   => $source->is_active,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }

                if (Schema::hasTable('product_ingredients')) {
                    $recipeLines = DB::table('product_ingredients')
                        ->whereIn('product_id', array_keys($productMap))
                        ->get();

                    foreach ($recipeLines as $line) {
                        if (! isset($productMap[(int) $line->ingredient_id])) {
                            continue;
                        }

                        DB::table('product_ingredients')->insert([
                            'product_id'    => $productMap[(int) $line->product_id],
                            'ingredient_id' => $productMap[(int) $line->ingredient_id],
                            'quantity'      => $line->quantity,
                            'unit'          => $line->unit,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                    }
                }

                foreach ($productMap as $sourceId => $branchProductId) {
                    DB::table('inventories')
                        ->where('tenant_id', $branch->tenant_id)
                        ->where('branch_id', $branch->id)
                        ->where('product_id', $sourceId)
                        ->update(['product_id' => $branchProductId]);

                    if (Schema::hasTable('inventory_logs')) {
                        DB::table('inventory_logs')
                            ->where('tenant_id', $branch->tenant_id)
                            ->where('branch_id', $branch->id)
                            ->where('product_id', $sourceId)
                            ->update(['product_id' => $branchProductId]);
                    }
                }

                if (Schema::hasTable('purchases') && Schema::hasTable('purchase_items')) {
                    foreach ($productMap as $sourceId => $branchProductId) {
                        DB::table('purchase_items')
                            ->whereIn('purchase_id', function ($query) use ($branch) {
                                $query->select('id')
                                    ->from('purchases')
                                    ->where('tenant_id', $branch->tenant_id)
                                    ->where('branch_id', $branch->id);
                            })
                            ->where('product_id', $sourceId)
                            ->update(['product_id' => $branchProductId]);
                    }
                }

                if (Schema::hasTable('orders') && Schema::hasTable('order_items')) {
                    foreach ($productMap as $sourceId => $branchProductId) {
                        DB::table('order_items')
                            ->whereIn('order_id', function ($query) use ($branch) {
                                $query->select('id')
                                    ->from('orders')
                                    ->where('tenant_id', $branch->tenant_id)
                                    ->where('branch_id', $branch->id);
                            })
                            ->where('product_id', $sourceId)
                            ->update(['product_id' => $branchProductId]);
                    }
                }

                if (Schema::hasTable('reviews') && Schema::hasColumn('reviews', 'branch_id')) {
                    foreach ($productMap as $sourceId => $branchProductId) {
                        DB::table('reviews')
                            ->where('tenant_id', $branch->tenant_id)
                            ->where('branch_id', $branch->id)
                            ->where('product_id', $sourceId)
                            ->update(['product_id' => $branchProductId]);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'branch_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex('products_location_catalog_idx');
            $table->dropColumn('branch_id');
        });
    }
};
