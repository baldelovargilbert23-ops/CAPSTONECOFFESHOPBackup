<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'payment_method')) {
            return;
        }

        // Before payment_method became a string, MySQL could coerce the UI's
        // `paymongo` value to an empty enum value when strict mode was off.
        // Only pending, unpaid rows are safe to identify and repair this way.
        DB::table('orders')
            ->where('payment_method', '')
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->update(['payment_method' => 'paymongo']);
    }

    public function down(): void
    {
        // Keep the corrected payment method; reverting it would make these
        // pending orders impossible to pay through PayMongo again.
    }
};
