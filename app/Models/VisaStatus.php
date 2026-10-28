<?php

namespace App\Models;

use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class VisaStatus extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_fee',
        'category',
        'is_active',
        'is_required',
        'display_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_required' => 'boolean',
        'default_fee' => 'decimal:2',
    ];

    public function renewalCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            VisaRenewalCategory::class,
            'visa_renewal_category_visa_status',
            'visa_status_id',
            'visa_renewal_category_id'
        )->withPivot('display_order')->withTimestamps();
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->whereHas('renewalCategories', function ($q) use ($categoryId) {
            $q->where('visa_renewal_categories.id', $categoryId);
        });
    }

    /**
     * Get active visa statuses
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActive()
    {
        return self::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public static function getActiveForCategory(int $categoryId)
    {
        $category = VisaRenewalCategory::query()->find($categoryId);
        if (! $category) {
            return collect();
        }

        return $category->visaStatuses()
            ->where('visa_statuses.is_active', true)
            ->get();
    }

    public static function uniqueNameRule(?int $ignoreId = null): Unique
    {
        $rule = Rule::unique('visa_statuses', 'name')->where(function ($q) {
            $q->whereNull('deleted_at');
            $companyId = CompanyContext::id();
            if ($companyId !== null && Schema::hasColumn('visa_statuses', 'company_id')) {
                $q->where('company_id', $companyId);
            }
        });

        if ($ignoreId) {
            $rule->ignore($ignoreId);
        }

        return $rule;
    }

    public function ticketNumber(): string
    {
        return 'T-' . str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }
}
