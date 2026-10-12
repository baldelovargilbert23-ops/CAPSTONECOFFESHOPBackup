<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // ✅ NULL = the tenant's own store
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->integer('quantity')->default(0);
            $table->integer('min_stock')->default(5);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'product_id'], 'inv_tenant_branch_product_unique');

            $table->index(['tenant_id', 'branch_id']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};