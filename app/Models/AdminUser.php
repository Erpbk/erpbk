<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $connection = 'mysql_admin';

    protected $table = 'admin_users';

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function roles()
    {
        return $this->belongsToMany(AdminRole::class, 'admin_model_has_roles', 'admin_user_id', 'admin_role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(AdminPermission::class, 'admin_model_has_permissions', 'admin_user_id', 'admin_permission_id');
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->status) {
            return false;
        }

        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            })
            ->exists();
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Compatibility helper for places expecting Spatie-style API.
     *
     * @param mixed ...$roles
     */
    public function hasAnyRole(...$roles): bool
    {
        $roleNames = [];
        foreach ($roles as $role) {
            if (is_array($role)) {
                $roleNames = array_merge($roleNames, $role);
            } elseif (is_string($role) && $role !== '') {
                $roleNames[] = $role;
            }
        }

        if ($roleNames === []) {
            return false;
        }

        return $this->roles()->whereIn('name', $roleNames)->exists();
    }
}

