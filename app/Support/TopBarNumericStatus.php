<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class TopBarNumericStatus
{
    /** @var list<int|string> */
    public const ACTIVE_VALUES = [1];

    /**
     * Non-active numeric statuses shared across modules.
     * Riders: 0/2/3 = inactive/off bike, 4 = vacation, 5 = absconded.
     *
     * @var list<int|string>
     */
    public const INACTIVE_VALUES = [0, 2, 3, 4, 5];

    public const ACTIVE_KEY = 'active';

    public const INACTIVE_KEY = 'inactive';

    /**
     * @return list<string>
     */
    public static function statusColumnCandidates(): array
    {
        return ['status', 'is_active', 'record_status', 'state'];
    }

    public static function resolveNumericStatusColumn(string $table): ?string
    {
        if ($table === '' || !Schema::hasTable($table)) {
            return null;
        }

        foreach (self::statusColumnCandidates() as $column) {
            if (self::isNumericStatusColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    public static function isNumericStatusColumn(string $table, string $column): bool
    {
        if ($column === '' || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return false;
        }

        try {
            $type = Schema::getColumnType($table, $column);
        } catch (\Throwable) {
            return false;
        }

        return in_array($type, ['integer', 'tinyint', 'smallint', 'mediumint', 'bigint', 'boolean'], true);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function statusFilterRules(?string $column = 'status'): array
    {
        $column = $column ?: 'status';

        return [
            self::ACTIVE_KEY => [
                'column' => $column,
                'operator' => '=',
                'value' => 1,
            ],
            self::INACTIVE_KEY => [
                'column' => $column,
                'operator' => '!=',
                'value' => 1,
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, icon: string}>
     */
    public static function listingStatDefinitions(): array
    {
        return [
            self::ACTIVE_KEY => ['label' => 'Active', 'icon' => 'ti-user-check'],
            self::INACTIVE_KEY => ['label' => 'Inactive', 'icon' => 'ti-user-x'],
        ];
    }

    public static function labelForValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Active' : 'Inactive';
        }

        $normalized = is_numeric($value) ? (int) $value : strtolower(trim((string) $value));

        if ($normalized === 1 || $normalized === '1' || $normalized === 'active') {
            return 'Active';
        }

        if (in_array($normalized, [0, 2, 3, 4, 5, '0', '2', '3', '4', '5', 'inactive'], true)) {
            return 'Inactive';
        }

        return (string) $value;
    }

    public static function valueForLabel(string $label): ?int
    {
        $key = strtolower(trim($label));

        return match ($key) {
            'active', '1' => 1,
            'inactive', '0' => 0,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5,
            default => is_numeric($key) ? (int) $key : null,
        };
    }

    public static function isInactiveLabel(string $label): bool
    {
        return in_array(strtolower(trim($label)), ['inactive', '0'], true);
    }

    public static function applyStatusKey(Builder $query, string $column, string $statusKey): void
    {
        if ($statusKey === self::ACTIVE_KEY) {
            $query->where($column, 1);

            return;
        }

        if ($statusKey === self::INACTIVE_KEY) {
            // Match top-bar inactive counts: anything that is not active.
            $query->where($column, '!=', 1);

            return;
        }
    }

    /**
     * @param  list<string>  $statusKeys
     */
    public static function applyActiveInactiveOrGroup(Builder $query, string $column, array $statusKeys): void
    {
        $statusKeys = self::normalizeStatusKeys($statusKeys);
        if ($statusKeys === []) {
            return;
        }

        $query->where(function (Builder $q) use ($column, $statusKeys) {
            foreach ($statusKeys as $statusKey) {
                if ($statusKey === self::ACTIVE_KEY) {
                    $q->orWhere($column, 1);
                } elseif ($statusKey === self::INACTIVE_KEY) {
                    $q->orWhere($column, '!=', 1);
                }
            }
        });
    }

    /**
     * @param  array<string>|string|null  $raw
     * @return list<string>
     */
    public static function normalizeStatusKeys(array|string|null $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $keys = is_array($raw) ? $raw : [$raw];

        return array_values(array_filter(array_map(
            static function ($key) {
                if (!is_string($key) && !is_numeric($key)) {
                    return '';
                }

                return strtolower(trim((string) $key));
            },
            $keys
        ), static fn ($key) => in_array($key, [self::ACTIVE_KEY, self::INACTIVE_KEY], true)));
    }

    /**
     * @param  array<string, array<string, mixed>>  $statusFilters
     */
    public static function usesNumericActiveInactive(array $statusFilters): bool
    {
        $active = $statusFilters[self::ACTIVE_KEY] ?? null;
        $inactive = $statusFilters[self::INACTIVE_KEY] ?? null;

        if (!is_array($active) || !is_array($inactive)) {
            return false;
        }

        $inactiveValues = $inactive['value'] ?? null;
        $inactiveOperator = strtolower((string) ($inactive['operator'] ?? '='));

        $inactiveMatches = ($inactiveOperator === '!=' && (int) ($inactive['value'] ?? -1) === 1)
            || ($inactiveOperator === 'in' && is_array($inactiveValues)
                && array_map('intval', $inactiveValues) === array_map('intval', self::INACTIVE_VALUES));

        return ($active['value'] ?? null) == 1 && $inactiveMatches;
    }

    /**
     * True when module should use Active/Inactive numeric stats (not string statuses like paid/unpaid).
     *
     * @param  array<string, array<string, mixed>>  $statusFilters
     */
    public static function shouldUseNumericActiveInactive(array $statusFilters): bool
    {
        if ($statusFilters === []) {
            return true;
        }

        if (self::usesNumericActiveInactive($statusFilters)) {
            return true;
        }

        $keys = array_keys($statusFilters);

        return !array_intersect($keys, ['cleared', 'pending', 'unpaid', 'paid', 'on_leave']);
    }
}
