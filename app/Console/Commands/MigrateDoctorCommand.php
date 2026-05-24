<?php

namespace App\Console\Commands;

use App\Support\DeployDatabaseConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MigrateDoctorCommand extends Command
{
    protected $signature = 'app:migrate-doctor';
    protected $description = 'Show which database migrations will run against and what is still pending.';

    public function handle(): int
    {
        DeployDatabaseConfig::refreshFromEnvironment();

        $this->info('Migration doctor');
        $this->line('APP_ENV: ' . config('app.env'));
        if (config('app.env') !== 'production' && str_contains((string) config('database.connections.mysql.host'), 'laravel.cloud')) {
            $this->warn('APP_ENV is not "production" on Laravel Cloud — set APP_ENV=production in environment variables.');
        }
        $this->line('Default connection: ' . config('database.default'));
        $this->line('Config cached: ' . (file_exists(base_path('bootstrap/cache/config.php')) ? 'yes' : 'no'));
        $this->newLine();

        foreach (['mysql', 'mysql_admin'] as $connection) {
            $this->inspectConnection($connection);
            $this->newLine();
        }

        return self::SUCCESS;
    }

    protected function inspectConnection(string $connection): void
    {
        $summary = DeployDatabaseConfig::connectionSummary($connection);
        $path = $connection === 'mysql_admin' ? 'database/migrations_admin' : 'database/migrations';

        $this->comment(strtoupper($connection) . " ({$path})");
        $this->table(
            ['Key', 'Value'],
            collect($summary)->map(fn ($value, $key) => [$key, is_bool($value) ? ($value ? 'true' : 'false') : (string) ($value ?? '')])->values()->all()
        );

        try {
            $databaseName = DB::connection($connection)->getDatabaseName();
            $this->line("Connected database: {$databaseName}");
            $this->line('Connected host: ' . DB::connection($connection)->getConfig('host'));

            $ran = DB::connection($connection)->table('migrations')->count();
            $this->line("Recorded migrations: {$ran}");

            $files = collect(File::glob(base_path($path . '/*.php')))
                ->map(fn ($file) => pathinfo($file, PATHINFO_FILENAME))
                ->sort()
                ->values();

            $ranNames = DB::connection($connection)
                ->table('migrations')
                ->pluck('migration');

            $ranLookup = $ranNames->flip();
            $pending = $files->reject(fn ($name) => $ranLookup->has($name))->values();
            $orphaned = $ranNames->reject(fn ($name) => $files->contains($name))->values();

            $this->line('Migration files on disk: ' . $files->count());
            $this->line('Pending migrations: ' . $pending->count());

            if ($pending->isNotEmpty()) {
                $this->warn('Pending (on disk but not recorded in DB):');
                $pending->take(15)->each(fn ($name) => $this->line("  - {$name}"));
                if ($pending->count() > 15) {
                    $this->line('  ... and ' . ($pending->count() - 15) . ' more');
                }
            } elseif ($orphaned->isNotEmpty()) {
                $this->warn('Recorded in DB but migration file missing on disk (' . $orphaned->count() . '):');
                $orphaned->take(10)->each(fn ($name) => $this->line("  - {$name}"));
                $this->line('These were already applied — no re-run needed unless schema is wrong.');
            } else {
                $this->info('All migration files are recorded. Schema should be up to date.');
            }
        } catch (\Throwable $e) {
            $this->error('Could not inspect connection: ' . $e->getMessage());
        }
    }
}
