<?php

namespace App\Console\Commands;

use App\Exceptions\GlobalAccountNotConfiguredException;
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
            try {
                $accountId = $resolver->id($row->code);
            } catch (GlobalAccountNotConfiguredException $e) {
                $this->error("[{$row->code}] {$e->getMessage()}");
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
