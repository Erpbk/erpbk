<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPermission extends BaseModel
{
    protected $table = 'admin_permissions';

    protected $fillable = [
        'parent_id',
        'name',
    ];

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}

