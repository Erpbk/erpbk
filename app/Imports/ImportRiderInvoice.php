<?php

namespace App\Imports;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Support\GlobalAccounts;
use App\Models\Accounts;
use App\Models\Branch;
use App\Models\Items;
use App\Models\RiderInvoiceItem;
use App\Models\RiderInvoices;
use App\Models\Riders;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportRiderInvoice implements ToCollection
{
    /**
     * @param  array  $row
     * @return Model|null
     */
    public function collection(Collection $rows)
    {
        $maxColumns = 40;

        $rows = $rows->map(function ($row) use ($maxColumns) {
            $rowArray = is_array($row) ? $row : $row->toArray();

            return array_pad(array_slice($rowArray, 0, $maxColumns), $maxColumns, null);
        });

        /*
        |--------------------------------------------------------------------------
        | Fixed columns (0-14) then item qty columns from index 15
        | 0 Invoice Date | 1 ID | 2 Rider_Name | 3 Rejection | 4 Login Hours
        | 5 Zone | 6 Working Days | 7 Perf_Attendance | 8 Off | 9 Performance
        | 10 Billing Month | 11 Description | 12 Status | 13 Notes | 14 branch_id
        |--------------------------------------------------------------------------
        */

        $itemStartIndex = 15;
        $itemColumns = [];

        for ($col = $itemStartIndex; $col < $maxColumns; $col++) {
            $itemName = trim($rows[0][$col] ?? '');

            if ($itemName !== '') {
                $itemColumns[$col] = $itemName;
            }
        }

        $itemIdMap = Items::whereIn('name', array_values($itemColumns))
            ->where('status', 1)
            ->select(['id', 'price', 'name'])
            ->get()
            ->keyBy('name');

        /*
        |--------------------------------------------------------------------------
        | Preload Riders
        |--------------------------------------------------------------------------
        */

        $riderIds = $rows->pluck(1)
            ->filter()
            ->unique()
            ->values();

        $riders = Riders::whereIn('rider_id', $riderIds)
            ->get()
            ->keyBy('rider_id');

        $salaryAccountId = GlobalAccounts::id('SALARY_ACCOUNT');
        if (! Accounts::find($salaryAccountId)) {
            throw ValidationException::withMessages([
                'file' => "Salary Expense Account (ID: {$salaryAccountId}) not found.",
            ]);
        }
        $vatPercentage = Common::getSetting('vat_percentage');

        $transactionService = new TransactionService;

        foreach ($rows as $index => $row) {

            if ($index === 0 || empty($row[1]) || $row[1] === 'ID') {
                continue;
            }

            DB::transaction(function () use (
                $row,
                $riders,
                $itemColumns,
                $itemIdMap,
                $salaryAccountId,
                $vatPercentage,
                $transactionService,
                $index
            ) {

                /*
                |--------------------------------------------------------------------------
                | Dates
                |--------------------------------------------------------------------------
                */

                $invoiceDate = Carbon::instance(
                    Date::excelToDateTimeObject($row[0])
                )->format('Y-m-d');

                $billingMonth = date('Y-m-01', strtotime($row[10]));

                if ($billingMonth === '1970-01-01') {
                    $billingMonth = Carbon::instance(
                        Date::excelToDateTimeObject($row[10])
                    )->format('Y-m-01');
                }

                /*
                |--------------------------------------------------------------------------
                | Rider
                |--------------------------------------------------------------------------
                */

                $rider = $riders[$row[1]] ?? null;

                if (! $rider) {
                    throw ValidationException::withMessages([
                        'file' => 'Row('.($index + 1).") - Rider ID {$row[1]} not found.",
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Duplicate Check
                |--------------------------------------------------------------------------
                */

                $exists = RiderInvoices::where('rider_id', $rider->id)
                    ->where('billing_month', $billingMonth)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'file' => 'Row('.($index + 1).") - Invoice already exists for Rider {$row[1]}.",
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                $excelStatus = strtolower(trim($row[12] ?? ''));

                $status = in_array($excelStatus, ['paid', '1']) ? 1 : 0;

                /*
                |--------------------------------------------------------------------------
                | Branch
                |--------------------------------------------------------------------------
                */

                $rawBranch = $row[14] ?? null;

                if ($rawBranch === null || trim((string) $rawBranch) === '') {
                    throw ValidationException::withMessages([
                        'file' => 'Row('.($index + 1).') - branch_id is required in the sheet.',
                    ]);
                }

                if (! is_numeric($rawBranch)) {
                    throw ValidationException::withMessages([
                        'file' => 'Row('.($index + 1).") - Invalid branch_id '{$rawBranch}'.",
                    ]);
                }

                $branchId = (int) $rawBranch;

                if (! Branch::where('id', $branchId)->exists()) {
                    throw ValidationException::withMessages([
                        'file' => 'Row('.($index + 1).") - branch_id {$branchId} not found.",
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Create Invoice
                |--------------------------------------------------------------------------
                */

                $invoice = RiderInvoices::create([
                    'inv_date' => $invoiceDate,
                    'rider_id' => $rider->id,
                    'vendor_id' => $rider->VID,
                    'zone' => $row[5] ?? 'DXB',
                    'login_hours' => $row[4],
                    'working_days' => $row[6],
                    'perfect_attendance' => $row[7],
                    'rejection' => $row[3],
                    'performance' => $row[9],
                    'billing_month' => $billingMonth,
                    'off' => $row[8],
                    'descriptions' => $row[11],
                    'notes' => $row[13],
                    'status' => $status,
                    'branch_id' => $branchId,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Invoice Items
                |--------------------------------------------------------------------------
                */

                $itemsData = [];
                $subtotal = 0;

                foreach ($itemColumns as $columnIndex => $itemName) {

                    $item = $itemIdMap->get($itemName);
                    $itemId = $item?->id ?? null;

                    if (! $itemId) {
                        throw ValidationException::withMessages([
                            'file' => "Item '{$itemName}' not found.",
                        ]);
                    }

                    $qty = (float) str_replace(',', '', $row[$columnIndex] ?? 0);

                    if ($qty == 0) {
                        continue;
                    }

                    $rate = $item?->price ?? 1;

                    $qty = round($qty, 2);
                    $amount = round($qty * $rate, 2);

                    $subtotal += $amount;

                    $itemsData[] = [
                        'inv_id' => $invoice->id,
                        'item_id' => $itemId,
                        'qty' => $qty,
                        'rate' => $rate,
                        'amount' => $amount,
                        'branch_id' => $branchId,
                        'company_id' => $rider->company_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (! empty($itemsData)) {
                    RiderInvoiceItem::insert($itemsData);
                }

                /*
                |--------------------------------------------------------------------------
                | VAT + Total
                |--------------------------------------------------------------------------
                */

                $vat = 0;

                if ($rider->vat == 1) {
                    $vat = ($subtotal * $vatPercentage) / 100;
                }

                $total = $subtotal + $vat;

                $invoice->update([
                    'subtotal' => $subtotal,
                    'vat' => $vat,
                    'total_amount' => $total,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Transactions
                |--------------------------------------------------------------------------
                | Positive total: Debit Salary / Credit Rider (and Debit VAT if any)
                | Negative total: reverse sides so debit/credit stay non-negative
                */

                // Match save/update: VAT + salary(subtotal) + rider(total); only unpaid
                if ($status == 0 && $total != 0) {

                    $transCode = Account::trans_code();
                    $absTotal = abs($total);
                    $absSubtotal = abs($subtotal);
                    $isNegativeTotal = $total < 0;

                    if ($vat != 0) {
                        $absVat = abs($vat);

                        $transactionService->recordTransaction([
                            'account_id' => GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'),
                            'reference_id' => $invoice->id,
                            'reference_type' => 'Invoice',
                            'trans_code' => $transCode,
                            'trans_date' => $invoiceDate,
                            'narration' => 'VAT - Rider Invoice #'.$invoice->id,
                            'debit' => $vat > 0 ? $absVat : 0,
                            'credit' => $vat < 0 ? $absVat : 0,
                            'billing_month' => $billingMonth,
                            'branch_id' => $branchId,
                        ]);
                    }

                    // Rider: credit when payable to rider; debit when total is negative
                    $transactionService->recordTransaction([
                        'account_id' => $rider->account_id,
                        'reference_id' => $invoice->id,
                        'reference_type' => 'Invoice',
                        'trans_code' => $transCode,
                        'trans_date' => $invoiceDate,
                        'narration' => 'Rider Invoice #'.$invoice->id,
                        'debit' => $isNegativeTotal ? $absTotal : 0,
                        'credit' => $isNegativeTotal ? 0 : $absTotal,
                        'billing_month' => $billingMonth,
                        'branch_id' => $branchId,
                    ]);

                    // Salary: ex-VAT subtotal (VAT posted separately) — matches save/update
                    $transactionService->recordTransaction([
                        'account_id' => $salaryAccountId,
                        'reference_id' => $invoice->id,
                        'reference_type' => 'Invoice',
                        'trans_code' => $transCode,
                        'trans_date' => $invoiceDate,
                        'narration' => 'Salary Debit - Rider Invoice #'.$invoice->id,
                        'debit' => $isNegativeTotal ? 0 : $absSubtotal,
                        'credit' => $isNegativeTotal ? $absSubtotal : 0,
                        'billing_month' => $billingMonth,
                        'branch_id' => $branchId,
                    ]);
                }
            });
        }
    }
}
