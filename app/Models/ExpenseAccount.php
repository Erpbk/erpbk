<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseAccount extends BaseModel
{
    public const MODULE_VISA = 'visa';
    public const MODULE_LICENSE = 'license';

    public $table = 'expense_accounts';

    public $fillable = [
        'account_id',
        'name',
        'rider_id',
        'employee_id',
        'module',
        'renewal_category_id',
        'company_id',
    ];

    protected $attributes = [
        'module' => self::MODULE_VISA,
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'account_id');
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Riders::class, 'rider_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function isEmployeeAccount(): bool
    {
        return $this->employee_id !== null;
    }

    public function renewalCategory(): BelongsTo
    {
        return $this->belongsTo(VisaRenewalCategory::class, 'renewal_category_id');
    }

    public function visaExpenses(): HasMany
    {
        return $this->hasMany(visa_expenses::class, 'expense_account_id');
    }

    /**
     * Installment plan rows for this renewal account.
     * visa_installment_plans.rider_id stores expense_accounts.id for renewal-scoped plans.
     */
    public function installmentPlans(): HasMany
    {
        return $this->hasMany(visa_installment_plan::class, 'rider_id', 'id');
    }

    public function licenseExpenses(): HasMany
    {
        return $this->hasMany(license_expenses::class, 'expense_account_id');
    }

    public function licenseInstallmentPlans(): HasMany
    {
        return $this->hasMany(license_installment_plan::class, 'rider_id', 'id');
    }

    public function scopeVisa(Builder $query): Builder
    {
        return $query->where('module', self::MODULE_VISA);
    }

    public function scopeLicense(Builder $query): Builder
    {
        return $query->where('module', self::MODULE_LICENSE);
    }

    public function isVisaModule(): bool
    {
        return ($this->module ?? self::MODULE_VISA) === self::MODULE_VISA;
    }

    public function isLicenseModule(): bool
    {
        return ($this->module ?? '') === self::MODULE_LICENSE;
    }
}
