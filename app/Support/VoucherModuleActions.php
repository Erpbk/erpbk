<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Vouchers;

/**
 * Resolve edit/delete modal targets for vouchers that belong to other modules
 * (Payment, Receipt, Expense, etc.) so the Vouchers list opens the same modal
 * as the owning module.
 */
class VoucherModuleActions
{
    /**
     * @return array{
     *   edit_url: string,
     *   edit_title: string,
     *   edit_size: string,
     *   delete_url: string,
     *   module: string
     * }
     */
    public static function forVoucher(Vouchers $voucher, ?string $companySlug = null): array
    {
        $companySlug = $companySlug ?? CompanyRouteContext::slug();
        $withSlug = static function (array $params) use ($companySlug): array {
            if (! empty($companySlug)) {
                $params['company_slug'] = $companySlug;
            }

            return array_filter($params, static fn ($v) => $v !== null && $v !== '');
        };

        $voucherLabel = $voucher->voucher_type . '-' . str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT);
        $type = (string) $voucher->voucher_type;

        if ($type === 'PV') {
            $paymentId = self::resolvePaymentId($voucher);
            if ($paymentId) {
                return [
                    'edit_url' => route('payments.edit', $withSlug(['payment' => $paymentId])),
                    'edit_title' => 'Update Payment Details',
                    'edit_size' => 'xl',
                    'delete_url' => route('payments.destroy', $withSlug(['payment' => $paymentId])),
                    'module' => 'payments',
                ];
            }
        }

        if ($type === 'RV') {
            $receiptId = self::resolveReceiptId($voucher);
            if ($receiptId) {
                return [
                    'edit_url' => route('receipts.edit', $withSlug(['receipt' => $receiptId])),
                    'edit_title' => 'Update Receipt Details',
                    'edit_size' => 'xl',
                    'delete_url' => route('receipts.destroy', $withSlug(['receipt' => $receiptId])),
                    'module' => 'receipts',
                ];
            }
        }

        if ($type === 'EXP') {
            return [
                'edit_url' => route('expenses.voucher.edit', $withSlug(['id' => $voucher->id])),
                'edit_title' => 'Edit Expense Voucher ' . $voucherLabel,
                'edit_size' => 'xl',
                'delete_url' => route('expenses.voucher.destroy', $withSlug(['id' => $voucher->id])),
                'module' => 'expenses',
            ];
        }

        return [
            'edit_url' => route('vouchers.edit', $withSlug(['voucher' => $voucher->trans_code])),
            'edit_title' => 'Edit Voucher No. ' . $voucherLabel,
            'edit_size' => 'xl',
            'delete_url' => route('vouchers.destroy', $withSlug(['voucher' => $voucher->trans_code])),
            'module' => 'vouchers',
        ];
    }

    protected static function resolvePaymentId(Vouchers $voucher): ?int
    {
        if (! empty($voucher->ref_id)) {
            return (int) $voucher->ref_id;
        }

        $id = Payment::where('voucher_id', $voucher->id)->value('id');

        return $id ? (int) $id : null;
    }

    protected static function resolveReceiptId(Vouchers $voucher): ?int
    {
        if (! empty($voucher->ref_id)) {
            return (int) $voucher->ref_id;
        }

        $id = Receipt::where('voucher_id', $voucher->id)->value('id');

        return $id ? (int) $id : null;
    }
}
