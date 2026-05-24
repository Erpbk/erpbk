<?php

namespace App\Console\Commands;

use App\Support\DeployDatabaseConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateAllDatabases extends Command
{
    protected $signature = 'app:migrate-all {--force : Force the operation to run in production}';
    protected $description = 'Run central and admin database migrations in sequence.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        DeployDatabaseConfig::refreshFromEnvironment();

        $this->info('Running central migrations (mysql / database/migrations)...');
        $centralExitCode = Artisan::call('main:migrate', [
            '--force' => $force,
        ]);
        $this->output->writeln(Artisan::output());

        if ($centralExitCode !== 0) {
            $this->error('Central migrations failed. Admin migrations were skipped.');
            return $centralExitCode;
        }

        $this->info('Running admin migrations...');
        $adminExitCode = Artisan::call('admin:migrate', [
            '--force' => $force,
        ]);
        $this->output->writeln(Artisan::output());

        if ($adminExitCode !== 0) {
            $this->error('Admin migrations failed.');
            return $adminExitCode;
        }

        $this->info('Central and admin migrations completed successfully.');

        return 0;
    }
}
