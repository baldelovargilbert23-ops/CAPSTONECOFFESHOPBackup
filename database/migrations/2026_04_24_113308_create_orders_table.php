<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // ✅ NULL = the tenant's own store
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('order_number');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('payment_method', ['cash', 'gcash', 'card'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('status')->default('pending');

            $table->unsignedBigInteger('barista_id')->nullable();
            $table->unsignedBigInteger('preferred_barista_id')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('ready_at')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('is_reward_order')->default(false);

            $table->timestamps();

            $table->unique(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'branch_id', 'status']);
            $table->index(['branch_id', 'created_at']);

            $table->foreign('barista_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('preferred_barista_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};