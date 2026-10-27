<?php

namespace App\Services\Notifications\Types\Cheques;

use App\Helpers\Currency;
use App\Models\Cheques;
use App\Models\Company;
use App\Models\NotificationRule;
use App\Services\Notifications\Types\Contracts\ConditionEvaluator;
use Carbon\Carbon;

class ChequeOverdueEvaluator implements ConditionEvaluator
{
    /** @var list<string> */
    private const INTERVALS = ['once', 'every_3_days', 'weekly', 'monthly'];

    public function evaluate(int $companyId, NotificationRule $rule): iterable
    {
        $config = is_array($rule->config) ? $rule->config : [];
        $statuses = $config['statuses'] ?? ['Issued'];
        if (! is_array($statuses) || $statuses === []) {
            $statuses = ['Issued'];
        }

        $intervals = $config['remind_intervals'] ?? ['once'];
        if (! is_array($intervals) || $intervals === []) {
            $intervals = ['once'];
        }
        $intervals = array_values(array_intersect(self::INTERVALS, array_map('strval', $intervals)));
        if ($intervals === []) {
            $intervals = ['once'];
        }

        $company = Company::query()->find($companyId);
        $slug = $company?->slug;
        $currency = Currency::code($companyId);
        $today = Carbon::today()->startOfDay();

        $cheques = Cheques::query()
            ->where('company_id', $companyId)
            ->whereIn('status', $statuses)
            ->where(function ($q) {
                $q->where('is_security', false)->orWhereNull('is_security');
            })
            ->whereNotNull('cheque_date')
            ->whereDate('cheque_date', '<', $today->toDateString())
            ->get();

        foreach ($cheques as $cheque) {
            $dueDate = Carbon::parse($cheque->cheque_date)->startOfDay();
            $due = $dueDate->format('d-M-Y');
            $dueKey = $dueDate->toDateString();
            $daysOverdue = (int) $dueDate->diffInDays($today);
            if ($daysOverdue < 1) {
                continue;
            }

            $amount = number_format((float) $cheque->amount, 2);
            $number = $cheque->cheque_number ?: ('#'.$cheque->id);
            $search = $cheque->cheque_number ?: (string) $cheque->id;

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
                    'email_action_url' => route('cheques.index', [
                        'company_slug' => $slug,
                        'quick_search' => $search,
                    ], false),
                ];
            }

            $baseData = array_merge([
                'cheque_id' => $cheque->id,
                'cheque_number' => $cheque->cheque_number,
                'amount' => $cheque->amount,
                'cheque_date' => $dueKey,
                'days_overdue' => $daysOverdue,
            ], $actionMeta);

            $recipientContext = [
                'created_by' => $cheque->created_by,
                'owner_id' => $cheque->created_by,
            ];

            foreach ($intervals as $interval) {
                $occurrence = $this->occurrenceFor($interval, $daysOverdue);
                if ($occurrence === null) {
                    continue;
                }

                [$title, $body] = $this->messageFor(
                    $interval,
                    $number,
                    $amount,
                    $due,
                    $daysOverdue,
                    $currency
                );

                $dedupe = ['overdue' => $dueKey];
                // Keep legacy fingerprint for "once" so existing notices stay deduped.
                if ($interval !== 'once') {
                    $dedupe['interval'] = $interval;
                    $dedupe['n'] = $occurrence;
                }

                yield [
                    'source' => $cheque,
                    'title' => $title,
                    'body' => $body,
                    'severity' => 'critical',
                    'action_url' => $actionUrl,
                    'data' => array_merge($baseData, [
                        'remind_interval' => $interval,
                    ]),
                    'dedupe_context' => $dedupe,
                    'recipient_context' => $recipientContext,
                ];
            }
        }
    }

    /**
     * @return int|null Occurrence number when this interval should fire today, else null
     */
    private function occurrenceFor(string $interval, int $daysOverdue): ?int
    {
        return match ($interval) {
            'once' => 1,
            'every_3_days' => ($daysOverdue >= 3 && $daysOverdue % 3 === 0)
                ? (int) ($daysOverdue / 3)
                : null,
            'weekly' => ($daysOverdue >= 7 && $daysOverdue % 7 === 0)
                ? (int) ($daysOverdue / 7)
                : null,
            'monthly' => ($daysOverdue >= 30 && $daysOverdue % 30 === 0)
                ? (int) ($daysOverdue / 30)
                : null,
            default => null,
        };
    }

    /** @return array{0: string, 1: string} */
    private function messageFor(
        string $interval,
        string $number,
        string $amount,
        string $due,
        int $daysOverdue,
        string $currency
    ): array {
        $pending = "Cheque {$number} for {$currency} {$amount} was due on {$due} and is still pending";

        $chequeLabel = "Cheque:{$number} {$currency} {$amount}";

        return match ($interval) {
            'every_3_days' => [
                "{$chequeLabel} Overdue Reminder",
                "{$pending} ({$daysOverdue} days overdue). This is your every-3-days reminder.",
            ],
            'weekly' => [
                "{$chequeLabel} Overdue Weekly Reminder",
                "{$pending} ({$daysOverdue} days overdue). This is your weekly reminder.",
            ],
            'monthly' => [
                "{$chequeLabel} Overdue Monthly Reminder",
                "{$pending} ({$daysOverdue} days overdue). This is your monthly reminder.",
            ],
            default => [
                "{$chequeLabel} Overdue",
                "{$pending}.",
            ],
        };
    }
}
