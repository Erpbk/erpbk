<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Sims extends Model
{
    use LogsActivity;

  public $table = 'sims';

  public $fillable = [
    'number',
    'company',
    'assign_to',
    'created_by',
    'updated_by',
    'fleet_supervisor',
    'status',
    'emi',
    'vendor'
  ];

  protected $casts = [
    'number' => 'string',
    'company' => 'string',
    'fleet_supervisor' => 'string',
    'emi' => 'string'
  ];

  public static array $rules = [
    'number' => 'required|string|max:191',
    'company' => 'required|string|max:191',
    'assign_to' => 'nullable',
    'created_by' => 'nullable',
    'updated_by' => 'nullable',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'fleet_supervisor' => 'nullable|string|max:50',
    'emi' => 'nullable|string|max:100',
    'vendor' => 'nullable'
  ];


}
