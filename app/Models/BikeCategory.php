<?php

namespace App\Models;

use App\Services\Bike\BikeDefaultCategoryService;
use Illuminate\Database\Eloquent\Builder;

class BikeCategory extends BaseModel
{
    protected static function boot()
    {
        parent::boot();

        static::saving(function (BikeCategory $category): void {
            if (
                (string) $category->slug === BikeDefaultCategoryService::DEFAULT_SLUG
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
                $shared->where("{$table}.slug", BikeDefaultCategoryService::DEFAULT_SLUG)
                    ->where("{$table}.is_system", true)
                    ->whereNull("{$table}.company_id");
            })->orWhere(function ($owned) use ($table, $companyId) {
                $owned->where("{$table}.company_id", $companyId)
                    ->whereNotNull("{$table}.company_id");
            });
        });
    }

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

