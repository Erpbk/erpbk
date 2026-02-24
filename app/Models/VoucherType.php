<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherType extends Model
{
    protected $table = 'voucher_types';

    protected $fillable = [
        'code',
        'label',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get active types ordered for display (e.g. dropdown when creating a voucher).
     */
    public static function activeOrdered()
    {
        return static::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get all types as code => label map (for resolving any code to label).
     */
    public static function codeLabelMap(): array
    {
        return static::orderBy('display_order')->orderBy('id')
            ->pluck('label', 'code')
            ->toArray();
    }

    /**
     * Get active types as code => label map (for create voucher UI).
     */
    public static function activeCodeLabelMap(): array
    {
        return static::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->pluck('label', 'code')
            ->toArray();
    }
}
