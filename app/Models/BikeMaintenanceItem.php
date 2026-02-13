<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BikeMaintenanceItem extends Model
{
    use HasFactory;
    protected $table = 'bike_maintenance_items';

    public $fillable = [
        'bike_maintenance_id',
        'item_id',
        'item_name',
        'quantity',
        'rate',
        'discount',
        'vat',
        'vat_amount',
        'total_amount',
        'charge_to',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];


}
