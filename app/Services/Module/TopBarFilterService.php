<?php

namespace App\Services\Module;

use App\Support\ErpModuleRegistry;
use App\Support\ModuleFieldSource;
use App\Support\TopBarNumericStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TopBarFilterService
{
    public const MODE_EXACT = 'exact_date';

    public const MODE_UPCOMING = 'upcoming_date';

    public const MODE_OVERDUE = 'overdue_date';

    public const MODE_RANGE = 'date_range';

    /**
     * Apply top-bar filters for a module to the listing query.
     */
    public function applyTopBarFilters(Builder $query, Request $request, string $moduleKey): void
    {
        $config = ErpModuleRegistry::topBarConfig($moduleKey);
        if ($config === null) {
            return;
        }

        $this->applyScopedStatus($query, $config);

        $optionIdParam = (string) ($config['request']['option_id'] ?? 'top_option_id');
        if (!$request->filled($optionIdParam)) {
            return;
        }

        $option = $this->resolveOption($config, (int) $request->input($optionIdParam));
        if (!$option) {
            return;
        }

        $strategy = (string) ($config['filter_strategy'] ?? 'column');
        if ($strategy === 'option_fk' && $this->shouldUseColumnFilterInsteadOfFk($config, $option)) {
            $this->applyColumnFilter($query, $config, $option, $request);
        } elseif ($strategy === 'option_fk') {
            $this->applyOptionFkFilter($query, $config, $option, $request);
        } else {
            $this->applyColumnFilter($query, $config, $option, $request);
        }

        $this->applyStatusFilters($query, $request, $config);
    }

    public function applyColumnFilter(Builder $query, array $config, Model $option, Request $request): void
    {
        $column = $this->resolveColumn($config, $option);
        if ($column === null) {
            return;
        }

        $table = $this->resolveSourceTable($config);
        if ($table === '') {
            return;
        }

        $qualifiedColumn = $this->qualifyColumn($table, $column);

        if ($this->isDateColumn($table, $column, $config)) {
            $this->applyDateColumnFilter($query, $table, $qualifiedColumn, $option, $request, $config);

            return;
        }

        $this->applyScalarColumnFilter($query, $qualifiedColumn, $option, $table, $column);
    }

    public function applyOptionFkFilter(Builder $query, array $config, Model $option, Request $request): void
    {
        $fkColumn = (string) ($config['fk_column'] ?? '');
        $table = $this->resolveSourceTable($config);

        if ($fkColumn === '' || $table === '' || !Schema::hasColumn($table, $fkColumn)) {
            return;
        }

        $query->where($this->qualifyColumn($table, $fkColumn), $option->getKey());

        $categoryColumn = $this->resolveColumn($config, $option);
        if ($categoryColumn !== null && Schema::hasColumn($table, $categoryColumn)) {
            $filterType = $this->resolveCategoryFilterType($config, $option);
            if (in_array($filterType, [self::MODE_EXACT, self::MODE_UPCOMING, self::MODE_OVERDUE, self::MODE_RANGE], true)
                && $this->isDateColumn($table, $categoryColumn, $config)) {
                $this->applyDateColumnFilter($query, $table, $this->qualifyColumn($table, $categoryColumn), $option, $request, $config);
            }
        }
    }

    public function applyStatusFilters(Builder $query, Request $request, array $config): void
    {
        $statusParam = (string) ($config['request']['status'] ?? '');
        $statusFilters = $config['status_filters'] ?? [];

        if ($statusParam === '' || $statusFilters === [] || !$request->has($statusParam)) {
            return;
        }

        $optionIdParam = (string) ($config['request']['option_id'] ?? 'top_option_id');
        if (!$request->filled($optionIdParam)) {
            return;
        }

        $table = $this->resolveSourceTable($config);
        $raw = $request->input($statusParam);
        $keys = is_array($raw) ? $raw : [(string) $raw];

        $query->where(function (Builder $q) use ($keys, $statusFilters, $table) {
            foreach ($keys as $key) {
                $rule = $statusFilters[(string) $key] ?? null;
                if (!is_array($rule)) {
                    continue;
                }
                $column = $this->qualifyColumn($table, (string) ($rule['column'] ?? 'status'));
                $operator = strtolower((string) ($rule['operator'] ?? '='));
                $value = $rule['value'] ?? null;

                if ($operator === 'in' && is_array($value)) {
                    $q->orWhereIn($column, $value);
                } elseif ($operator === '!=') {
                    $q->orWhere($column, '!=', $value);
                } else {
                    $q->orWhere($column, $operator, $value);
                }
            }
        });
    }

    public function countForOption(string $moduleKey, Model $option, ?string $statusFilter = null, ?Request $request = null): int
    {
        $config = ErpModuleRegistry::topBarConfig($moduleKey);
        if ($config === null) {
            return 0;
        }

        $table = (string) ($config['source_table'] ?? '');
        $modelClass = $this->resolveMainModelClass($table);
        if ($modelClass === null) {
            return 0;
        }

        $query = $modelClass::query();
        $this->applyScopedStatus($query, $config);
        $req = $request ?? new Request();
        $req->merge([(string) ($config['request']['option_id'] ?? 'top_option_id') => $option->getKey()]);

        if (($config['filter_strategy'] ?? '') === 'option_fk' && $this->shouldUseColumnFilterInsteadOfFk($config, $option)) {
            $this->applyColumnFilter($query, $config, $option, $req);
        } elseif (($config['filter_strategy'] ?? '') === 'option_fk') {
            $this->applyOptionFkFilter($query, $config, $option, $req);
        } else {
            $this->applyColumnFilter($query, $config, $option, $req);
        }

        if ($statusFilter !== null && isset($config['status_filters'][$statusFilter])) {
            $rule = $config['status_filters'][$statusFilter];
            $column = $this->qualifyColumn($table, (string) ($rule['column'] ?? 'status'));
            $operator = strtolower((string) ($rule['operator'] ?? '='));
            $value = $rule['value'] ?? null;

            if ($operator === 'in' && is_array($value)) {
                $query->whereIn($column, $value);
            } elseif ($operator === '!=') {
                $query->where($column, '!=', $value);
            } else {
                $query->where($column, $operator, $value);
            }
        }

        return (int) $query->count();
    }

    public function isDateColumn(string $table, string $column, array $config): bool
    {
        $configured = $config['date_columns'] ?? [];

        if (in_array($column, $configured, true)) {
            return Schema::hasColumn($table, $column);
        }

        if (!Schema::hasColumn($table, $column)) {
            return false;
        }

        try {
            $type = Schema::getColumnType($table, $column);
        } catch (\Throwable) {
            return false;
        }

        return in_array($type, ['date', 'datetime', 'timestamp'], true);
    }

    public function parseDate(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    public function selectableColumns(string $moduleKey): array
    {
        $config = ErpModuleRegistry::topBarConfig($moduleKey);
        if ($config === null) {
            return [];
        }

        $table = (string) ($config['source_table'] ?? '');
        if ($table === '' || !Schema::hasTable($table)) {
            $table = ModuleFieldSource::resolveSourceTable($moduleKey) ?? '';
        }

        if ($table === '') {
            return [];
        }

        $excluded = array_flip(ModuleFieldSource::defaultExcludedFieldsForModule($moduleKey));
        $options = [];

        foreach (Schema::getColumnListing($table) as $column) {
            if (isset($excluded[$column])) {
                continue;
            }
            $options[$column] = ucwords(str_replace('_', ' ', $column));
        }

        return $options;
    }

    protected function applyScopedStatus(Builder $query, array $config): void
    {
        $scopedStatus = (string) ($config['scoped_status'] ?? '');
        if ($scopedStatus === '') {
            return;
        }

        $table = $this->resolveSourceTable($config);
        $statusColumn = (string) ($config['status_column'] ?? 'status');
        if ($table !== '' && Schema::hasColumn($table, $statusColumn)) {
            $query->where($table . '.' . $statusColumn, $scopedStatus);
        }
    }

    protected function resolveOption(array $config, int $optionId): ?Model
    {
        $optionClass = $config['option_model'] ?? \App\Models\ErpModuleTopOption::class;

        if ($config['storage'] === 'generic') {
            $optionClass = \App\Models\ErpModuleTopOption::class;
        }

        return $optionClass::query()->with('category')->find($optionId);
    }

    /**
     * Riders (and similar modules) store some categories on scalar columns (fleet_supervisor, rider_status)
     * while others use the FK column (rider_top_option_id).
     */
    protected function shouldUseColumnFilterInsteadOfFk(array $config, Model $option): bool
    {
        $fkColumn = trim((string) ($config['fk_column'] ?? ''));
        $categoryColumn = $this->resolveColumn($config, $option);

        if ($categoryColumn === null) {
            return false;
        }

        return $categoryColumn !== $fkColumn;
    }

    protected function resolveColumn(array $config, Model $option): ?string
    {
        $category = $option->category ?? null;
        if (!$category) {
            return null;
        }

        $attr = (string) ($config['column_attribute'] ?? 'db_column');
        if ($config['storage'] === 'generic') {
            $attr = 'db_column';
        }

        $column = trim((string) ($category->{$attr} ?? $category->db_column ?? ''));
        $table = (string) ($config['source_table'] ?? '');

        if ($column === '' || $table === '' || !Schema::hasColumn($table, $column)) {
            return null;
        }

        return $column;
    }

    protected function resolveCategoryFilterType(array $config, Model $option): string
    {
        $category = $option->category;
        $filterType = trim((string) ($category->filter_type ?? ''));

        if ($filterType !== '') {
            return $filterType;
        }

        $column = $this->resolveColumn($config, $option);
        if ($column === null) {
            return (string) config('top_bar_filters.default_date_mode', self::MODE_EXACT);
        }

        $modes = $config['column_modes'] ?? [];
        $mapped = $modes[$column] ?? null;

        return $mapped !== null ? (string) $mapped : (string) config('top_bar_filters.default_date_mode', self::MODE_EXACT);
    }

    protected function resolveFilterMode(array $config, string $column, Model $option, ?string $requestMode): string
    {
        $requestMode = strtolower(trim((string) $requestMode));
        $allowed = [self::MODE_EXACT, self::MODE_UPCOMING, self::MODE_OVERDUE, self::MODE_RANGE, 'exact', 'upcoming', 'range', 'overdue'];
        if (in_array($requestMode, $allowed, true)) {
            return $this->normalizeMode($requestMode);
        }

        return $this->normalizeMode($this->resolveCategoryFilterType($config, $option));
    }

    protected function normalizeMode(string $mode): string
    {
        return match ($mode) {
            'exact', 'exact_date' => self::MODE_EXACT,
            'upcoming', 'upcoming_date' => self::MODE_UPCOMING,
            'overdue', 'overdue_date' => self::MODE_OVERDUE,
            'range', 'date_range' => self::MODE_RANGE,
            default => self::MODE_EXACT,
        };
    }

    protected function applyDateColumnFilter(
        Builder $query,
        string $table,
        string $column,
        Model $option,
        Request $request,
        array $config
    ): void {
        $modeParam = (string) ($config['request']['filter_mode'] ?? 'top_filter_mode');
        $fromParam = (string) ($config['request']['date_from'] ?? 'top_date_from');
        $toParam = (string) ($config['request']['date_to'] ?? 'top_date_to');

        $mode = $this->resolveFilterMode($config, $column, $option, $request->input($modeParam));
        $from = $this->parseDate($request->input($fromParam));
        $to = $this->parseDate($request->input($toParam));
        $selected = $this->parseDate((string) $option->name);

        if ($mode === self::MODE_RANGE && ($from || $to)) {
            $this->applyDateRange($query, $column, $from, $to);

            return;
        }

        if ($mode === self::MODE_OVERDUE) {
            $query->whereNotNull($column)
                ->whereDate($column, '<', Carbon::today()->toDateString());

            return;
        }

        if (!$selected && $mode !== self::MODE_OVERDUE) {
            return;
        }

        if ($mode === self::MODE_UPCOMING) {
            $query->whereNotNull($column)
                ->whereDate($column, '>=', $selected->toDateString());

            return;
        }

        if ($mode === self::MODE_RANGE && $selected) {
            $query->whereDate($column, $selected->toDateString());

            return;
        }

        $query->whereDate($column, $selected->toDateString());
    }

    protected function applyDateRange(Builder $query, string $column, ?Carbon $from, ?Carbon $to): void
    {
        $query->whereNotNull($column);

        if ($from) {
            $query->whereDate($column, '>=', $from->toDateString());
        }

        if ($to) {
            $query->whereDate($column, '<=', $to->toDateString());
        }
    }

    protected function applyScalarColumnFilter(
        Builder $query,
        string $qualifiedColumn,
        Model $option,
        ?string $table = null,
        ?string $rawColumn = null
    ): void {
        $value = trim((string) $option->name);
        if ($value === '') {
            return;
        }

        $parsed = $this->parseDate($value);
        if ($parsed !== null) {
            $query->whereDate($qualifiedColumn, $parsed->toDateString());

            return;
        }

        $columnForSchema = $rawColumn ?? $qualifiedColumn;

        if ($table !== null && $columnForSchema === 'customer_id' && Schema::hasTable('customers')) {
            if (is_numeric($value)) {
                $query->where($qualifiedColumn, (int) $value);

                return;
            }

            $customerId = \App\Models\Customers::query()
                ->where('name', $value)
                ->value('id');
            if ($customerId !== null) {
                $query->where($qualifiedColumn, $customerId);

                return;
            }
        }

        if ($table !== null && TopBarNumericStatus::isNumericStatusColumn($table, $columnForSchema)) {
            $mapped = TopBarNumericStatus::valueForLabel($value);
            if ($mapped !== null) {
                $query->where($qualifiedColumn, $mapped);

                return;
            }

            if (is_numeric($value)) {
                $query->where($qualifiedColumn, (int) $value);

                return;
            }
        }

        $query->where($qualifiedColumn, $value);
    }

    protected function qualifyColumn(string $table, string $column): string
    {
        if ($column === '' || str_contains($column, '.')) {
            return $column;
        }

        return $table !== '' ? $table . '.' . $column : $column;
    }

    protected function resolveSourceTable(array $config): string
    {
        $table = (string) ($config['source_table'] ?? '');
        if ($table !== '' && Schema::hasTable($table)) {
            return $table;
        }

        $moduleKey = (string) ($config['module_key'] ?? '');

        return ModuleFieldSource::resolveSourceTable($moduleKey) ?? '';
    }

    protected function resolveMainModelClass(string $table): ?string
    {
        $map = [
            'cheques' => \App\Models\Cheques::class,
            'riders' => \App\Models\Riders::class,
            'bikes' => \App\Models\Bikes::class,
            'employees' => \App\Models\Employee::class,
            'garages' => \App\Models\Garages::class,
            'customers' => \App\Models\Customers::class,
            'vendors' => \App\Models\vendors::class,
            'sims' => \App\Models\Sims::class,
            'rta_fines' => \App\Models\RtaFines::class,
        ];

        if (isset($map[$table])) {
            return $map[$table];
        }

        $candidates = [
            'App\\Models\\' . Str::studly(Str::singular($table)),
            'App\\Models\\' . Str::studly($table),
        ];

        foreach ($candidates as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }
}
