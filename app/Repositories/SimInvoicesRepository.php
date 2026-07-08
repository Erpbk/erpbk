<?php

namespace App\Repositories;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Support\GlobalAccounts;
use App\Models\SimInvoice;
use App\Models\SimInvoiceItem;
use App\Models\Sims;
use App\Models\Transactions;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SimInvoicesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'inv_date',
        'vendor_id',
        'billing_month',
        'invoice_number',
        'reference_number',
        'sim_invoice_number',
        'total_amount',
        'attachment',
        'status',
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return SimInvoice::class;
    }

    public function record($request, $id = null)
    {
        DB::beginTransaction();

        try {
            $input = $request->except(['sim_id', '_method', '_token', 'rental_amount']);
            $input['billing_month'] = $request->billing_month . '-01';

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('invoice', 'public');
                $input['attachment'] = $path;
            }

            if ($id) {
                $invoice = SimInvoice::where('id', $id)->first();
                $existingInvoice = SimInvoice::where('vendor_id', $input['vendor_id'])
                    ->where('billing_month', $input['billing_month'])
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingInvoice) {
                    throw new \Exception('An invoice for this vendor has already been generated for the selected billing month.');
                }

                if (isset($input['attachment']) && $invoice->attachment) {
                    Storage::disk('public')->delete($invoice->attachment);
                }

                $invoice->update($input);
                SimInvoiceItem::where('inv_id', $id)->delete();
            } else {
                $existingInvoice = SimInvoice::where('vendor_id', $input['vendor_id'])
                    ->where('billing_month', $input['billing_month'])
                    ->first();

                if ($existingInvoice) {
                    throw new \Exception('An invoice for this vendor has already been generated for the selected billing month.');
                }

                $input['status'] = 0;
                $invoice = SimInvoice::create($input);

                if (empty($invoice->invoice_number)) {
                    $invoice->invoice_number = 'SIMI' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT);
                    $invoice->save();
                }
            }

            $vatPercentage = Common::getSetting('vat_percentage') ?? 5;
            $subtotal = 0;
            $totalVat = 0;

            if (isset($request['sim_id']) && is_array($request['sim_id'])) {
                foreach ($request['sim_id'] as $key => $simId) {
                    if (!empty($simId) && isset($request['rental_amount'][$key]) && $request['rental_amount'][$key] > 0) {
                        $sim = Sims::withTrashed()
                            ->where('id', $simId)
                            ->where('vendor', $input['vendor_id'])
                            ->first();

                        if (!$sim) {
                            $sim = Sims::withTrashed()->find($simId);
                            if ($sim && $sim->trashed()) {
                                throw new \Exception('SIM ID ' . $sim->number . ' is deleted.');
                            } else {
                                throw new \Exception('SIM ID ' . $simId . ' does not belong to this vendor.');
                            }
                        }

                        $monthlyRate = (float) $request['rental_amount'][$key];
                        $days = isset($request['days'][$key]) && (int) $request['days'][$key] > 0
                            ? (int) $request['days'][$key]
                            : 30;
                        $days = min($days, 30);

                        $proratedAmount = $monthlyRate * ($days / 30);
                        $itemTaxRate = isset($request['tax_rate'][$key]) && $request['tax_rate'][$key] > 0
                            ? (float) $request['tax_rate'][$key]
                            : $vatPercentage;
                        $taxAmount = $proratedAmount * ($itemTaxRate / 100);
                        $totalAmount = $proratedAmount + $taxAmount;

                        $subtotal += $proratedAmount;
                        $totalVat += $taxAmount;

                        SimInvoiceItem::create([
                            'inv_id' => $invoice->id,
                            'sim_id' => $simId,
                            'days' => $days,
                            'rental_amount' => $monthlyRate,
                            'tax_rate' => $itemTaxRate,
                            'tax_amount' => $taxAmount,
                            'total_amount' => $totalAmount,
                        ]);
                    }
                }
            }

            $invoice->subtotal = $subtotal;
            $invoice->vat = $totalVat;
            $invoice->total_amount = $subtotal + $totalVat;
            $invoice->save();

            if ($id) {
                $oldTransCode = Transactions::where('reference_type', 'SimInvoice')
                    ->where('reference_id', $id)
                    ->value('trans_code');
                Transactions::where('reference_type', 'SimInvoice')
                    ->where('reference_id', $id)
                    ->delete();
                $this->recordTransactionsForInvoice($invoice, $oldTransCode ?: null);
            } else {
                $this->recordTransactionsForInvoice($invoice);
            }

            DB::commit();
            return $invoice;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function recordTransactionsForInvoice(SimInvoice $invoice, $transCode = null)
    {
        $invoice->load('vendor');
        $vendor = $invoice->vendor;
        if (!$vendor || !$vendor->account_id) {
            throw new \Exception('Vendor does not have a linked ledger account. Please set the account for this vendor before creating invoices.');
        }

        $trans_code = $transCode !== null ? $transCode : Account::trans_code();
        $subtotal = (float) $invoice->subtotal;
        $vatAmount = (float) $invoice->vat;
        $totalAmount = (float) $invoice->total_amount;
        $narration = 'SIM Invoice #' . ($invoice->invoice_number ?? $invoice->id) . ' - ' . ($invoice->descriptions ?? 'SIM Invoice');

        $expenseAccountId = GlobalAccounts::id('SIM_EXPENSE_ACCOUNT');
        $vatAccountId = GlobalAccounts::id('VAT_PURCHASE_ACCOUNT');

        $expenseAccountExists = \App\Support\CompanyQuery::table('accounts')->where('id', $expenseAccountId)->whereNull('deleted_at')->exists();
        if (!$expenseAccountExists) {
            throw new \Exception('Expense account (ID ' . $expenseAccountId . ') not found in Chart of Accounts.');
        }

        $vatAccountExists = \App\Support\CompanyQuery::table('accounts')->where('id', $vatAccountId)->whereNull('deleted_at')->exists();
        if (!$vatAccountExists) {
            throw new \Exception('VAT account (ID ' . $vatAccountId . ') not found in Chart of Accounts.');
        }

        $transDate = $invoice->inv_date ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d');
        $billingMonthStr = $invoice->billing_month ? \Carbon\Carbon::parse($invoice->billing_month)->format('Y-m-d') : date('Y-m-01');

        $transactionService = new TransactionService();
        try {
            $transactionService->recordTransaction([
                'account_id' => $expenseAccountId,
                'reference_id' => $invoice->id,
                'reference_type' => 'SimInvoice',
                'trans_code' => $trans_code,
                'trans_date' => $transDate,
                'narration' => $narration,
                'debit' => $subtotal,
                'billing_month' => $billingMonthStr,
            ], true);

            if ($vatAmount > 0) {
                $transactionService->recordTransaction([
                    'account_id' => $vatAccountId,
                    'reference_id' => $invoice->id,
                    'reference_type' => 'SimInvoice',
                    'trans_code' => $trans_code,
                    'trans_date' => $transDate,
                    'narration' => $narration . ' - VAT',
                    'debit' => $vatAmount,
                    'billing_month' => $billingMonthStr,
                ], true);
            }

            $transactionService->recordTransaction([
                'account_id' => $vendor->account_id,
                'reference_id' => $invoice->id,
                'reference_type' => 'SimInvoice',
                'trans_code' => $trans_code,
                'trans_date' => $transDate,
                'narration' => $narration,
                'credit' => $totalAmount,
                'billing_month' => $billingMonthStr,
            ], true);
        } catch (\Throwable $e) {
            throw new \Exception('Failed to record transaction for SIM Invoice. ' . $e->getMessage(), 0, $e);
        }
    }
}
