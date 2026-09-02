<?php

namespace App\Services\EmployeeInvoice;

use App\Helpers\Common;
use App\Helpers\General;
use App\Models\EmployeeInvoices;
use App\Models\Payment;
use App\Models\Transactions;
use App\Services\Agreements\AgreementPdfBranding;
use App\Support\CompanyContext;
use Illuminate\Support\Facades\DB;

class EmployeeInvoiceViewDataBuilder
{
    /**
     * Ledger reference types that are invoice earnings/settlements — not deduction/addition lines.
     */
    private const EXCLUDED_REFERENCE_TYPES = [
        'EmployeeInvoice',
        'Invoice',
        'PAY',
        'PV',
        'RV',
        'RI',
    ];

    /**
     * Display labels for known employee-ledger deduction/addition types.
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
     * Pull employee-account ledger nets for the invoice billing month, grouped by reference_type.
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
    public function build(EmployeeInvoices $employeeInvoice): array
    {
        $settings = $this->normalizeSettings(
            company_table('settings')->pluck('value', 'name')->toArray()
        );
        $vat_percentage = self::display(Common::getSetting('vat_percentage'), '0');
        $billing_month = date('M-y', strtotime($employeeInvoice->billing_month));
        $monthStart = date('Y-m-01', strtotime($employeeInvoice->billing_month));
        $accountId = $employeeInvoice->employee?->account_id
            ? (int) $employeeInvoice->employee->account_id
            : null;

        $ledger = $this->ledgerAdjustmentsForMonth($accountId, $monthStart);
        $ledger_deductions = $ledger['deductions'];
        $ledger_additions = $ledger['additions'];

        // Previous balance from employee ledger before this billing month (carry-forward).
        $employee_balance = 0.0;
        if ($accountId) {
            $employee_balance = round((float) Transactions::where('account_id', $accountId)
                ->whereDate('billing_month', '<', $monthStart)
                ->sum(DB::raw('debit - credit')), 2);
        }

        $monthDeductionsTotal = array_sum(array_column($ledger_deductions, 'amount'));
        $monthAdditionsTotal = array_sum(array_column($ledger_additions, 'amount'));

        $total_deductions = round($monthDeductionsTotal + ($employee_balance > 0 ? $employee_balance : 0), 2);
        $total_additions = round($monthAdditionsTotal + ($employee_balance < 0 ? abs($employee_balance) : 0), 2);

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

        $items = $employeeInvoice->items ?? collect();

        $total = round((float) $items->sum('amount'), 2);
        $total_qty = round((float) $items->sum('qty'), 2);

        // Prefer stored invoice VAT so totals stay stable if settings change later.
        $storedVat = round((float) ($employeeInvoice->vat ?? 0), 2);
        $appliesVat = abs($storedVat) > 0.004;
        $vatAmount = $appliesVat ? $storedVat : 0.0;
        $effectiveVatRate = ($appliesVat && abs($total) > 0.004)
            ? round($vatAmount / $total * 100, 2)
            : 0.0;

        // Fall back to header total_amount when line amounts are empty/out of sync.
        $items_total = abs($total) > 0.004
            ? round($total + $vatAmount, 2)
            : round((float) ($employeeInvoice->total_amount ?? 0), 2);

        if (abs($total) < 0.004 && abs($items_total) > 0.004) {
            $total = round($items_total - $vatAmount, 2);
        }

        $finalAmount = round($items_total - $total_deductions + $total_additions, 2);

        $paid_amount = 0.0;
        if ($accountId) {
            $paid_amount = round((float) Payment::where('payee_account_id', $accountId)
                ->whereDate('billing_month', $monthStart)
                ->sum('amount'), 2);
        }

        $employee_balance_final = round($finalAmount - $paid_amount, 2);

        $companyId = $employeeInvoice->company_id ?? CompanyContext::id();
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
            'employee_balance' => $employee_balance,
            'rider_balance' => $employee_balance, // alias for shared partials
            'total_deductions' => $total_deductions,
            'total_additions' => $total_additions,
            'totalBeforeTax' => $total,
            'vatAmount' => $vatAmount,
            'finalAmount' => $finalAmount,
            'paid_amount' => $paid_amount,
            'employee_balance_final' => $employee_balance_final,
            'rider_balance_final' => $employee_balance_final, // alias for shared partials
            'items_total' => $items_total,
            'invoiceNumber' => $employeeInvoice->invoice_number,
        ];
    }

    /**
     * Outstanding amounts as shown on the Employee Invoice (after deductions/additions).
     *
     * @return array{final_amount: float, paid_amount: float, balance: float}
     */
    public function outstandingAmounts(EmployeeInvoices $employeeInvoice): array
    {
        $data = $this->build($employeeInvoice);

        return [
            'final_amount' => (float) $data['finalAmount'],
            'paid_amount' => (float) $data['paid_amount'],
            'balance' => (float) $data['employee_balance_final'],
        ];
    }

    /**
     * Allocate each invoice only its own period so payment-form totals do not
     * double-count earlier unpaid months.
     *
     * @param  iterable<int, EmployeeInvoices>  $invoices
     */
    public function applySequentialOutstanding($invoices): void
    {
        collect($invoices)->filter()->groupBy(function ($invoice) {
            return (int) ($invoice->employee_id ?? 0);
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
     * @return array{final_amount: float, paid_amount: float, balance: float}
     */
    public function outstandingAmountsForPeriod(EmployeeInvoices $employeeInvoice, ?string $afterBillingMonth = null): array
    {
        if ($afterBillingMonth === null) {
            return $this->outstandingAmounts($employeeInvoice);
        }

        $monthStart = date('Y-m-01', strtotime((string) $employeeInvoice->billing_month));
        $afterMonth = date('Y-m-01', strtotime($afterBillingMonth));
        $itemsTotal = $this->invoiceItemsTotal($employeeInvoice);

        if ($afterMonth === $monthStart) {
            return [
                'final_amount' => $itemsTotal,
                'paid_amount' => 0.0,
                'balance' => $itemsTotal,
            ];
        }

        $accountId = $employeeInvoice->employee?->account_id
            ? (int) $employeeInvoice->employee->account_id
            : null;
        $ledger = $this->ledgerAdjustmentsForMonth($accountId, $monthStart);
        $monthDeductions = (float) array_sum(array_column($ledger['deductions'], 'amount'));
        $monthAdditions = (float) array_sum(array_column($ledger['additions'], 'amount'));

        $carry = 0.0;
        if ($accountId) {
            $carry = round((float) Transactions::query()
                ->where('account_id', $accountId)
                ->whereDate('billing_month', '>', $afterMonth)
                ->whereDate('billing_month', '<', $monthStart)
                ->sum(DB::raw('debit - credit')), 2);
        }

        $totalDeductions = round($monthDeductions + ($carry > 0 ? $carry : 0), 2);
        $totalAdditions = round($monthAdditions + ($carry < 0 ? abs($carry) : 0), 2);
        $finalAmount = round($itemsTotal - $totalDeductions + $totalAdditions, 2);

        $paidAmount = 0.0;
        if ($accountId) {
            $paidAmount = round((float) Payment::where('payee_account_id', $accountId)
                ->whereDate('billing_month', $monthStart)
                ->sum('amount'), 2);
        }

        return [
            'final_amount' => $finalAmount,
            'paid_amount' => $paidAmount,
            'balance' => round($finalAmount - $paidAmount, 2),
        ];
    }

    private function invoiceItemsTotal(EmployeeInvoices $employeeInvoice): float
    {
        $items = $employeeInvoice->items ?? collect();
        $total = round((float) $items->sum('amount'), 2);
        $storedVat = round((float) ($employeeInvoice->vat ?? 0), 2);
        $vatAmount = abs($storedVat) > 0.004 ? $storedVat : 0.0;

        if (abs($total) > 0.004) {
            return round($total + $vatAmount, 2);
        }

        return round((float) ($employeeInvoice->total_amount ?? 0), 2);
    }
}
