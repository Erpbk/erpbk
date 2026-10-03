<?php

namespace App\Services\Notifications\Types\Cheques;

use App\Models\Cheques;
use App\Models\Company;
use App\Models\NotificationRule;
use App\Services\Notifications\Types\Contracts\ScheduledEvaluator;
use Carbon\Carbon;

class ChequeDueSoonEvaluator implements ScheduledEvaluator
{
    public function evaluate(int $companyId, NotificationRule $rule): iterable
    {
        $config = is_array($rule->config) ? $rule->config : [];
        $daysBefore = $config['days_before'] ?? [3];
        if (! is_array($daysBefore) || $daysBefore === []) {
            $daysBefore = [3];
        }
        $daysBefore = array_values(array_unique(array_map('intval', $daysBefore)));
        $statuses = $config['statuses'] ?? ['Issued'];
        if (! is_array($statuses) || $statuses === []) {
            $statuses = ['Issued'];
        }

        $company = Company::query()->find($companyId);
        $slug = $company?->slug;

        foreach ($daysBefore as $days) {
            $targetDate = Carbon::today()->addDays($days)->toDateString();

            $cheques = Cheques::query()
                ->where('company_id', $companyId)
                ->whereIn('status', $statuses)
                ->whereDate('cheque_date', $targetDate)
                ->get();

            foreach ($cheques as $cheque) {
                $due = $cheque->cheque_date
                    ? Carbon::parse($cheque->cheque_date)->format('d-M-Y')
                    : $targetDate;
                $amount = number_format((float) $cheque->amount, 2);
                $number = $cheque->cheque_number ?: ('#'.$cheque->id);

                $actionUrl = null;
                $actionMeta = [];
                if ($slug) {
                    $actionUrl = route('cheques.show', [
                        'company_slug' => $slug,
                        'cheque' => $cheque->id,
                    ], false);
                    $actionMeta = [
                        'action_mode' => 'modal',
                        'action_size' => 'xl',
                        'action_title' => 'Cheque Details',
                    ];
                }

                $isPayable = ($cheque->type ?? 'payable') === 'payable';
                $typeLabel = $isPayable ? 'Payable' : 'Receivable';

                if ($days === 0) {
                    $title = $isPayable ? 'Cheque Due Today' : 'Receivable Cheque Due Today';
                } elseif ($days === 1) {
                    $title = $isPayable ? 'Cheque Due Tomorrow' : 'Receivable Cheque Due Tomorrow';
                } else {
                    $title = $isPayable
                        ? "Cheque Due in {$days} Days"
                        : "Receivable Cheque Due in {$days} Days";
                }

                $body = $isPayable
                    ? ($days === 0
                        ? "Cheque {$number} for PKR {$amount} is due today ({$due}). Please ensure sufficient funds are available."
                        : "Cheque {$number} for PKR {$amount} is due on {$due}. Please ensure sufficient funds are available.")
                    : ($days === 0
                        ? "Receivable cheque {$number} for PKR {$amount} is due today ({$due}). Please follow up to collect or deposit it."
                        : "Receivable cheque {$number} for PKR {$amount} is due on {$due}. Please follow up to collect or deposit it.");

                yield [
                    'source' => $cheque,
                    'title' => $title,
                    'body' => $body,
                    'severity' => $days <= 1 ? 'critical' : 'warning',
                    'action_url' => $actionUrl,
                    'data' => array_merge([
                        'cheque_id' => $cheque->id,
                        'cheque_number' => $cheque->cheque_number,
                        'amount' => $cheque->amount,
                        'cheque_date' => $targetDate,
                        'days_before' => $days,
                        'cheque_type' => $cheque->type,
                        'cheque_type_label' => $typeLabel,
                    ], $actionMeta),
                    'dedupe_context' => [
                        'days' => $days,
                        'due' => $targetDate,
                    ],
                    'recipient_context' => [
                        'created_by' => $cheque->created_by,
                        'owner_id' => $cheque->created_by,
                    ],
                ];
            }
        }
    }
}
