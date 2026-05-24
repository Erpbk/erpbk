<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BikeRegistrationStatus extends BaseModel
{
    use HasFactory;

    protected $table = 'bike_registration_statuses';

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

    public static function getActive()
    {
        return self::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }
}
