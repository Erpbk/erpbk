<?php

namespace App\Jobs\Notifications;

use App\Services\Notifications\RuleEvaluator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EvaluateCompanyNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $companyId)
    {
    }

    public function handle(RuleEvaluator $evaluator): void
    {
        $created = $evaluator->evaluateCompany($this->companyId);
        Log::info('notification.company_evaluated', [
            'company_id' => $this->companyId,
            'created' => $created,
        ]);
    }
}
