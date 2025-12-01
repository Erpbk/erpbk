<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class LeasingCompanies extends Model
{
    use LogsActivity;

  public $table = 'leasing_companies';

  public $fillable = [
    'name',
    'contact_person',
    'contact_number',
    'detail',
    'account_id',
    'status'
  ];

  protected $casts = [
    'name' => 'string',
    'contact_person' => 'string',
    'contact_number' => 'string',
    'detail' => 'string'

  ];

  public static array $rules = [
    'name' => 'nullable|string|max:255',
    'contact_person' => 'nullable|string|max:255',
    'contact_number' => 'nullable|string|max:100',
    'detail' => 'nullable|string|max:65535',

    'created_at' => 'nullable',
    'updated_at' => 'nullable'
  ];


  public static function dropdown()
  {
    return self::select('id', 'name')->pluck('name', 'id')->prepend('Select', '');
  }
  function account()
  {
    return $this->hasOne(Accounts::class, 'id', 'account_id');
  }

  function transactions()
  {
    return $this->hasMany(Transactions::class, 'account_id', 'account_id');
  }
}
