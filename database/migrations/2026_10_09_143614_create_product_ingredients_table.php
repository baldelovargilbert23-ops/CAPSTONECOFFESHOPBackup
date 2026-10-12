<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->id();

            // The finished product (e.g. Spanish Latte)
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // The ingredient used (a product with type = 'ingredient')
            $table->foreignId('ingredient_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // How much of the ingredient per 1 unit of the product
            $table->decimal('quantity', 10, 2);

            // Display unit: g, ml, pcs
            $table->string('unit', 20);

            $table->timestamps();

            // One line per ingredient per product
            $table->unique(['product_id', 'ingredient_id'], 'pi_product_ingredient_unique');

            $table->index(['product_id']);
            $table->index(['ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ingredients');
    }
};