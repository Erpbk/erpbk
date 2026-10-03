<?php

namespace App\Services\Notifications\Types\Cheques;

use App\Models\Cheques;
use App\Models\Company;
use App\Models\NotificationRule;
use App\Services\Notifications\Types\Contracts\ConditionEvaluator;
use Carbon\Carbon;

class ChequeOverdueEvaluator implements ConditionEvaluator
{
    public function evaluate(int $companyId, NotificationRule $rule): iterable
    {
        $config = is_array($rule->config) ? $rule->config : [];
        $statuses = $config['statuses'] ?? ['Issued'];
        if (! is_array($statuses) || $statuses === []) {
            $statuses = ['Issued'];
        }

        $company = Company::query()->find($companyId);
        $slug = $company?->slug;
        $today = Carbon::today()->toDateString();

        $cheques = Cheques::query()
            ->where('company_id', $companyId)
            ->whereIn('status', $statuses)
            ->whereNotNull('cheque_date')
            ->whereDate('cheque_date', '<', $today)
            ->get();

        foreach ($cheques as $cheque) {
            $due = Carbon::parse($cheque->cheque_date)->format('d-M-Y');
            $dueKey = Carbon::parse($cheque->cheque_date)->toDateString();
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

            yield [
                'source' => $cheque,
                'title' => 'Cheque Overdue',
                'body' => "Cheque {$number} for PKR {$amount} was due on {$due} and is still pending.",
                'severity' => 'critical',
                'action_url' => $actionUrl,
                'data' => array_merge([
                    'cheque_id' => $cheque->id,
                    'cheque_number' => $cheque->cheque_number,
                    'amount' => $cheque->amount,
                    'cheque_date' => $dueKey,
                ], $actionMeta),
                // One overdue notice per due-date (idempotent across daily runs)
                'dedupe_context' => [
                    'overdue' => $dueKey,
                ],
                'recipient_context' => [
                    'created_by' => $cheque->created_by,
                    'owner_id' => $cheque->created_by,
                ],
            ];
        }
    }
}
