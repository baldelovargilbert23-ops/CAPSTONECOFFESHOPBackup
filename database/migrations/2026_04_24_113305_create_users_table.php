<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('preferred_barista_id')->nullable();

            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();

            $table->string('verification_code', 6)->nullable();
            $table->timestamp('verification_code_expires_at')->nullable();

            $table->string('reset_code', 6)->nullable();
            $table->timestamp('reset_code_expires_at')->nullable();

            $table->string('password');

            $table->enum('role', [
                'admin',
                'owner',
                'branch_manager',
                'cashier',
                'barista',
                'customer',
            ])->default('customer');

            $table->boolean('is_active')->default(true);
            $table->boolean('is_on_shift')->default(false);
            $table->timestamp('shift_started_at')->nullable();

            $table->rememberToken();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'role']);
            $table->index('role');
            $table->index('is_active');
            $table->index('preferred_barista_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('preferred_barista_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->foreign('branch_manager_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('tenants', function (Blueprint $table) {
            // ✅ No more main_branch_id FK — tenant is its own store
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['branch_manager_id']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['preferred_barista_id']);
        });

        Schema::dropIfExists('users');
    }
};