<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'payment_session_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_session_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'payment_session_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['payment_session_id']);
            $table->dropColumn('payment_session_id');
        });
    }
};
