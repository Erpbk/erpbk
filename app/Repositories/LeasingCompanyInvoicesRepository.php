<?php

namespace App\Repositories;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\HeadAccount;
use App\Models\LeasingCompanyInvoice;
use App\Models\LeasingCompanyInvoiceItem;
use App\Models\Transactions;
use App\Repositories\BaseRepository;
use App\Services\TransactionService;

class LeasingCompanyInvoicesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'inv_date',
        'leasing_company_id',
        'billing_month',
        'invoice_number',
        'total_amount',
        'status'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return LeasingCompanyInvoice::class;
    }

    public function record($request, $id = null)
    {
        $input = $request->except(['bike_id', '_method', '_token', 'rental_amount']);

        $input['billing_month'] = $request->billing_month . "-01";

        if ($id) {
            $invoice = LeasingCompanyInvoice::where('id', $id)->first();

            // Check for duplicate only if leasing_company_id or billing_month is being changed
            $existingInvoice = LeasingCompanyInvoice::where('leasing_company_id', $input['leasing_company_id'])
                ->where('billing_month', $input['billing_month'])
                ->where('id', '!=', $id)
                ->first();

            if ($existingInvoice) {
                throw new \Exception('An invoice for this leasing company has already been generated for the selected billing month.');
            }

            $invoice->update($input);
            LeasingCompanyInvoiceItem::where('inv_id', $id)->delete();
        } else {
            // Check for duplicate invoice for same leasing company and billing month
            $existingInvoice = LeasingCompanyInvoice::where('leasing_company_id', $input['leasing_company_id'])
                ->where('billing_month', $input['billing_month'])
                ->first();

            if ($existingInvoice) {
                throw new \Exception('An invoice for this leasing company has already been generated for the selected billing month.');
            }

            $invoice = LeasingCompanyInvoice::create($input);

            // Generate invoice number if not provided
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = 'LCI' . str_pad($invoice->id, 8, '0', STR_PAD_LEFT);
                $invoice->save();
            }
        }

        // Get VAT percentage
        $vatPercentage = Common::getSetting('vat_percentage') ?? 5;
        $subtotal = 0;

        // Process bike items
        if (isset($request['bike_id']) && is_array($request['bike_id'])) {
            foreach ($request['bike_id'] as $key => $bikeId) {
                if (!empty($bikeId) && isset($request['rental_amount'][$key]) && $request['rental_amount'][$key] > 0) {
                    $rentalAmount = (float)$request['rental_amount'][$key];
                    $taxAmount = $rentalAmount * ($vatPercentage / 100);
                    $totalAmount = $rentalAmount + $taxAmount;
                    $subtotal += $rentalAmount;

                    $itemData = [
                        'inv_id' => $invoice->id,
                        'bike_id' => $bikeId,
                        'rental_amount' => $rentalAmount,
                        'tax_rate' => $vatPercentage,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $totalAmount
                    ];

                    LeasingCompanyInvoiceItem::create($itemData);
                }
            }
        }

        // Calculate totals
        $vat = $subtotal * ($vatPercentage / 100);
        $totalAmount = $subtotal + $vat;

        // Update invoice totals
        $invoice->subtotal = $subtotal;
        $invoice->vat = $vat;
        $invoice->total_amount = $totalAmount;
        $invoice->save();

        // Create transactions
        $transactionService = new TransactionService();

        if ($id) {
            // Delete existing transactions for this invoice
            $oldTransCode = Transactions::where('reference_type', 'LeasingCompanyInvoice')
                ->where('reference_id', $id)
                ->value('trans_code');

            Transactions::where('reference_type', 'LeasingCompanyInvoice')
                ->where('reference_id', $id)
                ->delete();

            $trans_code = $oldTransCode ? $oldTransCode : Account::trans_code();
        } else {
            $trans_code = Account::trans_code();
        }

        // VAT transaction
        if ($vat > 0) {
            $transactionData = [
                'account_id' => HeadAccount::TAX_ACCOUNT,
                'reference_id' => $invoice->id,
                'reference_type' => 'LeasingCompanyInvoice',
                'trans_code' => $trans_code,
                'trans_date' => $invoice->inv_date,
                'narration' => "Leasing Company Invoice #" . $invoice->id . ' - ' . ($invoice->descriptions ?? 'Rental Invoice'),
                'debit' => $vat,
                'billing_month' => $invoice->billing_month,
            ];
            $transactionService->recordTransaction($transactionData);
        }

        // Leasing Company Account (Credit - we owe them)
        $transactionData = [
            'account_id' => $invoice->leasingCompany->account_id,
            'reference_id' => $invoice->id,
            'reference_type' => 'LeasingCompanyInvoice',
            'trans_code' => $trans_code,
            'trans_date' => $invoice->inv_date,
            'narration' => "Leasing Company Invoice #" . $invoice->id . ' - ' . ($invoice->descriptions ?? 'Rental Invoice'),
            'credit' => $totalAmount,
            'billing_month' => $invoice->billing_month,
        ];
        $transactionService->recordTransaction($transactionData);

        // Expense Account (Debit - rental expense)
        $transactionData = [
            'account_id' => HeadAccount::SALARY_ACCOUNT, // Using salary account as expense account
            'reference_id' => $invoice->id,
            'reference_type' => 'LeasingCompanyInvoice',
            'trans_code' => $trans_code,
            'trans_date' => $invoice->inv_date,
            'narration' => "Leasing Company Invoice #" . $invoice->id . ' - ' . ($invoice->descriptions ?? 'Rental Invoice'),
            'debit' => $subtotal,
            'billing_month' => $invoice->billing_month,
        ];
        $transactionService->recordTransaction($transactionData);

        return $invoice;
    }
}
