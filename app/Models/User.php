<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasCustomCode;

    public const CODE_PREFIX = 'USR';

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'group',
    ];

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_TEKNISI = 'teknisi';
    public const ROLE_SALES = 'sales';
    public const ROLE_FINANCE = 'finance';
    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_TEKNISI,
        self::ROLE_SALES,
        self::ROLE_FINANCE,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_TEKNISI => 'Teknisi',
            self::ROLE_SALES => 'Sales',
            self::ROLE_FINANCE => 'Finance',
            default => 'Admin',
        };
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Relationship to the roles table (via slug).
     */
    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Role::class, 'role', 'slug');
    }

    /**
     * Check if this user's role has a given permission.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->role === self::ROLE_SUPER_ADMIN) {
            return true;
        }

        $role = \App\Models\Role::where('slug', $this->role)->first();

        return $role?->hasPermission($permission) ?? false;
    }
}
