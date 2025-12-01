<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class RiderAttendance extends Model
{
    use LogsActivity;

  public $table = 'rider_attendance';

  public $fillable = [
    'rider_id',
    'd_rider_id',
    'date',
    'shift',
    'attendance',
    'cdm_id',
    'day'
  ];

  protected $casts = [
    'date' => 'date',
    'shift' => 'string',
    'attendance' => 'string',
    'day' => 'string'
  ];

  public static array $rules = [
    'rider_id' => 'nullable',
    'd_rider_id' => 'nullable',
    'date' => 'nullable',
    'shift' => 'nullable|string|max:50',
    'attendance' => 'nullable|string|max:50',
    'cdm_id' => 'nullable',
    'day' => 'nullable|string|max:20',
    'created_at' => 'nullable',
    'updated_at' => 'nullable'
  ];

  public function rider()
  {
    //return $this->belongsTo(Riders::class);
    return $this->hasOne(Riders::class, 'id', 'rider_id');
  }
}
