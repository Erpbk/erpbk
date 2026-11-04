<?php

namespace App\Support\ExcelImport;

use InvalidArgumentException;

/**
 * Registry of Excel import mapping profiles. Add a module by registering a profile.
 */
class ExcelImportMappingRegistry
{
    public const MODULE_SIM_INVOICES = 'sim_invoices';

    /** @var array<string, ExcelImportModuleProfile>|null */
    private static ?array $profiles = null;

    /**
     * @return array<string, ExcelImportModuleProfile>
     */
    public static function all(): array
    {
        return self::$profiles ??= self::boot();
    }

    public static function get(string $moduleKey): ExcelImportModuleProfile
    {
        $profiles = self::all();
        if (! isset($profiles[$moduleKey])) {
            throw new InvalidArgumentException("Unknown Excel import mapping module [{$moduleKey}].");
        }

        return $profiles[$moduleKey];
    }

    public static function has(string $moduleKey): bool
    {
        return isset(self::all()[$moduleKey]);
    }

    /**
     * @return array<string, ExcelImportModuleProfile>
     */
    private static function boot(): array
    {
        $profiles = [
            self::MODULE_SIM_INVOICES => new ExcelImportModuleProfile(
                key: self::MODULE_SIM_INVOICES,
                label: 'SIM Invoice Import',
                fields: [
                    ['key' => 'sim_number', 'label' => 'SIM Number', 'required' => true],
                ],
                scope: [
                    'type' => 'sim_company',
                    'label' => 'SIM Company',
                ],
                dynamic: [
                    'enabled' => true,
                    'label' => 'Charge Columns',
                    'source' => 'sim_items',
                ],
                importType: 'default',
                defaultHeaderRowsToSkip: 1,
                permission: 'sims_invoices_view',
                maxColumnIndex: 60,
            ),
        ];

        return $profiles;
    }
}
