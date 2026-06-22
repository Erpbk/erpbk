<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetCategory extends BaseModel
{
    use SoftDeletes;

    public const METHOD_STRAIGHT_LINE = 'straight_line';
    public const METHOD_DECLINING_BALANCE = 'declining_balance';
    public const METHOD_DOUBLE_DECLINING_BALANCE = 'double_declining_balance';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_YEARLY = 'yearly';

    public const SYSTEM_CODE_VEHICLES = 'VEHICLES';
    public const SYSTEM_NAME_VEHICLES = 'Vehicles';

    public $table = 'asset_categories';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'depreciation_method',
        'useful_life_months',
        'depreciation_frequency',
        'salvage_value_percent',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'is_active',
        'is_system',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'useful_life_months' => 'integer',
        'salvage_value_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public static function depreciationMethods(): array
    {
        return [
            self::METHOD_STRAIGHT_LINE => 'Straight Line',
            self::METHOD_DECLINING_BALANCE => 'Declining Balance',
            self::METHOD_DOUBLE_DECLINING_BALANCE => 'Double Declining Balance',
        ];
    }

    public static function isDecliningBalanceMethod(string $method): bool
    {
        return in_array($method, [
            self::METHOD_DECLINING_BALANCE,
            self::METHOD_DOUBLE_DECLINING_BALANCE,
        ], true);
    }

    public static function depreciationFrequencies(): array
    {
        return [
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_YEARLY => 'Yearly',
        ];
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class, 'asset_category_id');
    }

    public function assetAccount()
    {
        return $this->belongsTo(Accounts::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount()
    {
        return $this->belongsTo(Accounts::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount()
    {
        return $this->belongsTo(Accounts::class, 'depreciation_expense_account_id');
    }

    public function salvageValueForCost(float $cost): float
    {
        return round($cost * ((float) $this->salvage_value_percent / 100), 2);
    }

    public function isVehicles(): bool
    {
        return (bool) $this->is_system
            && (string) $this->code === self::SYSTEM_CODE_VEHICLES;
    }

    public function isSystemLocked(): bool
    {
        return (bool) $this->is_system;
    }
}
