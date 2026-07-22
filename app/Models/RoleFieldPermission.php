<?php

namespace App\Models;

/**
 * Field-level permission for a role within a module.
 *
 * @property int $role_id
 * @property int $module_id  Parent permission id (module) from the permissions table.
 * @property string $field_name  DB column name, or "cf_{id}" for a custom field.
 * @property bool $visible
 * @property bool $editable
 * @property bool $required
 */
class RoleFieldPermission extends BaseModel
{
    protected $table = 'role_field_permissions';

    protected $fillable = [
        'role_id',
        'module_id',
        'field_name',
        'company_id',
        'visible',
        'editable',
        'required',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'module_id' => 'integer',
        'visible' => 'boolean',
        'editable' => 'boolean',
        'required' => 'boolean',
    ];
}
