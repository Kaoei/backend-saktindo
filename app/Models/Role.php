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
    ];

    /**
     * Permission groups for organized display.
     */
    public const PERMISSION_GROUPS = [
        'Umum'       => ['dashboard'],
        'User'       => ['users.view', 'users.create', 'users.edit', 'users.delete'],
        'Role'       => ['roles.manage'],
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
