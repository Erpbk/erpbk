<?php

namespace App\Console\Commands;

use App\Support\CompanyIdColumnEnsurer;
use Illuminate\Console\Command;

class EnsureCompanyIdColumns extends Command
{
    protected $signature = 'company:ensure-columns
                            {--connection= : Database connection name (default: app default)}
                            {--dry-run : Report changes without altering the database}
                            {--check : Only list tables missing company_id}';

    protected $description = 'Add company_id to all tenant tables that are missing it (idempotent, safe to re-run)';

    public function handle(CompanyIdColumnEnsurer $ensurer): int
    {
        $connection = $this->option('connection') ?: config('database.default');

        if ($this->option('check')) {
            $missing = $ensurer->tablesMissingCompanyId($connection);
            if ($missing === []) {
                $this->info('All tenant tables have a company_id column on connection [' . $connection . '].');

                return self::SUCCESS;
            }

            $this->warn(count($missing) . ' table(s) missing company_id:');
            foreach ($missing as $table) {
                $this->line('  - ' . $table);
            }

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->info('Dry run — no schema changes will be applied.');
        }

        $this->info('Ensuring company_id on all tenant tables (connection: ' . $connection . ')...');

        $report = $ensurer->run($connection, $dryRun);

        if (! empty($report['column_added'])) {
            $this->info('Columns added: ' . count($report['column_added']));
            foreach ($report['column_added'] as $table) {
                $this->line('  + ' . $table);
            }
        }

        if (! empty($report['backfilled'])) {
            $this->info('Rows backfilled:');
            foreach ($report['backfilled'] as $line) {
                $this->line('  * ' . $line);
            }
        }

        if (! empty($report['fk_added'])) {
            $this->info('Foreign keys added: ' . count($report['fk_added']));
        }

        if (! empty($report['errors'])) {
            $this->warn(count($report['errors']) . ' step(s) failed (column may still have been added):');
            foreach ($report['errors'] as $error) {
                $this->line("  ! {$error['table']} [{$error['step']}]: {$error['message']}");
            }
        }

        $stillMissing = $ensurer->tablesMissingCompanyId($connection);
        if ($stillMissing !== []) {
            $this->error('Still missing company_id on: ' . implode(', ', $stillMissing));

            return self::FAILURE;
        }

        $this->info('Done. Processed ' . $report['processed'] . ' table(s). All tenant tables have company_id.');

        return self::SUCCESS;
    }
}
