<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AdminMigrate extends Command
{
    protected $signature = 'admin:migrate';
    protected $description = 'Run migrations_admin against the mysql_admin database connection.';

    public function handle(): int
    {
        $this->info('Running admin migrations (database/migrations_admin) ...');

        Artisan::call('migrate', [
            '--database' => 'mysql_admin',
            '--path' => 'database/migrations_admin',
            '--force' => true,
        ]);

        $this->output->writeln(Artisan::output());

        $this->info('Admin migrations complete.');
        return 0;
    }
}

