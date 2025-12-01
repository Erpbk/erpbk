<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class RiderActivities extends Model
{
    use LogsActivity;

  public $table = 'rider_activities';

  public $fillable = [
    'rider_id',
    'd_rider_id',
    'payout_type',
    'delivered_orders',
    'ontime_orders',
    'ontime_orders_percentage',
    'avg_time',
    'rejected_orders',
    'rejected_orders_percentage',
    'login_hr',
    'date',
    'delivery_rating'
  ];

  protected $casts = [
    'payout_type' => 'string',
    'ontime_orders_percentage' => 'decimal:2',
    'avg_time' => 'decimal:2',
    'rejected_orders_percentage' => 'decimal:2',
    'login_hr' => 'decimal:2',
    //'date' => 'date'
  ];

  public static array $rules = [
    'rider_id' => 'nullable',
    'd_rider_id' => 'nullable',
    'payout_type' => 'nullable|string|max:20',
    'delivered_orders' => 'nullable',
    'ontime_orders' => 'nullable',
    'ontime_orders_percentage' => 'nullable|numeric',
    'avg_time' => 'nullable|numeric',
    'rejected_orders' => 'nullable',
    'rejected_orders_percentage' => 'nullable|numeric',
    'login_hr' => 'nullable|numeric',
    'date' => 'nullable',
    'created_at' => 'nullable',
    'updated_at' => 'nullable'
  ];

  public function rider()
  {
    //return $this->belongsTo(Riders::class);
    return $this->hasOne(Riders::class, 'id', 'rider_id');
  }
}
