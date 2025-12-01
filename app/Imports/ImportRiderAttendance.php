<?php

namespace App\Imports;

use App\Helpers\Account;
use App\Helpers\General;
use App\Helpers\HeadAccount;
use App\Models\Items;
use App\Models\RiderInvoiceItem;
use App\Models\RiderInvoices;
use App\Models\Riders;
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

class ImportRiderAttendance implements ToCollection
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
  public function collection(Collection $rows)
  {

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
    $attendance_date = date('Y-m-d');
    $i = 1;
    foreach ($rows as $row) {

      $i++;
      try {
        DB::beginTransaction();
        if ($i > 2) {
          $rider_id = $this->extractValue($row[0]);
          if ($rider_id) {
            $rider = Riders::where('rider_id', $rider_id)->first();
            if (!$rider) {
              throw ValidationException::withMessages(['file' => 'Row(' . $i . ') - Rider ID ' . $this->extractValue($row[1]) . ' do not match.']);
            } else {

              $rider->shift = $this->extractValue($row[2]);
              $rider->attendance = $this->extractValue($row[8]) ?? '';
              $rider->attendance_date = $attendance_date;
              $rider->save();
              /* $RID = $rider->id;
              $ret = \App\Models\RiderAttendance::create([
                'rider_id' => $RID,
                'd_rider_id' => $rider_id,
                'date' => $attendance_date,
                'shift' => $this->extractValue($row[2]),
                'attendance' => $this->extractValue($row[8]) ?? 'Present',
                'cdm_id' => $this->extractValue($row[7]),
                'day' => $this->extractValue($row[3])

              ]); */
            }

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
