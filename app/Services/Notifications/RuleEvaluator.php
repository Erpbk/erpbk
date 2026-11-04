<?php

namespace App\Services\Notifications;

use App\Models\Company;
use App\Models\NotificationRule;
use App\Services\Notifications\Types\Contracts\ConditionEvaluator;
use App\Services\Notifications\Types\Contracts\ScheduledEvaluator;
use Illuminate\Support\Facades\Log;

class RuleEvaluator
{
    public function __construct(
        private readonly NotificationTypeRegistry $registry,
        private readonly NotificationRuleService $ruleService,
        private readonly NotificationEngine $engine,
    ) {
    }

    public function evaluateCompany(int $companyId, ?string $trigger = null): int
    {
        $this->ruleService->ensureDefaultsForCompany($companyId);
        $count = 0;

        $types = $trigger
            ? $this->registry->byTrigger($trigger)
            : array_merge(
                $this->registry->byTrigger('scheduled'),
                $this->registry->byTrigger('condition')
            );

        foreach ($types as $typeKey => $def) {
            $rule = NotificationRule::query()
                ->where('company_id', $companyId)
                ->where('type_key', $typeKey)
                ->first();

            if (! $rule || ! $rule->enabled) {
                continue;
            }

            $evaluatorClass = $def['evaluator'] ?? null;
            if (! $evaluatorClass || ! class_exists($evaluatorClass)) {
                continue;
            }

            /** @var ScheduledEvaluator|ConditionEvaluator $evaluator */
            $evaluator = app($evaluatorClass);

            try {
                foreach ($evaluator->evaluate($companyId, $rule) as $candidate) {
                    $created = $this->engine->dispatch([
                        'type_key' => $typeKey,
                        'company_id' => $companyId,
                        'source' => $candidate['source'],
                        'title' => $candidate['title'],
                        'body' => $candidate['body'],
                        'severity' => $candidate['severity'] ?? ($def['severity'] ?? 'info'),
                        'action_url' => $candidate['action_url'] ?? null,
                        'data' => $candidate['data'] ?? [],
                        'dedupe_context' => $candidate['dedupe_context'] ?? [],
                        'recipient_context' => $candidate['recipient_context'] ?? [],
                    ]);
                    $count += count($created);
                }
            } catch (\Throwable $e) {
                Log::error('notification.rule_evaluation_failed', [
                    'company_id' => $companyId,
                    'type_key' => $typeKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /** @return list<int> */
    public function approvedCompanyIds(): array
    {
        return Company::query()
            ->where('status', Company::STATUS_APPROVED)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
