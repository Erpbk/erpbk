<?php

namespace App\Services\Voucher;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\Currency;
use App\Helpers\General;
use App\Models\Accounts;
use App\Models\Employee;
use App\Models\Riders;
use App\Models\Settings;
use App\Models\User;
use App\Models\Vouchers;
use App\Services\Agreements\AgreementPdfBranding;
use App\Support\CompanyContext;
use App\Support\RiderPaymentVoucherDefaults;
use App\Support\VoucherAccountKind;

/**
 * View-model for Rider-module vouchers (+ JV) using Payment Voucher document chrome.
 */
class RiderModuleVoucherViewData
{
    /**
     * Preferred types that always use Payment-style chrome.
     * Other non-visa types also use it via supports() so View never falls back to classic.
     *
     * @var list<string>
     */
    public const RIDER_DOCUMENT_TYPES = ['COD', 'PN', 'INC', 'VC', 'AL', 'PAY', 'JV'];

    /** @var list<string> */
    private const VISA_DOCUMENT_TYPES = ['LV', 'VL'];

    public function __construct(
        protected AgreementPdfBranding $branding,
        protected VoucherSignatureResolver $signatureResolver
    ) {}

    public function supports(Vouchers $voucher): bool
    {
        $type = strtoupper((string) $voucher->voucher_type);
        // Visa expenses keep VisaExpenseVoucherViewData chrome.
        if (in_array($type, self::VISA_DOCUMENT_TYPES, true)) {
            return false;
        }

        return true;
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

        $payee = $this->resolvePayee($voucher);
        $partyAccount = $payee['account'];
        $rider = $payee['rider'];
        $counterAccount = $this->counterAccount($voucher, $partyAccount?->id, $rider);
        $typeCode = strtoupper((string) $voucher->voucher_type);
        $typeLabel = General::VoucherType($typeCode) ?: $typeCode;
        $voucherNo = $typeCode.'-'.str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT);

        $narration = trim((string) ($voucher->remarks ?? ''));
        if ($narration === '' || strcasecmp($narration, 'Journal Voucher') === 0) {
            $txNarration = trim((string) ($voucher->transactions->first()->narration ?? ''));
            if ($txNarration !== '') {
                $narration = $txNarration;
            } elseif ($narration === '') {
                $narration = '—';
            }
        }

        $amountInWords = Account::convertNumberToWord(number_format($amount, 2, '.', ''));
        if (is_string($amountInWords) && $amountInWords !== '') {
            $amountInWords = ucwords(strtolower($amountInWords)).' Only';
        } else {
            $amountInWords = '—';
        }

        $attach = (string) ($voucher->attach_file ?? '');
        $attachmentUrl = $attach !== ''
            ? (in_array($typeCode, ['RFV', 'LV'], true)
                ? url('storage/'.ltrim($attach, '/'))
                : url('storage/vouchers/'.ltrim($attach, '/')))
            : null;

        $paymentMode = $this->resolvePaymentMode($voucher, $counterAccount);
        $lines = $this->buildLineRows($voucher);
        $riderModuleLabel = trim((string) Settings::getMenuLabel('riders')) ?: 'Riders';
        $moduleBadgeLabel = in_array($typeCode, self::RIDER_DOCUMENT_TYPES, true)
            ? $riderModuleLabel
            : (General::VoucherType($typeCode) ?: $typeCode);

        return [
            'voucher' => $voucher,
            'voucherTypeCode' => $typeCode,
            'settings' => $settings,
            'brand' => $brand,
            'documentTitle' => strtoupper($typeLabel),
            'companyName' => $brand['name'] ?? ($brand['company_name'] ?? '—'),
            'companyTrn' => trim((string) (
                (auth()->user() ? (Common::getSetting('vat_number') ?? '') : '')
                ?: ($brand['trn'] ?? '')
            )),
            'companyPhone' => trim((string) ($brand['phone'] ?? '')),
            'companyEmail' => trim((string) ($brand['email'] ?? '')),
            'companyAddress' => trim((string) ($brand['address'] ?? '')),
            'logoSrc' => $brand['logo_src'] ?? ($brand['logo_url'] ?? null),
            'voucherNo' => $voucherNo,
            'referenceNo' => $voucher->reference_number ?: $voucherNo,
            'voucherDate' => $voucher->trans_date
                ? Common::DateFormat($voucher->trans_date)
                : '—',
            // Paid To must match the debit party (same person as line/posted accounts).
            'payeeName' => $payee['name'],
            'riderCode' => $payee['code'],
            'billingMonth' => $voucher->billing_month
                ? Common::MonthFormat($voucher->billing_month)
                : '—',
            'counterAccount' => $this->accountLabel($counterAccount),
            'paidBy' => $this->accountLabel($counterAccount),
            'createdBy' => $this->userName($voucher->Created_By) ?: '—',
            'voucherTypeLabel' => $typeLabel,
            'paymentType' => $typeLabel,
            'paymentMode' => $paymentMode,
            'amount' => $amount,
            'currencyCode' => $currencyCode,
            'narration' => $narration,
            'amountInWords' => $amountInWords,
            'attachmentUrl' => $attachmentUrl,
            'attachmentName' => $attach !== '' ? basename($attach) : null,
            'lines' => $lines,
            // JV lists both debit+credit rows — total is the voucher amount, not the sum of both sides.
            'lineTotal' => $typeCode === 'JV'
                ? $amount
                : (round(collect($lines)->sum('amount'), 2) ?: $amount),
            'postedTransactions' => $voucher->transactions ?? collect(),
            'finDetail' => $typeCode === 'RFV'
                ? company_table('rta_fines')->where('id', $voucher->ref_id)->first()
                : null,
            'formatMoney' => static fn (float $n) => Currency::format($n),
            'riderModuleLabel' => $riderModuleLabel,
            'moduleBadge' => trim((string) $moduleBadgeLabel).' - '.$typeCode,
            'signatureSlots' => $this->signatureResolver->slotsForVoucherModule(
                $typeCode,
                CompanyContext::id() ?? ($voucher->company_id ? (int) $voucher->company_id : null),
                ! empty($voucher->Created_By) ? (int) $voucher->Created_By : null
            ),
        ];
    }

    /**
     * @return list<array{account:string,narration:string,amount:float}>
     */
    private function buildLineRows(Vouchers $voucher): array
    {
        $type = strtoupper((string) $voucher->voucher_type);
        // Journal shows both sides (debit + credit), matching the Payment-style document screenshot.
        $txs = $type === 'JV'
            ? $voucher->transactions->values()
            : $voucher->transactions
                ->filter(fn ($t) => (float) ($t->debit ?? 0) > 0.00001)
                ->values();

        if ($txs->isEmpty()) {
            $txs = $voucher->transactions->values();
        }

        $rows = [];
        foreach ($txs as $tx) {
            $debit = (float) ($tx->debit ?? 0);
            $credit = (float) ($tx->credit ?? 0);
            $amt = $debit > 0.00001 ? $debit : $credit;
            if ($amt < 0.00001) {
                continue;
            }
            $rows[] = [
                'account' => $this->accountLabel($tx->account),
                'narration' => trim((string) ($tx->narration ?? '')) ?: '—',
                'amount' => round($amt, 2),
            ];
        }

        return $rows;
    }

    private function resolvePaymentMode(Vouchers $voucher, ?Accounts $counterAccount): string
    {
        $fromList = $this->paymentTypeLabel($voucher->payment_type);
        if (in_array($fromList, ['Select', '—', '0', ''], true) || (int) $voucher->payment_type === 0) {
            $fromList = '';
        }

        if ($fromList !== '' && ! in_array($fromList, ['1', '2', '3', '4'], true)) {
            return $fromList;
        }

        if ($counterAccount) {
            $kind = VoucherAccountKind::classifyAccount($counterAccount);
            if ($kind === VoucherAccountKind::KIND_CASH) {
                return 'Cash';
            }
            if ($kind === VoucherAccountKind::KIND_BANK) {
                return 'Bank';
            }
        }

        return $fromList !== '' ? $fromList : 'Account';
    }

    /**
     * Paid To person/code — always aligned with the primary debit (posted) account.
     *
     * @return array{name:string,code:string,account:?Accounts,rider:?Riders}
     */
    private function resolvePayee(Vouchers $voucher): array
    {
        $partyAccount = $this->primaryPartyAccount($voucher);
        $fallbackName = $partyAccount->name ?? '—';
        $fallbackCode = $partyAccount->account_code ?? '—';

        if ($partyAccount) {
            $refName = strtolower(trim((string) ($partyAccount->ref_name ?? '')));
            $refId = (int) ($partyAccount->ref_id ?? 0);

            if ($refId > 0 && in_array($refName, ['employee', 'employees'], true)) {
                $employee = Employee::withTrashed()->find($refId);
                if ($employee) {
                    return [
                        'name' => trim((string) ($employee->name ?: $fallbackName)) ?: '—',
                        'code' => trim((string) ($employee->employee_id ?: $fallbackCode)) ?: '—',
                        'account' => $partyAccount,
                        'rider' => null,
                    ];
                }
            }

            if ($refId > 0 && $refName === 'rider') {
                $rider = Riders::withTrashed()->find($refId);
                if ($rider) {
                    return [
                        'name' => trim((string) ($rider->name ?: $fallbackName)) ?: '—',
                        'code' => trim((string) ($rider->rider_id ?: $fallbackCode)) ?: '—',
                        'account' => $partyAccount,
                        'rider' => $rider,
                    ];
                }
            }

            // Non-person accounts: still show the debit account label (matches lines).
            return [
                'name' => trim((string) $fallbackName) ?: '—',
                'code' => trim((string) $fallbackCode) ?: '—',
                'account' => $partyAccount,
                'rider' => null,
            ];
        }

        $rider = $this->resolveRiderFallback($voucher);
        if ($rider) {
            return [
                'name' => trim((string) ($rider->name ?? '')) ?: '—',
                'code' => trim((string) ($rider->rider_id ?? '')) ?: '—',
                'account' => null,
                'rider' => $rider,
            ];
        }

        return [
            'name' => '—',
            'code' => '—',
            'account' => null,
            'rider' => null,
        ];
    }

    /**
     * Primary "who was paid" account = first debit line (same source as voucher lines).
     */
    private function primaryPartyAccount(Vouchers $voucher): ?Accounts
    {
        $paymentTo = (int) ($voucher->payment_to ?? 0);
        if ($paymentTo > 0) {
            $toAccount = Accounts::query()->find($paymentTo);
            // Prefer payment_to only when it also appears on a debit line (keeps Paid To in sync).
            if ($toAccount && $voucher->transactions->contains(function ($t) use ($paymentTo) {
                return (int) ($t->account_id ?? 0) === $paymentTo
                    && (float) ($t->debit ?? 0) > 0.00001;
            })) {
                return $toAccount;
            }
        }

        $tx = $voucher->transactions
            ->first(fn ($t) => (float) ($t->debit ?? 0) > 0.00001)
            ?: $voucher->transactions->first();

        return $tx?->account;
    }

    private function resolveRiderFallback(Vouchers $voucher): ?Riders
    {
        $type = strtoupper((string) $voucher->voucher_type);
        $riderId = (int) ($voucher->rider_id ?: 0);
        // PV/RV/TRF/JV ref_id is usually Payment/other id — do not treat as rider pk.
        if ($riderId <= 0 && ! in_array($type, ['PV', 'RV', 'TRF', 'JV'], true)) {
            $riderId = (int) ($voucher->ref_id ?: 0);
        }
        if ($riderId > 0) {
            $rider = Riders::withTrashed()->find($riderId);
            if ($rider) {
                return $rider;
            }
        }

        return null;
    }

    private function counterAccount(Vouchers $voucher, ?int $partyAccountId, ?Riders $rider = null): ?Accounts
    {
        $paymentFromId = (int) ($voucher->payment_from ?? 0);
        if ($paymentFromId > 0 && $paymentFromId !== (int) $partyAccountId) {
            $fromPayment = Accounts::query()->find($paymentFromId);
            if ($fromPayment) {
                return $fromPayment;
            }
        }

        // Prefer a credit line for "Paid By".
        $credit = $voucher->transactions->first(function ($t) use ($partyAccountId, $rider) {
            if ($partyAccountId && (int) $t->account_id === (int) $partyAccountId) {
                return false;
            }
            $account = $t->account;
            if ($rider && $account
                && strcasecmp((string) ($account->ref_name ?? ''), 'Rider') === 0
                && (int) ($account->ref_id ?? 0) === (int) $rider->id) {
                return false;
            }

            return (float) ($t->credit ?? 0) > 0.00001;
        });

        if ($credit?->account) {
            return $credit->account;
        }

        $other = $voucher->transactions->first(function ($t) use ($partyAccountId, $rider) {
            if ($partyAccountId && (int) $t->account_id === (int) $partyAccountId) {
                return false;
            }
            $account = $t->account;
            if ($rider && $account
                && strcasecmp((string) ($account->ref_name ?? ''), 'Rider') === 0
                && (int) ($account->ref_id ?? 0) === (int) $rider->id) {
                return false;
            }

            return true;
        });

        return $other?->account;
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
