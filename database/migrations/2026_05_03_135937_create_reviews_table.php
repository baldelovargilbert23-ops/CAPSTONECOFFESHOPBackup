<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['product', 'branch'])->default('product');
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->json('photo_paths')->nullable();
            $table->text('owner_reply')->nullable();
            $table->boolean('is_visible')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id']);
            $table->index('product_id');
            $table->index(['type', 'rating']);

            // Uniqueness is enforced at the application layer (see Review model
            // booted() guard) since MySQL 8 forbids stored generated columns
            // that reference FK columns.
            $table->index(['user_id', 'product_id'], 'reviews_user_product_idx');
            $table->index(['user_id', 'branch_id'],  'reviews_user_branch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};