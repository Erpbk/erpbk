<?php

namespace App\Services\Cheques;

use App\Models\ChequeTopOption;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ChequeTopDateFilterService
{
    public const MODE_EXACT = 'exact';

    public const MODE_UPCOMING = 'upcoming';

    public const MODE_RANGE = 'range';

    /**
     * Apply top-bar option + optional status filters to the cheques listing query.
     */
    public function applyTopBarFilters(Builder $query, Request $request): void
    {
        if (!$request->filled('cheque_top_option_id')) {
            return;
        }

        $option = ChequeTopOption::query()
            ->with('category')
            ->find((int) $request->cheque_top_option_id);

        if (!$option || !$option->category) {
            return;
        }

        $this->applyOptionFilter($query, $option, $request);
        $this->applyStatusFilters($query, $request);
    }

    /**
     * Filter cheques by the selected top-bar option (column + value / date).
     */
    public function applyOptionFilter(Builder $query, ChequeTopOption $option, Request $request): void
    {
        $column = $this->resolveColumn($option);
        if ($column === null) {
            return;
        }

        if ($this->isDateColumn($column)) {
            $this->applyDateColumnFilter($query, $column, $option, $request);

            return;
        }

        $this->applyScalarColumnFilter($query, $column, $option);
    }

    public function applyStatusFilters(Builder $query, Request $request): void
    {
        if (!$request->has('cheque_top_status') || $request->cheque_top_status === '' || $request->cheque_top_status === []) {
            return;
        }

        if (!$request->filled('cheque_top_option_id')) {
            return;
        }

        $statusFilters = $request->cheque_top_status;
        if (!is_array($statusFilters)) {
            $statusFilters = [(string) $statusFilters];
        }

        $query->where(function (Builder $q) use ($statusFilters) {
            foreach ($statusFilters as $status) {
                if ($status === 'cleared') {
                    $q->orWhere('status', 'Cleared');
                } elseif ($status === 'pending') {
                    $q->orWhere('status', '!=', 'Cleared');
                }
            }
        });
    }

    /**
     * Count cheques for a slider card (option + optional cleared/pending).
     */
    public function countForOption(ChequeTopOption $option, ?string $statusFilter = null, ?Request $request = null): int
    {
        $query = \App\Models\Cheques::query();
        $this->applyOptionFilter($query, $option, $request ?? new Request());

        if ($statusFilter === 'cleared') {
            $query->where('status', 'Cleared');
        } elseif ($statusFilter === 'pending') {
            $query->where('status', '!=', 'Cleared');
        }

        return (int) $query->count();
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
        $configured = config('cheque_top_date_filters.date_columns', []);

        if (in_array($column, $configured, true)) {
            return Schema::hasColumn('cheques', $column);
        }

        if (!Schema::hasColumn('cheques', $column)) {
            return false;
        }

        try {
            $type = Schema::getColumnType('cheques', $column);
        } catch (\Throwable) {
            return false;
        }

        return in_array($type, ['date', 'datetime', 'timestamp'], true);
    }

    public function resolveFilterMode(string $column, ?string $requestMode = null): string
    {
        $requestMode = strtolower(trim((string) $requestMode));
        if (in_array($requestMode, [self::MODE_EXACT, self::MODE_UPCOMING, self::MODE_RANGE], true)) {
            return $requestMode;
        }

        $columnMode = strtolower((string) (config("cheque_top_date_filters.columns.{$column}.mode") ?? ''));

        if (in_array($columnMode, [self::MODE_EXACT, self::MODE_UPCOMING, self::MODE_RANGE], true)) {
            return $columnMode;
        }

        return (string) config('cheque_top_date_filters.default_mode', self::MODE_EXACT);
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

    protected function resolveColumn(ChequeTopOption $option): ?string
    {
        $column = trim((string) ($option->category->cheque_column ?? ''));

        if ($column === '' || !Schema::hasColumn('cheques', $column)) {
            return null;
        }

        return $column;
    }

    protected function applyDateColumnFilter(
        Builder $query,
        string $column,
        ChequeTopOption $option,
        Request $request
    ): void {
        $mode = $this->resolveFilterMode($column, $request->input('cheque_top_filter_mode'));
        $from = $this->parseDate($request->input('cheque_top_date_from'));
        $to = $this->parseDate($request->input('cheque_top_date_to'));
        $selected = $this->parseDate((string) $option->name);

        if ($mode === self::MODE_RANGE && ($from || $to)) {
            $this->applyDateRange($query, $column, $from, $to);

            return;
        }

        if (!$selected) {
            return;
        }

        if ($mode === self::MODE_UPCOMING) {
            $query->whereNotNull($column)
                ->whereDate($column, '>=', $selected->toDateString());

            return;
        }

        if ($mode === self::MODE_RANGE) {
            $query->whereDate($column, $selected->toDateString());

            return;
        }

        $query->whereDate($column, $selected->toDateString());
    }

    protected function applyDateRange(
        Builder $query,
        string $column,
        ?Carbon $from,
        ?Carbon $to
    ): void {
        $query->whereNotNull($column);

        if ($from) {
            $query->whereDate($column, '>=', $from->toDateString());
        }

        if ($to) {
            $query->whereDate($column, '<=', $to->toDateString());
        }
    }

    protected function applyScalarColumnFilter(Builder $query, string $column, ChequeTopOption $option): void
    {
        $value = trim((string) $option->name);
        if ($value === '') {
            return;
        }

        $parsed = $this->parseDate($value);
        if ($parsed !== null) {
            $query->whereDate($column, $parsed->toDateString());

            return;
        }

        $query->where($column, $value);
    }
}
