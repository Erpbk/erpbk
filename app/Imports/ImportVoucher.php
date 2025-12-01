<?php

namespace App\Imports;

use App\Helpers\Account;
use App\Helpers\General;
use App\Helpers\HeadAccount;
use App\Models\Items;
use App\Models\RiderActivities;
use App\Models\RiderInvoiceItem;
use App\Models\RiderInvoices;
use App\Models\Riders;
use App\Models\Vouchers;
use App\Services\TransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use DB;
use Auth;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class ImportVoucher implements ToCollection
{
  /**
   * @param array $row
   *
   * @return \Illuminate\Database\Eloquent\Model|null
   */

  function extractValue($formula)
  {
    // Matches fallback value after last comma before the closing parenthesis
    if (preg_match('/=IFERROR\([^,]+,\s*(.+?)\)$/', $formula, $matches)) {
      $fallback = trim($matches[1], '"'); // remove quotes if any
      return $fallback;
    }

    return null;
  }
  private function transformDate($value)
  {
    // Handle Excel date format
    if (is_numeric($value)) {
      return Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d');
    }

    // Try parsing as regular date string
    return Carbon::parse($value)->format('Y-m-d');
  }
  public function collection(Collection $rows)
  {
    $request = request()->all();

    /* if (!is_numeric($rows[0][0])) {
      preg_match('/"([\d\/]+)"/', $rows[0][8], $matches);
      $cleanDate = $matches[1] ?? null;
      // Optionally format it
      if ($cleanDate) {
        $carbonDate = Carbon::createFromFormat('n/j/Y', $cleanDate);
        $attendance_date = $carbonDate->format('Y-m-d'); // Output: 2025-04-08
      } else {
        $attendance_date = date('Y-m-d');
      }
    } */


    $i = 1;
    foreach ($rows as $row) {

      $i++;
      try {
        DB::beginTransaction();
        if ($i > 2) {
          $date = $this->transformDate($row[0]);
          $billing_month = $this->transformDate($row[1]);

          $rider = Riders::where('rider_id', $row[2])->first();

          if (!$rider) {
            //throw ValidationException::withMessages(['file' => 'Row(' . $i . ') - Rider ID ' . $row[1] . ' do not match.']);
          } else {

            /* $rider->shift = $this->extractValue($row[2]);
            $rider->attendance = $this->extractValue($row[8]) ?? '';
            $rider->save(); */
            $activity_date = date('Y-m-d', strtotime($row[0]));

            $RID = $rider->id;
            $trans_code = Account::trans_code();

            $data['remarks'] = 'Journal Voucher';
            $data['amount'] = $row[4];
            $data['payment_type'] = 0;
            $data['voucher_type'] = 'JV';
            $data['posting_date'] = $date;
            $data['trans_date'] = $date;
            $data['billing_month'] = $billing_month;
            $data['payment_to'] = $rider->account_id;
            $data['payment_from'] = $request['payment_from'];
            $data['Created_By'] = \Auth::user()->id;
            $data['trans_code'] = $trans_code;

            $ret = Vouchers::create($data);

            $TransactionService = new TransactionService();
            //debit recording
            $transactionData = [
              'account_id' => $rider->account_id,
              'reference_id' => $ret->id,
              'reference_type' => 'Voucher',
              'trans_code' => $trans_code,
              'trans_date' => $date,
              'narration' => $row[3],
              'debit' => $row[4] ?? 0,
              //'credit' => $data['credit'] ?? 0,
              'billing_month' => $billing_month ?? date('Y-m-01'),
            ];
            $TransactionService->recordTransaction($transactionData);

            //credit recording
            $transactionData = [
              'account_id' => $request['payment_from'],
              'reference_id' => $ret->id,
              'reference_type' => 'Voucher',
              'trans_code' => $trans_code,
              'trans_date' => $date,
              'narration' => $row[3],
              //'debit' => $request['dr_amount'][$key] ?? 0,
              'credit' => $row[4] ?? 0,
              'billing_month' => $billing_month ?? date('Y-m-01'),
            ];
            $TransactionService->recordTransaction($transactionData);
          }
        }

        DB::commit();
      } catch (QueryException $e) {
        DB::rollBack();
        throw $e;
      }
    }
  }
}
