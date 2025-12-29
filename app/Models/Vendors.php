<?php

namespace App\Models;

use App\Helpers\IConstants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;

class Vendors extends Model
{
  use LogsActivity, HasActiveStatus, SoftDeletes;

  public $table = 'vendors';

  public $fillable = [
    'name',
    'email',
    'contact_number',
    'address',
    'tax_number',
    'status',
    'account_id',
    'created_by',
    'updated_by'
  ];

  protected $casts = [
    'name' => 'string',
    'email' => 'string',
    'contact_number' => 'string',
    'address' => 'string',
    'tax_number' => 'string',
    'status' => 'integer'
  ];

  protected $dates = ['deleted_at'];

  public static array $rules = [
    'name' => 'required|string|max:255',
    'email' => 'required|string|max:100',
    'contact_number' => 'required|string|max:100',
    'address' => 'nullable|string|max:200',
    'tax_number' => 'nullable|string|max:100',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'account_id' => 'nullable',
    'created_by' => 'nullable',
    'updated_by' => 'nullable'
  ];
  function account()
  {
    return $this->hasOne(Accounts::class, 'id', 'account_id');
  }

  function transactions()
  {
    return $this->hasMany(Transactions::class, 'account_id', 'account_id');
  }

  public static function dropdown()
  {
    return self::select('id', 'name')->where('status', IConstants::ACTIVE)->pluck('name', 'id')->prepend('Select', '');
  }
}
