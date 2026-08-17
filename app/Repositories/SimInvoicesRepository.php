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
            $input = $request->except([
                'sim_id',
                '_method',
                '_token',
                'rental_amount',
                'additional_charges',
                'international_usage_charges',
                'tax_rate',
                'days',
                'vat_total',
                'total_amount_display',
                'subtotal',
                'company_id',
            ]);
            $input['vendor_id'] = $request->input('company_id');
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
            }

            $vatPercentage = Common::getSetting('vat_percentage') ?? 5;
            $subtotal = 0;
            $totalVat = 0;

            if (isset($request['sim_id']) && is_array($request['sim_id'])) {
                foreach ($request['sim_id'] as $key => $simId) {
                    if (!empty($simId)) {
                        $sim = Sims::where('id', $simId)
                            ->where('company', $input['vendor_id'])
                            ->first();

                        if (!$sim) {
                            $sim = Sims::withTrashed()->find($simId);
                            if ($sim && $sim->trashed()) {
                                throw new \Exception('SIM ' . $sim->number . ' is deleted.');
                            } else {
                                throw new \Exception('SIM ' . $sim->number . ' does not belong to this Company.');
                            }
                        }

                        $monthlyRate = (float) ($request['rental_amount'][$key] ?? 0);
                        $additionalCharges = (float) ($request['additional_charges'][$key] ?? 0);
                        $internationalUsageCharges = (float) ($request['international_usage_charges'][$key] ?? 0);
                        $lineSubtotal = $monthlyRate + $additionalCharges + $internationalUsageCharges;
                        $itemTaxRate = isset($request['tax_rate'][$key]) && $request['tax_rate'][$key] > 0
                            ? (float) $request['tax_rate'][$key]
                            : $vatPercentage;
                        $taxAmount = $lineSubtotal * ($itemTaxRate / 100);
                        $totalAmount = $lineSubtotal + $taxAmount;

                        $subtotal += $lineSubtotal;
                        $totalVat += $taxAmount;

                        SimInvoiceItem::create([
                            'inv_id' => $invoice->id,
                            'sim_id' => $simId,
                            'rental_amount' => $monthlyRate,
                            'additional_charges' => $additionalCharges,
                            'international_usage_charges' => $internationalUsageCharges,
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
        $invoice->load('company');
        $company = $invoice->company;
        if (!$company || !$company->account_id) {
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
                'account_id' => $company->account_id,
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

    /**
     * Create a SIM invoice from parsed import rows. VAT 0 is kept as 0.
     *
     * @param  array{vendor_id:int,inv_date:string,billing_month:string,reference_number:string,descriptions?:?string,notes?:?string,attachment?:?string}  $header
     * @param  array<int, array{sim_id:int,rental_amount:float,additional_charges:float,international_usage_charges:float,tax_rate:float}>  $items
     */
    public function createFromImport(array $header, array $items): SimInvoice
    {
        DB::beginTransaction();

        try {
            $billingMonth = $header['billing_month'] . '-01';
            $existingInvoice = SimInvoice::where('vendor_id', $header['vendor_id'])
                ->where('billing_month', $billingMonth)
                ->first();

            if ($existingInvoice) {
                throw new \Exception('An invoice for this vendor has already been generated for the selected billing month.');
            }

            $invoice = SimInvoice::create([
                'inv_date' => $header['inv_date'],
                'vendor_id' => $header['vendor_id'],
                'billing_month' => $billingMonth,
                'reference_number' => $header['reference_number'],
                'descriptions' => $header['descriptions'] ?? null,
                'notes' => $header['notes'] ?? null,
                'attachment' => $header['attachment'] ?? null,
                'status' => 0,
            ]);

            $subtotal = 0;
            $totalVat = 0;

            foreach ($items as $item) {
                $monthlyRate = (float) ($item['rental_amount'] ?? 0);
                $additionalCharges = (float) ($item['additional_charges'] ?? 0);
                $internationalUsageCharges = (float) ($item['international_usage_charges'] ?? 0);
                $itemTaxRate = (float) ($item['tax_rate'] ?? 0);
                $lineSubtotal = $monthlyRate + $additionalCharges + $internationalUsageCharges;
                $taxAmount = $lineSubtotal * ($itemTaxRate / 100);
                $totalAmount = $lineSubtotal + $taxAmount;

                $subtotal += $lineSubtotal;
                $totalVat += $taxAmount;

                SimInvoiceItem::create([
                    'inv_id' => $invoice->id,
                    'sim_id' => $item['sim_id'],
                    'rental_amount' => $monthlyRate,
                    'additional_charges' => $additionalCharges,
                    'international_usage_charges' => $internationalUsageCharges,
                    'tax_rate' => $itemTaxRate,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                ]);
            }

            $invoice->subtotal = $subtotal;
            $invoice->vat = $totalVat;
            $invoice->total_amount = $subtotal + $totalVat;
            $invoice->save();

            $this->recordTransactionsForInvoice($invoice);

            DB::commit();
            return $invoice;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
