<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseCategory extends BaseModel
{
    protected $fillable = [
        'name',
        'display_order',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function licenseExpenses(): HasMany
    {
        return $this->hasMany(license_expenses::class, 'license_category_id');
    }

    public function licenseStatuses(): HasMany
    {
        return $this->hasMany(LicenseStatus::class, 'license_category_id');
    }

    public function expenseAccounts(): HasMany
    {
        return $this->hasMany(ExpenseAccount::class, 'license_category_id');
    }
}
