<?php

namespace App\Models;

use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class LicenseStatus extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_fee',
        'category',
        'license_category_id',
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
        'license_category_id' => 'integer',
    ];

    public function licenseCategory(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class, 'license_category_id');
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('license_category_id', $categoryId);
    }

    public static function getActive()
    {
        return self::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public static function getActiveForCategory(int $categoryId)
    {
        return self::query()
            ->where('is_active', true)
            ->where('license_category_id', $categoryId)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public static function uniqueNameRule(int $categoryId, ?int $ignoreId = null): Unique
    {
        $rule = Rule::unique('license_statuses', 'name')->where(function ($q) use ($categoryId) {
            $q->where('license_category_id', $categoryId)
                ->whereNull('deleted_at');
            $companyId = CompanyContext::id();
            if ($companyId !== null && Schema::hasColumn('license_statuses', 'company_id')) {
                $q->where('company_id', $companyId);
            }
        });

        if ($ignoreId) {
            $rule->ignore($ignoreId);
        }

        return $rule;
    }
}
