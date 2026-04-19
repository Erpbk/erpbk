<?php

namespace App\Repositories;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\HeadAccount;
use App\Models\Bikes;
use App\Models\LeasingCompanyBillingInvoice;
use App\Models\LeasingCompanyBillingInvoiceItem;
use App\Models\Transactions;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeasingCompanyBillingInvoicesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'inv_date',
        'leasing_company_id',
        'billing_month',
        'invoice_number',
        'reference_number',
        'leasing_company_invoice_number',
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
        return LeasingCompanyBillingInvoice::class;
    }

    public function record($request, $id = null)
    {
        DB::beginTransaction();

        try {
            $input = $request->except(['bike_id', '_method', '_token', 'rental_amount']);
            $input['billing_month'] = $request->billing_month . '-01';

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('billing_invoice', 'public');
                $input['attachment'] = $path;
            }

            if ($id) {
                $invoice = LeasingCompanyBillingInvoice::where('id', $id)->first();

                $existingInvoice = LeasingCompanyBillingInvoice::where('leasing_company_id', $input['leasing_company_id'])
                    ->where('billing_month', $input['billing_month'])
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingInvoice) {
                    throw new \Exception('A billing invoice for this leasing company has already been generated for the selected billing month.');
                }

                if (isset($input['attachment']) && $invoice->attachment) {
                    Storage::disk('public')->delete($invoice->attachment);
                }

                $invoice->update($input);
                LeasingCompanyBillingInvoiceItem::where('inv_id', $id)->delete();
            } else {
                $existingInvoice = LeasingCompanyBillingInvoice::where('leasing_company_id', $input['leasing_company_id'])
                    ->where('billing_month', $input['billing_month'])
                    ->first();

                if ($existingInvoice) {
                    throw new \Exception('A billing invoice for this leasing company has already been generated for the selected billing month.');
                }

                $input['status'] = 0;
                $invoice = LeasingCompanyBillingInvoice::create($input);

                if (empty($invoice->invoice_number)) {
                    $invoice->invoice_number = 'LBI-' . $invoice->id;
                    $invoice->save();
                }
            }

            $vatPercentage = Common::getSetting('vat_percentage') ?? 5;
            $subtotal = 0;
            $totalVat = 0;

            if (isset($request['bike_id']) && is_array($request['bike_id'])) {
                foreach ($request['bike_id'] as $key => $bikeId) {
                    if (!empty($bikeId) && isset($request['rental_amount'][$key]) && $request['rental_amount'][$key] > 0) {
                        $bike = Bikes::where('id', $bikeId)
                            ->where('status', 1)
                            ->first();

                        if (!$bike) {
                            throw new \Exception('Bike ID ' . $bikeId . ' is not active.');
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

                        LeasingCompanyBillingInvoiceItem::create([
                            'inv_id' => $invoice->id,
                            'bike_id' => $bikeId,
                            'days' => $days,
                            'rental_amount' => $monthlyRate,
                            'tax_rate' => $itemTaxRate,
                            'tax_amount' => $taxAmount,
                            'total_amount' => $totalAmount,
                        ]);
                    }
                }
            }

            $vat = $totalVat;
            $totalAmount = $subtotal + $vat;

            $invoice->subtotal = $subtotal;
            $invoice->vat = $vat;
            $invoice->total_amount = $totalAmount;
            $invoice->save();

            if ($id) {
                $oldTransCode = Transactions::where('reference_type', 'LeasingCompanyBillingInvoice')
                    ->where('reference_id', $id)
                    ->value('trans_code');
                Transactions::where('reference_type', 'LeasingCompanyBillingInvoice')
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

    public function recordTransactionsForInvoice(LeasingCompanyBillingInvoice $invoice, $transCode = null)
    {
        $invoice->load('leasingCompany');
        $leasingCompany = $invoice->leasingCompany;
        if (!$leasingCompany || !$leasingCompany->account_id) {
            throw new \Exception('Leasing company does not have a linked ledger account. Please set the account before creating billing invoices.');
        }

        $trans_code = $transCode !== null ? $transCode : Account::trans_code();
        $subtotal = (float) $invoice->subtotal;
        $vatAmount = (float) $invoice->vat;
        $totalAmount = (float) $invoice->total_amount;
        $invoiceRef = $invoice->invoice_number ?: ('LBI-' . $invoice->id);
        $narration = 'Leasing Billing Invoice #' . $invoiceRef . ' - ' . ($invoice->descriptions ?? 'Billing Invoice');

        $bikeRentalAccountId = HeadAccount::BIKE_RENTAL_ACCOUNT;
        $vatAccountId = HeadAccount::VAT_ON_SALES;

        $bikeRentalAccountExists = \App\Support\CompanyQuery::table('accounts')->where('id', $bikeRentalAccountId)->whereNull('deleted_at')->exists();
        if (!$bikeRentalAccountExists) {
            throw new \Exception('Bike rental account (ID ' . $bikeRentalAccountId . ') not found in Chart of Accounts.');
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
                'account_id' => $bikeRentalAccountId,
                'reference_id' => $invoice->id,
                'reference_type' => 'LeasingCompanyBillingInvoice',
                'trans_code' => $trans_code,
                'trans_date' => $transDate,
                'narration' => $narration,
                'credit' => $subtotal,
                'billing_month' => $billingMonthStr,
            ], true);

            if ($vatAmount > 0) {
                $transactionService->recordTransaction([
                    'account_id' => $vatAccountId,
                    'reference_id' => $invoice->id,
                    'reference_type' => 'LeasingCompanyBillingInvoice',
                    'trans_code' => $trans_code,
                    'trans_date' => $transDate,
                    'narration' => $narration . ' - VAT',
                    'credit' => $vatAmount,
                    'billing_month' => $billingMonthStr,
                ], true);
            }

            $transactionService->recordTransaction([
                'account_id' => $leasingCompany->account_id,
                'reference_id' => $invoice->id,
                'reference_type' => 'LeasingCompanyBillingInvoice',
                'trans_code' => $trans_code,
                'trans_date' => $transDate,
                'narration' => $narration,
                'debit' => $totalAmount,
                'billing_month' => $billingMonthStr,
            ], true);
        } catch (\Throwable $e) {
            throw new \Exception('Failed to record transaction for Leasing Billing Invoice. ' . $e->getMessage(), 0, $e);
        }
    }
}
