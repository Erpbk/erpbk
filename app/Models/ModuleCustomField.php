<?php

namespace App\Models;

class ModuleCustomField extends BaseModel
{
    protected $table = 'module_custom_fields';

    protected $fillable = [
        'module_key',
        'company_id',
        'category_id',
        'label',
        'help_text',
        'data_privacy',
        'prevent_duplicate_values',
        'data_type',
        'is_mandatory',
        'is_visible',
        'default_value',
        'input_format',
        'config',
        'display_order',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_visible' => 'boolean',
        'prevent_duplicate_values' => 'boolean',
        'data_privacy' => 'array',
        'config' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(ModuleSettingCategory::class, 'category_id', 'id');
    }

    public static function dataTypes(): array
    {
        return BikeCustomField::dataTypes();
    }
}
