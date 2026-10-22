<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;
use App\Traits\BranchScope;

class Accounts extends BaseModel
{
  use LogsActivity, HasActiveStatus, SoftDeletes, BranchScope;

  protected static function boot()
  {
    parent::boot();

    static::saving(function (Accounts $account): void {
      if (!$account->shouldApplyCompanyScope() || !$account->hasCompanyColumn()) {
        return;
      }

      if ($account->is_fixed) {
        $account->company_id = null;

        return;
      }

      if ($account->company_id === null || $account->company_id === '') {
        $scopedCompanyId = $account->resolveScopedCompanyId();
        if ($scopedCompanyId !== null) {
          $account->company_id = $scopedCompanyId;
        }
      }
    });
  }

  /**
   * Fixed shared accounts (company_id NULL) plus this company's own accounts.
   */
  protected function applyCompanyScopeConstraint(Builder $builder, int $companyId): void
  {
    \App\Support\AccountsCompanyScope::apply($builder, $companyId, $this->getTable());
  }

  public $table = 'accounts';

  public $fillable = [
    'branch_id',
    'company_id',
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
    'is_fixed',
    'custom_field_values'
  ];

  protected $casts = [
    'account_code' => 'string',
    'name' => 'string',
    'account_type' => 'string',
    'opening_balance' => 'decimal:2',
    'is_locked' => 'boolean',
    'is_fixed' => 'boolean',
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

  public function branch()
  {
    return $this->belongsTo(Branch::class, 'branch_id', 'id');
  }

  public function ledgerEntries()
  {
    return $this->hasMany(LedgerEntry::class);
  }

  public function transactions()
  {
    return $this->hasMany(Transactions::class);
  }

  public function globalAccount(): HasOne
  {
    return $this->hasOne(GlobalAccount::class, 'account_id');
  }

  public function isLinkedToGlobalAccount(): bool
  {
    if ($this->relationLoaded('globalAccount')) {
      return $this->globalAccount !== null;
    }

    return GlobalAccount::query()->where('account_id', $this->id)->exists();
  }

  public function isLocked(): bool
  {
    return (bool) $this->is_locked;
  }

  public function parent()
  {
    return $this->belongsTo(self::class, 'parent_id');
  }

  public function children()
  {
    return $this->hasMany(self::class, 'parent_id')->with('children'); // Recursive relationship
  }

  /**
   * Direct children only (no recursive eager load) — used by leafOnly scope.
   */
  public function directChildren()
  {
    return $this->hasMany(self::class, 'parent_id');
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
   * Accounts with no children (postable / selectable in ledgers and vouchers).
   */
  public function scopeLeafOnly(Builder $query): Builder
  {
    return $query->whereDoesntHave('directChildren');
  }

  public function hasChildren(): bool
  {
    if ($this->relationLoaded('directChildren')) {
      return $this->directChildren->isNotEmpty();
    }

    if ($this->relationLoaded('children')) {
      return $this->children->isNotEmpty();
    }

    return $this->directChildren()->exists();
  }

  public function isParent(): bool
  {
    return $this->hasChildren();
  }

  /**
   * Dropdown of accounts for selects (e.g. vouchers, ledger).
   * By default returns leaf accounts only (no children).
   * When $parent_id is set, returns leaf children of that parent.
   * Pass $leafOnly = false for Chart of Accounts parent pickers.
   *
   * @param  mixed  $parent_id
   * @param  bool  $leafOnly
   * @param  array<int, int|string>  $includeIds  Always include these ids (edit-form legacy)
   */
  public static function dropdown($parent_id = null, bool $leafOnly = true, array $includeIds = [])
  {
    $query = self::select('id', \DB::raw("CONCAT(account_code, '-', name) as full_name"))
      ->orderBy('account_code');

    if ($parent_id) {
      $query->where('parent_id', $parent_id);
    }

    if ($leafOnly) {
      $includeIds = array_values(array_filter(array_map('intval', $includeIds)));
      if ($includeIds !== []) {
        $query->where(function ($q) use ($includeIds) {
          $q->leafOnly()->orWhereIn('id', $includeIds);
        });
      } else {
        $query->leafOnly();
      }
    }

    return $query->pluck('full_name', 'id')->prepend('Select', '');
  }

  public static function customDropdown($accountIds)
  {
    return self::select('id', \DB::raw("CONCAT(account_code, '-', name) as full_name"))
      ->whereIn('id', $accountIds)
      ->leafOnly()
      ->pluck('full_name', 'id');
  }

  public static function bankAccountsDropdown()
  {
    return self::select('id', \DB::raw("CONCAT(account_code, '-', name) as full_name"))
      ->where('account_type', 'Asset')
      ->where('parent_id', \App\Support\GlobalAccounts::id('BANK'))
      ->leafOnly()
      ->pluck('full_name', 'id')
      ->prepend('Select', '');
  }

  /**
   * Dropdown for Bank and Cash accounts (for voucher credit field).
   * Returns leaf accounts under the Bank parent.
   */
  public static function bankAndCashDropdown()
  {
    return self::select('id', \DB::raw("CONCAT(account_code, '-', name) as full_name"))
      ->where('account_type', 'Asset')
      ->where('parent_id', \App\Support\GlobalAccounts::id('BANK'))
      ->leafOnly()
      ->pluck('full_name', 'id')
      ->prepend('Select', '');
  }

  public function getBranchNameAttribute()
  {
    $branch = $this->branch? $this->branch->name . ' (' . $this->branch->code . ')' : 'All';
    return $branch;
  }
}
