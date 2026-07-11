<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Schema;

class DocumentExpiry
{
    public const DEFAULT_WINDOW_DAYS = 10;

    public static function windowDays(?int $days = null): int
    {
        $days = $days ?? self::DEFAULT_WINDOW_DAYS;

        return max(1, min(90, $days));
    }

    public static function baseQuery(): Builder
    {
        return company_table('files')->whereNotNull('expiry_date');
    }

    /**
     * Tenant-scoped files query limited to types the user may access.
     */
    public static function scopedQuery(?\App\Models\User $user = null): Builder
    {
        $query = self::baseQuery();
        if ($user === null) {
            return $query;
        }

        $types = DocumentExpiryDashboard::allowedFileTypesForUser($user);
        $includeGeneral = DocumentExpiryDashboard::userCanAccessSource($user, 'documents');

        if ($types === [] && ! $includeGeneral) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($types, $includeGeneral) {
            if ($types !== []) {
                $q->whereIn('type', $types);
            }
            if ($includeGeneral) {
                $method = $types !== [] ? 'orWhere' : 'where';
                $q->{$method}(function ($inner) {
                    $inner->whereNull('type')
                        ->orWhere('type', '')
                        ->orWhere('type', 'documents');
                });
            }
        });
    }

    public static function expiringCount(?int $days = null, ?\App\Models\User $user = null): int
    {
        if (! Schema::hasTable('files') || ! Schema::hasColumn('files', 'expiry_date')) {
            return 0;
        }

        $days = self::windowDays($days);
        $today = now()->startOfDay()->toDateString();
        $end = now()->addDays($days)->startOfDay()->toDateString();

        return (int) self::applyFilter(self::scopedQuery($user), 'expiring', $days)->count();
    }

    public static function expiredCount(?\App\Models\User $user = null): int
    {
        if (! Schema::hasTable('files') || ! Schema::hasColumn('files', 'expiry_date')) {
            return 0;
        }

        return (int) self::applyFilter(self::scopedQuery($user), 'expired')->count();
    }

    public static function applyFilter(Builder $query, string $filter, ?int $days = null): Builder
    {
        $days = self::windowDays($days);
        $today = now()->startOfDay()->toDateString();
        $end = now()->addDays($days)->startOfDay()->toDateString();

        if ($filter === 'expired') {
            return $query->whereDate('expiry_date', '<', $today);
        }

        if ($filter === 'expiring') {
            return $query
                ->whereDate('expiry_date', '>=', $today)
                ->whereDate('expiry_date', '<=', $end);
        }

        return $query;
    }

    public static function expiringCountForUser(?\App\Models\User $user, ?int $days = null): int
    {
        return self::expiringCount($days, $user) + self::bikeRegistrationExpiryCount($user, 'expiring', $days);
    }

    public static function expiredCountForUser(?\App\Models\User $user): int
    {
        return self::expiredCount($user) + self::bikeRegistrationExpiryCount($user, 'expired');
    }

    protected static function bikeRegistrationExpiryCount(?\App\Models\User $user, string $filter, ?int $days = null): int
    {
        if (! DocumentExpiryDashboard::userCanAccessSource($user, 'bike_registration')) {
            return 0;
        }
        if (! Schema::hasTable('bike_registrations') || ! Schema::hasColumn('bike_registrations', 'expiry_date')) {
            return 0;
        }

        $query = company_table('bike_registrations')->whereNotNull('expiry_date');
        $today = now()->startOfDay()->toDateString();
        $end = now()->addDays(self::windowDays($days))->startOfDay()->toDateString();

        if ($filter === 'expired') {
            return (int) $query->whereDate('expiry_date', '<', $today)->count();
        }

        return (int) $query
            ->whereDate('expiry_date', '>=', $today)
            ->whereDate('expiry_date', '<=', $end)
            ->count();
    }
}
