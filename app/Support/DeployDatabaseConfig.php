<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Re-apply database credentials at runtime (required on Laravel Cloud when config is cached).
 */
class DeployDatabaseConfig
{
    public static function refreshFromEnvironment(): void
    {
        $readEnv = static function (string $key, mixed $default = null): mixed {
            if (array_key_exists($key, $_ENV)) {
                return $_ENV[$key];
            }

            if (array_key_exists($key, $_SERVER)) {
                return $_SERVER[$key];
            }

            $value = getenv($key);

            return $value !== false ? $value : $default;
        };

        if ($url = $readEnv('DATABASE_URL')) {
            config([
                'database.connections.mysql.url' => $url,
                'database.connections.mysql_central.url' => $url,
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
