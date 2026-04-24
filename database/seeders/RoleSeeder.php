<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'        => 'Super Admin',
                'slug'        => 'super_admin',
                'description' => 'Akses penuh ke seluruh fitur sistem.',
                'is_system'   => true,
                'permissions' => array_keys(Role::PERMISSIONS),
            ],
            [
                'name'        => 'Admin',
                'slug'        => 'admin',
                'description' => 'Akses ke semua fitur kecuali manajemen role sistem.',
                'is_system'   => true,
                'permissions' => array_filter(array_keys(Role::PERMISSIONS), fn ($p) => $p !== 'roles.manage'),
            ],
        ];

        foreach ($roles as $data) {
            Role::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['permissions' => array_values($data['permissions'])])
            );
        }
    }
}
