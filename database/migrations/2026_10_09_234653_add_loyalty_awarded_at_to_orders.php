<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'loyalty_awarded_at')) {
                $table->timestamp('loyalty_awarded_at')->nullable()->after('is_reward_order');
            }
            if (! Schema::hasColumn('orders', 'loyalty_points_awarded')) {
                $table->integer('loyalty_points_awarded')->nullable()->after('loyalty_awarded_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'loyalty_points_awarded')) {
                $table->dropColumn('loyalty_points_awarded');
            }
            if (Schema::hasColumn('orders', 'loyalty_awarded_at')) {
                $table->dropColumn('loyalty_awarded_at');
            }
        });
    }
};