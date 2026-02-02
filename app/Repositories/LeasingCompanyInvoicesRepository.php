<?php

namespace App\Repositories;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\HeadAccount;
use App\Models\Bikes;
use App\Models\LeasingCompanyInvoice;
use App\Models\LeasingCompanyInvoiceItem;
use App\Models\Transactions;
use App\Repositories\BaseRepository;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;

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
        DB::beginTransaction();

        try {
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

                $input['status'] = 0; // Unpaid for new invoices
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

            // Process bike items - validate bikes belong to leasing company and are active
            $billingMonthDate = \Carbon\Carbon::parse($input['billing_month']);
            $daysInMonth = (int) $billingMonthDate->daysInMonth;

            if (isset($request['bike_id']) && is_array($request['bike_id'])) {
                foreach ($request['bike_id'] as $key => $bikeId) {
                    if (!empty($bikeId) && isset($request['rental_amount'][$key]) && $request['rental_amount'][$key] > 0) {
                        // Validate bike belongs to leasing company and is active
                        $bike = Bikes::where('id', $bikeId)
                            ->where('company', $input['leasing_company_id'])
                            ->where('status', 1)
                            ->first();

                        if (!$bike) {
                            throw new \Exception('Bike ID ' . $bikeId . ' is not active or does not belong to this leasing company.');
                        }

                        $monthlyRate = (float) $request['rental_amount'][$key];
                        $days = isset($request['days'][$key]) && (int) $request['days'][$key] > 0
                            ? (int) $request['days'][$key]
                            : $daysInMonth;
                        $days = min($days, $daysInMonth);

                        // Prorated amount = monthly rate * (days / days in month)
                        $proratedAmount = $monthlyRate * ($days / $daysInMonth);

                        $itemTaxRate = isset($request['tax_rate'][$key]) && $request['tax_rate'][$key] > 0
                            ? (float) $request['tax_rate'][$key]
                            : $vatPercentage;
                        $taxAmount = $proratedAmount * ($itemTaxRate / 100);
                        $totalAmount = $proratedAmount + $taxAmount;
                        $subtotal += $proratedAmount;

                        $itemData = [
                            'inv_id' => $invoice->id,
                            'bike_id' => $bikeId,
                            'days' => $days,
                            'rental_amount' => $monthlyRate,
                            'tax_rate' => $itemTaxRate,
                            'tax_amount' => $taxAmount,
                            'total_amount' => $totalAmount,
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

            if ($id) {
                // Delete existing transactions for this invoice before re-recording
                $oldTransCode = Transactions::where('reference_type', 'LeasingCompanyInvoice')
                    ->where('reference_id', $id)
                    ->value('trans_code');
                Transactions::where('reference_type', 'LeasingCompanyInvoice')
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

    /**
     * Create ledger entries (debit leasing expense, credit leasing company) for an invoice.
     * Used by record() and by cloneInvoice() so creation and clone behave the same.
     *
     * @param LeasingCompanyInvoice $invoice
     * @param int|null $transCode If null, a new trans_code is generated.
     * @throws \Exception
     */
    public function recordTransactionsForInvoice(LeasingCompanyInvoice $invoice, $transCode = null)
    {
        $invoice->load('leasingCompany');
        $leasingCompany = $invoice->leasingCompany;
        if (!$leasingCompany || !$leasingCompany->account_id) {
            throw new \Exception('Leasing company does not have a linked ledger account. Please set the account for this leasing company before creating invoices.');
        }

        $trans_code = $transCode !== null ? $transCode : Account::trans_code();
        $totalAmount = (float) $invoice->total_amount;
        $narration = "Leasing Company Invoice #" . ($invoice->invoice_number ?? $invoice->id) . ' - ' . ($invoice->descriptions ?? 'Rental Invoice');

        $debitAccountId = HeadAccount::LEASING_EXPENSE_ACCOUNT;
        $debitAccountExists = DB::table('accounts')->where('id', $debitAccountId)->whereNull('deleted_at')->exists();
        if (!$debitAccountExists) {
            throw new \Exception(
                'Leasing expense account (ID ' . $debitAccountId . ') not found in Chart of Accounts. ' .
                'Please run: php artisan migrate (to create it) or add this account manually in Chart of Accounts.'
            );
        }

        $transDate = $invoice->inv_date ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d');
        $billingMonthStr = $invoice->billing_month ? \Carbon\Carbon::parse($invoice->billing_month)->format('Y-m-d') : date('Y-m-01');

        $transactionService = new TransactionService();
        try {
            $transactionService->recordTransaction([
                'account_id' => $debitAccountId,
                'reference_id' => $invoice->id,
                'reference_type' => 'LeasingCompanyInvoice',
                'trans_code' => $trans_code,
                'trans_date' => $transDate,
                'narration' => $narration,
                'debit' => $totalAmount,
                'billing_month' => $billingMonthStr,
            ], true);

            $transactionService->recordTransaction([
                'account_id' => $leasingCompany->account_id,
                'reference_id' => $invoice->id,
                'reference_type' => 'LeasingCompanyInvoice',
                'trans_code' => $trans_code,
                'trans_date' => $transDate,
                'narration' => $narration,
                'credit' => $totalAmount,
                'billing_month' => $billingMonthStr,
            ], true);
        } catch (\Throwable $e) {
            throw new \Exception(
                'Failed to record transaction for Leasing Company Invoice. ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
