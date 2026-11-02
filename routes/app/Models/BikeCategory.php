<?php

namespace App\Models;

class BikeCategory extends BaseModel
{
    protected $table = 'bike_categories';

    protected $fillable = [
        'slug',
        'label',
        'display_order',
        'is_system',
        'company_id',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function customFields()
    {
        return $this->hasMany(BikeCustomField::class, 'category_id', 'id');
    }

    public static function defaultSlugLabels(): array
    {
        return [
            'bike_info' => 'Bike Info',
            'insurance_info' => 'Insurance Info',
            'documents_info' => 'Documents Info',
            'other' => 'Other',
        ];
    }
}
