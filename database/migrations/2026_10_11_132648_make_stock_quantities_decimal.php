<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock must allow decimals: 10 kg - 10 g = 9.99 kg.
 * Adjust table names if yours differ. Requires Laravel 10+ (->change()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->decimal('quantity', 14, 4)->default(0)->change();
            $table->decimal('min_stock', 14, 4)->default(5)->change();
        });

        Schema::table('product_ingredients', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
            $table->integer('min_stock')->default(5)->change();
        });

        Schema::table('product_ingredients', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->default(0)->change();
        });
    }
};