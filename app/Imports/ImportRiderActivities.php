<?php

namespace App\Imports;

use App\Models\Riders;
use App\Models\RiderActivities;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;

class ImportRiderActivities implements ToCollection
{
  private $importErrors = [];
  private $successCount = 0;
  private $skippedCount = 0;

  public function collection(Collection $rows)
  {
    $rowNumber = 1; // Excel row count
    $validRows = []; // Store valid rows for processing

    // First pass: Validate all rows without saving
    foreach ($rows as $row) {
      $rowNumber++;

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
          'success' => 0,
          'skipped' => $this->skippedCount,
          'errors'  => $this->importErrors,
        ]
      ]);
      session()->save(); // Ensure session is saved before throwing exception

      throw ValidationException::withMessages(['file' => $errorMessages]);
    }

    // Check if there are any valid rows to process
    if (empty($validRows)) {
      // No valid rows to import
      session([
        'activities_import_summary' => [
          'success' => 0,
          'skipped' => $this->skippedCount,
          'errors'  => $this->importErrors,
        ]
      ]);
      session()->save();

      throw ValidationException::withMessages(['file' => ['No valid rows found to import. All rows were empty or skipped.']]);
    }

    // Second pass: Save all valid rows (only if no errors)
    DB::beginTransaction();
    try {
      foreach ($validRows as $validRowData) {
        try {
          $this->processRow($validRowData['row']);
          $this->successCount++;
        } catch (\Throwable $rowError) {
          // Log individual row error but continue with other rows
          Log::error('Rider Activity Import - Row Processing Failed', [
            'row' => $validRowData['rowNumber'],
            'rider_id' => $validRowData['row'][1] ?? 'N/A',
            'error' => $rowError->getMessage(),
          ]);

          $this->importErrors[] = [
            'row'        => $validRowData['rowNumber'],
            'error_type' => 'Processing Error',
            'message'    => 'Failed to save row: ' . $rowError->getMessage(),
            'rider_id'   => $validRowData['row'][1] ?? 'N/A',
          ];
        }
      }

      // If any errors occurred during processing, rollback and throw exception
      if (!empty($this->importErrors)) {
        DB::rollBack();

        $errorMessages = [];
        foreach ($this->importErrors as $error) {
          $riderId = $error['rider_id'] ?? 'N/A';
          $errorMessages[] = 'Row(' . $error['row'] . ') - ' . $error['error_type'] . ': ' . $error['message'] . ($riderId !== 'N/A' ? ' (Rider ID: ' . $riderId . ')' : '');
        }

        session([
          'activities_import_summary' => [
            'success' => 0,
            'skipped' => $this->skippedCount,
            'errors'  => $this->importErrors,
          ]
        ]);
        session()->save();

        throw ValidationException::withMessages(['file' => $errorMessages]);
      }

      DB::commit();

      // Log successful import
      Log::info('Rider Activity Import Successful', [
        'success_count' => $this->successCount,
        'skipped_count' => $this->skippedCount,
      ]);
    } catch (ValidationException $ve) {
      // Re-throw validation exceptions
      throw $ve;
    } catch (\Throwable $e) {
      DB::rollBack();

      $this->importErrors[] = [
        'row'        => 'N/A',
        'error_type' => 'System Error',
        'message'    => 'Database transaction failed: ' . $e->getMessage(),
        'rider_id'   => 'N/A',
      ];

      Log::error('Rider Activity Import Failed - Transaction Error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      $errorMessages = [];
      foreach ($this->importErrors as $error) {
        $riderId = $error['rider_id'] ?? 'N/A';
        $errorMessages[] = 'Row(' . $error['row'] . ') - ' . $error['error_type'] . ': ' . $error['message'] . ($riderId !== 'N/A' ? ' (Rider ID: ' . $riderId . ')' : '');
      }

      session([
        'activities_import_summary' => [
          'success' => 0,
          'skipped' => $this->skippedCount,
          'errors'  => $this->importErrors,
        ]
      ]);
      session()->save();

      throw ValidationException::withMessages(['file' => $errorMessages]);
    }

    // Store result in session
    session([
      'activities_import_summary' => [
        'success' => $this->successCount,
        'skipped' => $this->skippedCount,
        'errors'  => $this->importErrors,
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
   * Save or update row
   */
  private function processRow($row)
  {
    $rider = Riders::where('rider_id', trim($row[1]))->first();

    if (!$rider) {
      throw new \Exception('Rider not found for ID: ' . trim($row[1]));
    }

    $date = date('Y-m-d', strtotime($row[0]));

    if (!$date || $date == '1970-01-01') {
      throw new \Exception('Invalid date format: ' . $row[0]);
    }

    $data = [
      'rider_id'                    => $rider->id,
      'd_rider_id'                  => trim($row[1]),
      'date'                        => $date,
      'payout_type'                 => $row[5] ?? null,
      'delivered_orders'            => (int) ($row[14] ?? 0),
      'ontime_orders_percentage'    => (float) str_replace('%', '', $row[22] ?? 0),
      'rejected_orders'             => (int) ($row[17] ?? 0),
      'login_hr'                    => (float) ($row[10] ?? 0),
      'delivery_rating'             => $row[8] ?? '-',
    ];

    $result = RiderActivities::updateOrCreate(
      [
        'rider_id' => $rider->id,
        'date'     => $date
      ],
      $data
    );

    if (!$result || !$result->id) {
      throw new \Exception('Failed to save rider activity for Rider ID: ' . trim($row[1]) . ', Date: ' . $date);
    }

    return $result;
  }
}
