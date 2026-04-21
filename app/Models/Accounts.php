<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;
use App\Traits\BranchScope;

class Accounts extends BaseModel
{
  use LogsActivity, HasActiveStatus, SoftDeletes, BranchScope;

  protected static function booted(): void
  {
    static::saving(function (self $account): void {
      // Parent/root accounts are shared globally for all companies.
      if (empty($account->parent_id) || (int) $account->parent_id === 0) {
        $account->company_id = null;
      }
    });
  }

  public $table = 'accounts';

  public $fillable = [
    'branch_id',
    'account_code',
    'name',
    'account_type',
    'parent_id',
    'ref_name',
    'ref_id',
    'status',
    'notes',
    'opening_balance',
    'is_locked',
    'custom_field_values'
  ];

  protected $casts = [
    'account_code' => 'string',
    'name' => 'string',
    'account_type' => 'string',
    'opening_balance' => 'decimal:2',
    'is_locked' => 'boolean',
    'custom_field_values' => 'array',
  ];

  protected $dates = ['deleted_at']; // Added for SoftDeletes

  public static array $rules = [

    'account_code' => 'nullable|string|max:100',
    'name' => 'required|string|max:100',
    'account_type' => 'required|string|max:50',
    'parent_id' => 'nullable',
    'opening_balance' => 'nullable|numeric'

  ];

  /**
   * Chart of Accounts must show shared main heads (company_id NULL) for every tenant,
   * along with tenant-owned accounts.
   */
  protected function includesGlobalCompanyRows(): bool
  {
    return true;
  }

  /**
   * Main parent heads are shared; all non-parent accounts are company-isolated.
   */
  protected function applyCompanyScopeConstraint(Builder $builder, int $companyId): void
  {
    $qualifiedCompany = $this->qualifyColumn('company_id');
    $qualifiedParent = $this->qualifyColumn('parent_id');

    $builder->where(function (Builder $query) use ($qualifiedCompany, $qualifiedParent, $companyId): void {
      $query
        // Shared main heads for all companies
        ->where(function (Builder $rootQuery) use ($qualifiedParent): void {
          $rootQuery->whereNull($qualifiedParent)->orWhere($qualifiedParent, 0);
        })
        // OR tenant-owned non-root accounts only
        ->orWhere(function (Builder $tenantQuery) use ($qualifiedParent, $qualifiedCompany, $companyId): void {
          $tenantQuery
            ->where($qualifiedParent, '!=', 0)
            ->whereNotNull($qualifiedParent)
            ->where($qualifiedCompany, $companyId);
        });
    });
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class, 'branch_id' , 'id');
  }

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

  public function expenseAccount()
  {
    return $this->hasOne(ExpenseAccount::class, 'account_id', 'id');
  }

  /**
   * Dropdown of accounts for selects (e.g. vouchers, ledger).
   * When $parent_id is null, returns all accounts (same set as Chart of Accounts), ordered by account_code.
   * When $parent_id is set, returns only direct children of that parent.
   */
  public static function dropdown($parent_id)
  {
    if ($parent_id) {
      $query = self::select('id', \DB::raw("CONCAT(account_code, '-', name) as full_name"))->where('parent_id', $parent_id)->orderBy('account_code')->pluck('full_name', 'id')->prepend('Select', '');
    } else {
      $query = self::select('id', \DB::raw("CONCAT(account_code, '-', name) as full_name"))->orderBy('account_code')->pluck('full_name', 'id')->prepend('Select', '');
    }
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

  /**
   * Dropdown for Bank and Cash accounts (for voucher credit field).
   * Returns accounts under Bank (994) and Cash (1643) parent accounts.
   */
  public static function bankAndCashDropdown()
  {
    return self::select('id', \DB::raw("CONCAT(account_code, '-', name) as full_name"))
      ->where('account_type', 'Asset')
      ->whereIn('parent_id', [994, 1643])
      ->orderBy('account_code')
      ->pluck('full_name', 'id')
      ->prepend('Select', '');
  }

  public function getBranchNameAttribute()
  {
    $branch = $this->branch_id ? $this->branch->name .' ( '. $this->branch->code .' )' : 'All' ; 
    return $branch;
  }
}
