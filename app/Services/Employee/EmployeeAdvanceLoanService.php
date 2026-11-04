<?php

namespace App\Services\Employee;

use App\Helpers\Account;
use App\Models\Accounts;
use App\Models\EmployeeInvoices;
use App\Models\Transactions;
use App\Models\VoucherType;
use App\Models\Vouchers;
use App\Support\GlobalAccounts;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployeeAdvanceLoanService
{
    /**
     * Create an Advance Loan (AL) voucher with optional employee invoice reference.
     *
     * @param  array{
     *     employee_account_id:int,
     *     bank_account_id:int,
     *     amount:float|int|string,
     *     trans_date?:string|null,
     *     billing_month?:string|null,
     *     reference_number?:string|null,
     *     payment_type?:int|null,
     *     branch_id?:int|null,
     *     invoice_id?:int|null,
     *     bank_charges?:float|int|string|null,
     *     debit_narration?:string|null,
     *     credit_narration?:string|null,
     *     remarks?:string|null,
     *     custom_field_values?:array|null,
     *     attach_file?:string|null,
     * }  $data
     * @return array{voucher: Vouchers, trans_code: int}
     */
    public function create(array $data): array
    {
        if (! VoucherType::isCodeAllowedForModule('AL', 'employees')) {
            throw new InvalidArgumentException(
                'Advance Loan voucher type (AL) is not assigned to the Employees module. Please assign it in Voucher Settings.'
            );
        }

        $employeeAccountId = (int) ($data['employee_account_id'] ?? 0);
        $bankAccountId = (int) ($data['bank_account_id'] ?? 0);
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $bankCharges = round((float) ($data['bank_charges'] ?? 0), 2);

        if ($employeeAccountId <= 0 || $bankAccountId <= 0) {
            throw new InvalidArgumentException('Employee account and bank/cash account are required.');
        }
        if ($amount < 0.01) {
            throw new InvalidArgumentException('Advance loan amount must be greater than zero.');
        }
        if ($bankCharges < 0) {
            throw new InvalidArgumentException('Bank charges cannot be negative.');
        }

        $employeeAccount = Accounts::find($employeeAccountId);
        if (! $employeeAccount) {
            throw new InvalidArgumentException('Employee account not found.');
        }

        $invoiceId = isset($data['invoice_id']) ? (int) $data['invoice_id'] : null;
        $invoice = null;
        if ($invoiceId) {
            $invoice = EmployeeInvoices::find($invoiceId);
            if (! $invoice) {
                throw new InvalidArgumentException('Selected invoice was not found.');
            }
            $employeeIdFromAccount = (int) ($employeeAccount->ref_id ?? 0);
            if ($employeeIdFromAccount > 0 && (int) $invoice->employee_id !== $employeeIdFromAccount) {
                throw new InvalidArgumentException('Selected invoice does not belong to this employee.');
            }
        }

        $billingMonth = $this->normalizeBillingMonth(
            $data['billing_month']
                ?? ($invoice && $invoice->billing_month ? $invoice->billing_month : null)
        );

        $remarks = $data['remarks'] ?? 'Advance Loan to Employee';
        if ($invoice) {
            $invoiceLabel = $invoice->invoice_number ?: ('#' . $invoice->id);
            $monthLabel = $invoice->billing_month
                ? date('M Y', strtotime((string) $invoice->billing_month))
                : date('M Y', strtotime($billingMonth));
            $remarks = trim($remarks . ' | Invoice ' . $invoiceLabel . ' (' . $monthLabel . ')');
        }

        $transCode = Account::trans_code();
        $transDate = $data['trans_date'] ?? date('Y-m-d');
        $debitNarration = $data['debit_narration'] ?? 'Advance Loan Received';
        $creditNarration = $data['credit_narration']
            ?? ('Advance Loan Given to ' . ($employeeAccount->name ?? 'Employee'));
        $bankChargesAccountId = $bankCharges > 0
            ? GlobalAccounts::account('BANK_CHARGES')->id
            : null;

        return DB::transaction(function () use (
            $data,
            $employeeAccount,
            $employeeAccountId,
            $bankAccountId,
            $amount,
            $bankCharges,
            $bankChargesAccountId,
            $billingMonth,
            $remarks,
            $transCode,
            $transDate,
            $debitNarration,
            $creditNarration,
            $invoiceId
        ) {
            $totalWithCharges = round($amount + $bankCharges, 2);
            $bankNarration = $creditNarration;
            if ($bankCharges > 0.009) {
                $bankNarration = trim($creditNarration . ' | Bank charges ' . number_format($bankCharges, 2));
            }

            $voucher = Vouchers::create([
                'trans_date' => $transDate,
                'voucher_type' => 'AL',
                'payment_type' => $data['payment_type'] ?? 1,
                'payment_from' => $bankAccountId,
                'billing_month' => $billingMonth,
                'amount' => $totalWithCharges,
                'remarks' => $remarks,
                'ref_id' => $employeeAccount->ref_id ?: $employeeAccount->id,
                'reference_number' => $data['reference_number'] ?? null,
                'invoice_id' => $invoiceId,
                'trans_code' => $transCode,
                'Created_By' => auth()->id(),
                'status' => 1,
                'branch_id' => $data['branch_id'] ?? null,
                'custom_field_values' => $data['custom_field_values'] ?? [],
                'attach_file' => $data['attach_file'] ?? null,
            ]);

            // 1. Debit employee — loan amount only
            Transactions::create([
                'account_id' => $employeeAccountId,
                'reference_id' => $voucher->id,
                'reference_type' => 'AL',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => $debitNarration,
                'debit' => $amount,
                'branch_id' => $data['branch_id'] ?? null,
                'billing_month' => $billingMonth,
                'Created_By' => auth()->id(),
            ]);

            // 2. Credit bank for loan amount + bank charges
            Transactions::create([
                'account_id' => $bankAccountId,
                'reference_id' => $voucher->id,
                'reference_type' => 'AL',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => $bankNarration,
                'credit' => $totalWithCharges,
                'billing_month' => $billingMonth,
                'Created_By' => auth()->id(),
                'branch_id' => $data['branch_id'] ?? null,
            ]);

            // 3. Debit bank charges account
            if ($bankCharges > 0.009 && $bankChargesAccountId) {
                Transactions::create([
                    'account_id' => $bankChargesAccountId,
                    'reference_id' => $voucher->id,
                    'reference_type' => 'AL',
                    'trans_code' => $transCode,
                    'trans_date' => $transDate,
                    'narration' => 'Bank charges for ( ' . $debitNarration . ' )',
                    'debit' => $bankCharges,
                    'billing_month' => $billingMonth,
                    'Created_By' => auth()->id(),
                    'branch_id' => $data['branch_id'] ?? null,
                ]);
            }

            return [
                'voucher' => $voucher,
                'trans_code' => $transCode,
            ];
        });
    }

    public function normalizeBillingMonth($input): string
    {
        if (empty($input)) {
            return date('Y-m-01');
        }
        if (preg_match('/^\d{4}-\d{2}$/', (string) $input)) {
            return $input . '-01';
        }
        $ts = strtotime((string) $input);
        if ($ts !== false) {
            return date('Y-m-01', $ts);
        }

        return date('Y-m-01');
    }

    /**
     * Map payment form amount_type string to vouchers.payment_type int.
     */
    public static function paymentTypeFromAmountType(?string $amountType): int
    {
        return match ($amountType) {
            'Cash' => 1,
            'Cheque' => 2,
            'Online' => 3,
            'Credit' => 4,
            default => 1,
        };
    }
}
