<?php

namespace App\Imports;

use App\Models\Riders;
use App\Models\liveactivities;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;

class ImportLiveActivities implements ToCollection
{
  private $importErrors = [];
  private $successCount = 0;
  private $skippedCount = 0;
  private $totalRows = 0;
  private $currentDate;

  public function __construct()
  {
    // Store current date for reference
    $this->currentDate = Carbon::today()->format('Y-m-d');
  }

  public function collection(Collection $rows)
  {
    $rowNumber = 1; // Excel row count
    $validRows = []; // Store valid rows for processing

    // First pass: Validate all rows without saving
    foreach ($rows as $row) {
      $rowNumber++;
      $this->totalRows++;

      // Skip header rows
      if ($rowNumber <= 2) {
        continue;
      }

      // Skip empty row
      if (collect($row)->filter()->isEmpty()) {
        $this->skippedCount++;
        continue;
      }

      // Validate row
      $error = $this->validateRow($row, $rowNumber);
      if ($error) {
        $this->importErrors[] = $error;
        $this->skippedCount++;
        continue;
      }

      // Store valid row for processing
      $validRows[] = ['row' => $row, 'rowNumber' => $rowNumber];
    }

    // If there are any errors, throw ValidationException to prevent data storage
    // This ensures no activities are saved if any Rider ID doesn't match
    if (!empty($this->importErrors)) {
      $errorMessages = [];
      foreach ($this->importErrors as $error) {
        $riderId = $error['rider_id'] ?? 'N/A';
        $errorMessages[] = 'Row(' . $error['row'] . ') - ' . $error['error_type'] . ': ' . $error['message'] . ($riderId !== 'N/A' ? ' (Rider ID: ' . $riderId . ')' : '');
      }

      // Store result in session before throwing exception
      session([
        'activities_import_summary' => [
          'total_rows' => $this->totalRows,
          'success_count' => 0,
          'skipped_count' => $this->skippedCount,
          'error_count' => count($this->importErrors),
          'errors'  => $this->importErrors,
          'current_date' => $this->currentDate,
        ]
      ]);
      session()->save(); // Ensure session is saved before throwing exception

      throw ValidationException::withMessages(['file' => $errorMessages]);
    }

    // Second pass: Save all valid rows (only if no errors)
    DB::beginTransaction();
    try {
      foreach ($validRows as $validRowData) {
        $this->processRow($validRowData['row'], $validRowData['rowNumber']);
        $this->successCount++;
      }
      DB::commit();
    } catch (\Throwable $e) {
      DB::rollBack();

      $this->importErrors[] = [
        'row'        => $validRowData['rowNumber'] ?? 'N/A',
        'error_type' => 'System Error',
        'message'    => $e->getMessage(),
        'rider_id'   => $validRowData['row'][1] ?? 'N/A',
      ];

      Log::error('Live Activity Import Failed', [
        'error' => $e->getMessage(),
      ]);

      $errorMessages = [];
      foreach ($this->importErrors as $error) {
        $riderId = $error['rider_id'] ?? 'N/A';
        $errorMessages[] = 'Row(' . $error['row'] . ') - ' . $error['error_type'] . ': ' . $error['message'] . ($riderId !== 'N/A' ? ' (Rider ID: ' . $riderId . ')' : '');
      }

      session([
        'activities_import_summary' => [
          'total_rows' => $this->totalRows,
          'success_count' => 0,
          'skipped_count' => $this->skippedCount,
          'error_count' => count($this->importErrors),
          'errors'  => $this->importErrors,
          'current_date' => $this->currentDate,
        ]
      ]);
      session()->save(); // Ensure session is saved before throwing exception

      throw ValidationException::withMessages(['file' => $errorMessages]);
    }

    // Store result in session
    session([
      'activities_import_summary' => [
        'total_rows' => $this->totalRows,
        'success_count' => $this->successCount,
        'skipped_count' => $this->skippedCount,
        'error_count' => count($this->importErrors),
        'errors'  => $this->importErrors,
        'current_date' => $this->currentDate,
      ]
    ]);
    session()->save(); // Ensure session is saved
  }

  /**
   * Validate single row
   */
  private function validateRow($row, $rowNumber)
  {
    // Rider ID empty
    if (empty($row[1])) {
      return [
        'row'        => $rowNumber,
        'error_type' => 'Empty Rider ID',
        'message'    => 'Rider ID is missing',
      ];
    }

    // Rider not found
    $rider = Riders::where('rider_id', trim($row[1]))->first();
    if (!$rider) {
      return [
        'row'        => $rowNumber,
        'error_type' => 'Rider Not Found',
        'message'    => 'Rider ID does not exist in system',
        'rider_id'   => $row[1],
      ];
    }

    // Date validation
    if (empty($row[0]) || strtotime($row[0]) === false) {
      return [
        'row'        => $rowNumber,
        'error_type' => 'Invalid Date',
        'message'    => 'Invalid or empty date',
        'rider_id'   => $row[1],
      ];
    }

    return null;
  }

  /**
   * Save or update row (updates existing rider record regardless of date)
   */
  private function processRow($row, $rowNumber)
  {
    $rider = Riders::where('rider_id', trim($row[1]))->first();
    $date  = date('Y-m-d', strtotime($row[0])); // Use date from import file

    // Get login hours
    $loginHours = (float) ($row[9] ?? 0);

    // Determine attendance status based on login hours
    // If login hours = 0, mark as Absent, otherwise Present
    $attendanceStatus = ($loginHours == 0) ? 'Absent' : 'Present';

    // Parse ontime percentage
    $ontimePercentage = $row[20] ?? '0';
    $ontimePercentage = (float) str_replace('%', '', $ontimePercentage);

    $data = [
      'rider_id'                    => $rider->id,
      'd_rider_id'                  => trim($row[1]),
      'date'                        => $date,
      'payout_type'                 => $row[5] ?? null,
      'delivered_orders'            => (int) ($row[14] ?? 0),
      'ontime_orders_percentage'    => $ontimePercentage,
      'rejected_orders'             => (int) ($row[17] ?? 0),
      'login_hr'                    => $loginHours,
      'delivery_rating'             => $attendanceStatus,
    ];
    // Update or create based on rider_id only
    // This ensures only one record per rider exists, which gets updated with new date data
    // No new records are created when importing activities for different dates
    liveactivities::updateOrCreate(
      [
        'rider_id' => $rider->id
      ],
      $data
    );
  }
}
