<?php

namespace App\Models;

use App\Services\Employee\EmployeeDefaultCategoryService;
use Illuminate\Database\Eloquent\Builder;

class EmployeeCategory extends BaseModel
{
    protected static function boot()
    {
        parent::boot();

        static::saving(function (EmployeeCategory $category): void {
            if (
                (string) $category->slug === EmployeeDefaultCategoryService::DEFAULT_SLUG
                && $category->is_system
            ) {
                $category->company_id = null;
            }
        });
    }

    /**
     * Shared system default (company_id NULL) plus this company's own categories.
     */
    protected function applyCompanyScopeConstraint(Builder $builder, int $companyId): void
    {
        $table = $this->getTable();

        $builder->where(function ($query) use ($table, $companyId) {
            $query->where(function ($shared) use ($table) {
                $shared->where("{$table}.slug", EmployeeDefaultCategoryService::DEFAULT_SLUG)
                    ->where("{$table}.is_system", true)
                    ->whereNull("{$table}.company_id");
            })->orWhere(function ($owned) use ($table, $companyId) {
                $owned->where("{$table}.company_id", $companyId)
                    ->whereNotNull("{$table}.company_id");
            });
        });
    }

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
            'employment_info' => 'Employment Info',
            'additional_info' => 'Additional Information',
            'other' => 'Other',
        ];
    }
}
