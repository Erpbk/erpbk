<?php

namespace App\Services\Module;

use App\Models\Bikes;
use App\Models\ErpModuleTopCategory;
use App\Models\ErpModuleTopOption;
use App\Models\Employee;
use App\Models\Riders;
use App\Support\ErpModuleRegistry;
use App\Support\TopBarNumericStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class TopBarListingService
{
    public function __construct(
        protected TopBarFilterService $filterService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function listingViewData(string $moduleKey, Request $request): array
    {
        $moduleKey = ErpModuleRegistry::resolveTopBarModuleKey($moduleKey);
        $config = ErpModuleRegistry::topBarConfig($moduleKey);

        if ($config === null) {
            return [
                'topBarModuleKey' => $moduleKey,
                'topBarConfig' => null,
                'topBarSliderCategories' => collect(),
                'topBarOptionStats' => [],
            ];
        }

        $categories = $this->categoriesForListing($moduleKey);
        $stats = $this->buildOptionStats($moduleKey, $categories, $request);

        return [
            'topBarModuleKey' => $moduleKey,
            'topBarConfig' => $config,
            'topBarSliderCategories' => $categories,
            'topBarOptionStats' => $stats,
        ];
    }

    public function categoriesForListing(string $moduleKey): Collection
    {
        $moduleKey = ErpModuleRegistry::resolveTopBarModuleKey($moduleKey);
        $config = ErpModuleRegistry::topBarConfig($moduleKey);

        if ($config === null) {
            return collect();
        }

        if (($config['storage'] ?? '') === 'generic') {
            return ErpModuleTopCategory::query()
                ->with(['options' => fn ($q) => $this->applyListingOptionsConstraint($q, ErpModuleTopOption::class)])
                ->where('module_key', $moduleKey)
                ->where('show_in_top_bar', true)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get()
                ->filter(fn ($cat) => $cat->options->isNotEmpty())
                ->values();
        }

        $modelClass = $config['category_model'] ?? null;
        $optionClass = $config['option_model'] ?? null;
        if (!$modelClass || !class_exists($modelClass)) {
            return collect();
        }

        return $modelClass::query()
            ->with(['options' => fn ($q) => $this->applyListingOptionsConstraint($q, $optionClass)])
            ->where('show_in_top_bar', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->filter(fn ($cat) => $cat->options->isNotEmpty())
            ->values();
    }

    /**
     * @param  iterable<int, Model>  $categories
     * @return array<int, array<string, int>>
     */
    public function buildOptionStats(string $moduleKey, iterable $categories, ?Request $request = null): array
    {
        $moduleKey = ErpModuleRegistry::resolveTopBarModuleKey($moduleKey);
        $config = ErpModuleRegistry::topBarConfig($moduleKey);
        if ($config === null) {
            return [];
        }

        $stats = [];
        $statusFilters = $config['status_filters'] ?? [];

        foreach ($categories as $category) {
            foreach ($category->options as $option) {
                if (isset($option->show_in_top_bar) && !$option->show_in_top_bar) {
                    continue;
                }

                $stats[(int) $option->id] = match ($moduleKey) {
                    'riders' => $this->riderOptionStats($option, $category),
                    'bike_list', 'bikes' => $this->bikeOptionStats($option, $category),
                    'employees' => $this->employeeOptionStats($option, $category),
                    default => $this->defaultOptionStats($moduleKey, $option, $statusFilters, $request),
                };
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, array<string, mixed>>  $statusFilters
     * @return array<string, int>
     */
    protected function defaultOptionStats(
        string $moduleKey,
        Model $option,
        array $statusFilters,
        ?Request $request
    ): array {
        if ($statusFilters !== []) {
            $row = [];
            foreach (array_keys($statusFilters) as $key) {
                $row[$key] = $this->filterService->countForOption($moduleKey, $option, $key, $request);
            }

            return $row;
        }

        return [
            'total' => $this->filterService->countForOption($moduleKey, $option, null, $request),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function riderOptionStats(Model $option, Model $category): array
    {
        $base = $this->riderStatsBaseQuery($option, $category);
        if ($base === null) {
            return ['active' => 0, 'inactive' => 0];
        }

        return [
            'active' => (int) (clone $base)->where('status', 1)->count(),
            'inactive' => (int) (clone $base)->where('status', '!=', 1)->count(),
        ];
    }

    protected function riderStatsBaseQuery(Model $option, Model $category): ?Builder
    {
        $column = trim((string) ($category->rider_column ?? ''));

        if ($column === 'rider_top_option_id' && Schema::hasColumn('riders', 'rider_top_option_id')) {
            return Riders::query()->where('rider_top_option_id', (int) $option->id);
        }

        if ($column === '' || !Schema::hasColumn('riders', $column)) {
            return null;
        }

        $base = Riders::query();

        if ($column === 'customer_id') {
            if (is_numeric($option->name)) {
                return $base->where($column, (int) $option->name);
            }

            $customerId = \App\Models\Customers::query()
                ->where('name', (string) $option->name)
                ->value('id');
            if ($customerId !== null) {
                return $base->where($column, $customerId);
            }

            return null;
        }

        if (TopBarNumericStatus::isNumericStatusColumn('riders', $column)) {
            $mapped = TopBarNumericStatus::valueForLabel((string) $option->name);
            if ($mapped !== null) {
                return $base->where($column, $mapped);
            }

            if (is_numeric($option->name)) {
                return $base->where($column, (int) $option->name);
            }

            return null;
        }

        return $base->where($column, (string) $option->name);
    }

    /**
     * @return array<string, int>
     */
    protected function bikeOptionStats(Model $option, Model $category): array
    {
        // Ensure category is available so column vs FK filter resolution matches click-filters.
        if (!$option->relationLoaded('category') || $option->getRelation('category') === null) {
            $option->setRelation('category', $category);
        }

        $base = $this->bikeStatsBaseQuery($option, $category);
        if ($base === null) {
            return ['active' => 0, 'inactive' => 0];
        }

        return [
            'active' => (int) (clone $base)->where('bikes.status', 1)->count(),
            'inactive' => (int) (clone $base)
                ->whereIn('bikes.status', TopBarNumericStatus::INACTIVE_VALUES)
                ->count(),
        ];
    }

    protected function bikeStatsBaseQuery(Model $option, Model $category): ?Builder
    {
        $column = trim((string) ($category->bike_column ?? ''));

        if ($column === 'bike_top_option_id' || $column === '') {
            if (!Schema::hasColumn('bikes', 'bike_top_option_id')) {
                return null;
            }

            return Bikes::query()->where('bike_top_option_id', (int) $option->id);
        }

        if (!Schema::hasColumn('bikes', $column)) {
            return null;
        }

        $base = Bikes::query();
        $value = trim((string) $option->name);
        if ($value === '') {
            return null;
        }

        // FK / integer columns (company, branch_id, customer_id, …) store ids in option name.
        if (in_array($column, ['company', 'branch_id', 'customer_id', 'rider_id', 'rental_company_id', 'vehicle_type', 'leased_return_company_id'], true)
            || ($column !== 'status' && TopBarNumericStatus::isNumericStatusColumn('bikes', $column))) {
            if (is_numeric($value)) {
                return $base->where('bikes.'.$column, (int) $value);
            }

            if ($column === 'company') {
                $companyId = \App\Models\LeasingCompanies::query()
                    ->where('name', $value)
                    ->value('id');
                if ($companyId !== null) {
                    return $base->where('bikes.company', $companyId);
                }

                return null;
            }

            if ($column === 'customer_id') {
                $customerId = \App\Models\Customers::query()
                    ->where('name', $value)
                    ->value('id');
                if ($customerId !== null) {
                    return $base->where('bikes.customer_id', $customerId);
                }

                return null;
            }
        }

        if ($column === 'status' && TopBarNumericStatus::isNumericStatusColumn('bikes', $column)) {
            $mapped = TopBarNumericStatus::valueForLabel($value);
            if ($mapped !== null) {
                return $base->where('bikes.status', $mapped);
            }

            if (is_numeric($value)) {
                return $base->where('bikes.status', (int) $value);
            }

            return null;
        }

        return $base->where('bikes.'.$column, $value);
    }

    /**
     * @return array<string, int>
     */
    protected function employeeOptionStats(Model $option, Model $category): array
    {
        $column = trim((string) ($category->employee_column ?? ''));
        $base = Employee::query();

        if ($column !== '' && Schema::hasColumn('employees', $column)) {
            $base->where($column, $option->name);
        }

        if ($column === 'status') {
            return [
                'active' => (int) (clone $base)->count(),
                'inactive' => 0,
            ];
        }

        return [
            'active' => (int) (clone $base)->where('status', 'active')->count(),
            'inactive' => (int) (clone $base)->whereIn('status', ['inactive', 'on_leave'])->count(),
        ];
    }

    public function applyFilters(Builder $query, Request $request, string $moduleKey): void
    {
        $this->filterService->applyTopBarFilters($query, $request, $moduleKey);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyListingOptionsConstraint($query, ?string $optionClass): void
    {
        $query->where('is_active', true);

        if ($optionClass && class_exists($optionClass)) {
            $table = (new $optionClass)->getTable();
            if (Schema::hasColumn($table, 'show_in_top_bar')) {
                $query->where('show_in_top_bar', true);
            }
        }

        $query->orderBy('display_order')->orderBy('id');
    }

    /**
     * Stat labels/icons for the slider UI.
     *
     * @return array<string, array{label: string, icon: string}>
     */
    public function statDefinitions(string $moduleKey): array
    {
        $moduleKey = ErpModuleRegistry::resolveTopBarModuleKey($moduleKey);
        $config = ErpModuleRegistry::topBarConfig($moduleKey) ?? [];
        $custom = $config['listing_stats'] ?? null;

        if (is_array($custom) && $custom !== []) {
            return $custom;
        }

        $statusFilters = $config['status_filters'] ?? [];
        if ($statusFilters !== []) {
            $defs = [];
            $numericLabels = TopBarNumericStatus::listingStatDefinitions();
            foreach ($statusFilters as $key => $rule) {
                if (isset($numericLabels[$key]) && TopBarNumericStatus::usesNumericActiveInactive($statusFilters)) {
                    $defs[$key] = $numericLabels[$key];

                    continue;
                }
                $defs[$key] = [
                    'label' => ucfirst((string) $key),
                    'icon' => $key === 'cleared' ? 'ti-circle-check' : ($key === 'pending' ? 'ti-clock' : 'ti-filter'),
                ];
            }

            return $defs;
        }

        return match ($moduleKey) {
            'riders', 'bike_list', 'bikes', 'employees' => TopBarNumericStatus::listingStatDefinitions(),
            default => [
                'total' => ['label' => 'Total', 'icon' => 'ti-list'],
            ],
        };
    }
}
