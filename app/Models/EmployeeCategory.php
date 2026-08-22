<?php

namespace App\Models;

class EmployeeCategory extends BaseModel
{
    protected $table = 'employee_categories';

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
        return $this->hasMany(EmployeeCustomField::class, 'category_id', 'id');
    }

    /**
     * Default slug-to-labels for seeding (used by migration / fixed-field mapping).
     */
    public static function defaultSlugLabels(): array
    {
        return [
            'employee_info' => 'Employee Info',
            'visa_info' => 'Visa Info',
            'labor_info' => 'Labor Info',
            'employment_info' => 'Employment Info',
            'additional_info' => 'Additional Information',
            'other' => 'Other',
        ];
    }
}
