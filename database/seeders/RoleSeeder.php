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
                'description' => 'Mengelola data klien dan penawaran.',
                'is_system'   => true,
                'permissions' => [
                    'dashboard',
                    'clients.view',
                    'clients.create',
                    'clients.edit',
                    'proposals.view',
                    'proposals.create',
                    'proposals.edit',
                    'proposals.delete',
                    'proposals.send_email',
                    'proposals.update_status',
                ],
            ],
            [
                'name'        => 'Finance',
                'slug'        => 'finance',
                'description' => 'Mengelola invoice dan penerimaan pembayaran.',
                'is_system'   => true,
                'permissions' => [
                    'dashboard',
                    'clients.view',
                    'invoices.view',
                    'invoices.mark_paid',
                    'invoices.receipt',
                    'proposals.view',
                ],
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
