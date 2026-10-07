<?php

namespace App\Support;

use App\Models\Payment;

/**
 * Resolve Payment module rows linked to an invoice (for Edit Payment actions).
 */
class InvoicePaymentLink
{
    /**
     * Latest Payment id allocated to / referencing this invoice, if any.
     */
    public static function latestId(object $invoice, ?int $payeeAccountId = null): ?int
    {
        $partials = $invoice->partial_paid_amount ?? null;
        if (is_array($partials) && $partials !== []) {
            $ids = array_values(array_filter(array_map('intval', array_keys($partials))));
            if ($ids !== []) {
                $fromPartials = Payment::query()
                    ->whereIn('id', $ids)
                    ->orderByDesc('id')
                    ->value('id');
                if ($fromPartials) {
                    return (int) $fromPartials;
                }
            }
        }

        $patterns = self::referencePatterns($invoice);
        if ($patterns === []) {
            return null;
        }

        $query = Payment::query()->where(function ($q) use ($patterns) {
            foreach ($patterns as $pattern) {
                $q->orWhere('reference', 'like', '%'.$pattern.'%')
                    ->orWhere('description', 'like', '%'.$pattern.'%');
            }
        });

        if ($payeeAccountId) {
            $query->where('payee_account_id', $payeeAccountId);
        }

        $id = $query->orderByDesc('id')->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * @return list<string>
     */
    private static function referencePatterns(object $invoice): array
    {
        $patterns = [];

        $number = $invoice->invoice_number ?? null;
        if (is_string($number) && $number !== '') {
            $patterns[] = $number;
        }

        if (! isset($invoice->id)) {
            return array_values(array_unique($patterns));
        }

        $id = (int) $invoice->id;
        $patterns[] = 'Invoice #'.$id;

        if (isset($invoice->rider_id)) {
            $patterns[] = 'Rider Invoice #'.$id;
            $patterns[] = 'RINV-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
        }

        if (isset($invoice->employee_id)) {
            $patterns[] = 'EMP_INV'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);
        }

        if (isset($invoice->leasing_company_id)) {
            $patterns[] = 'LCI'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);
            $patterns[] = 'LCI'.$id;
        }

        return array_values(array_unique(array_filter($patterns)));
    }
}
