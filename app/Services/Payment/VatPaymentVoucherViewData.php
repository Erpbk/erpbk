<?php

namespace App\Services\Payment;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\Currency;
use App\Models\Payment;
use App\Models\Transactions;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\Agreements\AgreementPdfBranding;
use App\Services\Voucher\VoucherSignatureResolver;
use App\Support\CompanyContext;
use App\Support\ResourceLinks;
use App\Support\RiderPaymentVoucherDefaults;
use Illuminate\Support\Collection;

class VatPaymentVoucherViewData
{
    public function __construct(
        protected AgreementPdfBranding $branding,
        protected VoucherSignatureResolver $signatureResolver
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

        $createdByName = $this->userName($payment->created_by);
        $currencyCode = Currency::code();
        $paidByAccount = $this->payingAccountLabel($payment);
        $amountInWords = $this->amountInWords($totalAmount);

        $referenceDisplay = $payment->reference
            ?: ($allocations->first()['invoice_number'] ?? null);
        $voucher = $payment->voucher;
        $voucherType = $voucher->voucher_type ?? 'VP';
        $voucherNo = $voucher
            ? ($voucher->formatted_id ?: ($voucherType.'-'.str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT)))
            : ($voucherType.'-'.str_pad((string) $payment->id, 4, '0', STR_PAD_LEFT));

        return [
            'payment' => $payment,
            'settings' => $settings,
            'brand' => $brand,
            'documentTitle' => 'VAT Payment',
            'companyName' => $brand['name'] ?? ($brand['company_name'] ?? '—'),
            'logoSrc' => $brand['logo_src'] ?? ($brand['logo_url'] ?? null),
            'voucherNo' => $voucherNo,
            'referenceNo' => $referenceDisplay ?: $voucherNo,
            'paymentDate' => $payment->date_of_payment
                ? Common::DateFormat($payment->date_of_payment)
                : '—',
            'payeeName' => $payment->payeeAccount->name ?? 'Federal Tax Authority (FTA)',
            'riderCode' => $payment->payeeAccount->account_code
                ?? (Common::getSetting('vat_number') ?: '—'),
            'billingMonth' => $payment->billing_month
                ? Common::MonthFormat($payment->billing_month)
                : '—',
            'paidBy' => $paidByAccount,
            'createdBy' => $createdByName ?: '—',
            'paymentMode' => $payment->amount_type ?: '—',
            'paymentType' => 'VAT Payment',
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
            'isRiderPayment' => false,
            'isEmployeePayment' => false,
            'isVatPayment' => true,
            'riderModuleLabel' => 'VAT',
            'formatMoney' => static fn (float $amount) => Currency::format($amount),
            'signatureSlots' => $this->signatureResolver->slotsFor(
                $voucherType,
                CompanyContext::id() ?? $payment->company_id
            ),
        ];
    }

    public function isVatPayment(Payment $payment): bool
    {
        $links = is_array($payment->resource_links) ? $payment->resource_links : [];
        if (ResourceLinks::invoiceType($links) === 'vat_return') {
            return true;
        }

        $voucherType = (string) ($payment->voucher?->voucher_type
            ?? optional($payment->relationLoaded('voucher') ? $payment->voucher : null)->voucher_type
            ?? '');
        if (strcasecmp($voucherType, 'VP') === 0) {
            return true;
        }

        $haystack = (string) ($payment->reference ?? '').' '.(string) ($payment->description ?? '');

        return (bool) preg_match('/VTR-?/i', $haystack);
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
        $links = is_array($payment->resource_links) ? $payment->resource_links : null;
        $linkAmounts = ResourceLinks::amountsByInvoiceId($links);
        $invoiceIds = ResourceLinks::invoiceIds($links);
        if ($invoiceIds === []) {
            $invoiceIds = $this->returnIdsFromReference($payment);
        }

        $query = VatReturn::query();
        if ($invoiceIds !== []) {
            $query->whereIn('id', $invoiceIds);
        } else {
            $query->whereNotNull('partial_paid_amount')
                ->where(function ($q) use ($paymentId) {
                    $q->where("partial_paid_amount->{$paymentId}", '>', 0)
                        ->orWhere("partial_paid_amount->{$paymentId}", '!=', null);
                });
        }

        $returns = $query->get()->unique('id')->values();

        foreach ($returns as $vatReturn) {
            $partial = is_array($vatReturn->partial_paid_amount) ? $vatReturn->partial_paid_amount : [];
            $thisPayment = round((float) ($partial[$paymentId] ?? $partial[(string) $paymentId] ?? 0), 2);
            if ($thisPayment < 0.01 && isset($linkAmounts[(int) $vatReturn->id])) {
                $thisPayment = (float) $linkAmounts[(int) $vatReturn->id];
            }
            if ($thisPayment < 0.01 && $returns->count() === 1) {
                $bankCharges = round((float) ($payment->bank_charges ?? 0), 2);
                $thisPayment = round(max(0, (float) $payment->amount - $bankCharges), 2);
            }
            if ($thisPayment < 0.01) {
                continue;
            }

            $total = round((float) $vatReturn->payable_amount, 2);
            $paidAll = round((float) array_sum($partial), 2);
            $previouslyPaid = round(max(0, $paidAll - $thisPayment), 2);
            $balanceDue = round(max(0, $total - $previouslyPaid), 2);

            $rows->push([
                'invoice_id' => (int) $vatReturn->id,
                'invoice_number' => $vatReturn->invoice_number,
                'billing_month' => ($vatReturn->quarter_label ?? ('Q'.$vatReturn->quarter_slot)).' '.$vatReturn->year,
                'total' => $total,
                'previously_paid' => $previouslyPaid,
                'balance_due' => $balanceDue,
                'payment' => $thisPayment,
            ]);
        }

        return $rows->values();
    }

    /**
     * @return list<int>
     */
    private function returnIdsFromReference(Payment $payment): array
    {
        $haystack = trim(($payment->reference ?? '').' '.($payment->description ?? ''));
        if ($haystack === '') {
            return [];
        }

        preg_match_all('/VTR-?0*(\d+)/i', $haystack, $matches);
        $ids = array_map('intval', $matches[1] ?? []);

        return array_values(array_unique(array_filter($ids)));
    }

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

        return User::find($userId)?->name;
    }
}
