<?php

namespace App\Services;

use App\Models\Cheques;
use App\Models\EmployeeInvoices;
use App\Models\LeasingCompanyInvoice;
use App\Models\Payment;
use App\Models\SimInvoice;
use App\Models\SupplierInvoices;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Models\DeleteRequest;
use Illuminate\Support\Facades\DB;

/**
 * Payment voucher deletion: queue for admin approval, or delete immediately when
 * delete-approval is disabled / bypassed.
 */
class PaymentDeletionService
{
    /**
     * Queue a delete request via the related PV voucher (soft-deletable root).
     * Payment itself is not soft-deleted; it is cascaded and removed on approval.
     */
    public static function queueDeleteRequest(Payment $payment): DeleteRequest
    {
        $payment->loadMissing('voucher');
        $voucher = $payment->voucher;

        if (! $voucher) {
            throw new \RuntimeException('Payment has no linked voucher.');
        }

        if (DeleteRequestService::hasPending($voucher) || DeleteRequestService::hasPending($payment)) {
            $existing = DeleteRequestService::lastCreatedFor($voucher)
                ?? DeleteRequestService::lastCreatedFor($payment);

            throw new \RuntimeException(
                DeleteRequestService::pendingMessage($existing)
            );
        }

        DB::beginTransaction();
        try {
            // Eloquent delete fires the interceptor and creates the pending request.
            $voucher->delete();

            $deleteRequest = request()->attributes->get('delete_approval_request')
                ?? DeleteRequestService::lastCreatedFor($voucher);

            if (! $deleteRequest) {
                DB::rollBack();
                throw new \RuntimeException('Could not create delete request for this payment voucher.');
            }

            $relatedTransactions = Transactions::where('trans_code', $voucher->trans_code)->get();
            foreach ($relatedTransactions as $transaction) {
                // Model delete while a root request is active → cascade only (no actual delete).
                $transaction->delete();
            }

            // Payment has no SoftDeletes — append manually (do not call delete()).
            $voucherLabel = ($voucher->voucher_type ?? 'PV') . '-' . $voucher->id;
            $deleteRequest->appendCascadedRecord(
                Payment::class,
                $payment->id,
                "Payment {$voucherLabel}" . ($payment->reference ? " ({$payment->reference})" : '')
            );

            DeleteRequestService::clearPendingIdsCache(Vouchers::class);
            DeleteRequestService::clearPendingIdsCache(Transactions::class);
            DeleteRequestService::clearPendingIdsCache(Payment::class);

            DB::commit();

            return $deleteRequest;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Immediate deletion (approval disabled / bypassed): voucher, transactions, payment.
     */
    public static function executeImmediate(Payment $payment): void
    {
        $payment->loadMissing('voucher');
        $voucher = $payment->voucher;

        if ($voucher) {
            Transactions::where('trans_code', $voucher->trans_code)->delete();
            Vouchers::where('id', $voucher->id)->delete();
        }

        static::applyRelatedCleanup($payment);
        $payment->delete();
    }

    /**
     * After an approved voucher delete request: revert invoice/cheque links and remove payment.
     * Voucher + transactions are already soft-deleted by DeleteRequestService.
     */
    public static function executeAfterVoucherApproved(Payment $payment): void
    {
        static::applyRelatedCleanup($payment);
        $payment->delete();
    }

    /**
     * Cheque unlink + invoice partial-payment reversals.
     */
    public static function applyRelatedCleanup(Payment $payment): void
    {
        if ($payment->amount_type == 'Cheque') {
            $cheque = Cheques::where('voucher_id', $payment->voucher_id)->first();
            if ($cheque) {
                $cheque->update([
                    'status' => 'Issued',
                    'cleared_date' => null,
                    'billing_month' => null,
                    'voucher_id' => null,
                ]);
            }
        }

        $reference = (string) ($payment->reference ?? '');

        if (str_contains($reference, 'LCI')) {
            $invoiceIds = [];
            foreach (explode(' ', $reference) as $invoice_number) {
                $id = LeasingCompanyInvoice::getIdFromInvoiceNumber($invoice_number);
                if ($id) {
                    $invoiceIds[] = $id;
                }
            }
            $invoices = LeasingCompanyInvoice::with('leasingCompany')
                ->whereIn('id', $invoiceIds)
                ->get();
            foreach ($invoices as $invoice) {
                $partialAmount = $invoice->partial_paid_amount ?? [];
                unset($partialAmount[$payment->id]);
                $invoice->partial_paid_amount = $partialAmount;
                $invoice->status = count($partialAmount) < 1 ? 0 : 3;
                $invoice->save();
            }
        }

        if (str_contains($reference, 'SUP')) {
            $invoiceIds = [];
            foreach (explode(' ', $reference) as $invoice_number) {
                $id = SupplierInvoices::getIdFromInvoiceNumber($invoice_number);
                if ($id) {
                    $invoiceIds[] = $id;
                }
            }
            $invoices = SupplierInvoices::with('supplier')
                ->where('is_invoice', true)
                ->whereIn('id', $invoiceIds)
                ->get();
            foreach ($invoices as $invoice) {
                $partialAmount = $invoice->partial_paid_amount ?? [];
                unset($partialAmount[$payment->id]);
                $invoice->partial_paid_amount = $partialAmount;
                $invoice->status = count($partialAmount) < 1 ? 'unpaid' : 'partially_paid';
                $invoice->save();
            }
        }

        if (str_contains($reference, 'EMP_INV')) {
            $invoiceIds = [];
            foreach (explode(' ', $reference) as $invoice_number) {
                $id = EmployeeInvoices::getIdFromInvoiceNumber($invoice_number);
                if ($id) {
                    $invoiceIds[] = $id;
                }
            }
            $invoices = EmployeeInvoices::with('employee')
                ->whereIn('id', $invoiceIds)
                ->get();
            foreach ($invoices as $invoice) {
                $partialAmount = $invoice->partial_paid_amount ?? [];
                unset($partialAmount[$payment->id]);
                $invoice->partial_paid_amount = $partialAmount;
                $invoice->status = count($partialAmount) < 1 ? 0 : 3;
                $invoice->save();
            }
        }

        if (str_contains($reference, 'SIMI')) {
            $invoiceIds = [];
            foreach (explode(' ', $reference) as $invoice_number) {
                $id = SimInvoice::getIdFromInvoiceNumber($invoice_number);
                if ($id) {
                    $invoiceIds[] = $id;
                }
            }
            $invoices = SimInvoice::with('vendor')
                ->whereIn('id', $invoiceIds)
                ->get();
            foreach ($invoices as $invoice) {
                $partialAmount = $invoice->partial_paid_amount ?? [];
                unset($partialAmount[$payment->id]);
                $invoice->partial_paid_amount = $partialAmount;
                $invoice->status = count($partialAmount) < 1 ? 0 : 3;
                $invoice->save();
            }
        }
    }
}
