<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'ROL';

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * All available permission slugs the system supports.
     */
    public const PERMISSIONS = [
        'dashboard'                  => 'Akses Dashboard',
        'users.view'                 => 'Lihat User',
        'users.create'               => 'Tambah User',
        'users.edit'                 => 'Edit User',
        'users.delete'               => 'Hapus User',
        'roles.manage'               => 'Kelola Role',
        'suppliers.view'             => 'Lihat Supplier',
        'suppliers.create'           => 'Tambah Supplier',
        'suppliers.edit'             => 'Edit Supplier',
        'suppliers.delete'           => 'Hapus Supplier',
        'sales_finance.view'         => 'Lihat Sales & Finance',
        'sales_finance.create'       => 'Tambah Sales & Finance',
        'sales_finance.edit'         => 'Edit Sales & Finance',
        'sales_finance.delete'       => 'Hapus Sales & Finance',
        'activity_logs.view'         => 'Lihat Activity Log',
        'sessions.manage'            => 'Kelola Session',
        // Gudang - Rak
        'rak.view'                  => 'Lihat Rak',
        'rak.create'                => 'Tambah Rak',
        'rak.edit'                  => 'Edit Rak',
        'rak.delete'                => 'Hapus Rak',
        // Gudang - Inbound
        'inbound.view'              => 'Lihat Inbound',
        'inbound.create'            => 'Tambah Inbound',
        'inbound.edit'              => 'Edit Inbound',
        'inbound.delete'            => 'Hapus Inbound',
        // Gudang - Product
        'gudang_product.view'       => 'Lihat Produk Gudang',
        'gudang_product.create'     => 'Tambah Produk Gudang',
        'gudang_product.delete'     => 'Hapus Produk Gudang',
        // Gudang - Warehouse Task
        'warehouse_task.view'       => 'Lihat Warehouse Task',
        'warehouse_task.create'     => 'Tambah Warehouse Task',
        'warehouse_task.edit'       => 'Edit Warehouse Task',
        'warehouse_task.delete'     => 'Hapus Warehouse Task',
        'warehouse_task.process'    => 'Proses Warehouse Task',
        'warehouse_task.complete'   => 'Selesaikan Warehouse Task',
        // Gudang - Outbound
        'outbound.view'             => 'Lihat Outbound',
        'outbound.create'           => 'Tambah Outbound',
        'outbound.print'            => 'Cetak Outbound',
        'outbound.delete'           => 'Hapus Outbound',
    ];

    /**
     * Permission groups for organized display.
     */
    public const PERMISSION_GROUPS = [
        'Umum'       => ['dashboard'],
        'User'       => ['users.view', 'users.create', 'users.edit', 'users.delete'],
        'Role'       => ['roles.manage'],
        'Supplier'   => ['suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete'],
        'Sales & Finance' => ['sales_finance.view', 'sales_finance.create', 'sales_finance.edit', 'sales_finance.delete'],
        'Keamanan'   => ['activity_logs.view', 'sessions.manage'],
        'Gudang' => [
            'rak.view',
            'rak.create',
            'rak.edit',
            'rak.delete',

            'inbound.view',
            'inbound.create',
            'inbound.edit',
            'inbound.delete',

            'gudang_product.view',
            'gudang_product.create',
            'gudang_product.delete',

            'warehouse_task.view',
            'warehouse_task.create',
            'warehouse_task.edit',
            'warehouse_task.delete',
            'warehouse_task.process',
            'warehouse_task.complete',

            'outbound.view',
            'outbound.create',
            'outbound.print',
            'outbound.delete',
        ],
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
        'is_system',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system'   => 'boolean',
    ];

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        return in_array($permission, $permissions, true);
    }

    public function getPermissionsCountAttribute(): int
    {
        return count($this->permissions ?? []);
    }
}
