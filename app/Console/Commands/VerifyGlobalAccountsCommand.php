<?php

namespace App\Console\Commands;

use App\Models\Accounts;
use App\Models\GlobalAccount;
use App\Services\GlobalAccountResolver;
use Illuminate\Console\Command;

class VerifyGlobalAccountsCommand extends Command
{
    protected $signature = 'global-accounts:verify {--code= : Verify a single global account code}';

    protected $description = 'Verify that active global account codes resolve to existing chart accounts';

    public function handle(GlobalAccountResolver $resolver): int
    {
        $codeFilter = $this->option('code');

        $query = GlobalAccount::query()->where('is_active', true)->orderBy('code');

        if ($codeFilter) {
            $query->where('code', $codeFilter);
        }

        $rows = $query->get();
        $failures = 0;

        foreach ($rows as $row) {
            $accountId = $resolver->idOrNull($row->code);

            if ($accountId === null) {
                $this->error("[{$row->code}] not configured (missing or inactive mapping).");
                $failures++;

                continue;
            }

            $exists = Accounts::withoutGlobalScopes(['company', 'branch'])->where('id', $accountId)->exists();

            if (! $exists) {
                $this->error("[{$row->code}] maps to missing account ID {$accountId}.");
                $failures++;

                continue;
            }

            $this->line("[{$row->code}] → account #{$accountId} ({$row->label})");
        }

        if ($rows->isEmpty()) {
            $this->warn('No active global accounts found.');

            return self::FAILURE;
        }

        if ($failures > 0) {
            $this->newLine();
            $this->error("{$failures} global account(s) failed verification. Configure them in Admin → Global Accounts.");

            return self::FAILURE;
        }

        $this->info('All active global accounts verified successfully.');

        return self::SUCCESS;
    }
}
