<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create or update admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'tenant_id' => null,
                'branch_id' => null,
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Admin user seeded successfully!');
        $this->command->info('📧 Email: admin@gmail.com');
        $this->command->info('🔑 Password: admin123');
    }
}