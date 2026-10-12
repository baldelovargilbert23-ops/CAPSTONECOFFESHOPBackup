<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('global_theme_settings')) {
            return;
        }

        Schema::create('global_theme_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->char('accent', 7)->default('#c8965f');
            $table->char('light_background', 7)->default('#f5f0e8');
            $table->char('light_surface', 7)->default('#ffffff');
            $table->char('light_text', 7)->default('#1a3a3a');
            $table->char('dark_background', 7)->default('#0a1f1a');
            $table->char('dark_surface', 7)->default('#14332c');
            $table->char('dark_text', 7)->default('#f0ebe0');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_theme_settings');
    }
};
