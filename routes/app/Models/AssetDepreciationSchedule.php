<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDepreciationSchedule extends BaseModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';
    public const STATUS_SKIPPED = 'skipped';

    public $table = 'asset_depreciation_schedules';

    protected $fillable = [
        'company_id',
        'fixed_asset_id',
        'period_number',
        'period_date',
        'depreciation_amount',
        'accumulated_depreciation',
        'book_value',
        'status',
        'voucher_id',
    ];

    protected $casts = [
        'period_date' => 'date',
        'depreciation_amount' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'period_number' => 'integer',
    ];

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Vouchers::class, 'voucher_id');
    }
}
