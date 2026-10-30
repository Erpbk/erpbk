<?php

namespace App\Services\RiderInvoice;

use App\Helpers\Common;
use App\Helpers\General;
use App\Models\Payment;
use App\Models\RiderInvoices;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Services\Agreements\AgreementPdfBranding;
use App\Support\CompanyContext;
use App\Support\ResourceLinks;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RiderInvoiceViewDataBuilder
{
    /**
     * Ledger reference types that are invoice earnings/settlements — not deduction/addition lines.
     */
    private const EXCLUDED_REFERENCE_TYPES = [
        'Invoice',
        'RiderInvoice',
        'PAY',
        'PV',
        'RV',
        'RI',
    ];

    /** @var array<string, string>|null */
    private $settingsCache = null;

    /** @var mixed */
    private $deliveryFeeCache = null;

    private bool $deliveryFeeResolved = false;

    /** @var array<int, array<string, list<object>>>|null account_id => monthStart => rows */
    private $ledgerTxCache = null;

    /** @var array<int, array<string, float>>|null account_id => monthStart => net */
    private $accountMonthNets = null;

    /** @var array<int, array<int, float>>|null invoice_id => account_id => sum debit */
    private $glPaidByInvoice = null;

    /** @var array<int, list<\App\Models\Payment>>|null */
    private $paymentsByAccount = null;

    /**
     * Display labels for known rider-ledger deduction/addition types.
     *
     * @var array<string, string>
     */
    private const REFERENCE_TYPE_LABELS = [
        'salik' => 'Salik Charges',
        'Salik' => 'Salik Charges',
        'Salik Voucher' => 'Salik Charges',
        'Salik Payment' => 'Salik Charges',
        'RTA FINE' => 'RTA Fine Charges',
        'RTA_FINE' => 'RTA Fine Charges',
        'RTA' => 'RTA Fine Charges',
        'PN' => 'Penalty Amount',
        'AL' => 'Advance Loan',
        'VL' => 'Loan',
        'COD' => 'COD Amount',
        'VC' => 'Vendor Charges',
        'fuel' => 'Fuel Card Charges',
        'INC' => 'Incentive Amount',
    ];

    public static function display(mixed $value, string $default = ''): string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_array($value)) {
            $parts = array_filter(array_map(
                static fn ($item) => is_scalar($item) ? (string) $item : null,
                $value
            ));

            return $parts !== [] ? implode(', ', $parts) : $default;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_object($value) && ! method_exists($value, '__toString')) {
            return $default;
        }

        return (string) $value;
    }

    public static function riderStatusLabel(mixed $status): string
    {
        if ($status === null || $status === '') {
            return '—';
        }

        $label = General::RiderStatus($status);

        return is_array($label) ? '—' : self::display($label, '—');
    }

    public static function ledgerEntryLabel(string $referenceType): string
    {
        if (isset(self::REFERENCE_TYPE_LABELS[$referenceType])) {
            return self::REFERENCE_TYPE_LABELS[$referenceType];
        }

        $voucherLabel = General::VoucherType($referenceType);

        if (is_string($voucherLabel) && $voucherLabel !== '') {
            return $voucherLabel;
        }

        return $referenceType !== '' ? $referenceType : 'Other';
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, string>
     */
    private function normalizeSettings(array $settings): array
    {
        $normalized = [];

        foreach ($settings as $key => $value) {
            $normalized[$key] = self::display($value, '');
        }

        return $normalized;
    }

    /**
     * Pull rider-account ledger nets for the invoice billing month, grouped by reference_type.
     * Debit surplus = deduction; credit surplus = addition.
     *
     * @return array{
     *     deductions: list<array{label: string, description: string, amount: float}>,
     *     additions: list<array{label: string, description: string, amount: float}>
     * }
     */
    public function ledgerAdjustmentsForMonth(?int $accountId, string $monthStart): array
    {
        $buckets = [];

        if (! $accountId) {
            return ['deductions' => [], 'additions' => []];
        }

        $monthStart = date('Y-m-01', strtotime($monthStart));

        if ($this->ledgerTxCache !== null) {
            $rows = collect($this->ledgerTxCache[$accountId][$monthStart] ?? []);
        } else {
            $rows = Transactions::query()
                ->where('account_id', $accountId)
                ->where('billing_month', $monthStart)
                ->where(function ($query) {
                    $query->whereNull('reference_type')
                        ->orWhereNotIn('reference_type', self::EXCLUDED_REFERENCE_TYPES);
                })
                ->get(['reference_type', 'narration', 'debit', 'credit']);
        }

        foreach ($rows as $row) {
            $debit = (float) ($row->debit ?? 0);
            $credit = (float) ($row->credit ?? 0);
            $net = round($debit - $credit, 2);

            if (abs($net) < 0.005) {
                continue;
            }

            $label = self::ledgerEntryLabel(trim((string) ($row->reference_type ?? '')));
            if (! isset($buckets[$label])) {
                $buckets[$label] = [
                    'net' => 0.0,
                    'narrations' => [],
                ];
            }
            $buckets[$label]['net'] = round($buckets[$label]['net'] + $net, 2);
            $narration = trim((string) ($row->narration ?? ''));
            if ($narration !== '') {
                $buckets[$label]['narrations'][$narration] = true;
            }
        }

        $deductions = [];
        $additions = [];
        foreach ($buckets as $label => $bucket) {
            $net = round((float) $bucket['net'], 2);
            if (abs($net) < 0.005) {
                continue;
            }
            $description = implode('; ', array_keys($bucket['narrations']));
            if ($description === '') {
                $description = $label;
            }
            $entry = [
                'label' => $label,
                'description' => $description,
                'amount' => abs($net),
            ];
            if ($net > 0) {
                $deductions[] = $entry;
            } else {
                $additions[] = $entry;
            }
        }

        return [
            'deductions' => $deductions,
            'additions' => $additions,
        ];
    }

    /**
     * Breakdown used by the rider payment form when an invoice is selected.
     * Uses the same ledger / outstanding math as the rider invoice show page
     * so deductions and previous balance are not double-counted.
     *
     * @return array{
     *     billing_month_label: string,
     *     invoice_total: float,
     *     previous_balance: float,
     *     deductions: list<array{name: string, description: string, amount: float}>,
     *     additions: list<array{name: string, description: string, amount: float}>,
     *     month_deductions_total: float,
     *     month_additions_total: float,
     *     total_deductions: float,
     *     total_additions: float,
     *     paid_amount: float,
     *     final_amount: float,
     *     payable: float
     * }
     */
    public function paymentFormBreakdown(RiderInvoices $riderInvoice): array
    {
        $riderInvoice->loadMissing(['rider.account', 'items']);
        $monthStart = date('Y-m-01', strtotime((string) $riderInvoice->billing_month));
        $accountId = $riderInvoice->rider?->account_id ? (int) $riderInvoice->rider->account_id : null;
        $ledger = $this->ledgerAdjustmentsForMonth($accountId, $monthStart);

        // Use model accessors so sequential payment-form balances stay consistent
        // with the Remaining Amount column (avoids double-counting prior months).
        $payable = round((float) $riderInvoice->balance, 2);
        $previous = round((float) $riderInvoice->previous_balance, 2);
        $paid = round((float) $riderInvoice->paid_amount, 2);
        $invoiceTotal = $this->invoiceItemsTotal($riderInvoice);
        $monthDeductionsTotal = round((float) array_sum(array_column($ledger['deductions'], 'amount')), 2);
        $monthAdditionsTotal = round((float) array_sum(array_column($ledger['additions'], 'amount')), 2);

        $mapLines = static function (array $rows): array {
            $out = [];
            foreach ($rows as $row) {
                $out[] = [
                    'name' => (string) ($row['label'] ?? 'Other'),
                    'description' => (string) ($row['description'] ?? ($row['label'] ?? 'Other')),
                    'amount' => round((float) ($row['amount'] ?? 0), 2),
                ];
            }

            return $out;
        };

        $totalDeductions = round($monthDeductionsTotal + ($previous > 0 ? $previous : 0), 2);
        $totalAdditions = round($monthAdditionsTotal + ($previous < 0 ? abs($previous) : 0), 2);

        return [
            'billing_month_label' => $riderInvoice->billing_month
                ? date('M Y', strtotime((string) $riderInvoice->billing_month))
                : '—',
            'invoice_total' => $invoiceTotal,
            'previous_balance' => $previous,
            'deductions' => $mapLines($ledger['deductions']),
            'additions' => $mapLines($ledger['additions']),
            'month_deductions_total' => $monthDeductionsTotal,
            'month_additions_total' => $monthAdditionsTotal,
            'total_deductions' => $totalDeductions,
            'total_additions' => $totalAdditions,
            'paid_amount' => $paid,
            'final_amount' => round($payable + $paid, 2),
            'payable' => $payable,
        ];
    }

    /**
     * @param  iterable<int, RiderInvoices>|null  $invoices
     */
    public function attachPaymentFormBreakdowns($invoices): void
    {
        foreach (collect($invoices ?? []) as $invoice) {
            if (! $invoice instanceof RiderInvoices) {
                continue;
            }
            $invoice->setAttribute('payment_breakdown', $this->paymentFormBreakdown($invoice));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function build(RiderInvoices $riderInvoice): array
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = $this->normalizeSettings(
                company_table('settings')->pluck('value', 'name')->toArray()
            );
        }
        if (! $this->deliveryFeeResolved) {
            $this->deliveryFeeCache = company_table('items')->where('name', 'Delivery fees')->first();
            $this->deliveryFeeResolved = true;
        }
        $settings = $this->settingsCache;
        $vat_percentage = self::display(Common::getSetting('vat_percentage'), '0');
        $deliveryfee = $this->deliveryFeeCache;
        $billing_month = date('M-y', strtotime($riderInvoice->billing_month));
        $monthStart = date('Y-m-01', strtotime($riderInvoice->billing_month));
        $accountId = $riderInvoice->rider?->account_id ? (int) $riderInvoice->rider->account_id : null;

        $ledger = $this->ledgerAdjustmentsForMonth($accountId, $monthStart);
        $ledger_deductions = $ledger['deductions'];
        $ledger_additions = $ledger['additions'];

        // Previous balance from rider ledger before this billing month (carry-forward).
        $rider_balance = $this->priorBalanceBeforeMonth($accountId, $monthStart);

        $monthDeductionsTotal = array_sum(array_column($ledger_deductions, 'amount'));
        $monthAdditionsTotal = array_sum(array_column($ledger_additions, 'amount'));

        $total_deductions = round($monthDeductionsTotal + ($rider_balance > 0 ? $rider_balance : 0), 2);
        $total_additions = round($monthAdditionsTotal + ($rider_balance < 0 ? abs($rider_balance) : 0), 2);

        // Legacy scalar keys (for any views still referencing named deduction vars).
        $amountByLabel = static function (array $rows, string $label): float {
            foreach ($rows as $row) {
                if (($row['label'] ?? '') === $label) {
                    return (float) ($row['amount'] ?? 0);
                }
            }

            return 0.0;
        };

        $fines = $amountByLabel($ledger_deductions, 'RTA Fine Charges');
        $salik = $amountByLabel($ledger_deductions, 'Salik Charges');
        $cod = $amountByLabel($ledger_deductions, 'COD Amount');
        $penalty = $amountByLabel($ledger_deductions, 'Penalty Amount');
        $advance_salary = $amountByLabel($ledger_deductions, 'Advance Loan')
            + $amountByLabel($ledger_deductions, 'Loan');
        $vendor_charges = $amountByLabel($ledger_deductions, 'Vendor Charges');
        $fuel_charges = $amountByLabel($ledger_deductions, 'Fuel Card Charges');
        $incentive = $amountByLabel($ledger_additions, 'Incentive Amount');

        $items = $riderInvoice->items ?? collect();

        // Item figures are the single source of truth for the invoice money math.
        $total = round((float) $items->sum('amount'), 2);
        $total_qty = round((float) $items->sum('qty'), 2);

        $totalOrders = 0;
        if ($deliveryfee && isset($deliveryfee->id)) {
            $totalOrders = (float) ($items->firstWhere('item_id', $deliveryfee->id)->qty ?? 0);
        }

        // Use the VAT stored on the invoice so totals stay stable when the
        // global vat_percentage setting changes after the invoice was raised.
        $storedVat = round((float) ($riderInvoice->vat ?? 0), 2);
        $appliesVat = abs($storedVat) > 0.004;
        $vatAmount = $appliesVat
            ? $storedVat
            : 0.0;
        $effectiveVatRate = ($appliesVat && abs($total) > 0.004)
            ? round($vatAmount / $total * 100, 2)
            : 0.0;

        $items_total = round($total + $vatAmount, 2);
        $finalAmount = round($items_total - $total_deductions + $total_additions, 2);

        $paid_amount = $this->paidAmountAgainstInvoice($riderInvoice, $accountId, $monthStart);

        // Amount still payable to the rider for this invoice.
        $rider_balance_final = round($finalAmount - $paid_amount, 2);

        $companyId = $riderInvoice->company_id ?? CompanyContext::id();
        $brand = app(AgreementPdfBranding::class)->forCompany($companyId);

        return [
            'settings' => $settings,
            'brand' => $brand,
            'total' => $total,
            'total_qty' => $total_qty,
            'running_total' => 0,
            'vat_percentage' => $vat_percentage,
            'invoice_vat_rate' => $effectiveVatRate,
            'invoice_applies_vat' => $appliesVat,
            'deliveryfee' => $deliveryfee,
            'totalOrders' => $totalOrders,
            'billing_month' => $billing_month,
            'ledger_deductions' => $ledger_deductions,
            'ledger_additions' => $ledger_additions,
            'fines' => $fines,
            'salik' => $salik,
            'cod' => $cod,
            'penalty' => $penalty,
            'incentive' => $incentive,
            'advance_salary' => $advance_salary,
            'vendor_charges' => $vendor_charges,
            'fuel_charges' => $fuel_charges,
            'rider_balance' => $rider_balance,
            'total_deductions' => $total_deductions,
            'total_additions' => $total_additions,
            'totalBeforeTax' => $total,
            'vatAmount' => $vatAmount,
            'finalAmount' => $finalAmount,
            'paid_amount' => $paid_amount,
            'rider_balance_final' => $rider_balance_final,
            'items_total' => $items_total,
            'invoiceNumber' => General::inv_sch($riderInvoice->id, $riderInvoice->created_at),
            'riderStatusLabel' => self::riderStatusLabel($riderInvoice->rider?->status),
        ];
    }

    /**
     * Payment vouchers (RI / PV) recorded against this rider invoice.
     *
     * @return Collection<int, Vouchers>
     */
    public function paymentVouchersForInvoice(RiderInvoices $riderInvoice, ?int $accountId = null): Collection
    {
        $accountId = $accountId
            ?? ($riderInvoice->rider?->account_id ? (int) $riderInvoice->rider->account_id : null);

        $invoiceId = (int) $riderInvoice->id;
        $patterns = array_values(array_filter([
            $riderInvoice->invoice_number ?? null,
            'Rider Invoice #'.$invoiceId,
            'Invoice #'.$invoiceId,
            'RINV-'.str_pad((string) $invoiceId, 4, '0', STR_PAD_LEFT),
        ]));

        $transCodes = Transactions::query()
            ->where('reference_type', 'RiderInvoice')
            ->where('reference_id', $invoiceId)
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->distinct()
            ->pluck('trans_code')
            ->filter()
            ->values();

        $voucherIdsFromPayments = collect();
        if ($accountId) {
            $voucherIdsFromPayments = Payment::query()
                ->where('payee_account_id', $accountId)
                ->whereNotNull('voucher_id')
                ->where(function ($q) use ($patterns, $invoiceId) {
                    ResourceLinks::whereContainsInvoiceId($q, $invoiceId);
                    foreach ($patterns as $pattern) {
                        $q->orWhere('reference', 'like', '%'.$pattern.'%')
                            ->orWhere('description', 'like', '%'.$pattern.'%');
                    }
                })
                ->pluck('voucher_id')
                ->filter()
                ->values();
        }

        if ($transCodes->isEmpty() && $voucherIdsFromPayments->isEmpty() && $patterns === []) {
            return collect();
        }

        return Vouchers::query()
            ->where(function ($q) use ($transCodes, $voucherIdsFromPayments, $patterns, $invoiceId) {
                if ($transCodes->isNotEmpty()) {
                    $q->orWhereIn('trans_code', $transCodes);
                }
                if ($voucherIdsFromPayments->isNotEmpty()) {
                    $q->orWhereIn('id', $voucherIdsFromPayments);
                }
                $q->orWhere(function ($inner) use ($patterns, $invoiceId) {
                    $inner->whereIn('voucher_type', ['RI', 'PV', 'PAY'])
                        ->where(function ($text) use ($patterns, $invoiceId) {
                            $text->where('remarks', 'like', '%Rider Invoice #'.$invoiceId.'%')
                                ->orWhere('remarks', 'like', '%Invoice #'.$invoiceId.'%');
                            foreach ($patterns as $pattern) {
                                $text->orWhere('reference_number', 'like', '%'.$pattern.'%')
                                    ->orWhere('remarks', 'like', '%'.$pattern.'%');
                            }
                        });
                });
            })
            ->orderByDesc('trans_date')
            ->orderByDesc('id')
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * Outstanding amounts as shown on the Rider Invoice (after deductions/additions).
     *
     * @return array{
     *     final_amount: float,
     *     paid_amount: float,
     *     balance: float,
     *     deductions: float,
     *     previous_balance: float
     * }
     */
    public function outstandingAmounts(RiderInvoices $riderInvoice): array
    {
        $data = $this->build($riderInvoice);
        $monthDeductions = round((float) array_sum(array_column($data['ledger_deductions'] ?? [], 'amount')), 2);

        return [
            'final_amount' => (float) $data['finalAmount'],
            'paid_amount' => (float) $data['paid_amount'],
            'balance' => (float) $data['rider_balance_final'],
            'deductions' => $monthDeductions,
            'previous_balance' => (float) ($data['rider_balance'] ?? 0),
        ];
    }

    /**
     * Allocate each invoice only its own period so payment-form totals do not
     * double-count earlier unpaid months.
     *
     * Oldest invoice: first ledger transaction through that billing month.
     * Each later invoice: after the previous selected month through its own month.
     *
     * @param  iterable<int, RiderInvoices>  $invoices
     */
    public function applySequentialOutstanding($invoices): void
    {
        collect($invoices)->filter()->groupBy(function ($invoice) {
            return ((int) ($invoice->rider_id ?? 0)).':'.((int) ($invoice->customer_id ?? 0));
        })->each(function ($group) {
            $ordered = $group->sortBy(function ($invoice) {
                return date('Y-m-01', strtotime((string) $invoice->billing_month))
                    . '-' . str_pad((string) $invoice->id, 10, '0', STR_PAD_LEFT);
            })->values();

            $previousMonth = null;
            foreach ($ordered as $invoice) {
                $invoice->setOutstandingSummary(
                    $this->outstandingAmountsForPeriod($invoice, $previousMonth)
                );
                $previousMonth = date('Y-m-01', strtotime((string) $invoice->billing_month));
            }
        });
    }

    /**
     * @return array{
     *     final_amount: float,
     *     paid_amount: float,
     *     balance: float,
     *     deductions: float,
     *     previous_balance: float
     * }
     */
    public function outstandingAmountsForPeriod(RiderInvoices $riderInvoice, ?string $afterBillingMonth = null): array
    {
        if ($afterBillingMonth === null) {
            return $this->outstandingAmounts($riderInvoice);
        }

        $monthStart = date('Y-m-01', strtotime((string) $riderInvoice->billing_month));
        $afterMonth = date('Y-m-01', strtotime($afterBillingMonth));
        $itemsTotal = $this->invoiceItemsTotal($riderInvoice);

        if ($afterMonth === $monthStart) {
            return [
                'final_amount' => $itemsTotal,
                'paid_amount' => 0.0,
                'balance' => $itemsTotal,
                'deductions' => 0.0,
                'previous_balance' => 0.0,
            ];
        }

        $accountId = $riderInvoice->rider?->account_id ? (int) $riderInvoice->rider->account_id : null;
        $ledger = $this->ledgerAdjustmentsForMonth($accountId, $monthStart);
        $monthDeductions = round((float) array_sum(array_column($ledger['deductions'], 'amount')), 2);
        $monthAdditions = (float) array_sum(array_column($ledger['additions'], 'amount'));

        $carry = $this->netBetweenMonths($accountId, $afterMonth, $monthStart);

        $totalDeductions = round($monthDeductions + ($carry > 0 ? $carry : 0), 2);
        $totalAdditions = round($monthAdditions + ($carry < 0 ? abs($carry) : 0), 2);
        $finalAmount = round($itemsTotal - $totalDeductions + $totalAdditions, 2);

        $paidAmount = $this->paidAmountAgainstInvoice($riderInvoice, $accountId, $monthStart);

        return [
            'final_amount' => $finalAmount,
            'paid_amount' => $paidAmount,
            'balance' => round($finalAmount - $paidAmount, 2),
            'deductions' => $monthDeductions,
            'previous_balance' => $carry,
        ];
    }

    /**
     * Amount paid against this specific invoice (not all rider payments for the month).
     *
     * Includes mark-as-paid / paid-import GL (reference_type RiderInvoice) and Payment
     * module rows that reference this invoice. Falls back to billing-month payments only
     * when no invoice-linked payment exists (legacy Payment-module flow).
     */
    private function paidAmountAgainstInvoice(RiderInvoices $riderInvoice, ?int $accountId, string $monthStart): float
    {
        $paid = 0.0;
        $invoiceId = (int) $riderInvoice->id;
        $monthStart = date('Y-m-01', strtotime($monthStart));

        if ($this->glPaidByInvoice !== null) {
            if ($accountId) {
                $paid += (float) ($this->glPaidByInvoice[$invoiceId][$accountId] ?? 0.0);
            } else {
                $paid += (float) array_sum($this->glPaidByInvoice[$invoiceId] ?? []);
            }
        } else {
            $invoiceGl = Transactions::query()
                ->where('reference_type', 'RiderInvoice')
                ->where('reference_id', $invoiceId);

            if ($accountId) {
                $invoiceGl->where('account_id', $accountId);
            }

            $paid += (float) $invoiceGl->sum('debit');
        }

        $partials = $riderInvoice->partial_paid_amount ?? [];
        if (! is_array($partials)) {
            $partials = [];
        }
        $partialSum = round((float) array_sum($partials), 2);
        $allocatedPaymentIds = array_values(array_filter(array_map('intval', array_keys($partials))));
        $paid += $partialSum;

        if (! $accountId) {
            return round($paid, 2);
        }

        $patterns = array_values(array_filter([
            $riderInvoice->invoice_number ?? null,
            'Rider Invoice #'.$invoiceId,
            'Invoice #'.$invoiceId,
            'RINV-'.str_pad((string) $invoiceId, 4, '0', STR_PAD_LEFT),
        ]));

        $linked = 0.0;
        if ($this->paymentsByAccount !== null) {
            $payments = $this->paymentsByAccount[$accountId] ?? [];
            $linkNeedle = ResourceLinks::containsInvoiceIdPayload($invoiceId);
            foreach ($payments as $payment) {
                if ($allocatedPaymentIds !== [] && in_array((int) $payment->id, $allocatedPaymentIds, true)) {
                    continue;
                }
                if ($this->paymentMatchesInvoice($payment, $invoiceId, $patterns, $linkNeedle)) {
                    $linked += (float) ($payment->amount ?? 0);
                }
            }
        } else {
            $linkedQuery = Payment::where('payee_account_id', $accountId)
                ->where(function ($q) use ($patterns, $invoiceId) {
                    ResourceLinks::whereContainsInvoiceId($q, $invoiceId);
                    foreach ($patterns as $pattern) {
                        $q->orWhere('reference', 'like', '%'.$pattern.'%')
                            ->orWhere('description', 'like', '%'.$pattern.'%');
                    }
                });
            if ($allocatedPaymentIds !== []) {
                $linkedQuery->whereNotIn('id', $allocatedPaymentIds);
            }
            $linked = (float) $linkedQuery->sum('amount');
        }

        if ($linked >= 0.01) {
            $paid += $linked;
        } elseif ($paid < 0.01) {
            // Legacy Payment-module payments for this billing month (no invoice text link)
            if ($this->paymentsByAccount !== null) {
                foreach ($this->paymentsByAccount[$accountId] ?? [] as $payment) {
                    if ($allocatedPaymentIds !== [] && in_array((int) $payment->id, $allocatedPaymentIds, true)) {
                        continue;
                    }
                    $payMonth = $payment->billing_month
                        ? date('Y-m-01', strtotime((string) $payment->billing_month))
                        : null;
                    if ($payMonth === $monthStart) {
                        $paid += (float) ($payment->amount ?? 0);
                    }
                }
            } else {
                $legacyQuery = Payment::where('payee_account_id', $accountId)
                    ->whereDate('billing_month', $monthStart);
                if ($allocatedPaymentIds !== []) {
                    $legacyQuery->whereNotIn('id', $allocatedPaymentIds);
                }
                $paid += (float) $legacyQuery->sum('amount');
            }
        }

        return round($paid, 2);
    }

    /**
     * Prefetch ledger / payment data for a page or export set, then apply sequential
     * outstanding summaries without one full build() query storm per invoice.
     *
     * @param  iterable<int, RiderInvoices>  $invoices
     */
    public function preloadOutstandingSummaries($invoices): void
    {
        $models = collect($invoices)->filter(fn ($invoice) => $invoice instanceof RiderInvoices)->values();
        if ($models->isEmpty()) {
            return;
        }

        // loadMissing exists on Eloquent collections, not Support\Collection.
        $collection = $invoices instanceof EloquentCollection
            ? $invoices->filter(fn ($invoice) => $invoice instanceof RiderInvoices)->values()
            : new EloquentCollection($models->all());
        $collection->loadMissing(['rider.customer', 'items']);

        $this->warmCachesForInvoices($collection);
        $this->applySequentialOutstanding($collection);
        $this->clearListCaches();
    }

    /**
     * @param  Collection<int, RiderInvoices>  $invoices
     */
    private function warmCachesForInvoices(Collection $invoices): void
    {
        $this->settingsCache = $this->normalizeSettings(
            company_table('settings')->pluck('value', 'name')->toArray()
        );
        $this->deliveryFeeCache = company_table('items')->where('name', 'Delivery fees')->first();
        $this->deliveryFeeResolved = true;

        $accountIds = $invoices
            ->map(fn (RiderInvoices $invoice) => $invoice->rider?->account_id ? (int) $invoice->rider->account_id : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $months = $invoices
            ->map(fn (RiderInvoices $invoice) => date('Y-m-01', strtotime((string) $invoice->billing_month)))
            ->unique()
            ->values()
            ->all();
        $invoiceIds = $invoices->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->ledgerTxCache = [];
        if ($accountIds !== [] && $months !== []) {
            $rows = Transactions::query()
                ->whereIn('account_id', $accountIds)
                ->whereIn('billing_month', $months)
                ->where(function ($query) {
                    $query->whereNull('reference_type')
                        ->orWhereNotIn('reference_type', self::EXCLUDED_REFERENCE_TYPES);
                })
                ->get(['account_id', 'billing_month', 'reference_type', 'narration', 'debit', 'credit']);

            foreach ($rows as $row) {
                $accountId = (int) $row->account_id;
                $month = date('Y-m-01', strtotime((string) $row->billing_month));
                $this->ledgerTxCache[$accountId][$month][] = $row;
            }
        }

        $this->accountMonthNets = [];
        if ($accountIds !== []) {
            $nets = Transactions::query()
                ->whereIn('account_id', $accountIds)
                ->select('account_id', 'billing_month', DB::raw('SUM(debit - credit) as net'))
                ->groupBy('account_id', 'billing_month')
                ->get();

            foreach ($nets as $row) {
                $accountId = (int) $row->account_id;
                $month = date('Y-m-01', strtotime((string) $row->billing_month));
                $this->accountMonthNets[$accountId][$month] = (float) $row->net;
            }
        }

        $this->glPaidByInvoice = [];
        if ($invoiceIds !== []) {
            $glRows = Transactions::query()
                ->where('reference_type', 'RiderInvoice')
                ->whereIn('reference_id', $invoiceIds)
                ->select('reference_id', 'account_id', DB::raw('SUM(debit) as paid'))
                ->groupBy('reference_id', 'account_id')
                ->get();

            foreach ($glRows as $row) {
                $refId = (int) $row->reference_id;
                $accId = (int) ($row->account_id ?? 0);
                $this->glPaidByInvoice[$refId][$accId] = (float) $row->paid;
            }
        }

        $this->paymentsByAccount = [];
        if ($accountIds !== []) {
            $payments = Payment::query()
                ->whereIn('payee_account_id', $accountIds)
                ->get();

            foreach ($payments as $payment) {
                $this->paymentsByAccount[(int) $payment->payee_account_id][] = $payment;
            }
        }
    }

    private function clearListCaches(): void
    {
        $this->ledgerTxCache = null;
        $this->accountMonthNets = null;
        $this->glPaidByInvoice = null;
        $this->paymentsByAccount = null;
        // Keep settings / delivery fee for the rest of the request.
    }

    private function priorBalanceBeforeMonth(?int $accountId, string $monthStart): float
    {
        if (! $accountId) {
            return 0.0;
        }

        $monthStart = date('Y-m-01', strtotime($monthStart));

        if ($this->accountMonthNets !== null) {
            $sum = 0.0;
            foreach ($this->accountMonthNets[$accountId] ?? [] as $month => $net) {
                if ($month < $monthStart) {
                    $sum += (float) $net;
                }
            }

            return round($sum, 2);
        }

        return round((float) Transactions::where('account_id', $accountId)
            ->whereDate('billing_month', '<', $monthStart)
            ->sum(DB::raw('debit - credit')), 2);
    }

    private function netBetweenMonths(?int $accountId, string $afterMonth, string $beforeMonth): float
    {
        if (! $accountId) {
            return 0.0;
        }

        $afterMonth = date('Y-m-01', strtotime($afterMonth));
        $beforeMonth = date('Y-m-01', strtotime($beforeMonth));

        if ($this->accountMonthNets !== null) {
            $sum = 0.0;
            foreach ($this->accountMonthNets[$accountId] ?? [] as $month => $net) {
                if ($month > $afterMonth && $month < $beforeMonth) {
                    $sum += (float) $net;
                }
            }

            return round($sum, 2);
        }

        return round((float) Transactions::query()
            ->where('account_id', $accountId)
            ->whereDate('billing_month', '>', $afterMonth)
            ->whereDate('billing_month', '<', $beforeMonth)
            ->sum(DB::raw('debit - credit')), 2);
    }

    /**
     * @param  list<string>  $patterns
     */
    private function paymentMatchesInvoice(Payment $payment, int $invoiceId, array $patterns, string $linkNeedle): bool
    {
        $links = (string) ($payment->resource_links ?? '');
        if ($linkNeedle !== '' && $links !== '' && str_contains($links, $linkNeedle)) {
            return true;
        }

        $haystack = trim((string) ($payment->reference ?? '').' '.(string) ($payment->description ?? ''));
        foreach ($patterns as $pattern) {
            if ($pattern !== '' && $haystack !== '' && str_contains($haystack, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function invoiceItemsTotal(RiderInvoices $riderInvoice): float
    {
        $items = $riderInvoice->items ?? collect();
        $total = round((float) $items->sum('amount'), 2);
        $storedVat = round((float) ($riderInvoice->vat ?? 0), 2);
        $vatAmount = abs($storedVat) > 0.004 ? $storedVat : 0.0;

        return round($total + $vatAmount, 2);
    }
}
