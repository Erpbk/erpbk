<?php

namespace App\Models;

use App\Services\Rider\RiderDefaultCategoryService;
use Illuminate\Database\Eloquent\Builder;

class RiderCategory extends BaseModel
{
    protected static function boot()
    {
        parent::boot();

        static::saving(function (RiderCategory $category): void {
            if (
                (string) $category->slug === RiderDefaultCategoryService::DEFAULT_SLUG
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
                $shared->where("{$table}.slug", RiderDefaultCategoryService::DEFAULT_SLUG)
                    ->where("{$table}.is_system", true)
                    ->whereNull("{$table}.company_id");
            })->orWhere(function ($owned) use ($table, $companyId) {
                $owned->where("{$table}.company_id", $companyId)
                    ->whereNotNull("{$table}.company_id");
            });
        });
    }
    protected $table = 'rider_categories';

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
        return $this->hasMany(RiderCustomField::class, 'category_id', 'id');
    }

    /**
     * Default slug-to-labels for seeding (used by migration / fixed-field mapping).
     */
    public static function defaultSlugLabels(): array
    {
        return [
            'rider_info' => 'Rider Info',
            'visa_info' => 'Visa Info',
            'job_info' => 'Job Info',
            'labor_info' => 'Labor Info',
            'additional_info' => 'Additional Information',
            'other' => 'Other',
        ];
    }
}
