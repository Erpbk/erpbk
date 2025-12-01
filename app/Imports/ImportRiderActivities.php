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
use App\Services\TransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Auth;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class ImportRiderActivities implements ToCollection
{
  private $importErrors = [];
  private $successCount = 0;
  private $skippedCount = 0;

  public function collection(Collection $rows)
  {
    $i = 1; // Start from 1 to match Excel row numbers
    foreach ($rows as $row) {
      $i++;

      // Skip header row
      if ($i <= 2) continue;

      try {
        DB::beginTransaction();

        // Validate row is not completely empty
        if (collect($row)->filter()->isEmpty()) {
          $this->skippedCount++;
          DB::rollBack();
          continue;
        }

        // Detailed row validation
        $validationErrors = $this->validateRow($row, $i);
        if (!empty($validationErrors)) {
          $this->importErrors[] = $validationErrors;
          $this->skippedCount++;
          DB::rollBack();
          continue;
        }

        // Process the row
        $this->processRow($row, $i);

        DB::commit();
        $this->successCount++;
      } catch (\Exception $e) {
        DB::rollBack();

        $this->importErrors[] = [
          'row' => $i,
          'error_type' => 'Unexpected Error',
          'message' => $e->getMessage(),
          'rider_id' => $row[1] ?? 'N/A',
          'date' => $row[0] ?? 'N/A',
          'payout_type' => $row[2] ?? 'N/A',
          'full_row_data' => $row->toArray()
        ];
        $this->skippedCount++;

        Log::error('Unexpected error during rider activities import', [
          'row' => $i,
          'error' => $e->getMessage(),
          'trace' => $e->getTraceAsString()
        ]);
      }
    }

    // Store import summary in session
    session([
      'activities_import_summary' => [
        'total_rows' => $i - 2, // Exclude header rows
        'success_count' => $this->successCount,
        'skipped_count' => $this->skippedCount,
        'error_count' => count($this->importErrors),
        'errors' => $this->importErrors
      ]
    ]);

    // Throw exception if there are errors to trigger error page
    if (!empty($this->importErrors)) {
      $errorMessage = "Import completed with errors. {$this->successCount} records imported successfully, {$this->skippedCount} records skipped.";
      throw new \Exception($errorMessage);
    }
  }

  private function validateRow($row, $rowNumber)
  {
    $errors = [];

    // Validate Rider ID
    if (empty($row[1])) {
      $errors = [
        'row' => $rowNumber,
        'error_type' => 'Empty Rider ID',
        'message' => 'Rider ID cannot be empty',
        'rider_id' => 'N/A',
        'date' => $row[0] ?? 'N/A',
        'payout_type' => $row[2] ?? 'N/A'
      ];
      return $errors;
    }

    // Validate Rider exists
    $rider = Riders::where('rider_id', trim($row[1]))->first();
    if (!$rider) {
      $errors = [
        'row' => $rowNumber,
        'error_type' => 'Rider Not Found',
        'message' => 'No rider exists in the system with this Rider ID',
        'rider_id' => $row[1],
        'date' => $row[0] ?? 'N/A',
        'payout_type' => $row[2] ?? 'N/A'
      ];
      return $errors;
    }

    // Validate Date
    try {
      if (empty($row[0])) {
        throw new \Exception('Date field is empty');
      }
      $activity_date = date('Y-m-d', strtotime($row[0]));
      if ($activity_date === '1970-01-01' || $activity_date === false) {
        throw new \Exception('Invalid date format');
      }
    } catch (\Exception $e) {
      $errors = [
        'row' => $rowNumber,
        'error_type' => 'Invalid Date',
        'message' => 'Date field is empty or has invalid format: ' . ($row[0] ?? 'N/A'),
        'rider_id' => $row[1],
        'date' => $row[0] ?? 'N/A',
        'payout_type' => $row[2] ?? 'N/A'
      ];
      return $errors;
    }

    // Validate numeric columns
    $numericColumns = [
      ['index' => 12, 'name' => 'Delivered Orders', 'type' => 'integer'],
      ['index' => 13, 'name' => 'On-time Orders Percentage', 'type' => 'float'],
      ['index' => 10, 'name' => 'Rejected Orders', 'type' => 'integer'],
      ['index' => 16, 'name' => 'Login Hours', 'type' => 'float'],
      ['index' => 17, 'name' => 'Delivery Rating', 'type' => 'float']
    ];

    foreach ($numericColumns as $column) {
      $value = $row[$column['index']] ?? null;

      // Skip if value is empty or null
      if ($value === null || $value === '') continue;

      // Remove any percentage signs or dashes
      $value = str_replace(['-', '%'], '', $value);

      // Validate numeric type
      if ($column['type'] === 'integer' && !is_numeric($value)) {
        $errors = [
          'row' => $rowNumber,
          'error_type' => 'Invalid Numeric Value',
          'message' => "{$column['name']} must be a valid number",
          'rider_id' => $row[1],
          'date' => $row[0] ?? 'N/A',
          'payout_type' => $row[2] ?? 'N/A',
          'invalid_column' => $column['name'],
          'invalid_value' => $row[$column['index']] ?? 'N/A'
        ];
        return $errors;
      }

      // Validate float type
      if ($column['type'] === 'float' && !is_numeric($value)) {
        $errors = [
          'row' => $rowNumber,
          'error_type' => 'Invalid Numeric Value',
          'message' => "{$column['name']} must be a valid decimal number",
          'rider_id' => $row[1],
          'date' => $row[0] ?? 'N/A',
          'payout_type' => $row[2] ?? 'N/A',
          'invalid_column' => $column['name'],
          'invalid_value' => $row[$column['index']] ?? 'N/A'
        ];
        return $errors;
      }
    }

    return $errors;
  }

  private function processRow($row, $rowNumber)
  {
    $riderID = trim($row[1]);
    $rider = Riders::where('rider_id', $riderID)->first();
    $activity_date = date('Y-m-d', strtotime($row[0]));

    $data = [
      'rider_id' => $rider->id,
      'd_rider_id' => $riderID,
      'date' => $activity_date,
      'payout_type' => $row[2],
      'delivered_orders' => $row[12] ?? 0,
      'ontime_orders_percentage' => number_format(str_replace('-', '0', $row[13] ?? 0), 2),
      'rejected_orders' => $row[10] ?? 0,
      'login_hr' => $row[16] ?? 0,
      'delivery_rating' => str_replace("-", "0", $row[17] ?? 0),
    ];

    // Check for existing activity on the same date
    $activity_exist = RiderActivities::where('rider_id', $rider->id)
      ->where('date', $activity_date)
      ->first();

    if (!$activity_exist) {
      RiderActivities::create($data);
    } else {
      $activity_exist->update($data);
    }
  }

  /**
   * Get import errors
   * 
   * @return array
   */
  public function getErrors()
  {
    return $this->importErrors;
  }

  /**
   * Get success count
   * 
   * @return int
   */
  public function getSuccessCount()
  {
    return $this->successCount;
  }

  /**
   * Get skipped count
   * 
   * @return int
   */
  public function getSkippedCount()
  {
    return $this->skippedCount;
  }
}
