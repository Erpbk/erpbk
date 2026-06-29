<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Re-apply database credentials at runtime (required on Laravel Cloud when config is cached).
 */
class DeployDatabaseConfig
{
    /** @var array<string, string>|null */
    protected static ?array $fileEnv = null;

    public static function refreshFromEnvironment(): void
    {
        $readEnv = static fn (string $key, mixed $default = null): mixed => self::readEnv($key, $default);

        if ($url = $readEnv('DATABASE_URL')) {
            config([
                'database.connections.mysql.url' => $url,
                'database.connections.mysql_central.url' => $url,
            ]);
        } else {
            // When config is cached at build time, a stale DATABASE_URL can override
            // runtime DB_* credentials injected by Laravel Cloud.
            config([
                'database.connections.mysql.url' => null,
                'database.connections.mysql_central.url' => null,
            ]);
        }

        foreach (self::credentialKeys('DB') as $configKey => $envKey) {
            $value = $readEnv($envKey);
            if ($value !== null && $value !== '') {
                config([
                    "database.connections.mysql.{$configKey}" => $value,
                    "database.connections.mysql_central.{$configKey}" => $value,
                ]);
            }
        }

        if ($url = $readEnv('ADMIN_DATABASE_URL')) {
            config([
                'database.connections.mysql_admin.url' => $url,
                'database.connections.admin.url' => $url,
            ]);
        } else {
            config([
                'database.connections.mysql_admin.url' => null,
                'database.connections.admin.url' => null,
            ]);
        }

        foreach (self::credentialKeys('ADMIN_DB', 'DB') as $configKey => $envKey) {
            [$primaryEnv, $fallbackEnv] = $envKey;
            $value = $readEnv($primaryEnv);
            if ($value === null || $value === '') {
                $value = $readEnv($fallbackEnv);
            }

            if ($value !== null && $value !== '') {
                config([
                    "database.connections.mysql_admin.{$configKey}" => $value,
                    "database.connections.admin.{$configKey}" => $value,
                ]);
            }
        }

        foreach (['mysql', 'mysql_central', 'mysql_admin', 'admin'] as $connection) {
            DB::purge($connection);
        }
    }

    protected static function readEnv(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            $value = $_ENV[$key];
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        if (array_key_exists($key, $_SERVER)) {
            $value = $_SERVER[$key];
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        $fileValue = self::fileEnv()[$key] ?? null;

        return ($fileValue !== null && $fileValue !== '') ? $fileValue : $default;
    }

    /**
     * @return array<string, string>
     */
    protected static function fileEnv(): array
    {
        if (self::$fileEnv !== null) {
            return self::$fileEnv;
        }

        self::$fileEnv = [];
        $path = base_path('.env');

        if (! is_readable($path)) {
            return self::$fileEnv;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return self::$fileEnv;
        }

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            self::$fileEnv[$name] = $value;
        }

        return self::$fileEnv;
    }

    public static function looksLikeUnresolvedDefaults(string $connection): bool
    {
        $summary = self::connectionSummary($connection);

        if (! empty($summary['url_set'])) {
            return false;
        }

        $host = (string) ($summary['host'] ?? '');
        $database = (string) ($summary['database'] ?? '');

        $localHosts = ['127.0.0.1', 'localhost', '::1'];
        $placeholderDatabases = ['forge', 'homestead', 'laravel'];

        return in_array($host, $localHosts, true)
            && in_array($database, $placeholderDatabases, true);
    }

    /**
     * @return array<string, string|array{0: string, 1: string}>
     */
    protected static function credentialKeys(string $prefix, ?string $fallbackPrefix = null): array
    {
        $map = [
            'host' => "{$prefix}_HOST",
            'port' => "{$prefix}_PORT",
            'database' => "{$prefix}_DATABASE",
            'username' => "{$prefix}_USERNAME",
            'password' => "{$prefix}_PASSWORD",
        ];

        if ($fallbackPrefix === null) {
            return $map;
        }

        $resolved = [];
        foreach ($map as $configKey => $envKey) {
            $resolved[$configKey] = [$envKey, str_replace($prefix, $fallbackPrefix, $envKey)];
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    public static function connectionSummary(string $connection): array
    {
        $config = config("database.connections.{$connection}", []);

        return [
            'connection' => $connection,
            'driver' => $config['driver'] ?? null,
            'url_set' => ! empty($config['url']),
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? null,
            'database' => $config['database'] ?? null,
            'username' => $config['username'] ?? null,
        ];
    }
}
