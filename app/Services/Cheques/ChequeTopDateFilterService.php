<?php

namespace App\Services\Cheques;

use App\Models\ChequeTopOption;
use App\Services\Module\TopBarFilterService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Cheques top-bar filtering – delegates to centralized TopBarFilterService.
 */
class ChequeTopDateFilterService
{
    public const MODE_EXACT = 'exact';

    public const MODE_UPCOMING = 'upcoming';

    public const MODE_RANGE = 'range';

    public function __construct(
        protected TopBarFilterService $topBarFilter
    ) {}

    public function applyTopBarFilters(Builder $query, Request $request): void
    {
        $this->topBarFilter->applyTopBarFilters($query, $request, 'cheques');
    }

    public function applyOptionFilter(Builder $query, ChequeTopOption $option, Request $request): void
    {
        $config = \App\Support\ErpModuleRegistry::topBarConfig('cheques');
        if ($config) {
            $this->topBarFilter->applyColumnFilter($query, $config, $option, $request);
        }
    }

    public function applyStatusFilters(Builder $query, Request $request): void
    {
        $config = \App\Support\ErpModuleRegistry::topBarConfig('cheques');
        if ($config) {
            $this->topBarFilter->applyStatusFilters($query, $request, $config);
        }
    }

    public function countForOption(ChequeTopOption $option, ?string $statusFilter = null, ?Request $request = null): int
    {
        return $this->topBarFilter->countForOption('cheques', $option, $statusFilter, $request);
    }

    /**
     * @param  iterable<int, \App\Models\ChequeTopCategory>  $categories
     * @return array<int, array{cleared: int, pending: int}>
     */
    public function buildOptionStats(iterable $categories): array
    {
        $stats = [];

        foreach ($categories as $category) {
            foreach ($category->options as $option) {
                $stats[(int) $option->id] = [
                    'cleared' => $this->countForOption($option, 'cleared'),
                    'pending' => $this->countForOption($option, 'pending'),
                ];
            }
        }

        return $stats;
    }

    public function isDateColumn(string $column): bool
    {
        $config = \App\Support\ErpModuleRegistry::topBarConfig('cheques') ?? [];

        return $this->topBarFilter->isDateColumn('cheques', $column, $config);
    }

    public function resolveFilterMode(string $column, ?string $requestMode = null): string
    {
        $legacy = strtolower(trim((string) $requestMode));
        if (in_array($legacy, [self::MODE_EXACT, self::MODE_UPCOMING, self::MODE_RANGE], true)) {
            return $legacy;
        }

        $columnMode = strtolower((string) (config("cheque_top_date_filters.columns.{$column}.mode") ?? ''));
        if (in_array($columnMode, [self::MODE_EXACT, self::MODE_UPCOMING, self::MODE_RANGE], true)) {
            return $columnMode;
        }

        return (string) config('cheque_top_date_filters.default_mode', self::MODE_EXACT);
    }

    public function parseDate(?string $value): ?Carbon
    {
        return $this->topBarFilter->parseDate($value);
    }
}
