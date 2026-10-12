<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // First, add the role column if it doesn't exist
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'owner', 'branch_manager', 'cashier', 'barista', 'customer'])
                    ->default('customer')
                    ->after('password');
            }
            
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
        });

        // Now set the roles for existing users
        // Admin
        $admin = DB::table('users')->where('email', 'admin@gmail.com')->first();
        if ($admin) {
            DB::table('users')->where('id', $admin->id)->update([
                'role' => 'admin',
                'is_active' => 1
            ]);
        } else {
            // Create admin if doesn't exist
            DB::table('users')->insert([
                'name' => 'Super Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'tenant_id' => null,
                'branch_id' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Owner
        DB::table('users')->where('email', 'owner@gmail.com')->update(['role' => 'owner', 'is_active' => 1]);
        
        // Customer
        DB::table('users')->where('email', 'customer@gmail.com')->update(['role' => 'customer', 'is_active' => 1]);
        
        // Manager
        DB::table('users')->where('email', 'manager@gmail.com')->update(['role' => 'branch_manager', 'is_active' => 1]);
        
        // Cashier
        DB::table('users')->where('email', 'cashier@gmail.com')->update(['role' => 'cashier', 'is_active' => 1]);
        
        // Barista
        DB::table('users')->where('email', 'barista@gmail.com')->update(['role' => 'barista', 'is_active' => 1]);

        // Fix any users with empty roles
        DB::table('users')->whereNull('role')->orWhere('role', '')->update(['role' => 'customer']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};