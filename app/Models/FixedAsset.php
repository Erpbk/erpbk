<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends BaseModel
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FULLY_DEPRECIATED = 'fully_depreciated';
    public const STATUS_DISPOSED = 'disposed';

    public const ACQUISITION_POSTING_ALREADY_POSTED = 'already_posted';
    public const ACQUISITION_POSTING_POSTED = 'posted';

    public const ACQUISITION_NEW_PURCHASE = 'new_purchase';
    public const ACQUISITION_OPENING_BALANCE = 'opening_balance';
    public const ACQUISITION_DONATION = 'donation';

    public const PAST_DEPR_BACKDATED = 'backdated_entries';
    public const PAST_DEPR_CATCH_UP = 'catch_up_entry';
    public const PAST_DEPR_CURRENT_PERIOD = 'current_period';

    public $table = 'fixed_assets';

    protected $fillable = [
        'company_id',
        'asset_category_id',
        'bike_id',
        'asset_code',
        'name',
        'description',
        'serial_number',
        'branch_id',
        'acquisition_date',
        'in_service_date',
        'acquisition_type',
        'acquisition_cost',
        'salvage_value',
        'opening_accumulated_depreciation',
        'depreciation_as_of_date',
        'past_depreciation_handling',
        'depreciation_method',
        'useful_life_months',
        'depreciation_frequency',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'status',
        'acquisition_posting',
        'acquisition_voucher_id',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'in_service_date' => 'date',
        'depreciation_as_of_date' => 'date',
        'acquisition_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'opening_accumulated_depreciation' => 'decimal:2',
        'useful_life_months' => 'integer',
        'bike_id' => 'integer',
    ];

    public static function acquisitionTypes(): array
    {
        return [
            self::ACQUISITION_NEW_PURCHASE => 'New Purchase',
            self::ACQUISITION_OPENING_BALANCE => 'Opening Balance',
            self::ACQUISITION_DONATION => 'Donation',
        ];
    }

    public static function pastDepreciationHandlingOptions(): array
    {
        return [
            self::PAST_DEPR_BACKDATED => 'Backdated depreciation entries',
            self::PAST_DEPR_CATCH_UP => 'Single catch-up depreciation entry',
            self::PAST_DEPR_CURRENT_PERIOD => 'Start depreciation from current period',
        ];
    }

    public static function lastMonthStartDate(): Carbon
    {
        return Carbon::now()->startOfMonth()->subMonth();
    }

    public static function endOfLastMonthDate(): Carbon
    {
        return Carbon::now()->startOfMonth()->subDay();
    }

    public function requiresPastDepreciationHandling(): bool
    {
        if ($this->isOpeningBalance()) {
            return false;
        }

        $inService = Carbon::parse($this->scheduleStartDate())->startOfDay();

        return $inService->lt(static::lastMonthStartDate()->startOfDay());
    }

    public static function initialStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Active',
        ];
    }

    public static function allStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_FULLY_DEPRECIATED => 'Fully Depreciated',
            self::STATUS_DISPOSED => 'Disposed',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isAcquisitionPosted(): bool
    {
        return in_array($this->acquisition_posting, [
            self::ACQUISITION_POSTING_ALREADY_POSTED,
            self::ACQUISITION_POSTING_POSTED,
        ], true);
    }

    public function canPostDepreciation(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->isAcquisitionPosted();
    }

    public function isOpeningBalance(): bool
    {
        return $this->acquisition_type === self::ACQUISITION_OPENING_BALANCE
            || $this->acquisition_type === 'already_owned';
    }

    public function isDonation(): bool
    {
        return $this->acquisition_type === self::ACQUISITION_DONATION;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function bike(): BelongsTo
    {
        return $this->belongsTo(Bikes::class, 'bike_id');
    }

    public function isVehicleAsset(): bool
    {
        return $this->bike_id !== null || $this->category?->isVehicles() === true;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function scheduleStartDate(): string
    {
        return ($this->in_service_date ?? $this->acquisition_date)->toDateString();
    }

    public function depreciationAsOfDate(): string
    {
        if ($this->isOpeningBalance()) {
            return ($this->depreciation_as_of_date ?? $this->acquisition_date)->toDateString();
        }

        return $this->scheduleStartDate();
    }

    public function depreciationSchedules(): HasMany
    {
        return $this->hasMany(AssetDepreciationSchedule::class, 'fixed_asset_id')
            ->orderBy('period_number');
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'depreciation_expense_account_id');
    }

    public function acquisitionVoucher(): BelongsTo
    {
        return $this->belongsTo(Vouchers::class, 'acquisition_voucher_id');
    }

    /** Total depreciable amount over the full useful life (cost minus salvage). */
    public function totalDepreciableAmount(): float
    {
        return max(0, (float) $this->acquisition_cost - (float) $this->salvage_value);
    }

    /** Remaining amount still to be depreciated from the as-of date forward. */
    public function remainingDepreciableAmount(): float
    {
        return max(0, $this->totalDepreciableAmount() - (float) $this->opening_accumulated_depreciation);
    }

    public function currentBookValue(): float
    {
        return max(
            (float) $this->salvage_value,
            (float) $this->acquisition_cost - (float) $this->opening_accumulated_depreciation
        );
    }

    /** @deprecated Use totalDepreciableAmount() or remainingDepreciableAmount() */
    public function depreciableAmount(): float
    {
        return $this->remainingDepreciableAmount();
    }
}
