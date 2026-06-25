<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Retry database connections during deploy (Laravel Cloud ProxySQL can time out while MySQL warms up).
 */
class DeployDatabaseWaiter
{
    protected static ?Throwable $lastException = null;

    public static function lastException(): ?Throwable
    {
        return self::$lastException;
    }

    public static function waitForConnections(
        array $connections,
        int $maxAttempts = 5,
        int $sleepSeconds = 3,
        ?OutputInterface $output = null
    ): bool {
        DeployDatabaseConfig::refreshFromEnvironment();

        foreach ($connections as $connection) {
            if (! self::waitForConnection($connection, $maxAttempts, $sleepSeconds, $output)) {
                return false;
            }
        }

        return true;
    }

    public static function waitForConnection(
        string $connection,
        int $maxAttempts = 5,
        int $sleepSeconds = 3,
        ?OutputInterface $output = null
    ): bool {
        DeployDatabaseConfig::refreshFromEnvironment();
        self::$lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $line = "Waiting for database [{$connection}] (attempt {$attempt}/{$maxAttempts})...";
            self::deployLog($line);
            $output?->writeln($line);

            try {
                DB::connection($connection)->getPdo();
                DB::connection($connection)->select('SELECT 1');

                $success = "Database [{$connection}] is reachable.";
                self::deployLog($success);
                $output?->writeln("<info>{$success}</info>");

                return true;
            } catch (Throwable $exception) {
                self::$lastException = $exception;
                DB::purge($connection);

                $error = "Database [{$connection}] attempt {$attempt} failed: {$exception->getMessage()}";
                self::deployLog($error);
                $output?->writeln("<comment>{$error}</comment>");

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

        $lastError = self::$lastException?->getMessage();
        $hint = 'On Laravel Cloud: attach the MySQL database in the same region as your app, redeploy after attaching, '
            . 'ensure the cluster has free disk space, and remove any custom DB_* overrides that point to the wrong host.';

        if (($summary['host'] ?? null) === '127.0.0.1' || ($summary['host'] ?? null) === 'localhost') {
            $hint = 'DB_HOST is still localhost — remove custom DB_* overrides so Laravel Cloud can inject database credentials.';
        }

        $message = "Database connection [{$connection}] failed after retries ({$details}). {$hint}";
        if ($lastError) {
            $message .= " Last error: {$lastError}";
        }

        return $message;
    }

    public static function deployLog(string $message): void
    {
        error_log('[deploy-db] ' . $message);
    }
}
