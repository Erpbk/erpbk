<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;
use App\Traits\BranchScope;

class Banks extends BaseModel
{
  use SoftDeletes, LogsActivity, HasActiveStatus, BranchScope;

  public $table = 'banks';

  public $fillable = [
    'branch_id',
    'name',
    'title',
    'account_no',
    'iban',
    'swift',
    'branch',
    'account_type',
    'balance',
    'status',
    'account_id',
    'notes'
  ];

  protected $casts = [
    'name' => 'string',
    'title' => 'string',
    'account_no' => 'string',
    'iban' => 'string',
    'swift' => 'string',
    'branch' => 'string',
    'account_type' => 'string',
    'balance' => 'decimal:2',
    'status' => 'integer',
    'notes' => 'string',
    'deleted_at' => 'datetime'
  ];

  /**
   * The attributes that should be included in the model's array form.
   *
   * @var array
   */
  protected $dates = ['deleted_at'];

  public static array $rules = [
    'name' => 'string|max:255',
    'title' => 'string|max:255',
    'account_no' => 'string|max:255',
    'iban' => 'string|max:255',
    'swift' => 'string|max:255',
    'branch' => 'string|max:255',
    'account_type' => 'string|max:100',
    'balance' => 'numeric',
    'notes' => 'string|max:255',
  ];

  function account()
  {
    return $this->hasOne(Accounts::class, 'id', 'account_id');
  }

  function transactions()
  {
    return $this->hasMany(Transactions::class, 'account_id', 'account_id');
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class, 'branch_id', 'id');
  }

  public function getBranchNameAttribute(): string
  {
    if (!$this->branch_id) {
      return 'All';
    }

    // `branch` is also a string column on banks — use the relationship, not the attribute.
    $branchModel = $this->getRelationValue('branch');

    if (!$branchModel) {
      return 'All';
    }

    return $branchModel->name . ' ( ' . $branchModel->code . ' )';
  }
}
