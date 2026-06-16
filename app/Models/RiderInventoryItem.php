<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiderInventoryItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'item_price',
        'is_active',
        'display_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'item_price' => 'decimal:2',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(RiderInventoryAssignment::class, 'inventory_item_id');
    }

    public static function getActive()
    {
        return self::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public static function availableForAssignment()
    {
        return Items::availableForAssignment();
    }
}
