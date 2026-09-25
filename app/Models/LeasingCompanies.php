<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class LeasingCompanies extends BaseModel
{
  use LogsActivity, SoftDeletes, BranchScope;

  public $table = 'leasing_companies';

  public $fillable = [
    'branch_id',
    'name',
    'contact_person',
    'contact_number',
    'trn_number',
    'detail',
    'account_id',
    'status'
  ];

  protected $casts = [
    'name' => 'string',
    'contact_person' => 'string',
    'contact_number' => 'string',
    'trn_number' => 'string',
    'detail' => 'string'

  ];

  protected $dates = ['deleted_at'];

  public static array $rules = [
    'name' => 'nullable|string|max:255',
    'contact_person' => 'nullable|string|max:255',
    'contact_number' => 'nullable|string|max:100',
    'trn_number' => 'nullable|string|max:100',
    'detail' => 'nullable|string|max:65535',

    'created_at' => 'nullable',
    'updated_at' => 'nullable'
  ];


  public static function dropdown()
  {
    return self::select('id', 'name')->pluck('name', 'id')->prepend('Select', '');
  }

  /**
   * Company select for bike forms: "own" (tenant company) plus leasing companies.
   *
   * @return array<string|int, string>
   */
  public static function dropdownWithOwnOption(): array
  {
    $opts = self::select('id', 'name')->pluck('name', 'id')->toArray();

    return ['' => 'Select', self::OWN_OPTION_VALUE => self::ownOptionLabel()] + $opts;
  }

  public const OWN_OPTION_VALUE = 'own';

  /**
   * Display label for the top-bar / form "own vehicles" option (tenant company name).
   */
  public static function ownOptionLabel(): string
  {
    $currentCompany = view()->shared('currentCompany');
    $companyName = trim((string) (
      \App\Helpers\Common::getSetting('company_name')
      ?: (is_object($currentCompany) ? ($currentCompany->name ?? '') : '')
      ?: 'Own'
    ));

    return $companyName !== '' ? $companyName : 'Own';
  }

  public static function isOwnOptionValue(mixed $value): bool
  {
    return strtolower(trim((string) $value)) === self::OWN_OPTION_VALUE;
  }

  function account()
  {
    return $this->hasOne(Accounts::class, 'id', 'account_id');
  }

  function transactions()
  {
    return $this->hasMany(Transactions::class, 'account_id', 'account_id');
  }

  function bikes()
  {
    return $this->hasMany(Bikes::class, 'company', 'id');
  }

  function vouchers()
  {
    return $this->hasMany(Vouchers::class, 'lease_company', 'id');
  }
}
