<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;

class Accounts extends Model
{
  use LogsActivity, HasActiveStatus, SoftDeletes;

  public $table = 'accounts';

  public $fillable = [
    'account_code',
    'name',
    'account_type',
    'parent_id',
    'ref_name',
    'ref_id',
    'status',
    'notes',
    'opening_balance',
    'is_locked'
  ];

  protected $casts = [
    'account_code' => 'string',
    'name' => 'string',
    'account_type' => 'string',
    'opening_balance' => 'decimal:2',
    'is_locked' => 'boolean',
  ];

  protected $dates = ['deleted_at']; // Added for SoftDeletes

  public static array $rules = [

    'account_code' => 'nullable|string|max:100',
    'name' => 'required|string|max:100',
    'account_type' => 'required|string|max:50',
    'parent_id' => 'nullable',
    'opening_balance' => 'nullable|numeric'

  ];

  public function ledgerEntries()
  {
    return $this->hasMany(LedgerEntry::class);
  }

  public function transactions()
  {
    return $this->hasMany(Transactions::class);
  }

  public function parent()
  {
    return $this->belongsTo(self::class, 'parent_id');
  }
  public function children()
  {
    return $this->hasMany(self::class, 'parent_id')->with('children'); // Recursive relationship
  }
  public function visa_expenses()
  {
    return $this->hasMany(visa_expenses::class, 'rider_id', 'id');
  }

  public function salikEntries()
  {
    return $this->hasMany(\App\Models\salik::class, 'account_id', 'id');
  }

  public static function dropdown($parent_id)
  {
    if ($parent_id) {
      $query = self::select('id', \DB::raw("CONCAT(account_code, '-', name) as full_name"))->where('parent_id', $parent_id)->pluck('full_name', 'id')->prepend('Select', '');
    } else {
      $query = self::select('id', \DB::raw("CONCAT(account_code, '-', name) as full_name"))->whereNotNull('parent_id')->pluck('full_name', 'id')->prepend('Select', '');
    }
    //return self::select('id', 'plate')->pluck('plate', 'id')->prepend('Select', '');
    return $query;
  }

  public static function customDropdown($accountIds)
  {

    $query = self::select('id', \DB::raw("CONCAT(account_code, '-', name) as full_name"))->whereIn('id', $accountIds)->pluck('full_name', 'id');


    return $query;
  }

  public static function bankAccountsDropdown()
  {
    return self::select('id', \DB::raw("CONCAT(account_code, '-', name) as full_name"))
      ->where('account_type', 'Asset')
      ->whereIn('parent_id', [994, 1643])
      ->pluck('full_name', 'id')
      ->prepend('Select', '');
  }
}
