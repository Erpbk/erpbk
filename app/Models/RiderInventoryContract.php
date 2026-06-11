<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiderInventoryContract extends BaseModel
{
    public const TYPE_ASSIGNMENT = 'assignment';
    public const TYPE_RETURN = 'return';

    protected $fillable = [
        'rider_id',
        'contract_type',
        'contract_number',
        'contract_date',
        'total_items',
        'total_returned',
        'total_lost',
        'total_chargeable_amount',
        'remarks',
        'generated_by',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'total_chargeable_amount' => 'decimal:2',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Riders::class, 'rider_id');
    }

    public function generatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RiderInventoryAssignment::class, 'return_contract_number', 'contract_number');
    }

    public static function nextContractNumber(string $type): string
    {
        $prefix = $type === self::TYPE_ASSIGNMENT ? 'RIA' : 'RIR';
        $year = date('Y');
        $count = static::query()
            ->where('contract_type', $type)
            ->whereYear('contract_date', $year)
            ->count() + 1;

        return sprintf('%s-%05d-%s', $prefix, $count, $year);
    }
}
