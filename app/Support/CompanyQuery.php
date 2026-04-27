<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompanyQuery
{
    /**
     * @var array<string, bool>
     */
    private static array $companyColumnCache = [];

    public static function table(string $table, ?string $connection = null): Builder
    {
        $query = $connection
            ? DB::connection($connection)->table($table)
            : DB::table($table);

        $companyId = self::resolveCompanyId();
        if ($companyId === null || !self::shouldApplyScope()) {
            return $query;
        }

        $connectionName = $connection ?: (DB::getDefaultConnection() ?: config('database.default'));
        if (!self::hasCompanyIdColumn($table, $connectionName)) {
            return $query;
        }

        return $query->where(self::qualifiedCompanyColumn($table), $companyId);
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

    private static function shouldApplyScope(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        if (Auth::guard('admin')->check()) {
            return false;
        }

        $request = request();
        if (!$request) {
            return false;
        }

        if ($request->is('admin/*')) {
            return false;
        }

        return true;
    }

    private static function resolveCompanyId(): ?int
    {
        $request = request();
        $company = $request?->attributes->get('company');
        if ($company && isset($company->id)) {
            return (int) $company->id;
        }

        $authUser = Auth::user();
        if ($authUser && !empty($authUser->company_id)) {
            return (int) $authUser->company_id;
        }

        $companySlug = $request?->route('company_slug') ?? $request?->session()->get('company_slug');
        if (empty($companySlug)) {
            return null;
        }

        $resolvedCompany = Company::query()->where('slug', (string) $companySlug)->first();
        if (!$resolvedCompany && is_numeric($companySlug)) {
            $resolvedCompany = Company::query()->find((int) $companySlug);
        }

        if (!$resolvedCompany) {
            return null;
        }

        $request?->attributes->set('company', $resolvedCompany);

        return (int) $resolvedCompany->id;
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

    private static function prepareInsertPayload(string $table, array $values, ?string $connection): array
    {
        if (!self::shouldAttachCompanyIdToInsert($table, $connection)) {
            return $values;
        }

        $companyId = self::resolveCompanyId();
        if ($companyId === null) {
            return $values;
        }

        if (!self::isListOfRows($values)) {
            if (empty($values['company_id'])) {
                $values['company_id'] = $companyId;
            }

            return $values;
        }

        foreach ($values as &$row) {
            if (is_array($row) && empty($row['company_id'])) {
                $row['company_id'] = $companyId;
            }
        }
        unset($row);

        return $values;
    }

    private static function shouldAttachCompanyIdToInsert(string $table, ?string $connection): bool
    {
        $companyId = self::resolveCompanyId();
        if ($companyId === null || !self::shouldApplyScope()) {
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
