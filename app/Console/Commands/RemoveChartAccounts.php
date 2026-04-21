<?php

namespace App\Console\Commands;

use App\Models\Accounts;
use Illuminate\Console\Command;

class RemoveChartAccounts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'accounts:remove-chart
                            {--company_id= : Remove only one company\'s accounts}
                            {--name=* : Remove accounts by name (repeatable, supports partial match)}
                            {--hard : Permanently delete instead of soft delete}
                            {--yes : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Remove accounts from Chart of Accounts (soft delete by default).';

    public function handle(): int
    {
        $companyId = $this->option('company_id');
        $names = collect((array) $this->option('name'))
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();
        $hard = (bool) $this->option('hard');

        $query = Accounts::query();
        if ($companyId !== null && $companyId !== '') {
            $query->where('company_id', (int) $companyId);
        }
        if ($names !== []) {
            $query->where(function ($q) use ($names) {
                foreach ($names as $term) {
                    $q->orWhere('name', 'like', '%' . $term . '%');
                }
            });
        }

        $count = (clone $query)->count();
        if ($count === 0) {
            $this->info('No matching accounts found.');
            return self::SUCCESS;
        }

        $scopeText = ($companyId !== null && $companyId !== '')
            ? "company_id={$companyId}"
            : 'all companies';
        if ($names !== []) {
            $scopeText .= '; names~[' . implode(', ', $names) . ']';
        }
        $modeText = $hard ? 'HARD DELETE (permanent)' : 'SOFT DELETE (recoverable via trash)';

        $this->warn("About to remove {$count} account(s) for {$scopeText} using {$modeText}.");
        if (!$this->option('yes') && !$this->confirm('Do you want to continue?')) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        if ($hard) {
            // Include already-soft-deleted rows when doing permanent purge.
            $affected = Accounts::withTrashed()
                ->when($companyId !== null && $companyId !== '', fn ($q) => $q->where('company_id', (int) $companyId))
                ->when($names !== [], function ($q) use ($names) {
                    $q->where(function ($nameQuery) use ($names) {
                        foreach ($names as $term) {
                            $nameQuery->orWhere('name', 'like', '%' . $term . '%');
                        }
                    });
                })
                ->forceDelete();
        } else {
            $affected = $query->delete();
        }

        $this->info("Done. {$affected} account(s) removed from Chart of Accounts.");

        return self::SUCCESS;
    }
}

