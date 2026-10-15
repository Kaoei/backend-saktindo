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
                'permissions' => array_values(array_filter(array_keys(Role::PERMISSIONS), fn ($p) => $p !== 'roles.manage')),
            ],
            [
                'name'        => 'Gudang',
                'slug'        => 'gudang',
                'description' => 'Akses operasional gudang: stok barang, inbound, penataan rak, warehouse task, dan outbound.',
                'is_system'   => true,
                'permissions' => [
                    'dashboard',
                    'suppliers.view',
                    'rak.view', 'rak.create', 'rak.edit',
                    'inbound.view', 'inbound.create', 'inbound.edit',
                    'gudang_product.view', 'gudang_product.create',
                    'warehouse_task.view', 'warehouse_task.process', 'warehouse_task.complete',
                    'outbound.view', 'outbound.create', 'outbound.print',
                ],
            ],
            [
                'name'        => 'Sales',
                'slug'        => 'sales',
                'description' => 'Akses dashboard, melihat, membuat, dan mengedit data penjualan serta memantau task gudang pesanan.',
                'is_system'   => true,
                'permissions' => [
                    'dashboard',
                    'sales_finance.view', 'sales_finance.create', 'sales_finance.edit',
                    'warehouse_task.view',
                    'suppliers.view',
                ],
            ],
            [
                'name'        => 'Finance',
                'slug'        => 'finance',
                'description' => 'Akses dashboard, pengelolaan piutang (AR), hutang (AP), faktur, dan pencatatan pembayaran/pelunasan.',
                'is_system'   => true,
                'permissions' => [
                    'dashboard',
                    'sales_finance.view', 'sales_finance.create', 'sales_finance.edit', 'sales_finance.delete',
                    'suppliers.view',
                ],
            ],
            [
                'name'        => 'Teknisi',
                'slug'        => 'teknisi',
                'description' => 'Akses dashboard, checklist stok, dan penyiapan barang.',
                'is_system'   => true,
                'permissions' => ['dashboard', 'warehouse_task.view'],
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
