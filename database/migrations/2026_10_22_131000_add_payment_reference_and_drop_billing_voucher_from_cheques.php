<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cheques')) {
            return;
        }

        if (! Schema::hasColumn('cheques', 'payment_reference')) {
            DB::statement(
                'ALTER TABLE `cheques` ADD COLUMN `payment_reference` JSON NULL AFTER `cleared_amount`'
            );
        }

        $this->backfillPaymentReference();

        // Partials should not keep a cleared_date; only fully Cleared rows keep one.
        DB::table('cheques')
            ->where('status', '!=', 'Cleared')
            ->whereNotNull('cleared_date')
            ->update(['cleared_date' => null]);

        if (Schema::hasColumn('cheques', 'billing_month')) {
            DB::statement('ALTER TABLE `cheques` DROP COLUMN `billing_month`');
        }

        if (Schema::hasColumn('cheques', 'voucher_id')) {
            DB::statement('ALTER TABLE `cheques` DROP COLUMN `voucher_id`');
        }

        if (Schema::hasTable('cheque_field_category_assignments')) {
            DB::table('cheque_field_category_assignments')
                ->where('field_key', 'billing_month')
                ->delete();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cheques')) {
            return;
        }

        if (! Schema::hasColumn('cheques', 'billing_month')) {
            DB::statement(
                'ALTER TABLE `cheques` ADD COLUMN `billing_month` DATE NULL AFTER `stop_payment_date`'
            );
        }

        if (! Schema::hasColumn('cheques', 'voucher_id')) {
            DB::statement(
                'ALTER TABLE `cheques` ADD COLUMN `voucher_id` BIGINT UNSIGNED NULL AFTER `is_security`'
            );
        }

        // Restore latest voucher_id from payment_reference when present.
        if (Schema::hasColumn('cheques', 'payment_reference')) {
            DB::table('cheques')
                ->whereNotNull('payment_reference')
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        $refs = json_decode($row->payment_reference ?? '[]', true);
                        if (! is_array($refs) || $refs === []) {
                            continue;
                        }
                        $last = end($refs);
                        $voucherId = (int) ($last['voucher_id'] ?? 0);
                        if ($voucherId > 0) {
                            DB::table('cheques')->where('id', $row->id)->update([
                                'voucher_id' => $voucherId,
                            ]);
                        }
                    }
                });

            DB::statement('ALTER TABLE `cheques` DROP COLUMN `payment_reference`');
        }

        if (Schema::hasTable('cheque_field_category_assignments') && Schema::hasTable('cheque_categories')) {
            $categoryId = DB::table('cheque_categories')->where('slug', 'cheque_details')->value('id')
                ?? DB::table('cheque_categories')->where('slug', 'other')->value('id');
            if ($categoryId) {
                $maxOrder = (int) DB::table('cheque_field_category_assignments')->max('display_order');
                DB::table('cheque_field_category_assignments')->updateOrInsert(
                    ['field_key' => 'billing_month', 'company_id' => null],
                    [
                        'category_id' => $categoryId,
                        'display_order' => $maxOrder + 1,
                        'is_visible' => true,
                        'is_required' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    private function backfillPaymentReference(): void
    {
        $hasPaymentLinks = Schema::hasTable('payments') && Schema::hasColumn('payments', 'resource_links');
        $hasReceiptLinks = Schema::hasTable('receipts') && Schema::hasColumn('receipts', 'resource_links');
        $hasPaymentBankCharges = Schema::hasTable('payments') && Schema::hasColumn('payments', 'bank_charges');

        DB::table('cheques')->orderBy('id')->chunkById(100, function ($cheques) use (
            $hasPaymentLinks,
            $hasReceiptLinks,
            $hasPaymentBankCharges
        ) {
            foreach ($cheques as $cheque) {
                $entries = [];

                if ($cheque->type === 'payable' && $hasPaymentLinks) {
                    $payments = DB::table('payments')
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(resource_links, '$.cheque_id')) = ?", [(string) $cheque->id])
                        ->orderBy('id')
                        ->get([
                            'id',
                            'amount',
                            'bank_charges',
                            'voucher_id',
                            'date_of_payment',
                        ]);

                    foreach ($payments as $payment) {
                        $charges = $hasPaymentBankCharges ? (float) ($payment->bank_charges ?? 0) : 0.0;
                        $clearAmount = round(max(0, (float) $payment->amount - $charges), 2);
                        if ($clearAmount < 0.01 || ! $payment->voucher_id) {
                            continue;
                        }
                        $entries[] = [
                            'date' => $payment->date_of_payment
                                ? substr((string) $payment->date_of_payment, 0, 10)
                                : null,
                            'amount' => $clearAmount,
                            'voucher_id' => (int) $payment->voucher_id,
                        ];
                    }
                } elseif ($cheque->type === 'receiveable' && $hasReceiptLinks) {
                    $receipts = DB::table('receipts')
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(resource_links, '$.cheque_id')) = ?", [(string) $cheque->id])
                        ->orderBy('id')
                        ->get(['id', 'amount', 'voucher_id', 'date_of_receipt']);

                    foreach ($receipts as $receipt) {
                        $clearAmount = round((float) $receipt->amount, 2);
                        if ($clearAmount < 0.01 || ! $receipt->voucher_id) {
                            continue;
                        }
                        $entries[] = [
                            'date' => $receipt->date_of_receipt
                                ? substr((string) $receipt->date_of_receipt, 0, 10)
                                : null,
                            'amount' => $clearAmount,
                            'voucher_id' => (int) $receipt->voucher_id,
                        ];
                    }
                }

                // Legacy: single voucher_id with no resource_links matches
                if ($entries === [] && ! empty($cheque->voucher_id) && Schema::hasColumn('cheques', 'voucher_id')) {
                    if ($cheque->type === 'payable' && Schema::hasTable('payments')) {
                        $payment = DB::table('payments')->where('voucher_id', $cheque->voucher_id)->first();
                        if ($payment) {
                            $charges = $hasPaymentBankCharges ? (float) ($payment->bank_charges ?? 0) : 0.0;
                            $clearAmount = round(max(0, (float) $payment->amount - $charges), 2);
                            if ($clearAmount >= 0.01) {
                                $entries[] = [
                                    'date' => $payment->date_of_payment
                                        ? substr((string) $payment->date_of_payment, 0, 10)
                                        : ($cheque->cleared_date ? substr((string) $cheque->cleared_date, 0, 10) : null),
                                    'amount' => $clearAmount,
                                    'voucher_id' => (int) $cheque->voucher_id,
                                ];
                            }
                        }
                    } elseif ($cheque->type === 'receiveable' && Schema::hasTable('receipts')) {
                        $receipt = DB::table('receipts')->where('voucher_id', $cheque->voucher_id)->first();
                        if ($receipt) {
                            $clearAmount = round((float) $receipt->amount, 2);
                            if ($clearAmount >= 0.01) {
                                $entries[] = [
                                    'date' => $receipt->date_of_receipt
                                        ? substr((string) $receipt->date_of_receipt, 0, 10)
                                        : ($cheque->cleared_date ? substr((string) $cheque->cleared_date, 0, 10) : null),
                                    'amount' => $clearAmount,
                                    'voucher_id' => (int) $cheque->voucher_id,
                                ];
                            }
                        }
                    }

                    // Last resort: keep voucher_id with cheque amount/cleared_amount when doc row missing
                    if ($entries === []) {
                        $amount = round((float) ($cheque->cleared_amount ?: $cheque->amount), 2);
                        if ($amount >= 0.01) {
                            $entries[] = [
                                'date' => $cheque->cleared_date
                                    ? substr((string) $cheque->cleared_date, 0, 10)
                                    : null,
                                'amount' => $amount,
                                'voucher_id' => (int) $cheque->voucher_id,
                            ];
                        }
                    }
                }

                $update = [
                    'payment_reference' => $entries === [] ? null : json_encode($entries),
                ];

                if ($entries !== []) {
                    $cleared = round(array_sum(array_column($entries, 'amount')), 2);
                    $update['cleared_amount'] = $cleared;
                    $lastDate = null;
                    foreach ($entries as $entry) {
                        if (! empty($entry['date'])) {
                            $lastDate = $entry['date'];
                        }
                    }
                    if ($cheque->status === 'Cleared') {
                        $update['cleared_date'] = $lastDate ?: ($cheque->cleared_date
                            ? substr((string) $cheque->cleared_date, 0, 10)
                            : null);
                    }
                }

                DB::table('cheques')->where('id', $cheque->id)->update($update);
            }
        });
    }
};
