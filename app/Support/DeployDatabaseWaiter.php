<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Retry database connections during deploy (Laravel Cloud ProxySQL can time out while MySQL warms up).
 */
class DeployDatabaseWaiter
{
    public static function waitForConnections(
        array $connections,
        int $maxAttempts = 12,
        int $sleepSeconds = 5
    ): bool {
        DeployDatabaseConfig::refreshFromEnvironment();

        foreach ($connections as $connection) {
            if (! self::waitForConnection($connection, $maxAttempts, $sleepSeconds)) {
                return false;
            }
        }

        return true;
    }

    public static function waitForConnection(
        string $connection,
        int $maxAttempts = 12,
        int $sleepSeconds = 5
    ): bool {
        DeployDatabaseConfig::refreshFromEnvironment();

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                DB::connection($connection)->getPdo();
                DB::connection($connection)->select('SELECT 1');

                return true;
            } catch (Throwable) {
                DB::purge($connection);

                if ($attempt < $maxAttempts) {
                    sleep($sleepSeconds);
                }
            }
        }

        return false;
    }

    public static function connectionFailureMessage(string $connection): string
    {
        $summary = DeployDatabaseConfig::connectionSummary($connection);
        $details = collect($summary)
            ->map(fn ($value, $key) => $key . '=' . (is_bool($value) ? ($value ? 'true' : 'false') : (string) ($value ?? 'null')))
            ->implode(', ');

        return "Database connection [{$connection}] failed after retries ({$details}). "
            . 'On Laravel Cloud: attach the MySQL database in the same region as your app, redeploy after attaching, '
            . 'ensure the cluster has free disk space, and remove any custom DB_* overrides that point to the wrong host.';
    }
}
