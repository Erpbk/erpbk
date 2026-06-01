<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BikeMaintenanceItem extends BaseModel
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
        'branch_id',
        'company_id',
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

    public function bikeMaintenance()
    {
        return $this->belongsTo(BikeMaintenance::class, 'bike_maintenance_id');
    }
}
