<?php

namespace App\Services\Voucher;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\Currency;
use App\Helpers\General;
use App\Models\Accounts;
use App\Models\Riders;
use App\Models\User;
use App\Models\Vouchers;
use App\Services\Agreements\AgreementPdfBranding;
use App\Support\CompanyContext;
use App\Support\RiderPaymentVoucherDefaults;

/**
 * View-model for Rider-module vouchers (COD, PN, INC, VC, AL, legacy PAY)
 * using the same document chrome as the Payment Voucher.
 */
class RiderModuleVoucherViewData
{
    /** @var list<string> */
    public const RIDER_DOCUMENT_TYPES = ['COD', 'PN', 'INC', 'VC', 'AL', 'PAY'];

    public function __construct(
        protected AgreementPdfBranding $branding,
        protected VoucherSignatureResolver $signatureResolver
    ) {}

    public function supports(Vouchers $voucher): bool
    {
        return in_array(strtoupper((string) $voucher->voucher_type), self::RIDER_DOCUMENT_TYPES, true);
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

        $rider = $this->resolveRider($voucher);
        $partyAccount = $this->partyAccount($voucher, $rider);
        $counterAccount = $this->counterAccount($voucher, $partyAccount?->id, $rider);
        $typeCode = strtoupper((string) $voucher->voucher_type);
        $typeLabel = General::VoucherType($typeCode) ?: $typeCode;
        $voucherNo = $typeCode.'-'.str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT);

        $narration = trim((string) ($voucher->remarks ?? ''));
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
            ? (in_array($typeCode, ['RFV', 'LV'], true)
                ? url('storage/'.ltrim($attach, '/'))
                : url('storage/vouchers/'.ltrim($attach, '/')))
            : null;

        $paymentTypeLabel = $this->paymentTypeLabel($voucher->payment_type);

        return [
            'voucher' => $voucher,
            'settings' => $settings,
            'brand' => $brand,
            'documentTitle' => strtoupper($typeLabel),
            'companyName' => $brand['name'] ?? ($brand['company_name'] ?? '—'),
            'logoSrc' => $brand['logo_src'] ?? ($brand['logo_url'] ?? null),
            'voucherNo' => $voucherNo,
            'referenceNo' => $voucher->reference_number ?: $voucherNo,
            'voucherDate' => $voucher->trans_date
                ? Common::DateFormat($voucher->trans_date)
                : '—',
            'payeeName' => $rider?->name
                ?: ($partyAccount->name ?? '—'),
            'riderCode' => $rider?->rider_id
                ?: ($partyAccount->account_code ?? '—'),
            'billingMonth' => $voucher->billing_month
                ? Common::MonthFormat($voucher->billing_month)
                : '—',
            'counterAccount' => $this->accountLabel($counterAccount),
            'createdBy' => $this->userName($voucher->Created_By) ?: '—',
            'voucherTypeLabel' => $typeLabel,
            'paymentMode' => $paymentTypeLabel,
            'amount' => $amount,
            'currencyCode' => $currencyCode,
            'narration' => $narration,
            'amountInWords' => $amountInWords,
            'attachmentUrl' => $attachmentUrl,
            'attachmentName' => $attach !== '' ? basename($attach) : null,
            'postedTransactions' => $voucher->transactions ?? collect(),
            'finDetail' => $typeCode === 'RFV'
                ? company_table('rta_fines')->where('id', $voucher->ref_id)->first()
                : null,
            'formatMoney' => static fn (float $n) => Currency::format($n),
            'signatureSlots' => $this->signatureResolver->slotsFor(
                $typeCode,
                CompanyContext::id() ?? $voucher->company_id
            ),
        ];
    }

    private function resolveRider(Vouchers $voucher): ?Riders
    {
        $riderId = (int) ($voucher->ref_id ?: $voucher->rider_id ?: 0);
        if ($riderId > 0) {
            $rider = Riders::withTrashed()->find($riderId);
            if ($rider) {
                return $rider;
            }
        }

        $party = $this->partyAccount($voucher);
        if ($party && strcasecmp((string) ($party->ref_name ?? ''), 'Rider') === 0 && $party->ref_id) {
            return Riders::withTrashed()->find($party->ref_id);
        }

        return null;
    }

    private function partyAccount(Vouchers $voucher, ?Riders $rider = null): ?Accounts
    {
        // Prefer the rider ledger line (INC/AL credit the rider; COD/PN/VC debit the rider).
        if ($rider) {
            $riderTx = $voucher->transactions->first(function ($t) use ($rider) {
                $account = $t->account;
                if (! $account) {
                    return false;
                }

                return strcasecmp((string) ($account->ref_name ?? ''), 'Rider') === 0
                    && (int) ($account->ref_id ?? 0) === (int) $rider->id;
            });
            if ($riderTx?->account) {
                return $riderTx->account;
            }
        }

        $tx = $voucher->transactions
            ->first(fn ($t) => (float) ($t->debit ?? 0) > 0.00001)
            ?: $voucher->transactions->first();

        return $tx?->account;
    }

    private function counterAccount(Vouchers $voucher, ?int $partyAccountId, ?Riders $rider = null): ?Accounts
    {
        // Same account stored on create (INCENTIVE_ACCOUNT / COD_ACCOUNT / etc.).
        $paymentFromId = (int) ($voucher->payment_from ?? 0);
        if ($paymentFromId > 0 && $paymentFromId !== (int) $partyAccountId) {
            $fromPayment = Accounts::query()->find($paymentFromId);
            if ($fromPayment) {
                return $fromPayment;
            }
        }

        // Otherwise: non-rider / non-party line from posted transactions.
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
