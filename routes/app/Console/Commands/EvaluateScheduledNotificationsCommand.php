<?php

namespace App\Console\Commands;

use App\Jobs\Notifications\EvaluateCompanyNotificationsJob;
use App\Services\Notifications\RuleEvaluator;
use Illuminate\Console\Command;

class EvaluateScheduledNotificationsCommand extends Command
{
    protected $signature = 'notifications:evaluate-scheduled
                            {--company= : Limit to a single company id}
                            {--sync : Run inline instead of queueing per company}';

    protected $description = 'Evaluate scheduled and condition notification rules for approved companies';

    public function handle(RuleEvaluator $evaluator): int
    {
        $companyOpt = $this->option('company');
        $companyIds = $companyOpt
            ? [(int) $companyOpt]
            : $evaluator->approvedCompanyIds();

        if ($companyIds === []) {
            $this->info('No companies to evaluate.');

            return self::SUCCESS;
        }

        $sync = (bool) $this->option('sync');
        $total = 0;

        foreach ($companyIds as $companyId) {
            if ($sync) {
                $created = $evaluator->evaluateCompany($companyId);
                $total += $created;
                $this->line("Company {$companyId}: created {$created}");
            } else {
                EvaluateCompanyNotificationsJob::dispatch($companyId)->onQueue('notifications');
                $this->line("Queued company {$companyId}");
            }
        }

        if ($sync) {
            $this->info("Done. Created {$total} notification(s).");
        } else {
            $this->info('Queued '.count($companyIds).' company evaluation job(s).');
        }

        return self::SUCCESS;
    }
}
