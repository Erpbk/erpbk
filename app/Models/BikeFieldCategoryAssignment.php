<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BikeFieldCategoryAssignment extends BaseModel
{
    protected $table = 'bike_field_category_assignments';

    /**
     * Assignments are globally unique by field_key.
     */
    protected function shouldApplyCompanyScope(): bool
    {
        return false;
    }

    protected $fillable = [
        'field_key',
        'display_label',
        'input_type',
        'input_config',
        'category_id',
        'display_order',
        'is_visible',
        'is_required',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_required' => 'boolean',
        'input_config' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(BikeCategory::class, 'category_id', 'id');
    }
}

