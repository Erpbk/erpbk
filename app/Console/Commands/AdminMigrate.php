<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AdminMigrate extends Command
{
    protected $signature = 'admin:migrate {--force : Force the operation to run in production}';
    protected $description = 'Run migrations_admin against the mysql_admin database connection.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->info('Running admin migrations (database/migrations_admin) ...');

        $exitCode = Artisan::call('migrate', [
            '--database' => 'mysql_admin',
            '--path' => 'database/migrations_admin',
            '--force' => $force,
        ]);

        $this->output->writeln(Artisan::output());

        if ($exitCode !== 0) {
            $this->error('Admin migrations failed.');
            return $exitCode;
        }

        $this->info('Admin migrations complete.');
        return 0;
    }
}

