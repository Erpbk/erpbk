<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class BikeHistory extends Model
{
    use LogsActivity;

  public $table = 'bike_histories';

  public $fillable = [
    'bike_id',
    'rider_id',
    'notes',
    'note_date',
    'return_date',
    'created_by',
    'updated_by',
    'warehouse',
    'contract',
    'customer_id'
  ];

  protected $casts = [
    'notes' => 'string',
    'note_date' => 'date',
    'return_date' => 'date',
    'warehouse' => 'string',
    'created_by' => 'string',
    'updated_by' => 'string',
    'contract' => 'string'
  ];

  public static array $rules = [
    'bike_id' => 'required',
    'rider_id' => 'nullable',
    'notes' => 'nullable|string|max:65535',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'note_date' => 'nullable',
    'return_date' => 'nullable',
    'created_by' => 'nullable',
    'updated_by' => 'nullable',
    'warehouse' => 'nullable|string|max:50',
    'contract' => 'nullable|string|max:255'
  ];

  public function rider()
  {
    return $this->belongsTo(Riders::class, 'rider_id', 'id');
  }
  public function bike()
  {
    return $this->belongsTo(Bikes::class, 'bike_id', 'id');
  }
}
