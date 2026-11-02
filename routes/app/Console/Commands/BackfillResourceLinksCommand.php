<?php

namespace App\Console\Commands;

use App\Models\Cheques;
use App\Models\CustomerInvoices;
use App\Models\EmployeeInvoices;
use App\Models\LeasingCompanyBillingInvoice;
use App\Models\LeasingCompanyInvoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\RiderInvoices;
use App\Models\SimInvoice;
use App\Models\SupplierInvoices;
use App\Support\ResourceLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillResourceLinksCommand extends Command
{
    protected $signature = 'resource-links:backfill
                            {--dry-run : Report changes without writing}
                            {--only= : payments|receipts (default: both)}';

    protected $description = 'Backfill payments.resource_links and receipts.resource_links from partial_paid_amount / legacy reference / cheque voucher';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $only = $this->option('only');

        if ($dryRun) {
            $this->info('Dry run — no rows will be updated.');
        }

        $paymentsUpdated = 0;
        $receiptsUpdated = 0;

        if ($only === null || $only === 'payments') {
            if (! Schema::hasColumn('payments', 'resource_links')) {
                $this->error('payments.resource_links column missing — run migrations first.');

                return self::FAILURE;
            }
            $paymentsUpdated = $this->backfillPayments($dryRun);
            $this->info("Payments updated: {$paymentsUpdated}");
        }

        if ($only === null || $only === 'receipts') {
            if (! Schema::hasColumn('receipts', 'resource_links')) {
                $this->error('receipts.resource_links column missing — run migrations first.');

                return self::FAILURE;
            }
            $receiptsUpdated = $this->backfillReceipts($dryRun);
            $this->info("Receipts updated: {$receiptsUpdated}");
        }

        return self::SUCCESS;
    }

    private function backfillPayments(bool $dryRun): int
    {
        $index = $this->buildPaymentAllocationIndex();
        $updated = 0;

        Payment::query()
            ->where(function ($q) {
                $q->whereNull('resource_links')
                    ->orWhere('resource_links', '')
                    ->orWhere('resource_links', '[]')
                    ->orWhere('resource_links', '{}');
            })
            ->orderBy('id')
            ->chunkById(200, function ($payments) use (&$updated, $index, $dryRun) {
                foreach ($payments as $payment) {
                    if (! ResourceLinks::isEmpty(is_array($payment->resource_links) ? $payment->resource_links : null)) {
                        continue;
                    }

                    $links = $this->resolvePaymentLinks($payment, $index);
                    if (ResourceLinks::isEmpty($links)) {
                        continue;
                    }

                    $updated++;
                    if ($dryRun) {
                        $this->line("  [payment #{$payment->id}] would set invoice_type=".($links['invoice_type'] ?? 'null').' invoices='.count($links['invoices'] ?? []));

                        continue;
                    }

                    $payment->resource_links = $links;
                    $payment->save();
                }
            });

        return $updated;
    }

    private function backfillReceipts(bool $dryRun): int
    {
        $index = $this->buildReceiptAllocationIndex();
        $updated = 0;

        Receipt::query()
            ->where(function ($q) {
                $q->whereNull('resource_links')
                    ->orWhere('resource_links', '')
                    ->orWhere('resource_links', '[]')
                    ->orWhere('resource_links', '{}');
            })
            ->orderBy('id')
            ->chunkById(200, function ($receipts) use (&$updated, $index, $dryRun) {
                foreach ($receipts as $receipt) {
                    if (! ResourceLinks::isEmpty(is_array($receipt->resource_links) ? $receipt->resource_links : null)) {
                        continue;
                    }

                    $links = $this->resolveReceiptLinks($receipt, $index);
                    if (ResourceLinks::isEmpty($links)) {
                        continue;
                    }

                    $updated++;
                    if ($dryRun) {
                        $this->line("  [receipt #{$receipt->id}] would set invoice_type=".($links['invoice_type'] ?? 'null').' invoices='.count($links['invoices'] ?? []));

                        continue;
                    }

                    $receipt->resource_links = $links;
                    $receipt->save();
                }
            });

        return $updated;
    }

    /**
     * @return array<int, array{invoice_type:string, invoices:list<array{id:int, amount:float, number:?string}>}>
     */
    private function buildPaymentAllocationIndex(): array
    {
        $index = [];

        $sources = [
            'rider' => [RiderInvoices::class, 'rider'],
            'employee' => [EmployeeInvoices::class, 'employee'],
            'sim' => [SimInvoice::class, 'sim'],
            'supplier' => [SupplierInvoices::class, 'supplier'],
            'leasingCompany' => [LeasingCompanyInvoice::class, 'leasingCompany'],
        ];

        foreach ($sources as [$modelClass, $type]) {
            $modelClass::query()
                ->whereNotNull('partial_paid_amount')
                ->orderBy('id')
                ->chunkById(200, function ($invoices) use (&$index, $type) {
                    foreach ($invoices as $invoice) {
                        $partials = $invoice->partial_paid_amount ?? [];
                        if (! is_array($partials) || $partials === []) {
                            continue;
                        }
                        $number = is_string($invoice->invoice_number ?? null) && $invoice->invoice_number !== ''
                            ? $invoice->invoice_number
                            : null;
                        foreach ($partials as $paymentId => $amount) {
                            $pid = (int) $paymentId;
                            if ($pid <= 0) {
                                continue;
                            }
                            if (! isset($index[$pid])) {
                                $index[$pid] = [
                                    'invoice_type' => $type,
                                    'invoices' => [],
                                ];
                            }
                            // Prefer first discovered type; skip mixing types on one payment.
                            if ($index[$pid]['invoice_type'] !== $type) {
                                continue;
                            }
                            $index[$pid]['invoices'][] = [
                                'id' => (int) $invoice->id,
                                'amount' => round((float) $amount, 2),
                                'number' => $number,
                            ];
                        }
                    }
                });
        }

        return $index;
    }

    /**
     * @return array<int, array{invoice_type:string, invoices:list<array{id:int, amount:float, number:?string}>}>
     */
    private function buildReceiptAllocationIndex(): array
    {
        $index = [];

        $sources = [
            'customer' => CustomerInvoices::class,
            'leasingBilling' => LeasingCompanyBillingInvoice::class,
        ];

        foreach ($sources as $type => $modelClass) {
            $modelClass::query()
                ->whereNotNull('partial_paid_amount')
                ->orderBy('id')
                ->chunkById(200, function ($invoices) use (&$index, $type) {
                    foreach ($invoices as $invoice) {
                        $partials = $invoice->partial_paid_amount ?? [];
                        if (! is_array($partials) || $partials === []) {
                            continue;
                        }
                        $number = is_string($invoice->invoice_number ?? null) && $invoice->invoice_number !== ''
                            ? $invoice->invoice_number
                            : null;
                        foreach ($partials as $receiptId => $amount) {
                            $rid = (int) $receiptId;
                            if ($rid <= 0) {
                                continue;
                            }
                            if (! isset($index[$rid])) {
                                $index[$rid] = [
                                    'invoice_type' => $type,
                                    'invoices' => [],
                                ];
                            }
                            if ($index[$rid]['invoice_type'] !== $type) {
                                continue;
                            }
                            $index[$rid]['invoices'][] = [
                                'id' => (int) $invoice->id,
                                'amount' => round((float) $amount, 2),
                                'number' => $number,
                            ];
                        }
                    }
                });
        }

        return $index;
    }

    /**
     * @param  array<int, array{invoice_type:string, invoices:list<array{id:int, amount:float, number:?string}>}>  $index
     * @return array<string, mixed>
     */
    private function resolvePaymentLinks(Payment $payment, array $index): array
    {
        $chequeId = null;
        if ($payment->amount_type === 'Cheque' && $payment->voucher_id) {
            $cheque = Cheques::findByClearVoucherId((int) $payment->voucher_id);
            $chequeId = $cheque?->id ? (int) $cheque->id : null;
        }

        if (isset($index[$payment->id])) {
            $entry = $index[$payment->id];

            return ResourceLinks::build(
                $entry['invoice_type'],
                array_column($entry['invoices'], 'id'),
                collect($entry['invoices'])->mapWithKeys(fn ($r) => [$r['id'] => $r['amount']])->all(),
                collect($entry['invoices'])->map(fn ($r) => (object) [
                    'id' => $r['id'],
                    'invoice_number' => $r['number'],
                ]),
                $chequeId,
                ResourceLinks::SOURCE_PAYMENT_FORM
            );
        }

        $fromRef = $this->parsePaymentReference((string) ($payment->reference ?? ''), (string) ($payment->description ?? ''));
        if ($fromRef['invoice_type'] && $fromRef['invoice_ids'] !== []) {
            $amount = round(max(0, (float) $payment->amount - (float) ($payment->bank_charges ?? 0)), 2);
            $amounts = [];
            foreach ($fromRef['invoice_ids'] as $id) {
                $amounts[$id] = count($fromRef['invoice_ids']) === 1 ? $amount : 0.0;
            }

            return ResourceLinks::build(
                $fromRef['invoice_type'],
                $fromRef['invoice_ids'],
                $amounts,
                null,
                $chequeId,
                ResourceLinks::SOURCE_PAYMENT_FORM
            );
        }

        if ($chequeId) {
            return ResourceLinks::build(
                null,
                [],
                [],
                null,
                $chequeId,
                ResourceLinks::SOURCE_CHEQUE_CLEAR
            );
        }

        return ResourceLinks::build(null, [], [], null, null, ResourceLinks::SOURCE_PAYMENT_FORM);
    }

    /**
     * @param  array<int, array{invoice_type:string, invoices:list<array{id:int, amount:float, number:?string}>}>  $index
     * @return array<string, mixed>
     */
    private function resolveReceiptLinks(Receipt $receipt, array $index): array
    {
        $chequeId = null;
        if ($receipt->amount_type === 'Cheque' && $receipt->voucher_id) {
            $cheque = Cheques::findByClearVoucherId((int) $receipt->voucher_id);
            $chequeId = $cheque?->id ? (int) $cheque->id : null;
        }

        if (isset($index[$receipt->id])) {
            $entry = $index[$receipt->id];

            return ResourceLinks::build(
                $entry['invoice_type'],
                array_column($entry['invoices'], 'id'),
                collect($entry['invoices'])->mapWithKeys(fn ($r) => [$r['id'] => $r['amount']])->all(),
                collect($entry['invoices'])->map(fn ($r) => (object) [
                    'id' => $r['id'],
                    'invoice_number' => $r['number'],
                ]),
                $chequeId,
                ResourceLinks::SOURCE_RECEIPT_FORM
            );
        }

        $reference = (string) ($receipt->reference ?? '');
        $invoiceType = null;
        $invoiceIds = [];
        if (str_contains($reference, 'CI-')) {
            $invoiceType = 'customer';
            foreach (explode(' ', $reference) as $token) {
                $id = CustomerInvoices::getIdFromInvoiceNumber($token);
                if ($id) {
                    $invoiceIds[] = (int) $id;
                }
            }
        } elseif (str_contains($reference, 'LBI-')) {
            $invoiceType = 'leasingBilling';
            foreach (explode(' ', $reference) as $token) {
                $id = LeasingCompanyBillingInvoice::getIdFromInvoiceNumber($token);
                if ($id) {
                    $invoiceIds[] = (int) $id;
                }
            }
        }

        $invoiceIds = array_values(array_unique($invoiceIds));
        if ($invoiceType && $invoiceIds !== []) {
            $amount = round((float) $receipt->amount, 2);
            $amounts = [];
            foreach ($invoiceIds as $id) {
                $amounts[$id] = count($invoiceIds) === 1 ? $amount : 0.0;
            }

            return ResourceLinks::build(
                $invoiceType,
                $invoiceIds,
                $amounts,
                null,
                $chequeId,
                ResourceLinks::SOURCE_RECEIPT_FORM
            );
        }

        if ($chequeId) {
            return ResourceLinks::build(
                null,
                [],
                [],
                null,
                $chequeId,
                ResourceLinks::SOURCE_CHEQUE_CLEAR
            );
        }

        return ResourceLinks::build(null, [], [], null, null, ResourceLinks::SOURCE_RECEIPT_FORM);
    }

    /**
     * @return array{invoice_type:?string, invoice_ids:list<int>}
     */
    private function parsePaymentReference(string $reference, string $description): array
    {
        $haystack = trim($reference.' '.$description);
        if ($haystack === '') {
            return ['invoice_type' => null, 'invoice_ids' => []];
        }

        $tokens = preg_split('/\s+/', $haystack) ?: [];

        if (str_contains($haystack, 'RINV') || preg_match('/Rider Invoice #\d+/i', $haystack)) {
            $ids = [];
            foreach ($tokens as $token) {
                if (preg_match('/RINV-?0*(\d+)/i', $token, $m)) {
                    $ids[] = (int) $m[1];
                }
            }
            if (preg_match_all('/Rider Invoice #(\d+)/i', $haystack, $m)) {
                foreach ($m[1] as $id) {
                    $ids[] = (int) $id;
                }
            }

            return ['invoice_type' => 'rider', 'invoice_ids' => array_values(array_unique($ids))];
        }

        if (str_contains($haystack, 'EMP_INV')) {
            $ids = [];
            foreach ($tokens as $token) {
                $id = EmployeeInvoices::getIdFromInvoiceNumber($token);
                if ($id) {
                    $ids[] = (int) $id;
                }
            }

            return ['invoice_type' => 'employee', 'invoice_ids' => array_values(array_unique($ids))];
        }

        if (str_contains($haystack, 'LCI')) {
            $ids = [];
            foreach ($tokens as $token) {
                $id = LeasingCompanyInvoice::getIdFromInvoiceNumber($token);
                if ($id) {
                    $ids[] = (int) $id;
                }
            }

            return ['invoice_type' => 'leasingCompany', 'invoice_ids' => array_values(array_unique($ids))];
        }

        if (str_contains($haystack, 'SUP')) {
            $ids = [];
            foreach ($tokens as $token) {
                $id = SupplierInvoices::getIdFromInvoiceNumber($token);
                if ($id) {
                    $ids[] = (int) $id;
                }
            }

            return ['invoice_type' => 'supplier', 'invoice_ids' => array_values(array_unique($ids))];
        }

        if (str_contains($haystack, 'SIMI')) {
            $ids = [];
            foreach ($tokens as $token) {
                $id = SimInvoice::getIdFromInvoiceNumber($token);
                if ($id) {
                    $ids[] = (int) $id;
                }
            }

            return ['invoice_type' => 'sim', 'invoice_ids' => array_values(array_unique($ids))];
        }

        return ['invoice_type' => null, 'invoice_ids' => []];
    }
}
