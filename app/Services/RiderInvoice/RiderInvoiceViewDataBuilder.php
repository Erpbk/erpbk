<?php

namespace App\Services\RiderInvoice;

use App\Helpers\Common;
use App\Helpers\General;
use App\Models\RiderInvoices;
use App\Models\Transactions;
use App\Services\Agreements\AgreementPdfBranding;
use App\Support\CompanyContext;
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
     * @return array{deductions: list<array{label: string, amount: float}>, additions: list<array{label: string, amount: float}>}
     */
    private function ledgerAdjustmentsForMonth(?int $accountId, string $monthStart): array
    {
        $deductionsByLabel = [];
        $additionsByLabel = [];

        if (! $accountId) {
            return ['deductions' => [], 'additions' => []];
        }

        $rows = Transactions::query()
            ->where('account_id', $accountId)
            ->where('billing_month', $monthStart)
            ->where(function ($query) {
                $query->whereNull('reference_type')
                    ->orWhereNotIn('reference_type', self::EXCLUDED_REFERENCE_TYPES);
            })
            ->selectRaw('reference_type, SUM(debit) as debit_sum, SUM(credit) as credit_sum')
            ->groupBy('reference_type')
            ->get();

        foreach ($rows as $row) {
            $debit = (float) ($row->debit_sum ?? 0);
            $credit = (float) ($row->credit_sum ?? 0);
            $net = round($debit - $credit, 2);

            if (abs($net) < 0.005) {
                continue;
            }

            $label = self::ledgerEntryLabel(trim((string) ($row->reference_type ?? '')));

            if ($net > 0) {
                $deductionsByLabel[$label] = ($deductionsByLabel[$label] ?? 0) + $net;
            } else {
                $additionsByLabel[$label] = ($additionsByLabel[$label] ?? 0) + abs($net);
            }
        }

        $deductions = [];
        foreach ($deductionsByLabel as $label => $amount) {
            $deductions[] = ['label' => $label, 'amount' => round((float) $amount, 2)];
        }

        $additions = [];
        foreach ($additionsByLabel as $label => $amount) {
            $additions[] = ['label' => $label, 'amount' => round((float) $amount, 2)];
        }

        return [
            'deductions' => $deductions,
            'additions' => $additions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function build(RiderInvoices $riderInvoice): array
    {
        $settings = $this->normalizeSettings(
            company_table('settings')->pluck('value', 'name')->toArray()
        );
        $total = 0;
        $total_qty = 0;
        $running_total = 0;
        $vat_percentage = self::display(Common::getSetting('vat_percentage'), '0');
        $deliveryfee = company_table('items')->where('name', 'Delivery fees')->first();
        $totalOrders = 0;
        $billing_month = date('M-y', strtotime($riderInvoice->billing_month));
        $monthStart = date('Y-m-01', strtotime($riderInvoice->billing_month));
        $accountId = $riderInvoice->rider?->account_id ? (int) $riderInvoice->rider->account_id : null;

        $ledger = $this->ledgerAdjustmentsForMonth($accountId, $monthStart);
        $ledger_deductions = $ledger['deductions'];
        $ledger_additions = $ledger['additions'];

        // Previous balance from rider ledger before this billing month (carry-forward).
        $rider_balance = 0;
        if ($accountId) {
            $rider_balance = (float) Transactions::where('account_id', $accountId)
                ->whereDate('billing_month', '<', $monthStart)
                ->sum(DB::raw('debit - credit'));
        }

        $monthDeductionsTotal = array_sum(array_column($ledger_deductions, 'amount'));
        $monthAdditionsTotal = array_sum(array_column($ledger_additions, 'amount'));

        $total_deductions = $monthDeductionsTotal + ($rider_balance > 0 ? $rider_balance : 0);
        $total_additions = $monthAdditionsTotal + ($rider_balance < 0 ? abs($rider_balance) : 0);

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

        $companyId = $riderInvoice->company_id ?? CompanyContext::id();
        $brand = app(AgreementPdfBranding::class)->forCompany($companyId);

        return [
            'settings' => $settings,
            'brand' => $brand,
            'total' => $total,
            'total_qty' => $total_qty,
            'running_total' => $running_total,
            'vat_percentage' => $vat_percentage,
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
            'totalBeforeTax' => 0,
            'finalAmount' => 0,
            'paid_amount' => 0,
            'rider_balance_final' => 0,
            'items_total' => 0,
            'invoiceNumber' => General::inv_sch($riderInvoice->id, $riderInvoice->created_at),
            'riderStatusLabel' => self::riderStatusLabel($riderInvoice->rider?->status),
        ];
    }
}
