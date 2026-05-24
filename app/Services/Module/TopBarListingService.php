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
                    'riders' => $this->riderOptionStats($option),
                    'bike_list', 'bikes' => $this->bikeOptionStats($option),
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
    protected function riderOptionStats(Model $option): array
    {
        if (!Schema::hasColumn('riders', 'rider_top_option_id')) {
            return ['active' => 0, 'inactive' => 0];
        }

        $optionId = (int) $option->id;

        return [
            'active' => (int) Riders::query()
                ->where('rider_top_option_id', $optionId)
                ->where('status', 1)
                ->count(),
            'inactive' => (int) Riders::query()
                ->where('rider_top_option_id', $optionId)
                ->whereIn('status', TopBarNumericStatus::INACTIVE_VALUES)
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function bikeOptionStats(Model $option): array
    {
        if (!Schema::hasColumn('bikes', 'bike_top_option_id')) {
            return ['active' => 0, 'inactive' => 0];
        }

        $optionId = (int) $option->id;

        return [
            'active' => (int) Bikes::query()
                ->where('bike_top_option_id', $optionId)
                ->where('status', 1)
                ->count(),
            'inactive' => (int) Bikes::query()
                ->where('bike_top_option_id', $optionId)
                ->whereIn('status', TopBarNumericStatus::INACTIVE_VALUES)
                ->count(),
        ];
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
