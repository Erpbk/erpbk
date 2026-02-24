<?php

namespace App\Imports;

use App\Helpers\Account;
use App\Models\Accounts;
use App\Models\Riders;
use App\Models\Vouchers;
use App\Services\TransactionService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;
use DB;

class ImportRiderVoucherOnly implements ToCollection
{
    private function parseDate($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
        }
        return Carbon::parse($value)->format('Y-m-d');
    }

    public function collection(Collection $rows)
    {
        $rowNum = 0;
        foreach ($rows as $row) {
            $rowNum++;
            // Skip header
            if ($rowNum === 1) {
                continue;
            }
            // Required minimal columns check
            if (!isset($row[0]) || !isset($row[1]) || !isset($row[2]) || !isset($row[3])) {
                continue;
            }

            try {
                DB::beginTransaction();
                $riderExternalId = trim((string)($row[0] ?? ''));
                $billingMonth = $this->parseDate($row[1] ?? null);
                $transDate = $this->parseDate($row[2] ?? null);
                $amount = (float) ($row[3] ?? 0);
                $voucherType = trim((string)($row[4] ?? 'JV')) ?: 'JV';
                if ($voucherType == 'Advance Loan') {
                    $voucherType = 'AL';
                } else if ($voucherType == 'Payment Voucher') {
                    $voucherType = 'PAY';
                } else if ($voucherType == 'Incentive') {
                    $voucherType = 'INC';
                } else if ($voucherType == 'Vendor Charges') {
                    $voucherType = 'VC';
                } elseif ($voucherType == 'COD') {
                    $voucherType = 'COD';
                } elseif ($voucherType == 'Penalty') {
                    $voucherType = 'PN';
                } else {
                    $voucherType = 'JV'; // Default to Journal Voucher
                }
                $code = trim((string)($row[6] ?? ''));

                $account = Accounts::where('account_code', $code)->first();

                if (!$account) {
                    dd("Account code not found in DB: " . $code);
                }

                $accountId = $account->id;
                $rider = Riders::where('rider_id', $riderExternalId)->first();
                if (!$rider) {
                    throw ValidationException::withMessages([
                        'file' => "Row({$rowNum}) - Rider ID {$riderExternalId} not found."
                    ]);
                }
                $riderAccount = Accounts::where('ref_id', $rider->id)->first();

                $transCode = Account::trans_code();
                $billingsMonth = Carbon::parse($billingMonth ?: date('Y-m-01'))->format('M-Y');
                $narration = $row[5];
                $voucherData = [
                    'trans_date' => $transDate ?: date('Y-m-d'),
                    'posting_date' => $transDate ?: date('Y-m-d'),
                    'billing_month' => $billingMonth ?: date('Y-m-01'),
                    'payment_to' => $riderAccount->id,
                    'payment_from' => $accountId, // nullable
                    'payment_type' => 0,
                    'voucher_type' => $voucherType,
                    'amount' => $amount,
                    'remarks' => $narration,
                    'ref_id' => $rider->id,
                    'rider_id' => $rider->id,
                    'trans_code' => $transCode,
                    'Created_By' => auth()->id(),
                    'status' => 1,
                    'custom_field_values' => [],
                ];

                $voucher = Vouchers::create($voucherData);

                // Create accounting transactions (debit rider account, credit provided account)
                $tx = new TransactionService();



                // Debit - rider account

                $tx->recordTransaction([
                    'account_id' => $riderAccount->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => $voucherType,
                    'trans_code' => $transCode,
                    'trans_date' => $voucherData['trans_date'],
                    'narration' => $narration,
                    'debit' => $amount,
                    'billing_month' => $voucherData['billing_month'],
                ]);

                // Credit - counter account if provided
                if (!empty($accountId)) {
                    $tx->recordTransaction([
                        'account_id' => $accountId,
                        'reference_id' => $voucher->id,
                        'reference_type' => $voucherType,
                        'trans_code' => $transCode,
                        'trans_date' => $voucherData['trans_date'],
                        'narration' => $narration,
                        'credit' => $amount,
                        'billing_month' => $voucherData['billing_month'],
                    ]);
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }
    }
}
