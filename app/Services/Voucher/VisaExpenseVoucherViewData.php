<?php

namespace App\Services\Voucher;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\Currency;
use App\Helpers\General;
use App\Models\Accounts;
use App\Models\Employee;
use App\Models\ExpenseAccount;
use App\Models\Riders;
use App\Models\User;
use App\Models\visa_expenses;
use App\Models\visa_installment_plan;
use App\Models\Vouchers;
use App\Services\Agreements\AgreementPdfBranding;
use App\Support\CompanyContext;
use App\Support\RiderPaymentVoucherDefaults;

/**
 * View-model for Visa Expense (LV) and Visa Loan Installment (VL) vouchers,
 * using the same document chrome as Rider-module / Payment vouchers.
 */
class VisaExpenseVoucherViewData
{
    /** @var list<string> */
    public const DOCUMENT_TYPES = ['LV', 'VL'];

    public function __construct(
        protected AgreementPdfBranding $branding,
        protected VoucherSignatureResolver $signatureResolver
    ) {}

    public function supports(Vouchers $voucher): bool
    {
        return in_array(strtoupper((string) $voucher->voucher_type), self::DOCUMENT_TYPES, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Vouchers $voucher): array
    {
        $voucher->loadMissing(['transactions.account']);

        $settings = RiderPaymentVoucherDefaults::all();
        $brand = $this->branding->forCompany(CompanyContext::id() ?? $voucher->company_id);
        $currencyCode = Currency::code();
        $amount = round((float) ($voucher->amount ?? 0), 2);
        if ($amount < 0.00001) {
            $amount = round((float) $voucher->transactions->sum('debit'), 2);
        }

        $typeCode = strtoupper((string) $voucher->voucher_type);
        $typeLabel = General::VoucherType($typeCode) ?: $typeCode;
        $voucherNo = $typeCode.'-'.str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT);

        $context = $this->resolveContext($voucher, $typeCode);
        $partyAccount = $context['partyAccount'];
        $counterAccount = $this->counterAccount($voucher, $partyAccount?->id);

        $narration = trim((string) ($voucher->remarks ?? ''));
        if ($narration === '') {
            $narration = trim((string) ($context['defaultNarration'] ?? ''));
        }
        if ($narration === '') {
            $narration = trim((string) ($voucher->transactions->first()->narration ?? '')) ?: '—';
        }

        $amountInWords = Account::convertNumberToWord(number_format($amount, 2, '.', ''));
        if (is_string($amountInWords) && $amountInWords !== '') {
            $amountInWords = ucwords(strtolower($amountInWords)).' Only';
        } else {
            $amountInWords = '—';
        }

        $attach = (string) ($voucher->attach_file ?? '');
        $attachmentUrl = $attach !== ''
            ? url('storage/'.ltrim($attach, '/'))
            : null;

        $typedPaymentMode = $this->paymentTypeLabel($voucher->payment_type);
        $fallbackPaymentType = $typeCode === 'VL' ? 'Loan Installment' : 'Visa Expense';
        // Document "Payment Type" mirrors voucher kind (matches LV/VL print layout).
        $paymentType = $typeLabel ?: $fallbackPaymentType;
        // Prefer bank/cheque mode when set; otherwise show visa status / installment as the mode cue.
        $paymentMode = $typedPaymentMode !== '—'
            ? $typedPaymentMode
            : (trim((string) ($context['detailValue'] ?? '')) !== ''
                ? (string) $context['detailValue']
                : $fallbackPaymentType);

        $referenceNo = trim((string) ($voucher->reference_number ?? ''));
        if ($referenceNo === '' && ! empty($context['detailLabel']) && ! empty($context['detailValue']) && $typedPaymentMode !== '—') {
            $referenceNo = (string) $context['detailValue'];
        }
        if ($referenceNo === '') {
            $referenceNo = $voucherNo;
        }

        $billingMonth = $voucher->billing_month
            ? Common::MonthFormat($voucher->billing_month)
            : '—';
        $detailLabel = (string) ($context['detailLabel'] ?? ($typeCode === 'VL' ? 'Installment' : 'Visa Status'));
        $detailValue = trim((string) ($context['detailValue'] ?? '')) ?: '—';
        $voucherDate = $voucher->trans_date
            ? Common::DateFormat($voucher->trans_date)
            : '—';
        $payeeName = $context['payeeName'] ?: '—';
        $counterAccountLabel = $this->accountLabel($counterAccount);
        // VL installment vouchers: show rider/employee on Paid By (no bank credit side).
        $paidBy = $typeCode === 'VL' ? $payeeName : $counterAccountLabel;

        $allocations = collect([[
            'line_label' => $detailValue,
            'billing_month' => $billingMonth,
            'payment' => $amount,
        ]]);

        return [
            'voucher' => $voucher,
            'settings' => $settings,
            'brand' => $brand,
            'documentTitle' => strtoupper($typeLabel),
            'companyName' => $brand['name'] ?? ($brand['company_name'] ?? '—'),
            'logoSrc' => $brand['logo_src'] ?? ($brand['logo_url'] ?? null),
            'voucherNo' => $voucherNo,
            'referenceNo' => $referenceNo,
            'voucherDate' => $voucherDate,
            'paymentDate' => $voucherDate,
            'payeeName' => $payeeName,
            'riderCode' => $context['payeeCode'] ?: '—',
            'riderModuleLabel' => $context['personLabel'] ?? 'Rider',
            'billingMonth' => $billingMonth,
            'counterAccount' => $counterAccountLabel,
            'paidBy' => $paidBy,
            'createdBy' => $this->userName($voucher->Created_By ?? $voucher->created_by) ?: '—',
            'voucherTypeLabel' => $typeLabel,
            'paymentMode' => $paymentMode,
            'paymentType' => $paymentType,
            'detailLabel' => $detailLabel,
            'detailValue' => $detailValue,
            'visaStatus' => $typeCode === 'LV' ? $detailValue : null,
            'amount' => $amount,
            'allocatedTotal' => $amount,
            'allocations' => $allocations,
            'lineColumnLabel' => $detailLabel,
            'currencyCode' => $currencyCode,
            'narration' => $narration,
            'amountInWords' => $amountInWords,
            'attachmentUrl' => $attachmentUrl,
            'attachmentName' => $attach !== '' ? basename($attach) : null,
            'postedTransactions' => $voucher->transactions ?? collect(),
            'finDetail' => null,
            'isVisaExpenseDocument' => true,
            'formatMoney' => static fn (float $n) => Currency::format($n),
            'signatureSlots' => $this->signatureResolver->slotsFor(
                $typeCode,
                CompanyContext::id() ?? $voucher->company_id
            ),
        ];
    }

    /**
     * @return array{
     *   payeeName: string,
     *   payeeCode: string,
     *   personLabel: string,
     *   partyAccount: ?Accounts,
     *   detailLabel: ?string,
     *   detailValue: ?string,
     *   defaultNarration: ?string
     * }
     */
    private function resolveContext(Vouchers $voucher, string $typeCode): array
    {
        $empty = [
            'payeeName' => '',
            'payeeCode' => '',
            'personLabel' => 'Rider',
            'partyAccount' => null,
            'detailLabel' => null,
            'detailValue' => null,
            'defaultNarration' => null,
        ];

        if ($typeCode === 'LV') {
            $expense = visa_expenses::withTrashed()->find($voucher->ref_id);
            if (! $expense) {
                return $empty;
            }

            $person = $this->personFromExpense($expense);
            $party = $this->partyAccountFromPerson($person['rider'], $person['employee'])
                ?: $this->debitAccount($voucher);

            return [
                'payeeName' => $person['name'],
                'payeeCode' => $person['code'],
                'personLabel' => $person['label'],
                'partyAccount' => $party,
                'detailLabel' => 'Visa Status',
                'detailValue' => trim((string) ($expense->visa_status ?? '')) ?: null,
                'defaultNarration' => trim((string) ($expense->detail ?? '')) ?: null,
            ];
        }

        $plan = visa_installment_plan::withTrashed()->find($voucher->ref_id);
        if (! $plan) {
            return $empty;
        }

        $expenseAccount = ExpenseAccount::query()->find($plan->rider_id);
        $person = $this->personFromExpenseAccount($expenseAccount);
        $party = $this->partyAccountFromPerson($person['rider'], $person['employee'])
            ?: ($expenseAccount?->account_id ? Accounts::query()->find($expenseAccount->account_id) : null)
            ?: $this->debitAccount($voucher);

        $installmentNo = null;
        if (is_string($plan->narration) && preg_match('/installment\s+(\d+)/i', $plan->narration, $m)) {
            $installmentNo = $m[1];
        }

        return [
            'payeeName' => $person['name'] ?: ($expenseAccount->name ?? ''),
            'payeeCode' => $person['code'] ?: (string) ($plan->rider_id ?? ''),
            'personLabel' => $person['label'],
            'partyAccount' => $party,
            'detailLabel' => 'Installment',
            'detailValue' => $installmentNo,
            'defaultNarration' => trim((string) ($plan->narration ?? '')) ?: null,
        ];
    }

    /**
     * @return array{name: string, code: string, label: string, rider: ?Riders, employee: ?Employee}
     */
    private function personFromExpense(visa_expenses $expense): array
    {
        if ($expense->employee_id) {
            $employee = Employee::withTrashed()->find($expense->employee_id);
            if ($employee) {
                return [
                    'name' => (string) ($employee->name ?? ''),
                    'code' => (string) ($employee->employee_id ?? $employee->id ?? ''),
                    'label' => 'Employee',
                    'rider' => null,
                    'employee' => $employee,
                ];
            }
        }

        if ($expense->rider_id) {
            $rider = Riders::withTrashed()->find($expense->rider_id);
            if ($rider) {
                return [
                    'name' => (string) ($rider->name ?? ''),
                    'code' => (string) ($rider->rider_id ?? $rider->id ?? ''),
                    'label' => 'Rider',
                    'rider' => $rider,
                    'employee' => null,
                ];
            }
        }

        $account = ExpenseAccount::query()->find($expense->expense_account_id);

        return $this->personFromExpenseAccount($account);
    }

    /**
     * @return array{name: string, code: string, label: string, rider: ?Riders, employee: ?Employee}
     */
    private function personFromExpenseAccount(?ExpenseAccount $account): array
    {
        if (! $account) {
            return ['name' => '', 'code' => '', 'label' => 'Rider', 'rider' => null, 'employee' => null];
        }

        if ($account->employee_id) {
            $employee = Employee::withTrashed()->find($account->employee_id);
            if ($employee) {
                return [
                    'name' => (string) ($employee->name ?? $account->name ?? ''),
                    'code' => (string) ($employee->employee_id ?? $employee->id ?? ''),
                    'label' => 'Employee',
                    'rider' => null,
                    'employee' => $employee,
                ];
            }
        }

        if ($account->rider_id) {
            $rider = Riders::withTrashed()->find($account->rider_id);
            if ($rider) {
                return [
                    'name' => (string) ($rider->name ?? $account->name ?? ''),
                    'code' => (string) ($rider->rider_id ?? $rider->id ?? ''),
                    'label' => 'Rider',
                    'rider' => $rider,
                    'employee' => null,
                ];
            }
        }

        return [
            'name' => (string) ($account->name ?? ''),
            'code' => (string) ($account->id ?? ''),
            'label' => 'Rider',
            'rider' => null,
            'employee' => null,
        ];
    }

    private function partyAccountFromPerson(?Riders $rider, ?Employee $employee): ?Accounts
    {
        if ($rider) {
            return Accounts::query()
                ->where('ref_name', 'Rider')
                ->where('ref_id', $rider->id)
                ->first();
        }

        if ($employee) {
            return Accounts::query()
                ->where('ref_name', 'Employee')
                ->where('ref_id', $employee->id)
                ->first();
        }

        return null;
    }

    private function debitAccount(Vouchers $voucher): ?Accounts
    {
        $tx = $voucher->transactions
            ->first(fn ($t) => (float) ($t->debit ?? 0) > 0.00001)
            ?: $voucher->transactions->first();

        return $tx?->account;
    }

    private function counterAccount(Vouchers $voucher, ?int $partyAccountId): ?Accounts
    {
        $paymentFromId = (int) ($voucher->payment_from ?? 0);
        if ($paymentFromId > 0 && $paymentFromId !== (int) $partyAccountId) {
            $fromPayment = Accounts::query()->find($paymentFromId);
            if ($fromPayment) {
                return $fromPayment;
            }
        }

        $credit = $voucher->transactions->first(function ($t) use ($partyAccountId) {
            if ((float) ($t->credit ?? 0) <= 0.00001) {
                return false;
            }
            if ($partyAccountId && (int) $t->account_id === (int) $partyAccountId) {
                return false;
            }

            return true;
        });

        return $credit?->account
            ?: $voucher->transactions->first(fn ($t) => $partyAccountId
                ? (int) $t->account_id !== (int) $partyAccountId
                : true)?->account;
    }

    private function accountLabel(?Accounts $account): string
    {
        if (! $account) {
            return '—';
        }

        return trim(($account->account_code ? $account->account_code.'-' : '').($account->name ?? '')) ?: '—';
    }

    private function paymentTypeLabel(mixed $paymentType): string
    {
        $list = Account::payment_type_list();
        if (is_array($list) && array_key_exists($paymentType, $list)) {
            return (string) $list[$paymentType];
        }

        return $paymentType !== null && $paymentType !== '' ? (string) $paymentType : '—';
    }

    private function userName(mixed $userId): ?string
    {
        if (! $userId) {
            return null;
        }

        return User::query()->whereKey($userId)->value('name');
    }
}
