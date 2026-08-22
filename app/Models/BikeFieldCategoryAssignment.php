<?php

namespace App\Models;

use App\Support\CompanyContext;

class BikeFieldCategoryAssignment extends BaseModel
{
    protected $table = 'bike_field_category_assignments';

    protected static function boot()
    {
        parent::boot();

        static::saving(function (BikeFieldCategoryAssignment $assignment): void {
            if (! $assignment->hasCompanyColumn()) {
                return;
            }

            $companyId = CompanyContext::id();
            if ($companyId !== null) {
                $assignment->company_id = $companyId;
            }
        });
    }

    protected $fillable = [
        'company_id',
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
