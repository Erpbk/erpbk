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
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
use Illuminate\Support\Facades\Storage;
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

class LeasingCompanyInvoicesRepository extends BaseRepository
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

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            // Handle file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('invoice', 'public');
                $input['attachment'] = $path;
            }

            if ($id) {
                $invoice = LeasingCompanyInvoice::where('id', $id)->first();

                // Check for duplicate only if leasing_company_id or billing_month is being changed
                $existingInvoice = LeasingCompanyInvoice::where('leasing_company_id', $input['leasing_company_id'])
                    ->where('billing_month', $input['billing_month'])
                    ->where('id', '!=', $id)
=======
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
>>>>>>> Stashed changes
                    ->first();

                if ($existingInvoice) {
                    throw new \Exception('An invoice for this leasing company has already been generated for the selected billing month.');
                }

<<<<<<< Updated upstream
                // Delete old attachment if new one is uploaded
                if (isset($input['attachment']) && $invoice->attachment) {
                    Storage::disk('public')->delete($invoice->attachment);
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

=======
                $input['status'] = 0; // Unpaid for new invoices
                $invoice = LeasingCompanyInvoice::create($input);

>>>>>>> Stashed changes
=======
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

>>>>>>> Stashed changes
=======
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

>>>>>>> Stashed changes
=======
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

>>>>>>> Stashed changes
                // Generate invoice number if not provided
                if (empty($invoice->invoice_number)) {
                    $invoice->invoice_number = 'LCI' . str_pad($invoice->id, 8, '0', STR_PAD_LEFT);
                    $invoice->save();
                }
            }

            // Get VAT percentage
            $vatPercentage = Common::getSetting('vat_percentage') ?? 5;
            $subtotal = 0;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            $totalVat = 0;

            // Process bike items - validate bikes belong to leasing company and are active
            $billingMonthDate = \Carbon\Carbon::parse($input['billing_month']);
            // Always use 30 days for calculation regardless of actual month days
            $daysInMonth = 30;
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

            // Process bike items - validate bikes belong to leasing company and are active
            $billingMonthDate = \Carbon\Carbon::parse($input['billing_month']);
            $daysInMonth = (int) $billingMonthDate->daysInMonth;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

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
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                            : 30;
                        $days = min($days, 30);

                        // Prorated amount = monthly rate * (days / 30)
                        // Always use 30 days for consistency with frontend calculation
                        $proratedAmount = $monthlyRate * ($days / 30);
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                            : $daysInMonth;
                        $days = min($days, $daysInMonth);

                        // Prorated amount = monthly rate * (days / days in month)
                        $proratedAmount = $monthlyRate * ($days / $daysInMonth);
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

                        $itemTaxRate = isset($request['tax_rate'][$key]) && $request['tax_rate'][$key] > 0
                            ? (float) $request['tax_rate'][$key]
                            : $vatPercentage;
                        $taxAmount = $proratedAmount * ($itemTaxRate / 100);
                        $totalAmount = $proratedAmount + $taxAmount;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream

                        // Sum up subtotal and VAT from each item
                        $subtotal += $proratedAmount;
                        $totalVat += $taxAmount;
=======
                        $subtotal += $proratedAmount;
>>>>>>> Stashed changes
=======
                        $subtotal += $proratedAmount;
>>>>>>> Stashed changes
=======
                        $subtotal += $proratedAmount;
>>>>>>> Stashed changes
=======
                        $subtotal += $proratedAmount;
>>>>>>> Stashed changes

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

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            // Use summed totals from actual items (not recalculated)
            $vat = $totalVat;
=======
            // Calculate totals
            $vat = $subtotal * ($vatPercentage / 100);
>>>>>>> Stashed changes
=======
            // Calculate totals
            $vat = $subtotal * ($vatPercentage / 100);
>>>>>>> Stashed changes
=======
            // Calculate totals
            $vat = $subtotal * ($vatPercentage / 100);
>>>>>>> Stashed changes
=======
            // Calculate totals
            $vat = $subtotal * ($vatPercentage / 100);
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
     * Create ledger entries for an invoice.
     * Debit: VAT Account (1023) with VAT amount
     * Debit: Leasing Expense Account (1129) with subtotal (excluding VAT)
     * Credit: Leasing Company Account with total amount (including VAT)
=======
     * Create ledger entries (debit leasing expense, credit leasing company) for an invoice.
     * Used by record() and by cloneInvoice() so creation and clone behave the same.
>>>>>>> Stashed changes
=======
     * Create ledger entries (debit leasing expense, credit leasing company) for an invoice.
     * Used by record() and by cloneInvoice() so creation and clone behave the same.
>>>>>>> Stashed changes
=======
     * Create ledger entries (debit leasing expense, credit leasing company) for an invoice.
     * Used by record() and by cloneInvoice() so creation and clone behave the same.
>>>>>>> Stashed changes
=======
     * Create ledger entries (debit leasing expense, credit leasing company) for an invoice.
     * Used by record() and by cloneInvoice() so creation and clone behave the same.
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        $subtotal = (float) $invoice->subtotal;
        $vatAmount = (float) $invoice->vat;
        $totalAmount = (float) $invoice->total_amount;
        $narration = "Leasing Company Invoice #" . ($invoice->invoice_number ?? $invoice->id) . ' - ' . ($invoice->descriptions ?? 'Rental Invoice');

        // Validate required accounts exist
        $expenseAccountId = HeadAccount::LEASING_EXPENSE_ACCOUNT;
        $vatAccountId = HeadAccount::TAX_ACCOUNT;

        $expenseAccountExists = DB::table('accounts')->where('id', $expenseAccountId)->whereNull('deleted_at')->exists();
        if (!$expenseAccountExists) {
            throw new \Exception(
                'Leasing expense account (ID ' . $expenseAccountId . ') not found in Chart of Accounts. ' .
                    'Please run: php artisan migrate (to create it) or add this account manually in Chart of Accounts.'
            );
        }

        $vatAccountExists = DB::table('accounts')->where('id', $vatAccountId)->whereNull('deleted_at')->exists();
        if (!$vatAccountExists) {
            throw new \Exception(
                'VAT account (ID ' . $vatAccountId . ') not found in Chart of Accounts. ' .
                    'Please add this account in Chart of Accounts.'
=======
        $totalAmount = (float) $invoice->total_amount;
        $narration = "Leasing Company Invoice #" . ($invoice->invoice_number ?? $invoice->id) . ' - ' . ($invoice->descriptions ?? 'Rental Invoice');

=======
        $totalAmount = (float) $invoice->total_amount;
        $narration = "Leasing Company Invoice #" . ($invoice->invoice_number ?? $invoice->id) . ' - ' . ($invoice->descriptions ?? 'Rental Invoice');

>>>>>>> Stashed changes
=======
        $totalAmount = (float) $invoice->total_amount;
        $narration = "Leasing Company Invoice #" . ($invoice->invoice_number ?? $invoice->id) . ' - ' . ($invoice->descriptions ?? 'Rental Invoice');

>>>>>>> Stashed changes
=======
        $totalAmount = (float) $invoice->total_amount;
        $narration = "Leasing Company Invoice #" . ($invoice->invoice_number ?? $invoice->id) . ' - ' . ($invoice->descriptions ?? 'Rental Invoice');

>>>>>>> Stashed changes
        $debitAccountId = HeadAccount::LEASING_EXPENSE_ACCOUNT;
        $debitAccountExists = DB::table('accounts')->where('id', $debitAccountId)->whereNull('deleted_at')->exists();
        if (!$debitAccountExists) {
            throw new \Exception(
                'Leasing expense account (ID ' . $debitAccountId . ') not found in Chart of Accounts. ' .
                'Please run: php artisan migrate (to create it) or add this account manually in Chart of Accounts.'
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            );
        }

        $transDate = $invoice->inv_date ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d');
        $billingMonthStr = $invoice->billing_month ? \Carbon\Carbon::parse($invoice->billing_month)->format('Y-m-d') : date('Y-m-01');

        $transactionService = new TransactionService();
        try {
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            // 1. Debit Leasing Expense Account with subtotal (excluding VAT)
            $transactionService->recordTransaction([
                'account_id' => $expenseAccountId,
=======
            $transactionService->recordTransaction([
                'account_id' => $debitAccountId,
>>>>>>> Stashed changes
=======
            $transactionService->recordTransaction([
                'account_id' => $debitAccountId,
>>>>>>> Stashed changes
=======
            $transactionService->recordTransaction([
                'account_id' => $debitAccountId,
>>>>>>> Stashed changes
=======
            $transactionService->recordTransaction([
                'account_id' => $debitAccountId,
>>>>>>> Stashed changes
                'reference_id' => $invoice->id,
                'reference_type' => 'LeasingCompanyInvoice',
                'trans_code' => $trans_code,
                'trans_date' => $transDate,
                'narration' => $narration,
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                'debit' => $subtotal,
                'billing_month' => $billingMonthStr,
            ], true);

            // 2. Debit VAT Account with VAT amount only
            if ($vatAmount > 0) {
                $transactionService->recordTransaction([
                    'account_id' => $vatAccountId,
                    'reference_id' => $invoice->id,
                    'reference_type' => 'LeasingCompanyInvoice',
                    'trans_code' => $trans_code,
                    'trans_date' => $transDate,
                    'narration' => $narration . ' - VAT',
                    'debit' => $vatAmount,
                    'billing_month' => $billingMonthStr,
                ], true);
            }

            // 3. Credit Leasing Company Account with total amount (including VAT)
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                'debit' => $totalAmount,
                'billing_month' => $billingMonthStr,
            ], true);

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
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
