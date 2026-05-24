<?php

namespace App\Console\Commands;

use App\Support\DeployDatabaseConfig;
use App\Support\MigrateConnectionRunner;
use Illuminate\Console\Command;

class MainMigrate extends Command
{
    protected $signature = 'main:migrate {--force : Force the operation to run in production}';
    protected $description = 'Run only main app migrations (database/migrations) on mysql.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->info('Running main migrations (database/migrations) on mysql ...');

        $exitCode = MigrateConnectionRunner::run(
            'mysql',
            'database/migrations',
            $force,
            $this->output
        );

        if ($exitCode !== 0) {
            $this->error('Main migrations failed.');
            return $exitCode;
        }

        $this->info('Main migrations complete.');
        return 0;
    }
}
