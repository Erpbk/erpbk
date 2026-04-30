<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MainMigrate extends Command
{
    protected $signature = 'main:migrate {--force : Force the operation to run in production}';
    protected $description = 'Run only main app migrations (database/migrations) on mysql.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->info('Running main migrations (database/migrations) on mysql ...');

        $exitCode = Artisan::call('migrate', [
            '--database' => 'mysql',
            '--path' => 'database/migrations',
            '--force' => $force,
        ]);

        $this->output->writeln(Artisan::output());

        if ($exitCode !== 0) {
            $this->error('Main migrations failed.');
            return $exitCode;
        }

        $this->info('Main migrations complete.');
        return 0;
    }
}

