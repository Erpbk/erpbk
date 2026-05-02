<?php

namespace App\Models;

class ModuleSettingCategory extends BaseModel
{
    protected $table = 'module_setting_categories';

    protected $fillable = [
        'module_key',
        'company_id',
        'label',
        'slug',
        'is_system',
        'display_order',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];
}
