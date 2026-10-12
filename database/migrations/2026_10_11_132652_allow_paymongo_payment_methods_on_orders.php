<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'payment_method')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            // Keep payment provider/method values forward-compatible instead of
            // silently coercing PayMongo's `paymongo` value to an empty enum.
            $table->string('payment_method')->default('cash')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'payment_method')) {
            return;
        }

        if (DB::table('orders')->whereNotIn('payment_method', ['cash', 'gcash', 'card'])->exists()) {
            throw new RuntimeException(
                'Cannot restore the old payment-method enum while orders use newer payment methods.'
            );
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'gcash', 'card'])->default('cash')->change();
        });
    }
};
