<?php

namespace App\Console\Commands;

use App\Support\MigrateConnectionRunner;
use Illuminate\Console\Command;

class AdminMigrate extends Command
{
    protected $signature = 'admin:migrate {--force : Force the operation to run in production}';
    protected $description = 'Run migrations_admin against the mysql_admin database connection.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->info('Running admin migrations (database/migrations_admin) ...');

        $exitCode = MigrateConnectionRunner::run(
            'mysql_admin',
            'database/migrations_admin',
            $force,
            $this->output
        );

        if ($exitCode !== 0) {
            $this->error('Admin migrations failed.');
            return $exitCode;
        }

        $this->info('Admin migrations complete.');
        return 0;
    }
}
