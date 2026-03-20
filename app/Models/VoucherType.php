<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherType extends Model
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
        return $this->hasMany(VoucherTypeModuleAssignment::class, 'voucher_type_id');
    }

    public function getModuleKeysAttribute(): array
    {
        $moduleKeys = $this->relationLoaded('moduleAssignments')
            ? $this->moduleAssignments->pluck('module_key')->all()
            : $this->moduleAssignments()->pluck('module_key')->all();

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
     * Sync module assignments with optional per-module can_edit and can_delete flags.
     *
     * @param array $moduleKeys List of module_key to assign
     * @param array $canEditByModule [module_key => bool]
     * @param array $canDeleteByModule [module_key => bool]
     */
    public function syncModules(array $moduleKeys, array $canEditByModule = [], array $canDeleteByModule = []): void
    {
        $allowedKeys = array_keys(static::availableModules());
        $moduleKeys = collect($moduleKeys)
            ->filter(fn($moduleKey) => is_string($moduleKey) && in_array($moduleKey, $allowedKeys, true))
            ->unique()
            ->values()
            ->all();

        if (empty($moduleKeys)) {
            $this->moduleAssignments()->delete();
            return;
        }

        $this->moduleAssignments()->whereNotIn('module_key', $moduleKeys)->delete();

        foreach ($moduleKeys as $moduleKey) {
            $canEdit = $canEditByModule[$moduleKey] ?? true;
            $canDelete = $canDeleteByModule[$moduleKey] ?? true;
            $assignment = $this->moduleAssignments()->firstOrNew(['module_key' => $moduleKey]);
            $assignment->can_edit = $canEdit;
            $assignment->can_delete = $canDelete;
            $assignment->save();
        }
    }

    /**
     * Whether this voucher type can be edited in the Vouchers module.
     * Separate from module assignment; only applies when type is assigned to vouchers.
     */
    public function getAllowEditInVoucherModuleAttribute(): bool
    {
        $a = $this->relationLoaded('moduleAssignments')
            ? $this->moduleAssignments->firstWhere('module_key', 'vouchers')
            : $this->moduleAssignments()->where('module_key', 'vouchers')->first();
        return $a ? (bool) $a->can_edit : false;
    }

    /**
     * Whether this voucher type can be deleted in the Vouchers module.
     * Separate from module assignment; only applies when type is assigned to vouchers.
     */
    public function getAllowDeleteInVoucherModuleAttribute(): bool
    {
        $a = $this->relationLoaded('moduleAssignments')
            ? $this->moduleAssignments->firstWhere('module_key', 'vouchers')
            : $this->moduleAssignments()->where('module_key', 'vouchers')->first();
        return $a ? (bool) $a->can_delete : false;
    }

    /**
     * For a given module, return edit/delete flags per voucher type code.
     * Used in views to show/hide Edit and Delete per voucher type in that module.
     *
     * @return array [voucher_type_code => ['can_edit' => bool, 'can_delete' => bool]]
     */
    public static function getEditDeleteFlagsByModule(string $moduleKey): array
    {
        $assignments = static::query()
            ->with('moduleAssignments')
            ->get()
            ->flatMap(function (VoucherType $type) use ($moduleKey) {
                $a = $type->moduleAssignments->firstWhere('module_key', $moduleKey);
                if (!$a) {
                    return [];
                }
                return [$type->code => [
                    'can_edit' => (bool) $a->can_edit,
                    'can_delete' => (bool) $a->can_delete,
                ]];
            });

        return $assignments->all();
    }

    public function scopeForModule(Builder $query, string $moduleKey): Builder
    {
        return $query->whereHas('moduleAssignments', function (Builder $builder) use ($moduleKey) {
            $builder->where('module_key', $moduleKey);
        });
    }

    /**
     * Get active types ordered for display (e.g. dropdown when creating a voucher).
     */
    public static function activeOrdered()
    {
        return static::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get all types as code => label map (for resolving any code to label).
     */
    public static function codeLabelMap(): array
    {
        return static::orderBy('display_order')->orderBy('id')
            ->pluck('label', 'code')
            ->toArray();
    }

    /**
     * Get active types as code => label map (for create voucher UI).
     */
    public static function activeCodeLabelMap(): array
    {
        return static::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->pluck('label', 'code')
            ->toArray();
    }

    public static function activeCodeLabelMapForModule(string $moduleKey): array
    {
        return static::where('is_active', true)
            ->forModule($moduleKey)
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
        return static::where('code', $code)
            ->where('is_active', true)
            ->forModule($moduleKey)
            ->exists();
    }
}
