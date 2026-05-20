<?php

namespace App\Support;

use App\Models\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DashboardCardRegistry
{
    public static function maxVisibleCards(): int
    {
        return max(1, (int) config('dashboard_main_modules.max_visible_cards', 8));
    }

    /**
     * ERP module keys available for dashboard settings (checkboxes), excluding non-data modules.
     *
     * @return list<string>
     */
    public static function settingsSelectableKeys(): array
    {
        $erp = array_keys(config('erp_modules.modules', []));
        $exclude = config('dashboard_main_modules.exclude_from_settings', []);
        $keys = array_values(array_filter($erp, static fn ($k) => ! in_array($k, $exclude, true)));

        foreach (array_keys(config('dashboard_cards', [])) as $k) {
            if (! in_array($k, $keys, true)) {
                $keys[] = $k;
            }
        }

        sort($keys);

        return $keys;
    }

    /**
     * Merged definitions for settings + dashboard cards (label, icon, route, table, etc.).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        $defs = [];
        foreach (self::settingsSelectableKeys() as $key) {
            $defs[$key] = self::resolveDefinition($key);
        }

        uasort(
            $defs,
            static fn ($a, $b) => strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''))
        );

        return $defs;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function resolveDefinition(string $key): array
    {
        $auto = self::buildAutoDefinition($key);
        $over = config("dashboard_cards.$key", []);
        if (! is_array($over)) {
            return $auto;
        }

        return array_merge($auto, $over);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function buildAutoDefinition(string $key): array
    {
        $erpLabels = config('erp_modules.modules', []);
        $main = config('dashboard_main_modules', []);
        $icons = is_array($main['default_icons'] ?? null) ? $main['default_icons'] : [];
        $routes = is_array($main['index_routes'] ?? null) ? $main['index_routes'] : [];

        $label = $erpLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
        $icon = $icons[$key] ?? 'ti-layout-grid';
        $route = $routes[$key] ?? null;
        $table = ModuleFieldSource::resolveSourceTable($key);

        $strategy = null;
        $filterQuery = null;
        $statLabels = null;
        $statRoutes = null;
        $statusColumn = 'status';

        if ($table === 'sims') {
            $strategy = 'sim_char_status';
        } elseif ($table && TopBarNumericStatus::resolveNumericStatusColumn($table)) {
            $strategy = 'numeric_status';
        } else {
            $strategy = 'total_only';
        }

        // Only remap plain totals into richer strategies when `status` is non-numeric; keep numeric / SIM logic.
        if ($table && $strategy === 'total_only' && Schema::hasColumn($table, 'status')) {
            $strategy = self::refineCountStrategyForKey($key, $table, $strategy);
        }

        if ($strategy === 'sim_char_status') {
            $filterQuery = [
                'active' => ['list_status' => 'active'],
                'inactive' => ['list_status' => 'inactive'],
            ];
            $statLabels = [
                'active' => __('Active'),
                'inactive' => __('Inactive'),
            ];
        } elseif ($strategy === 'numeric_status') {
            if ($key === 'riders') {
                $filterQuery = [
                    'active' => ['rider_status' => 'active'],
                    'inactive' => ['rider_status' => 'inactive'],
                ];
            } elseif ($key === 'bikes') {
                $filterQuery = [
                    'active' => ['bike_top_wh' => 'active'],
                    'inactive' => ['bike_top_wh' => 'inactive'],
                ];
            } else {
                $filterQuery = [
                    'active' => ['list_status' => 'active'],
                    'inactive' => ['list_status' => 'inactive'],
                ];
            }
            $statLabels = [
                'active' => __('Active'),
                'inactive' => __('Inactive'),
            ];
        } elseif ($strategy === 'paid_unpaid_status') {
            $filterQuery = [
                'active' => [],
                'inactive' => [],
            ];
            $statLabels = [
                'active' => __('Paid'),
                'inactive' => __('Unpaid'),
            ];
            if ($key === 'rta_fines') {
                $statRoutes = [
                    'active' => 'rtaFines.paid',
                    'inactive' => 'rtaFines.tickets',
                ];
            }
        } elseif ($strategy === 'employee_enum_status') {
            $filterQuery = [
                'active' => ['employee_status' => 'active'],
                'inactive' => ['employee_status' => 'inactive'],
            ];
            $statLabels = [
                'active' => __('Active'),
                'inactive' => __('Inactive / On leave'),
            ];
        } elseif ($strategy === 'fuel_card_status') {
            $filterQuery = [
                'active' => ['status' => 'Active'],
                'inactive' => ['status' => 'Inactive'],
            ];
            $statLabels = [
                'active' => __('Active'),
                'inactive' => __('Inactive'),
            ];
        } elseif ($strategy === 'cheque_cleared_pending') {
            $filterQuery = [
                'active' => ['cheque_top_status' => 'cleared'],
                'inactive' => ['cheque_top_status' => 'pending'],
            ];
            $statLabels = [
                'active' => __('Cleared'),
                'inactive' => __('Pending'),
            ];
        } else {
            $filterQuery = [
                'active' => [],
                'inactive' => [],
            ];
            if ($strategy === 'total_only') {
                $statLabels = [
                    'active' => __('Total'),
                    'inactive' => '',
                ];
            }
        }

        $out = [
            'label' => $label,
            'icon' => $icon,
            'route' => $route,
            'table' => $table,
            'count_strategy' => $strategy,
            'filter_query' => $filterQuery,
        ];

        if ($statLabels !== null) {
            $out['stat_labels'] = $statLabels;
        }
        if ($statRoutes !== null) {
            $out['stat_routes'] = $statRoutes;
        }
        if ($strategy === 'paid_unpaid_status') {
            $out['status_column'] = $statusColumn;
        }

        return $out;
    }

    /**
     * Prefer accurate per-module strategies when `status` is not a numeric active flag.
     */
    protected static function refineCountStrategyForKey(string $key, string $table, string $currentStrategy): string
    {
        if (! Schema::hasColumn($table, 'status')) {
            return $currentStrategy;
        }

        return match ($key) {
            'rta_fines', 'rta_saliks' => 'paid_unpaid_status',
            'employees' => 'employee_enum_status',
            'fuel_cards' => 'fuel_card_status',
            'cheques' => 'cheque_cleared_pending',
            default => $currentStrategy,
        };
    }

    public static function settingNameForUser(int $userId): string
    {
        return 'dashboard_visible_cards_user_' . $userId;
    }

    /**
     * @return list<string>
     */
    public static function selectedKeysForUser(User $user): array
    {
        $ordered = array_keys(self::definitions());
        if ($ordered === []) {
            return [];
        }

        $raw = Settings::query()
            ->where('name', self::settingNameForUser((int) $user->id))
            ->value('value');

        if ($raw === null || $raw === '') {
            return self::firstRenderableKeys(self::maxVisibleCards());
        }

        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return self::firstRenderableKeys(self::maxVisibleCards());
        }

        if ($decoded === []) {
            return [];
        }

        $picked = [];
        foreach ($decoded as $key) {
            $key = (string) $key;
            if (in_array($key, $ordered, true)) {
                $picked[] = $key;
            }
        }

        if ($picked === []) {
            return self::firstRenderableKeys(self::maxVisibleCards());
        }

        return array_slice($picked, 0, self::maxVisibleCards());
    }

    /**
     * @param  list<string>  $keys
     */
    public static function saveSelectedKeysForUser(User $user, array $keys): void
    {
        $allowed = array_flip(self::settingsSelectableKeys());
        $normalized = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            if (isset($allowed[$key])) {
                $normalized[] = $key;
            }
        }

        Settings::updateOrCreate(
            ['name' => self::settingNameForUser((int) $user->id)],
            ['value' => json_encode(array_slice(array_values(array_unique($normalized)), 0, self::maxVisibleCards()))]
        );
    }

    /**
     * @return list<string>
     */
    public static function renderableKeys(): array
    {
        $keys = [];
        foreach (array_keys(self::definitions()) as $key) {
            if (self::canRenderKey($key)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public static function canRenderKey(string $key): bool
    {
        $def = self::resolveDefinition($key);
        $table = (string) ($def['table'] ?? '');
        if ($table === '' || ! Schema::hasTable($table)) {
            return false;
        }

        $routeName = (string) ($def['route'] ?? '');

        return $routeName !== '' && Route::has($routeName);
    }

    /**
     * @return list<string>
     */
    protected static function candidateKeysForUser(User $user): array
    {
        $selected = self::selectedKeysForUser($user);
        $ordered = array_keys(self::definitions());
        $merged = $selected;
        foreach ($ordered as $key) {
            if (! in_array($key, $merged, true)) {
                $merged[] = $key;
            }
        }

        return $merged;
    }

    /**
     * @return list<string>
     */
    protected static function firstRenderableKeys(int $limit): array
    {
        $keys = [];
        foreach (array_keys(self::definitions()) as $key) {
            if (! self::canRenderKey($key)) {
                continue;
            }
            $keys[] = $key;
            if (count($keys) >= $limit) {
                break;
            }
        }

        return $keys;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function buildCardForKey(string $key): ?array
    {
        if (! self::canRenderKey($key)) {
            return null;
        }

        $def = self::resolveDefinition($key);
        $table = (string) ($def['table'] ?? '');
        $routeName = (string) ($def['route'] ?? '');
        [$active, $inactive] = self::countsForDefinition($def);

        $slug = (string) (request()->route('company_slug') ?? session('company_slug') ?? '');
        try {
            $baseUrl = route($routeName, ['company_slug' => $slug]);
        } catch (\Throwable) {
            try {
                $baseUrl = route($routeName);
            } catch (\Throwable) {
                return null;
            }
        }

        $filterQuery = is_array($def['filter_query'] ?? null) ? $def['filter_query'] : [];
        $activeQuery = is_array($filterQuery['active'] ?? null) ? $filterQuery['active'] : [];
        $inactiveQuery = is_array($filterQuery['inactive'] ?? null) ? $filterQuery['inactive'] : [];

        $statRoutes = is_array($def['stat_routes'] ?? null) ? $def['stat_routes'] : [];
        $routeActive = isset($statRoutes['active']) ? (string) $statRoutes['active'] : null;
        $routeInactive = isset($statRoutes['inactive']) ? (string) $statRoutes['inactive'] : null;

        $urlActive = self::resolveDashboardStatUrl($routeActive, $baseUrl, $activeQuery, $slug);
        $urlInactive = self::resolveDashboardStatUrl($routeInactive, $baseUrl, $inactiveQuery, $slug);

        $labels = self::statLabelsFromDefinition($def);

        return [
            'key' => $key,
            'label' => (string) ($def['label'] ?? $key),
            'icon' => (string) ($def['icon'] ?? 'ti-layout-grid'),
            'active' => $active,
            'inactive' => $inactive,
            'label_active' => $labels['active'],
            'label_inactive' => $labels['inactive'],
            'url_all' => $baseUrl,
            'url_active' => $urlActive,
            'url_inactive' => $urlInactive,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function cardsForUser(User $user): array
    {
        $out = [];
        $max = self::maxVisibleCards();
        $used = [];

        foreach (self::candidateKeysForUser($user) as $key) {
            if (count($out) >= $max) {
                break;
            }
            if (isset($used[$key])) {
                continue;
            }
            $card = self::buildCardForKey($key);
            if ($card === null) {
                continue;
            }
            $used[$key] = true;
            $out[] = $card;
        }

        return $out;
    }

    /**
     * Human-readable labels for the two dashboard stat tiles (paid/unpaid, cleared/pending, etc.).
     *
     * @return array{active: string, inactive: string}
     */
    protected static function statLabelsFromDefinition(array $def): array
    {
        $sl = $def['stat_labels'] ?? null;
        if (is_array($sl)) {
            return [
                'active' => (string) ($sl['active'] ?? __('Active')),
                'inactive' => (string) ($sl['inactive'] ?? __('Inactive')),
            ];
        }

        return [
            'active' => __('Active'),
            'inactive' => __('Inactive'),
        ];
    }

    protected static function resolveDashboardStatUrl(?string $namedRoute, string $fallbackBaseUrl, array $query, string $companySlug): string
    {
        if ($namedRoute !== null && $namedRoute !== '' && Route::has($namedRoute)) {
            try {
                return route($namedRoute, ['company_slug' => $companySlug]);
            } catch (\Throwable) {
                try {
                    return route($namedRoute);
                } catch (\Throwable) {
                    // Fall back to listing URL + query string.
                }
            }
        }

        return self::appendQuery($fallbackBaseUrl, $query);
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected static function countsForDefinition(array $def): array
    {
        $table = (string) ($def['table'] ?? '');
        $strategy = (string) ($def['count_strategy'] ?? 'numeric_status');
        if ($table === '' || ! Schema::hasTable($table)) {
            return [0, 0];
        }

        return match ($strategy) {
            'total_only' => self::countsTotalOnly($table),
            'sim_char_status' => self::countsSimChar($table),
            'numeric_status' => self::countsNumericStatus($table),
            'paid_unpaid_status' => self::countsPaidUnpaid($table, (string) ($def['status_column'] ?? 'status')),
            'employee_enum_status' => self::countsEmployeeEnum($table),
            'fuel_card_status' => self::countsFuelCard($table),
            'cheque_cleared_pending' => self::countsChequeClearedPending($table),
            default => self::countsNumericStatus($table),
        };
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected static function countsTotalOnly(string $table): array
    {
        $base = company_table($table);

        return [(int) $base->count(), 0];
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected static function countsSimChar(string $table): array
    {
        $base = company_table($table);

        return [
            (int) (clone $base)->where('status', '1')->count(),
            (int) (clone $base)->where('status', '0')->count(),
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected static function countsNumericStatus(string $table): array
    {
        $base = company_table($table);
        $statusCol = TopBarNumericStatus::resolveNumericStatusColumn($table) ?? 'status';
        if (! self::tableHasColumn($table, $statusCol)) {
            return [(int) $base->count(), 0];
        }

        $active = (int) (clone $base)->whereIn($statusCol, TopBarNumericStatus::ACTIVE_VALUES)->count();
        $inactive = (int) (clone $base)->whereIn($statusCol, TopBarNumericStatus::INACTIVE_VALUES)->count();

        return [$active, $inactive];
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected static function countsPaidUnpaid(string $table, string $statusColumn): array
    {
        if (! self::tableHasColumn($table, $statusColumn)) {
            return self::countsTotalOnly($table);
        }

        $base = company_table($table);

        return [
            (int) (clone $base)->where($statusColumn, 'paid')->count(),
            (int) (clone $base)->where($statusColumn, 'unpaid')->count(),
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected static function countsEmployeeEnum(string $table): array
    {
        if (! self::tableHasColumn($table, 'status')) {
            return self::countsTotalOnly($table);
        }

        $base = company_table($table);

        return [
            (int) (clone $base)->where('status', 'active')->count(),
            (int) (clone $base)->whereIn('status', ['inactive', 'on_leave'])->count(),
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected static function countsFuelCard(string $table): array
    {
        if (! self::tableHasColumn($table, 'status')) {
            return self::countsTotalOnly($table);
        }

        $base = company_table($table);
        $active = (int) (clone $base)->where('status', 'Active')->count();
        $inactive = (int) (clone $base)->where(function ($q) {
            $q->where('status', 'Inactive')->orWhereNull('status');
        })->count();

        return [$active, $inactive];
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected static function countsChequeClearedPending(string $table): array
    {
        if (! self::tableHasColumn($table, 'status')) {
            return self::countsTotalOnly($table);
        }

        $base = company_table($table);

        return [
            (int) (clone $base)->where('status', 'Cleared')->count(),
            (int) (clone $base)->where('status', '!=', 'Cleared')->count(),
        ];
    }

    protected static function tableHasColumn(string $table, string $column): bool
    {
        if ($column === '') {
            return false;
        }

        return Schema::hasColumn($table, $column);
    }

    /**
     * @param  array<string, string|int>  $query
     */
    protected static function appendQuery(string $url, array $query): string
    {
        if ($query === []) {
            return $url;
        }

        $sep = str_contains($url, '?') ? '&' : '?';

        return $url . $sep . http_build_query($query);
    }
}
