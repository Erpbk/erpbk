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

            $ran = DB::connection($connection)->table('migrations')->count();
            $this->line("Recorded migrations: {$ran}");

            $files = collect(File::glob(base_path($path . '/*.php')))
                ->map(fn ($file) => pathinfo($file, PATHINFO_FILENAME))
                ->sort()
                ->values();

            $ranNames = DB::connection($connection)
                ->table('migrations')
                ->pluck('migration')
                ->flip();

            $pending = $files->reject(fn ($name) => $ranNames->has($name))->values();

            $this->line('Migration files on disk: ' . $files->count());
            $this->line('Pending migrations: ' . $pending->count());

            if ($pending->isNotEmpty()) {
                $this->warn('Pending:');
                $pending->take(15)->each(fn ($name) => $this->line("  - {$name}"));
                if ($pending->count() > 15) {
                    $this->line('  ... and ' . ($pending->count() - 15) . ' more');
                }
            } else {
                $this->info('Nothing pending for this connection/path.');
            }
        } catch (\Throwable $e) {
            $this->error('Could not inspect connection: ' . $e->getMessage());
        }
    }
}
