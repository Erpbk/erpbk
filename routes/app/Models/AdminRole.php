<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRole extends BaseModel
{
    protected $connection = 'mysql_admin';

    protected $table = 'admin_roles';

    protected $fillable = [
        'name',
    ];

    public function permissions()
    {
        return $this->belongsToMany(AdminPermission::class, 'admin_role_has_permissions', 'admin_role_id', 'admin_permission_id');
    }

    public function users()
    {
        return $this->belongsToMany(AdminUser::class, 'admin_model_has_roles', 'admin_role_id', 'admin_user_id');
    }
}

