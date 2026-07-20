<?php

namespace App\Imports;

use App\Models\Riders;
use App\Services\Attendance\RiderAttendanceActivitySync;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use DB;

class ImportRiderAttendance implements ToCollection
{
  function extractValue($formula)
  {
    if (preg_match('/=IFERROR\([^,]+,\s*(.+?)\)$/', $formula, $matches)) {
      $fallback = trim($matches[1], '"');
      return $fallback;
    }

    return null;
  }

  public function collection(Collection $rows)
  {
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
            }

            $attendanceStatus = $this->extractValue($row[8]) ?? 'Present';
            $rider->shift = $this->extractValue($row[2]);
            $rider->attendance = $attendanceStatus;
            $rider->save();

            $status = RiderAttendanceActivitySync::normalizeAttendanceStatus($attendanceStatus);

            $attendance = RiderAttendanceActivitySync::syncAttendanceFromActivity($rider, $attendance_date, [], $status);
            RiderAttendanceActivitySync::syncActivityFromAttendance(
                $rider->id,
                $attendance_date,
                RiderAttendanceActivitySync::syncDataFromAttendance($attendance)
            );
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
