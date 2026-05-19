<?php

namespace App\Models;

class ModuleFieldCategoryAssignment extends BaseModel
{
    protected $table = 'module_field_category_assignments';

    protected $fillable = [
        'module_key',
        'company_id',
        'field_key',
        'field_label',
        'category_id',
        'display_label',
        'is_visible',
        'is_required',
        'input_type',
        'input_config',
        'display_order',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_required' => 'boolean',
        'input_config' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(ModuleSettingCategory::class, 'category_id', 'id');
    }
}
