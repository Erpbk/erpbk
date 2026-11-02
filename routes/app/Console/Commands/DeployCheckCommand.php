<?php

namespace App\Console\Commands;

use App\Support\DeployDatabaseConfig;
use App\Support\DeployDatabaseWaiter;
use App\Support\PublicStorageDisk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeployCheckCommand extends Command
{
    protected $signature = 'app:deploy-check';
    protected $description = 'Verify database connectivity and env for Laravel Cloud deploys.';

    public function handle(): int
    {
        DeployDatabaseConfig::refreshFromEnvironment();

        $this->info('Deploy database check');
        $this->line('APP_ENV: ' . config('app.env'));
        $this->line('Config cached: ' . (file_exists(base_path('bootstrap/cache/config.php')) ? 'yes' : 'no'));
        DeployDatabaseWaiter::deployLog('APP_ENV=' . config('app.env'));
        $this->newLine();

        $failed = false;

        foreach (['mysql', 'mysql_admin'] as $connection) {
            $summary = DeployDatabaseConfig::connectionSummary($connection);
            $this->comment(strtoupper($connection));
            $this->table(
                ['Key', 'Value'],
                collect($summary)
                    ->map(fn ($value, $key) => [$key, is_bool($value) ? ($value ? 'true' : 'false') : (string) ($value ?? '')])
                    ->values()
                    ->all()
            );
            DeployDatabaseWaiter::deployLog($connection . ' summary: ' . json_encode($summary));

            try {
                DB::connection($connection)->getPdo();
                DB::connection($connection)->select('SELECT 1');
                $this->info("{$connection}: connected OK");
                DeployDatabaseWaiter::deployLog("{$connection}: connected OK");
            } catch (Throwable $exception) {
                $failed = true;
                $message = "{$connection}: {$exception->getMessage()}";
                $this->error($message);
                DeployDatabaseWaiter::deployLog($message);
            }

            $this->newLine();
        }

        if ($failed) {
            $this->error('Deploy check failed. Fix database attachment / env vars before migrating.');
            return self::FAILURE;
        }

        if (PublicStorageDisk::isEphemeralLocal()) {
            $this->warn('Public uploads use local disk in production (AWS_BUCKET is not set).');
            $this->warn('Uploaded files (logos, attachments) will disappear when the app container is replaced.');
            $this->warn('Attach Laravel Cloud Object Storage and set AWS_BUCKET + AWS_URL on the environment.');
        } elseif (PublicStorageDisk::isCloud()) {
            $this->info('Public uploads: object storage (S3/R2) configured.');
        } else {
            $this->info('Public uploads: local disk (development).');
        }

        $sessionDriver = config('session.driver');
        $cacheDriver = config('cache.default');
        if (app()->environment('production')) {
            if ($sessionDriver === 'file') {
                $this->warn('SESSION_DRIVER=file in production causes stale-session proxy errors on Laravel Cloud.');
                $this->warn('Set SESSION_DRIVER=database (or redis) on the environment.');
            } else {
                $this->info("Sessions: {$sessionDriver} driver (OK for cloud).");
            }

            if ($cacheDriver === 'file') {
                $this->warn('CACHE_DRIVER=file in production is ephemeral on Laravel Cloud.');
                $this->warn('Set CACHE_DRIVER=redis or CACHE_DRIVER=database on the environment.');
            } else {
                $this->info("Cache: {$cacheDriver} driver (OK for cloud).");
            }
        }

        $this->newLine();
        $this->info('Deploy check passed.');
        return self::SUCCESS;
    }
}
