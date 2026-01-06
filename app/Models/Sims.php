<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Sims extends Model
{
    use SoftDeletes, LogsActivity;

  public $table = 'sims';

  public $fillable = [
    'number',
    'company',
    'assign_to',
    'created_by',
    'updated_by',
    'deleted_at',
    'fleet_supervisor',
    'status',
    'emi',
    'vendor'
  ];

  protected $casts = [
    'number' => 'string',
    'company' => 'string',
    'fleet_supervisor' => 'string',
    'emi' => 'string',
    'vendor' => 'string',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime'
  ];

  protected $dates = ['deleted_at'];

  public static array $rules = [
    'number' => 'required|string|max:191',
    'company' => 'required|string|max:191',
    'assign_to' => 'nullable',
    'created_by' => 'nullable',
    'updated_by' => 'nullable',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'deleted_at' => 'nullable',
    'fleet_supervisor' => 'nullable|string|max:50',
    'emi' => 'nullable|string|max:100',
    'vendor' => 'nullable'
  ];

  public function histories()
  {
      return $this->hasMany(SimHistory::class, 'sim_id', 'id');
  }

  public function riders()
  {
    return $this->belongsTo(Riders::class, 'assign_to', 'id');
  }

  public function vendors()
  {
    return $this->hasOne(Vendors::class, 'id', 'vendor');
  }
}
