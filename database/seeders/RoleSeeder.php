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
            [
                'name'        => 'Sales',
                'slug'        => 'sales',
                'description' => 'Akses dashboard, melihat, membuat, dan mengedit data penjualan.',
                'is_system'   => true,
                'permissions' => ['dashboard', 'sales_finance.view', 'sales_finance.create', 'sales_finance.edit'],
            ],
            [
                'name'        => 'Finance',
                'slug'        => 'finance',
                'description' => 'Akses dashboard, melihat, membuat, mengedit, dan memproses data keuangan/penjualan.',
                'is_system'   => true,
                'permissions' => ['dashboard', 'sales_finance.view', 'sales_finance.create', 'sales_finance.edit', 'sales_finance.delete'],
            ],
            [
                'name'        => 'Teknisi',
                'slug'        => 'teknisi',
                'description' => 'Akses dashboard gudang, checklist stok, dan penyiapan barang.',
                'is_system'   => true,
                'permissions' => ['dashboard', 'sales_finance.view'],
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
