<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BikeMaintenance extends Model
{
    use HasFactory;
    public $table = 'bike_maintenances';

    public $fillable = [
        'bike_id',
        'rider_id',
        'maintenance_date',
        'description',
        'current_km',
        'previous_km',
        'maintenance_at',
        'overdue_km',
        'overdue_cost_per_km',
        'total_cost',
        'overdue_paidby',
        'paidby',
        'billing_month',
        'attachment',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $dates = [ 'delete_at'];

    protected $casts = [
        'maintenance_date' => 'datetime',
        'billing_month' =>'dateeime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function bike(){
        return $this->belongsTo(Bikes::class,'bike_id','id');
    }

    public function rider(){
        return $this->hasOne(Riders::class, 'rider_id', 'id');
    }

    public function maintenanceItems(){
        return $this->hasMany(BikeMaintenanceItem::class,'bike_maintenance_id', 'id');
    }

    public function CreatedBy(){
        return $this->belongsTo(User::class,'created_by','id');
    }
}
