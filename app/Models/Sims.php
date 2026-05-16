<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class Sims extends BaseModel
{
  use SoftDeletes, LogsActivity, BranchScope;

  public $table = 'sims';

  public $fillable = [
    'branch_id',
    'number',
    'company',
    'assign_to',
    'assign_type',
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
    'number' => 'nullable|string|min:10|max:13|unique:sims,number',
    'company' => 'nullable|string|max:191',
    'branch_id' => 'nullable|numeric|exists:branches,id',
    'assign_to' => 'nullable',
    'created_by' => 'nullable',
    'updated_by' => 'nullable',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'deleted_at' => 'nullable',
    'fleet_supervisor' => 'nullable|string|max:50',
    'emi' => 'nullable|string|min:15|max:25',
    'vendor' => 'nullable|integer',
  ];

  public function histories()
  {
    return $this->hasMany(SimHistory::class, 'sim_id', 'id');
  }

  public function riders()
  {
    return $this->belongsTo(Riders::class, 'assign_to', 'id');
  }

  public function employee()
  {
    return $this->belongsTo(Employee::class, 'assign_to', 'id');
  }

  public function assignee()
  {
    return $this->assign_type === 'employee' ? $this->employee() : $this->riders();
  }

  public function vendors()
  {
    return $this->hasOne(Vendors::class, 'id', 'vendor');
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class, 'branch_id', 'id');
  }
}
