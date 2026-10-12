<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // LOYALTY SETTINGS (one per tenant)
        // ============================================================
        if (! Schema::hasTable('loyalty_settings')) {
            Schema::create('loyalty_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_enabled')->default(true);
                $table->integer('points_per_peso')->default(1);
                $table->integer('points_per_order')->default(10);
                $table->integer('points_expiry_days')->default(365);
                $table->integer('min_points_to_redeem')->default(100);
                $table->integer('points_value_in_peso')->default(1);
                $table->timestamps();

                $table->unique(['tenant_id'], 'loyalty_settings_tenant_unique');
            });
        }

        // ============================================================
        // BRANCH LOYALTY SETTINGS (one per branch)
        // ============================================================
        if (! Schema::hasTable('branch_loyalty_settings')) {
            Schema::create('branch_loyalty_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();

                $table->unique(['branch_id'], 'bls_branch_unique');
                $table->index(['tenant_id', 'is_enabled'], 'bls_tenant_enabled_idx');
            });
        }

        // ============================================================
        // CUSTOMER POINTS (one per tenant+user)
        // ============================================================
        if (! Schema::hasTable('customer_points')) {
            Schema::create('customer_points', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->integer('points')->default(0);
                $table->integer('lifetime_points')->default(0);
                $table->integer('points_used')->default(0);
                $table->date('points_expiry')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'user_id'], 'cp_tenant_user_unique');
                $table->index(['tenant_id', 'points'], 'cp_tenant_points_idx');
            });
        }

        // ============================================================
        // REWARD ITEMS
        // ============================================================
        if (! Schema::hasTable('reward_items')) {
            Schema::create('reward_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->integer('points_required');
                $table->integer('stock')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['tenant_id', 'is_active'], 'ri_tenant_active_idx');
            });
        }

        // ============================================================
        // REWARD REDEMPTIONS
        // ============================================================
        if (! Schema::hasTable('reward_redemptions')) {
            Schema::create('reward_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reward_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('points_used');
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'branch_id', 'status'], 'rr_tenant_branch_status_idx');
                $table->index(['user_id', 'status'], 'rr_user_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('reward_items');
        Schema::dropIfExists('customer_points');
        Schema::dropIfExists('branch_loyalty_settings');
        Schema::dropIfExists('loyalty_settings');
    }
};