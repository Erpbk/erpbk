<?php

namespace App\Imports;

use App\Models\Riders;
use App\Models\RiderActivities;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class ImportRiderActivities implements ToCollection
{
  private $importErrors = [];
  private $successCount = 0;
  private $skippedCount = 0;

  public function collection(Collection $rows)
  {
    $rowNumber = 1; // Excel row count

    foreach ($rows as $row) {
      $rowNumber++;

      // Skip header rows
      if ($rowNumber <= 2) {
        continue;
      }

      DB::beginTransaction();

      try {
        // Skip empty row
        if (collect($row)->filter()->isEmpty()) {
          $this->skippedCount++;
          DB::rollBack();
          continue;
        }

        // Validate row
        $error = $this->validateRow($row, $rowNumber);
        if ($error) {
          $this->importErrors[] = $error;
          $this->skippedCount++;
          DB::rollBack();
          continue;
        }

        // Process row
        $this->processRow($row);

        DB::commit();
        $this->successCount++;
      } catch (\Throwable $e) {
        DB::rollBack();

        $this->importErrors[] = [
          'row'        => $rowNumber,
          'error_type' => 'System Error',
          'message'    => $e->getMessage(),
          'rider_id'   => $row[1] ?? 'N/A',
        ];

        Log::error('Rider Activity Import Failed', [
          'row' => $rowNumber,
          'error' => $e->getMessage(),
        ]);

        $this->skippedCount++;
      }
    }

    // Store result in session
    session([
      'activities_import_summary' => [
        'success' => $this->successCount,
        'skipped' => $this->skippedCount,
        'errors'  => $this->importErrors,
      ]
    ]);
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
   * Save or update row
   */
  private function processRow($row)
  {
    $rider = Riders::where('rider_id', trim($row[1]))->first();
    $date  = date('Y-m-d', strtotime($row[0]));

    $data = [
      'rider_id'                    => $rider->id,
      'd_rider_id'                  => trim($row[1]),
      'date'                        => $date,
      'payout_type'                 => $row[5] ?? null,
      'delivered_orders'            => (int) ($row[14] ?? 0),
      'ontime_orders_percentage'    => (float) str_replace('%', '', $row[22] ?? 0),
      'rejected_orders'             => (int) ($row[17] ?? 0),
      'login_hr'                    => (float) ($row[10] ?? 0),
      'delivery_rating'             => (float) ($row[8] ?? 0),
    ];

    RiderActivities::updateOrCreate(
      [
        'rider_id' => $rider->id,
        'date'     => $date
      ],
      $data
    );
  }
}
