<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompanyQuery
{
    /**
     * @var array<string, bool>
     */
    private static array $companyColumnCache = [];

    /**
     * @var array<string, bool>
     */
    private static array $deletedAtColumnCache = [];

    public static function table(string $table, ?string $connection = null, bool $withTrashed = false): Builder
    {
        $query = $connection
            ? DB::connection($connection)->table($table)
            : DB::table($table);

        if (! $withTrashed) {
            $query = self::applySoftDeleteScope($query, $table, $connection);
        }

        if (! self::shouldApplyScope()) {
            return $query;
        }

        $companyId = self::resolveCompanyId();
        if ($companyId === null) {
            return $query->whereRaw('0 = 1');
        }

        $connectionName = $connection ?: (DB::getDefaultConnection() ?: config('database.default'));
        if (! self::hasCompanyIdColumn($table, $connectionName)) {
            return $query;
        }

        if (AccountsCompanyScope::appliesToTable($table, $connectionName)) {
            return AccountsCompanyScope::apply($query, $companyId, $table);
        }

        $companyColumn = self::qualifiedCompanyColumn($table);

        return $query->where($companyColumn, $companyId)->whereNotNull($companyColumn);
    }

    /**
     * Same as table(), but includes soft-deleted rows (for recycle bin / restore flows).
     */
    public static function tableWithTrashed(string $table, ?string $connection = null): Builder
    {
        return self::table($table, $connection, true);
    }

    /**
     * Only soft-deleted rows (recycle bin listings that use the query builder).
     */
    public static function tableOnlyTrashed(string $table, ?string $connection = null): Builder
    {
        $query = self::table($table, $connection, true);
        $connectionName = $connection ?: (DB::getDefaultConnection() ?: config('database.default'));

        if (! self::hasDeletedAtColumn($table, $connectionName)) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereNotNull(self::qualifiedDeletedAtColumn($table));
    }

    public static function insert(string $table, array $values, ?string $connection = null): bool
    {
        $query = $connection
            ? DB::connection($connection)->table($table)
            : DB::table($table);

        $payload = self::prepareInsertPayload($table, $values, $connection);

        return $query->insert($payload);
    }

    public static function insertGetId(string $table, array $values, ?string $connection = null, ?string $sequence = null): int
    {
        $query = $connection
            ? DB::connection($connection)->table($table)
            : DB::table($table);

        $payload = self::prepareInsertPayload($table, $values, $connection);

        return $query->insertGetId($payload, $sequence);
    }

    private static function applySoftDeleteScope(Builder $query, string $table, ?string $connection): Builder
    {
        $connectionName = $connection ?: (DB::getDefaultConnection() ?: config('database.default'));
        if (! self::hasDeletedAtColumn($table, $connectionName)) {
            return $query;
        }

        return $query->whereNull(self::qualifiedDeletedAtColumn($table));
    }

    private static function shouldApplyScope(): bool
    {
        return CompanyContext::shouldApplyScope();
    }

    private static function resolveCompanyId(): ?int
    {
        return CompanyContext::id();
    }

    private static function hasCompanyIdColumn(string $table, string $connection): bool
    {
        $tableName = self::baseTableName($table);
        $cacheKey = $connection . ':' . $tableName;

        if (array_key_exists($cacheKey, self::$companyColumnCache)) {
            return self::$companyColumnCache[$cacheKey];
        }

        self::$companyColumnCache[$cacheKey] = Schema::connection($connection)->hasColumn($tableName, 'company_id');

        return self::$companyColumnCache[$cacheKey];
    }

    private static function hasDeletedAtColumn(string $table, string $connection): bool
    {
        $tableName = self::baseTableName($table);
        $cacheKey = $connection . ':' . $tableName;

        if (array_key_exists($cacheKey, self::$deletedAtColumnCache)) {
            return self::$deletedAtColumnCache[$cacheKey];
        }

        self::$deletedAtColumnCache[$cacheKey] = Schema::connection($connection)->hasColumn($tableName, 'deleted_at');

        return self::$deletedAtColumnCache[$cacheKey];
    }

    private static function prepareInsertPayload(string $table, array $values, ?string $connection): array
    {
        if (! self::shouldAttachCompanyIdToInsert($table, $connection)) {
            return $values;
        }

        $companyId = self::resolveCompanyId();
        if ($companyId === null) {
            return $values;
        }

        if (! self::isListOfRows($values)) {
            if (empty($values['company_id'])) {
                $values['company_id'] = $companyId;
            } elseif ($values['company_id'] === null) {
                $values['company_id'] = $companyId;
            }

            return $values;
        }

        foreach ($values as &$row) {
            if (! is_array($row)) {
                continue;
            }
            if (empty($row['company_id']) || $row['company_id'] === null) {
                $row['company_id'] = $companyId;
            }
        }
        unset($row);

        return $values;
    }

    private static function shouldAttachCompanyIdToInsert(string $table, ?string $connection): bool
    {
        $companyId = self::resolveCompanyId();
        if ($companyId === null || ! self::shouldApplyScope()) {
            return false;
        }

        $connectionName = $connection ?: (DB::getDefaultConnection() ?: config('database.default'));

        return self::hasCompanyIdColumn($table, $connectionName);
    }

    private static function isListOfRows(array $values): bool
    {
        if ($values === []) {
            return false;
        }

        return isset($values[0]) && is_array($values[0]);
    }

    private static function qualifiedCompanyColumn(string $table): string
    {
        return self::tableAlias($table) . '.company_id';
    }

    private static function qualifiedDeletedAtColumn(string $table): string
    {
        return self::tableAlias($table) . '.deleted_at';
    }

    private static function tableAlias(string $table): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $table) ?? $table);
        if (stripos($normalized, ' as ') !== false) {
            $parts = preg_split('/\s+as\s+/i', $normalized);

            return trim((string) end($parts));
        }

        $parts = preg_split('/\s+/', $normalized);
        if (count($parts) > 1) {
            return trim((string) end($parts));
        }

        return self::baseTableName($table);
    }

    private static function baseTableName(string $table): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $table) ?? $table);
        if (stripos($normalized, ' as ') !== false) {
            $parts = preg_split('/\s+as\s+/i', $normalized);

            return trim((string) ($parts[0] ?? $normalized));
        }

        $parts = preg_split('/\s+/', $normalized);

        return trim((string) ($parts[0] ?? $normalized));
    }
}
