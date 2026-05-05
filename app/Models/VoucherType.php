<?php

namespace App\Models;

use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class VoucherType extends BaseModel
{
    protected $table = 'voucher_types';

    protected $fillable = [
        'code',
        'label',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function moduleAssignments(): HasMany
    {
        $relation = $this->hasMany(VoucherTypeModuleAssignment::class, 'voucher_type_id');
        $companyId = CompanyContext::id();
        $assignmentTable = (new VoucherTypeModuleAssignment())->getTable();
        if ($companyId && Schema::hasColumn($assignmentTable, 'company_id')) {
            $relation->where($assignmentTable . '.company_id', $companyId);
        }

        return $relation;
    }

    public function moduleAssignmentsAllCompanies(): HasMany
    {
        return $this->hasMany(VoucherTypeModuleAssignment::class, 'voucher_type_id')
            ->withoutGlobalScope('company');
    }

    protected static function queryForCurrentCompany(): Builder
    {
        $query = static::query();
        $companyId = CompanyContext::id();
        $table = (new static())->getTable();

        if ($companyId && Schema::hasColumn($table, 'company_id')) {
            $query->where($table . '.company_id', $companyId);
        }

        return $query;
    }

    public function getModuleKeysAttribute(): array
    {
        $moduleMap = static::defaultModuleAssignmentMap();
        $moduleKeys = $moduleMap[(string) $this->code] ?? [];

        return array_values(array_unique($moduleKeys));
    }

    public function getModuleLabelsAttribute(): array
    {
        $availableModules = static::availableModules();

        return collect($this->module_keys)
            ->map(fn($moduleKey) => $availableModules[$moduleKey] ?? $moduleKey)
            ->values()
            ->all();
    }

    public static function availableModules(): array
    {
        return collect(config('voucher_modules.modules', []))
            ->mapWithKeys(function ($defaultLabel, $moduleKey) {
                return [$moduleKey => Settings::getMenuLabel($moduleKey)];
            })
            ->all();
    }

    /**
     * Seeded/default voucher type -> module assignments used across the ERP.
     * These act as a fallback when assignment rows are missing.
     *
     * @return array<string, list<string>>
     */
    protected static function defaultModuleAssignmentMap(): array
    {
        return [
            'JV' => ['vouchers', 'rta_fines', 'visa_expense'],
            'LV' => ['vouchers', 'visa_expense'],
            'RFV' => ['vouchers', 'rta_fines'],
            'VL' => ['vouchers', 'visa_expense'],
            'AL' => ['riders', 'vouchers'],
            'SV' => ['vouchers', 'rta_saliks'],
            'COD' => ['riders', 'vouchers'],
            'PN' => ['riders', 'vouchers'],
            'INC' => ['riders', 'vouchers'],
            'PAY' => ['riders', 'vouchers'],
            'VC' => ['riders', 'vouchers'],
            'RI' => ['riders_list', 'invoices', 'vouchers'],
            'GV' => ['garage_items', 'vouchers'],
            'RV' => ['cash_banks', 'vouchers'],
            'PV' => ['cash_banks', 'cheques', 'vouchers'],
            'EXP' => ['expenses', 'vouchers'],
            'VP' => ['vat', 'vouchers'],
        ];
    }

    /**
     * Default voucher type codes that belong to a module.
     *
     * @return list<string>
     */
    protected static function defaultCodesForModule(string $moduleKey): array
    {
        $allowedModules = array_keys(static::availableModules());
        if (!in_array($moduleKey, $allowedModules, true)) {
            return [];
        }

        $codes = [];
        foreach (static::defaultModuleAssignmentMap() as $code => $moduleKeys) {
            if (in_array($moduleKey, $moduleKeys, true)) {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Sync module assignments with optional per-module can_edit and can_delete flags.
     *
     * @param array $moduleKeys List of module_key to assign
     * @param array $canEditByModule [module_key => bool]
     * @param array $canDeleteByModule [module_key => bool]
     */
    public function syncModules(array $moduleKeys, array $canEditByModule = [], array $canDeleteByModule = []): void
    {
        // Module mapping is fixed in code (defaultModuleAssignmentMap), so DB assignments are ignored.
    }

    /**
     * Whether this voucher type can be edited in the Vouchers module.
     * Separate from module assignment; only applies when type is assigned to vouchers.
     */
    public function getAllowEditInVoucherModuleAttribute(): bool
    {
        return in_array('vouchers', $this->module_keys, true);
    }

    /**
     * Whether this voucher type can be deleted in the Vouchers module.
     * Separate from module assignment; only applies when type is assigned to vouchers.
     */
    public function getAllowDeleteInVoucherModuleAttribute(): bool
    {
        return in_array('vouchers', $this->module_keys, true);
    }

    /**
     * For a given module, return edit/delete flags per voucher type code.
     * Used in views to show/hide Edit and Delete per voucher type in that module.
     *
     * @return array [voucher_type_code => ['can_edit' => bool, 'can_delete' => bool]]
     */
    public static function getEditDeleteFlagsByModule(string $moduleKey): array
    {
        $defaultCodes = static::defaultCodesForModule($moduleKey);
        $flags = [];
        foreach ($defaultCodes as $code) {
            $flags[$code] = [
                'can_edit' => true,
                'can_delete' => true,
            ];
        }

        return $flags;
    }

    public function scopeForModule(Builder $query, string $moduleKey): Builder
    {
        // Module assignment checks are disabled project-wide.
        return $query;
    }

    /**
     * Get active types ordered for display (e.g. dropdown when creating a voucher).
     */
    public static function activeOrdered()
    {
        return static::queryForCurrentCompany()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get all types as code => label map (for resolving any code to label).
     */
    public static function codeLabelMap(): array
    {
        return static::queryForCurrentCompany()
            ->orderBy('display_order')->orderBy('id')
            ->pluck('label', 'code')
            ->toArray();
    }

    /**
     * Get active types as code => label map (for create voucher UI).
     */
    public static function activeCodeLabelMap(): array
    {
        return static::queryForCurrentCompany()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->pluck('label', 'code')
            ->toArray();
    }

    public static function activeCodeLabelMapForModule(string $moduleKey): array
    {
        return static::queryForCurrentCompany()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->pluck('label', 'code')
            ->toArray();
    }

    /**
     * Get active types for create/filter UI in the given module.
     * Only includes types explicitly allowed to be edited in that module (can_edit = true).
     */
    public static function activeCodeLabelMapForModuleWithEditAccess(string $moduleKey): array
    {
        return static::queryForCurrentCompany()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->pluck('label', 'code')
            ->toArray();
    }

    /**
     * Check whether a voucher type code is assigned and active for the given module.
     * Use when a module creates a voucher with a fixed type to ensure it is allowed.
     */
    public static function isCodeAllowedForModule(string $code, string $moduleKey): bool
    {
        return static::queryForCurrentCompany()
            ->where('code', $code)
            ->where('is_active', true)
            ->exists();
    }
}
