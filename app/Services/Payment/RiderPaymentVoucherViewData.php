<?php

namespace App\Services\Payment;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\Currency;
use App\Helpers\General;
use App\Models\Payment;
use App\Models\RiderInvoices;
use App\Models\Riders;
use App\Models\Transactions;
use App\Models\User;
use App\Services\Agreements\AgreementPdfBranding;
use App\Support\CompanyContext;
use App\Support\RiderPaymentVoucherDefaults;
use Illuminate\Support\Collection;

class RiderPaymentVoucherViewData
{
    public function __construct(
        protected AgreementPdfBranding $branding
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Payment $payment): array
    {
        $payment->loadMissing(['payeeAccount', 'bank.account', 'voucher.transactions.account', 'branch']);

        $settings = RiderPaymentVoucherDefaults::all();
        $brand = $this->branding->forCompany(CompanyContext::id() ?? $payment->company_id);
        $bankCharges = round((float) ($payment->bank_charges ?? 0), 2);
        $totalAmount = round((float) ($payment->amount ?? 0), 2);
        $paymentAmount = round(max(0, $totalAmount - $bankCharges), 2);

        $allocations = $this->allocationRows($payment);
        $allocatedTotal = round($allocations->sum('payment'), 2);
        $difference = round($paymentAmount - $allocatedTotal, 2);

        $rider = $this->resolveRider($payment);
        $createdByName = $this->userName($payment->created_by);
        $currencyCode = Currency::code();
        $paidByAccount = $this->payingAccountLabel($payment);

        $amountInWords = $this->amountInWords($totalAmount);

        $referenceDisplay = $payment->reference
            ?: ($allocations->first()['invoice_number'] ?? null);
        $voucherId = $payment->voucher_id ?: $payment->id;
        $voucherType = $payment->voucher->voucher_type ?? 'PV';
        $voucherNo = $voucherType.'-'.$voucherId;

        return [
            'payment' => $payment,
            'settings' => $settings,
            'brand' => $brand,
            'documentTitle' => $settings['title'],
            'companyName' => $brand['name'] ?? ($brand['company_name'] ?? '—'),
            'logoSrc' => $brand['logo_src'] ?? ($brand['logo_url'] ?? null),
            'voucherNo' => $voucherNo,
            'referenceNo' => $referenceDisplay ?: $voucherNo,
            'paymentDate' => $payment->date_of_payment
                ? Common::DateFormat($payment->date_of_payment)
                : '—',
            'payeeName' => $rider?->name
                ?: ($payment->payeeAccount->name ?? '—'),
            'riderCode' => $rider?->rider_id
                ?: ($payment->payeeAccount->account_code ?? '—'),
            'billingMonth' => $payment->billing_month
                ? Common::MonthFormat($payment->billing_month)
                : '—',
            'paidBy' => $paidByAccount,
            'createdBy' => $createdByName ?: '—',
            'paymentMode' => $payment->amount_type ?: '—',
            'paymentType' => 'Payment',
            'paymentAmount' => $paymentAmount,
            'bankCharges' => $bankCharges,
            'totalPayment' => $totalAmount,
            'allocatedTotal' => $allocatedTotal,
            'difference' => $difference,
            'currencyCode' => $currencyCode,
            'narration' => trim((string) ($payment->description ?? '')) ?: '—',
            'internalNotes' => trim((string) ($payment->internal_notes ?? '')),
            'allocations' => $allocations,
            'postedTransactions' => $this->postedTransactions($payment),
            'amountInWords' => $amountInWords,
            'attachmentUrl' => $payment->attachment
                ? url('storage/vouchers/'.ltrim((string) $payment->attachment, '/'))
                : null,
            'attachmentName' => $payment->attachment ? basename((string) $payment->attachment) : null,
            'isRiderPayment' => $this->isRiderPayment($payment),
            'riderModuleLabel' => \App\Models\Settings::getMenuLabel('riders'),
            'formatMoney' => static fn (float $amount) => Currency::format($amount),
        ];
    }

    private function amountInWords(float $amount): string
    {
        $normalized = number_format(abs($amount), 2, '.', '');
        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '00');

        $wholeWords = Account::convertNumberToWord($whole);
        if (! is_string($wholeWords) || $wholeWords === '') {
            return '—';
        }

        $text = ucwords(strtolower($wholeWords));
        $fractionInt = (int) $fraction;
        if ($fractionInt > 0) {
            $fracWords = Account::convertNumberToWord((string) $fractionInt);
            if (is_string($fracWords) && $fracWords !== '') {
                $text .= ' and '.ucwords(strtolower($fracWords));
            }
        }

        return $text.' Only';
    }

    /**
     * Ledger lines posted for this payment (voucher trans_code or PV reference).
     *
     * @return Collection<int, Transactions>
     */
    private function postedTransactions(Payment $payment): Collection
    {
        $payment->loadMissing(['voucher.transactions.account']);

        $viaVoucher = $payment->voucher?->transactions;
        if ($viaVoucher && $viaVoucher->isNotEmpty()) {
            return $viaVoucher->values();
        }

        $transCode = $payment->voucher?->trans_code;
        if ($transCode) {
            $byCode = Transactions::with('account')
                ->where('trans_code', $transCode)
                ->orderBy('id')
                ->get();
            if ($byCode->isNotEmpty()) {
                return $byCode;
            }
        }

        return Transactions::with('account')
            ->where('reference_type', 'PV')
            ->where('reference_id', $payment->id)
            ->orderBy('id')
            ->get();
    }

    public function isRiderPayment(Payment $payment): bool
    {
        $payment->loadMissing('payeeAccount');
        $payee = $payment->payeeAccount;
        if ($payee && strcasecmp((string) ($payee->ref_name ?? ''), 'Rider') === 0) {
            return true;
        }

        $ref = (string) ($payment->reference ?? '');
        $desc = (string) ($payment->description ?? '');

        return (bool) preg_match('/RINV/i', $ref.' '.$desc);
    }

    private function resolveRider(Payment $payment): ?Riders
    {
        $payment->loadMissing('payeeAccount');
        $payee = $payment->payeeAccount;
        if ($payee && strcasecmp((string) ($payee->ref_name ?? ''), 'Rider') === 0 && $payee->ref_id) {
            return Riders::withTrashed()->find($payee->ref_id);
        }

        return null;
    }

    /**
     * @return Collection<int, array{
     *     invoice_id:int,
     *     invoice_number:string,
     *     billing_month:string,
     *     total:float,
     *     previously_paid:float,
     *     balance_due:float,
     *     payment:float
     * }>
     */
    private function allocationRows(Payment $payment): Collection
    {
        $paymentId = (int) $payment->id;
        $rows = collect();
        $invoiceIds = $this->invoiceIdsFromReference($payment);

        $query = RiderInvoices::query()->with(['items', 'rider']);
        if ($invoiceIds !== []) {
            $query->whereIn('id', $invoiceIds);
        } else {
            // Fallback: invoices that record this payment id in partial_paid_amount.
            $query->whereNotNull('partial_paid_amount')
                ->where(function ($q) use ($paymentId) {
                    $q->where("partial_paid_amount->{$paymentId}", '>', 0)
                        ->orWhere("partial_paid_amount->{$paymentId}", '!=', null);
                });
        }

        $invoices = $query->get()->unique('id')->values();

        foreach ($invoices as $invoice) {
            $partial = $invoice->partial_paid_amount ?? [];
            $thisPayment = round((float) ($partial[$paymentId] ?? $partial[(string) $paymentId] ?? 0), 2);

            // Fallback: if invoice matched by reference but no partial map, use full net payment once.
            if ($thisPayment < 0.01 && $invoices->count() === 1) {
                $bankCharges = round((float) ($payment->bank_charges ?? 0), 2);
                $thisPayment = round(max(0, (float) $payment->amount - $bankCharges), 2);
            }
            if ($thisPayment < 0.01) {
                continue;
            }

            $total = round(
                (float) ($invoice->items?->sum('amount') ?? 0) + (float) ($invoice->vat ?? 0),
                2
            );
            if ($total < 0.01) {
                $total = round((float) ($invoice->total_amount ?? $invoice->total ?? 0), 2);
            }

            $paidAll = 0.0;
            foreach ((array) $partial as $pid => $amt) {
                $paidAll += (float) $amt;
            }
            $paidAll = round($paidAll, 2);
            $previouslyPaid = round(max(0, $paidAll - $thisPayment), 2);
            $balanceDue = round(max(0, $total - $previouslyPaid), 2);

            $rows->push([
                'invoice_id' => (int) $invoice->id,
                'invoice_number' => $invoice->invoice_number
                    ?: General::inv_sch($invoice->id, $invoice->created_at),
                'billing_month' => $invoice->billing_month
                    ? Common::MonthFormat($invoice->billing_month)
                    : '—',
                'total' => $total,
                'previously_paid' => $previouslyPaid,
                'balance_due' => $balanceDue,
                'payment' => $thisPayment,
            ]);
        }

        return $rows->values();
    }

    /**
     * @return list<string>
     */
    private function referencePatterns(Payment $payment): array
    {
        $haystack = trim(($payment->reference ?? '').' '.($payment->description ?? ''));
        if ($haystack === '') {
            return [];
        }

        preg_match_all('/RINV-?\d+/i', $haystack, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }

    /**
     * @return list<int>
     */
    private function invoiceIdsFromReference(Payment $payment): array
    {
        $ids = [];
        foreach ($this->referencePatterns($payment) as $pattern) {
            if (preg_match('/RINV-?0*(\d+)/i', $pattern, $m)) {
                $ids[] = (int) $m[1];
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Bank / cash account used to pay the rider (same as the form "Paid By" field).
     */
    private function payingAccountLabel(Payment $payment): string
    {
        $payment->loadMissing(['bank.account']);
        $bank = $payment->bank;
        if (! $bank) {
            return '—';
        }

        $account = $bank->account;
        if ($account) {
            $code = trim((string) ($account->account_code ?? ''));
            $name = trim((string) ($account->name ?? $bank->name ?? ''));
            if ($code !== '' && $name !== '') {
                return $code.'-'.$name;
            }
            if ($name !== '') {
                return $name;
            }
            if ($code !== '') {
                return $code;
            }
        }

        $bankName = trim((string) ($bank->name ?? ''));

        return $bankName !== '' ? $bankName : '—';
    }

    private function userName(mixed $userId): ?string
    {
        if (! $userId) {
            return null;
        }

        $user = User::find($userId);

        return $user?->name;
    }
}
