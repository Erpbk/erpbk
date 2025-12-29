<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;

class Banks extends Model
{
  use SoftDeletes, LogsActivity, HasActiveStatus;

  public $table = 'banks';

  public $fillable = [
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
    'name' => 'required|string|max:255',
    'title' => 'required|string|max:255',
    'account_no' => 'required|string|max:255',
    'iban' => 'required|string|max:255',
    'swift' => 'nullable|string|max:255',
    'branch' => 'required|string|max:255',
    'account_type' => 'nullable|string|max:100',
    'balance' => 'nullable|numeric',

    'notes' => 'nullable|string|max:255',
    'created_at' => 'nullable',
    'updated_at' => 'nullable'
  ];

  function account()
  {
    return $this->hasOne(Accounts::class, 'id', 'account_id');
  }

  function transactions()
  {
    return $this->hasMany(Transactions::class, 'account_id', 'account_id');
  }
}
