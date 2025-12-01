<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;

class Customers extends Model
{
  use LogsActivity, HasActiveStatus;

  public $table = 'customers';

  public $fillable = [
    'name',
    'company_name',
    'company_email',
    'contact_number',
    'address',
    'tax_number',
    'status',
    'tax_percentage'
  ];

  protected $casts = [
    'name' => 'string',
    'company_name' => 'string',
    'company_email' => 'string',
    'contact_number' => 'string',
    'address' => 'string',
    'tax_number' => 'string',
    'tax_percentage' => 'decimal:2'
  ];

  public static array $rules = [
    'name' => 'required|string|max:255',
    'company_name' => 'nullable|string|max:255',
    'company_email' => 'nullable|string|max:100',
    'contact_number' => 'required|string|max:100',
    'address' => 'nullable|string|max:200',
    'tax_number' => 'required|string|max:100',

    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'tax_percentage' => 'required|numeric'
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
    $query = self::select('id', 'name')->pluck('name', 'id')->prepend('Select', '');
    return $query;
  }
}
