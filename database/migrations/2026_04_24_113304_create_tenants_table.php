<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('slug', 100)->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->string('tagline', 200)->nullable();
            $table->text('introduction')->nullable();

            // Media
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();

            // Business / owner
            $table->enum('business_type', [
                'sole_proprietor', 'partnership', 'corporation', 'individual', 'other',
            ])->nullable();
            $table->string('business_type_other')->nullable();
            $table->string('business_address')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_phone', 30)->nullable();
            $table->string('owner_id_path')->nullable();

            // Approval
            $table->enum('status', ['pending', 'active', 'inactive', 'rejected', 'suspended'])
                  ->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};