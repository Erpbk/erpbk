<?php

namespace App\Support;

class ErpModuleRegistry
{
    /**
     * Modules that use a dedicated settings page with their own top-bar tab (Rider Top, Vehicle top, etc.).
     *
     * @return list<string>
     */
    public static function dedicatedTopBarSettingsModules(): array
    {
        return [
            'riders',
            'rider_settings',
            'cheques',
            'cheques_settings',
            'employees',
            'employee_settings',
            'bike_list',
            'bike_settings',
        ];
    }

    /**
     * Module-settings pages that already define a different top-bar UI on the same blade.
     *
     * @return list<string>
     */
    public static function moduleSettingsWithAlternateTopBarTab(): array
    {
        return [
            'visa_expense',
            'legal_case',
            'passport_handover',
            'bike_registration',
        ];
    }

    public static function topBarModules(): array
    {
        return config('top_bar_filters.modules', []);
    }

    public static function hasTopBar(string $moduleKey): bool
    {
        return self::topBarConfig($moduleKey) !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function topBarConfig(string $moduleKey): ?array
    {
        $moduleKey = self::resolveTopBarModuleKey($moduleKey);
        $defaults = [
            'module_key' => $moduleKey,
            'storage' => 'generic',
            'filter_strategy' => 'column',
            'request' => [
                'option_id' => 'top_option_id',
                'filter_mode' => 'top_filter_mode',
                'date_from' => 'top_date_from',
                'date_to' => 'top_date_to',
            ],
        ];

        $explicit = self::topBarModules()[$moduleKey] ?? null;
        if ($explicit !== null) {
            $config = array_merge($defaults, $explicit, ['module_key' => $moduleKey]);

            return self::applyNumericStatusDefaults($config);
        }

        if (in_array($moduleKey, self::dedicatedTopBarSettingsModules(), true)) {
            return null;
        }

        $table = ModuleFieldSource::resolveSourceTable($moduleKey);
        if ($table === null) {
            return null;
        }

        $config = array_merge($defaults, [
            'source_table' => $table,
        ]);

        return self::applyNumericStatusDefaults($config);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected static function applyNumericStatusDefaults(array $config): array
    {
        $table = (string) ($config['source_table'] ?? '');
        $statusColumn = TopBarNumericStatus::resolveNumericStatusColumn($table);
        if ($statusColumn === null) {
            return $config;
        }

        $statusFilters = $config['status_filters'] ?? [];
        if (!TopBarNumericStatus::shouldUseNumericActiveInactive($statusFilters)) {
            return $config;
        }

        if (!TopBarNumericStatus::usesNumericActiveInactive($statusFilters)) {
            $config['status_filters'] = TopBarNumericStatus::statusFilterRules($statusColumn);
            $config['listing_default_statuses'] = $config['listing_default_statuses']
                ?? [TopBarNumericStatus::ACTIVE_KEY, TopBarNumericStatus::INACTIVE_KEY];
            $config['listing_stats'] = $config['listing_stats'] ?? TopBarNumericStatus::listingStatDefinitions();
            $config['numeric_status_column'] = $statusColumn;
        }

        return $config;
    }

    /**
     * Whether this module uses the shared erp_module_top_* tables.
     */
    public static function usesGenericTopBarStorage(string $moduleKey): bool
    {
        $config = self::topBarConfig($moduleKey);

        return $config !== null && ($config['storage'] ?? 'generic') === 'generic';
    }

    /**
     * Show "{Module} Top" tab on module-settings (bike_settings) pages.
     */
    public static function showTopBarTabInModuleSettings(string $moduleKey): bool
    {
        $moduleKey = self::normalizeKey($moduleKey);

        if (in_array($moduleKey, self::moduleSettingsWithAlternateTopBarTab(), true)) {
            return false;
        }

        return self::usesGenericTopBarStorage($moduleKey);
    }

    public static function usesDedicatedTopBarStorage(string $moduleKey): bool
    {
        $config = self::topBarConfig($moduleKey);

        return $config !== null && ($config['storage'] ?? '') === 'dedicated';
    }

    /**
     * Tab label matching Rider Top pattern, e.g. "Customer Top".
     */
    public static function topBarTabLabel(string $moduleKey, ?string $moduleLabel = null): string
    {
        $moduleKey = self::normalizeKey($moduleKey);
        $label = trim((string) $moduleLabel);

        if ($label === '') {
            $label = config('menu_labels.defaults.' . $moduleKey, ucwords(str_replace('_', ' ', $moduleKey)));
        }

        $singular = preg_replace('/\s+(list|settings)$/i', '', $label) ?: $label;

        return rtrim($singular) . ' Top';
    }

    /**
     * @return list<string>
     */
    public static function menuLabelAliases(string $menuKey): array
    {
        $menuKey = self::normalizeKey($menuKey);
        $aliases = config('top_bar_filters.menu_label_aliases', []);
        $keys = [$menuKey];
        $related = $aliases[$menuKey] ?? [];

        foreach ($related as $alias) {
            $keys[] = self::normalizeKey($alias);
        }

        return array_values(array_unique($keys));
    }

    public static function erpModules(): array
    {
        return config('erp_modules.modules', []);
    }

    public static function normalizeKey(string $key): string
    {
        return str_replace('-', '_', strtolower(trim($key)));
    }

    /**
     * Map legacy/shared keys to the top-bar module used on listings and in settings.
     */
    public static function resolveTopBarModuleKey(string $moduleKey): string
    {
        $moduleKey = self::normalizeKey($moduleKey);

        return match ($moduleKey) {
            'rta_fines' => 'rta_fines_unpaid',
            default => $moduleKey,
        };
    }

    /**
     * Module key stored on field/category settings (shared across RTA fine sub-modules).
     */
    public static function settingsFieldsModuleKey(string $moduleKey): string
    {
        $moduleKey = self::normalizeKey($moduleKey);

        return match ($moduleKey) {
            'rta_fines_unpaid', 'rta_fines_paid' => 'rta_fines',
            default => $moduleKey,
        };
    }

    public static function isRtaFinesTopBarModule(string $moduleKey): bool
    {
        $moduleKey = self::normalizeKey($moduleKey);

        return in_array($moduleKey, ['rta_fines_unpaid', 'rta_fines_paid'], true);
    }
}
