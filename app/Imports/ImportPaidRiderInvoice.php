<?php

namespace App\Imports;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\General;
use App\Helpers\HeadAccount;
use App\Models\Items;
use App\Models\RiderInvoiceItem;
use App\Models\RiderInvoices;
use App\Models\Riders;
use App\Models\Accounts;
use App\Services\TransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class ImportPaidRiderInvoice implements ToCollection
{
    /**
     * Import paid rider invoices from Excel
     * 
     * @param Collection $rows
     * @return void
     */
    public function collection(Collection $rows)
    {
        $items = [
            $rows[0][3],
            $rows[0][4],
            $rows[0][5],
            $rows[0][6],
            $rows[0][7],
            $rows[0][8],
            $rows[0][9],
            $rows[0][10],
            $rows[0][11],
            $rows[0][12],
            $rows[0][13],
            $rows[0][14],
            $rows[0][15],
            $rows[0][16],
            $rows[0][17],
            $rows[0][18],
            $rows[0][19],
            $rows[0][20]
        ];

        $i = 1;
        $processedInvoices = [];

        foreach ($rows as $row) {
            $i++;
            try {
                DB::beginTransaction();

                if ($row[1] != 'ID' && $row[1] != '') {
                    // Parse dates
                    $dateTimeObject = Date::excelToDateTimeObject($row[0]);
                    $invoice_date = Carbon::instance($dateTimeObject)->format('Y-m-d');

                    $billing_month = date('Y-m-01', strtotime($row[28]));
                    if ($billing_month == '1970-01-01') {
                        $Billingdate = Date::excelToDateTimeObject($row[28]);
                        $billing_month = Carbon::instance($Billingdate)->format('Y-m-01');
                    }

                    // Get rider information
                    $rider = Riders::where('rider_id', $row[1])->first();
                    if (!$rider) {
                        throw ValidationException::withMessages(['file' => 'Row(' . $i . ') - Rider ID ' . $row[1] . ' does not exist.']);
                    }

                    // Check if unpaid invoice exists for same rider and billing month
                    $existingInvoice = RiderInvoices::where('rider_id', $rider->id)
                        ->where('billing_month', $billing_month)
                        ->where('status', 0) // unpaid
                        ->first();

                    if (!$existingInvoice) {
                        // Skip this row if no matching unpaid invoice exists
                        DB::rollBack();
                        continue;
                    }

                    // Get bank account from Excel (assuming it's in a specific column)
                    // You may need to adjust this column index based on your Excel structure
                    $bankAccountName = $row[32] ?? ''; // Try multiple columns for bank account
                    $bankAccountId = $this->getBankAccountId($bankAccountName);
                    if (!$bankAccountId) {
                        throw ValidationException::withMessages(['file' => 'Row(' . $i . ') - Invalid or missing bank account specified: ' . $bankAccountName]);
                    }

                    // Update existing invoice status to paid
                    $existingInvoice->update([
                        'status' => 1, // paid
                        'updated_at' => now()
                    ]);

                    // Create voucher entries
                    $this->createVoucherEntries($existingInvoice, $rider, $bankAccountId, $invoice_date, $billing_month);

                    $processedInvoices[] = $existingInvoice->id;
                }

                DB::commit();
            } catch (QueryException $e) {
                DB::rollBack();
                throw $e;
            } catch (ValidationException $e) {
                DB::rollBack();
                throw $e;
            } catch (\Exception $e) {
                DB::rollBack();
                throw new ValidationException(ValidationException::withMessages(['file' => 'Row(' . $i . ') - Error processing: ' . $e->getMessage()]));
            }
        }

        // Log processed invoices for reference
        if (!empty($processedInvoices)) {
            Log::info('Paid rider invoices processed successfully', [
                'invoice_ids' => $processedInvoices,
                'count' => count($processedInvoices)
            ]);
        }
    }

    /**
     * Create voucher entries for paid invoice
     * 
     * @param RiderInvoices $invoice
     * @param Riders $rider
     * @param int $bankAccountId
     * @param string $invoiceDate
     * @param string $billingMonth
     * @return void
     */
    private function createVoucherEntries($invoice, $rider, $bankAccountId, $invoiceDate, $billingMonth)
    {
        $transactionService = new TransactionService();
        $trans_code = Account::trans_code();
        $totalAmount = $invoice->total_amount;

        // Debit rider account
        $transactionDataDebit = [
            'account_id' => $rider->account_id,
            'reference_id' => $invoice->id,
            'reference_type' => 'RiderInvoice',
            'trans_code' => $trans_code,
            'trans_date' => $invoiceDate,
            'narration' => "Payment for Rider Invoice #" . $invoice->id . ' - ' . ($invoice->descriptions ?? 'Paid Invoice'),
            'debit' => $totalAmount,
            'credit' => 0,
            'billing_month' => $billingMonth,
        ];
        $transactionService->recordTransaction($transactionDataDebit);

        // Credit bank account
        $transactionDataCredit = [
            'account_id' => $bankAccountId,
            'reference_id' => $invoice->id,
            'reference_type' => 'RiderInvoice',
            'trans_code' => $trans_code,
            'trans_date' => $invoiceDate,
            'narration' => "Payment received for Rider Invoice #" . $invoice->id . ' - ' . ($invoice->descriptions ?? 'Paid Invoice'),
            'debit' => 0,
            'credit' => $totalAmount,
            'billing_month' => $billingMonth,
        ];
        $transactionService->recordTransaction($transactionDataCredit);

        // Create voucher record
        $voucherData = [
            'trans_date' => $invoiceDate,
            'voucher_type' => 'RI', // Rider Invoice Payment Voucher
            'payment_type' => 1,
            'payment_from' => $bankAccountId,
            'billing_month' => $billingMonth,
            'amount' => $totalAmount,
            'trans_code' => $trans_code,
            'Created_By' => Auth::user()->id,
            'remarks' => "Payment for Rider Invoice #" . $invoice->id,
        ];

        DB::table('vouchers')->insert($voucherData);
    }

    /**
     * Get bank account ID from Excel data
     * You may need to adjust this based on your bank account structure
     * 
     * @param string $bankAccountName
     * @return int|null
     */
    private function getBankAccountId($bankAccountName)
    {
        if (empty($bankAccountName)) {
            return null;
        }

        $bankAccountName = trim($bankAccountName);

        // If it's numeric, try to find by ID first
        if (is_numeric($bankAccountName)) {
            $account = Accounts::find($bankAccountName);
            if ($account) {
                return $account->id;
            }
        }

        // Try to find bank account by exact name
        $account = Accounts::where('name', $bankAccountName)->first();
        if (!$account) {
            // Try partial match on account name
            $account = Accounts::where('name', 'LIKE', '%' . $bankAccountName . '%')->first();
        }

        if (!$account) {
            // Try to match by account code
            $account = Accounts::where('account_code', $bankAccountName)->first();
        }
        dd($account);
        return $account ? $account->id : null;
    }
}
