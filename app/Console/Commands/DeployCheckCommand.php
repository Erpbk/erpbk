<?php

namespace App\Console\Commands;

use App\Support\DeployDatabaseConfig;
use App\Support\DeployDatabaseWaiter;
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

        $this->info('Deploy check passed.');
        return self::SUCCESS;
    }
}
