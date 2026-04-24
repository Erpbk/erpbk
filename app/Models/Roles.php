<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Roles extends BaseModel
{
    use LogsActivity;

    public $table = 'roles';

    public $fillable = [
        'name',
        'guard_name',
        'company_id'
    ];

    protected $casts = [
        'name' => 'string',
        'guard_name' => 'string',
        'company_id' => 'integer'
    ];

    public static array $rules = [
        'name' => 'required|string|max:255',
        'guard_name' => 'required|string|max:255',
        'company_id' => 'required|exists:companies,id|nullable',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];

    public function modelHasRole(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\ModelHasRole::class);
    }

    public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Permissions::class, 'role_has_permissions', 'role_id', 'permission_id');
    }
}
