<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_logs') && Schema::hasColumn('inventory_logs', 'quantity')) {
            Schema::table('inventory_logs', function (Blueprint $table) {
                $table->decimal('quantity', 14, 4)->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_logs') && Schema::hasColumn('inventory_logs', 'quantity')) {
            Schema::table('inventory_logs', function (Blueprint $table) {
                $table->integer('quantity')->default(0)->change();
            });
        }
    }
};
