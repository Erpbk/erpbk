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
        'data_type',
        'is_mandatory',
        'default_value',
        'input_format',
        'config',
        'display_order',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
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
