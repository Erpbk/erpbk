<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BikeAssignFieldAssignment extends BaseModel
{
    protected $table = 'bike_assign_field_assignments';

    protected $fillable = [
        'field_key',
        'custom_field_id',
        'kind',
        'display_label',
        'input_type',
        'input_config',
        'display_order',
        'is_visible',
        'is_required',
        'show_on_active',
        'show_on_change',
    ];

    protected $casts = [
        'input_config' => 'array',
        'is_visible' => 'boolean',
        'is_required' => 'boolean',
        'show_on_active' => 'boolean',
        'show_on_change' => 'boolean',
    ];

    public function customField()
    {
        return $this->belongsTo(BikeCustomField::class, 'custom_field_id', 'id');
    }

    public function resolvedLabel(): string
    {
        if ($this->display_label) {
            return $this->display_label;
        }

        if ($this->kind === 'custom' && $this->customField) {
            return $this->customField->label;
        }

        return BikeCustomField::humanizeFieldKey((string) $this->field_key);
    }
}
