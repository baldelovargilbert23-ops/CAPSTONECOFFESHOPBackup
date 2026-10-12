<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{

        protected array $defaultPermissions = [
        'can_view',
        'can_create',
        'can_update',
        'can_delete',
        'can_view_any'

        ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->defaultPermissions as $permission) {
           Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web'
            ]);
        }
    }
}
