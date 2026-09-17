<?php

namespace App\Imports;

use App\Models\Riders;
use App\Models\liveactivities;
use App\Services\RiderActivities\RiderActivityImportMappingService;
use App\Support\ExcelDate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;

class ImportLiveActivities implements ToCollection
{
  private array $importErrors = [];
  private array $missingRecords = [];
  private int $successCount = 0;
  private int $skippedCount = 0;
  private ?string $importedDateFrom = null;
  private ?string $importedDateTo = null;
  private int $headerRowsToSkip;
  /** @var array<string, int> */
  private array $columnMappings;
  /** @var array<string, bool> */
  private array $requiredFields;
  /** @var array<string, string> */
  private array $fieldLabels;

  public function __construct(
    private readonly int $customerId = RiderActivityImportMappingService::DEFAULT_CUSTOMER_ID,
    ?RiderActivityImportMappingService $mappingService = null
  ) {
    $mappingService ??= app(RiderActivityImportMappingService::class);
    $resolved = $mappingService->resolve($this->customerId, RiderActivityImportMappingService::TYPE_LIVE);
    $this->headerRowsToSkip = $resolved['header_rows_to_skip'];
    $this->columnMappings = $resolved['column_mappings'];
    $this->requiredFields = $resolved['required_fields'] ?? RiderActivityImportMappingService::requiredFields();
    $this->fieldLabels = RiderActivityImportMappingService::fieldLabels();
  }

  public function collection(Collection $rows)
  {
    $rowNumber = 0;
    $validRows = [];

    foreach ($rows as $row) {
      $rowNumber++;

      if ($rowNumber <= $this->headerRowsToSkip) {
        continue;
      }

      if (collect($row)->filter()->isEmpty()) {
        $this->skippedCount++;
        continue;
      }

      $error = $this->validateRow($row, $rowNumber);
      if ($error) {
        if ($error['error_type'] === 'Rider Not Found') {
          $this->missingRecords[] = [
            'row'        => $rowNumber,
            'rider_id'   => $error['rider_id'] ?? 'N/A',
            'date'       => $this->columnValue($row, 'date') ?? 'N/A',
            'error_type' => 'Rider Not Found',
            'message'    => 'Rider ID does not exist in system',
            'raw_data'   => [
              'rider_id' => $this->columnValue($row, 'rider_id'),
              'date' => $this->columnValue($row, 'date'),
              'payout_type' => $this->columnValue($row, 'payout_type'),
              'delivered_orders' => $this->columnValue($row, 'delivered_orders'),
            ],
          ];
          $this->skippedCount++;
          continue;
        }

        $this->importErrors[] = $error;
        $this->skippedCount++;
        continue;
      }

      $validRows[] = ['row' => $row, 'rowNumber' => $rowNumber];
    }

    if (!empty($this->importErrors)) {
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
          'missing_records' => $this->missingRecords,
          'customer_id' => $this->customerId,
          'import_type' => RiderActivityImportMappingService::TYPE_LIVE,
        ]
      ]);
      session()->save();

      throw ValidationException::withMessages(['file' => $errorMessages]);
    }

    if (empty($validRows)) {
      session([
        'activities_import_summary' => [
          'success' => 0,
          'skipped' => $this->skippedCount,
          'errors'  => $this->importErrors,
          'missing_records' => $this->missingRecords,
          'customer_id' => $this->customerId,
          'import_type' => RiderActivityImportMappingService::TYPE_LIVE,
        ]
      ]);
      session()->save();

      throw ValidationException::withMessages(['file' => ['No valid rows found to import. All rows were empty or skipped.']]);
    }

    DB::beginTransaction();
    try {
      foreach ($validRows as $validRowData) {
        try {
          $result = $this->processRow($validRowData['row'], $validRowData['rowNumber']);
          if ($result !== null) {
            $this->successCount++;
          }
        } catch (\Throwable $rowError) {
          Log::error('Live Activity Import - Row Processing Failed', [
            'row' => $validRowData['rowNumber'],
            'rider_id' => $this->columnValue($validRowData['row'], 'rider_id') ?? 'N/A',
            'customer_id' => $this->customerId,
            'error' => $rowError->getMessage(),
          ]);

          $this->importErrors[] = [
            'row'        => $validRowData['rowNumber'],
            'error_type' => 'Processing Error',
            'message'    => 'Failed to save row: ' . $rowError->getMessage(),
            'rider_id'   => $this->columnValue($validRowData['row'], 'rider_id') ?? 'N/A',
          ];
        }
      }

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
            'missing_records' => $this->missingRecords,
            'customer_id' => $this->customerId,
            'import_type' => RiderActivityImportMappingService::TYPE_LIVE,
          ]
        ]);
        session()->save();

        throw ValidationException::withMessages(['file' => $errorMessages]);
      }

      DB::commit();

      Log::info('Live Activity Import Successful', [
        'success_count' => $this->successCount,
        'skipped_count' => $this->skippedCount,
        'missing_records_count' => count($this->missingRecords),
        'customer_id' => $this->customerId,
      ]);
    } catch (ValidationException $ve) {
      throw $ve;
    } catch (\Throwable $e) {
      DB::rollBack();

      $this->importErrors[] = [
        'row'        => 'N/A',
        'error_type' => 'System Error',
        'message'    => 'Database transaction failed: ' . $e->getMessage(),
        'rider_id'   => 'N/A',
      ];

      Log::error('Live Activity Import Failed - Transaction Error', [
        'error' => $e->getMessage(),
        'customer_id' => $this->customerId,
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
          'missing_records' => $this->missingRecords,
          'customer_id' => $this->customerId,
          'import_type' => RiderActivityImportMappingService::TYPE_LIVE,
        ]
      ]);
      session()->save();

      throw ValidationException::withMessages(['file' => $errorMessages]);
    }

    session([
      'activities_import_summary' => [
        'success' => $this->successCount,
        'skipped' => $this->skippedCount,
        'errors'  => $this->importErrors,
        'missing_records' => $this->missingRecords,
        'customer_id' => $this->customerId,
        'import_type' => RiderActivityImportMappingService::TYPE_LIVE,
        'date_from' => $this->importedDateFrom,
        'date_to' => $this->importedDateTo,
      ]
    ]);
    session()->save();
  }

  private function validateRow($row, $rowNumber)
  {
    $riderIdValue = $this->columnValue($row, 'rider_id');

    if ($riderIdValue === null || $riderIdValue === '') {
      return [
        'row'        => $rowNumber,
        'error_type' => 'Empty Rider ID',
        'message'    => 'Rider ID is missing',
      ];
    }

    $rider = Riders::where('rider_id', trim((string) $riderIdValue))->first();
    if (!$rider) {
      return [
        'row'        => $rowNumber,
        'error_type' => 'Rider Not Found',
        'message'    => 'Rider ID does not exist in system',
        'rider_id'   => $riderIdValue,
      ];
    }

    $dateValue = $this->columnValue($row, 'date');
    $parsedDate = $this->parseDateValue($dateValue);
    if ($parsedDate === null) {
      return [
        'row'        => $rowNumber,
        'error_type' => 'Invalid Date',
        'message'    => 'Invalid or empty date',
        'rider_id'   => $riderIdValue,
      ];
    }

    foreach ($this->requiredFields as $field => $isRequired) {
      if (! $isRequired || ! $this->isMapped($field)) {
        continue;
      }
      if (in_array($field, ['date', 'rider_id'], true)) {
        continue;
      }

      $value = $this->columnValue($row, $field);
      if ($value === null || $value === '') {
        $label = $this->fieldLabels[$field] ?? $field;

        return [
          'row'        => $rowNumber,
          'error_type' => 'Required Field Missing',
          'message'    => $label . ' is required for this project but the mapped Excel cell is empty',
          'rider_id'   => $riderIdValue,
        ];
      }
    }

    return null;
  }

  private function processRow($row, $rowNumber = null)
  {
    $riderIdValue = trim((string) $this->columnValue($row, 'rider_id'));
    $rider = Riders::where('rider_id', $riderIdValue)->first();

    if (!$rider) {
      $this->missingRecords[] = [
        'row'        => $rowNumber ?? 'N/A',
        'rider_id'   => $riderIdValue ?: 'N/A',
        'date'       => $this->columnValue($row, 'date') ?? 'N/A',
        'error_type' => 'Rider Not Found',
        'message'    => 'Rider ID does not exist in system',
      ];
      $this->skippedCount++;

      return null;
    }

    $date = $this->parseDateValue($this->columnValue($row, 'date'));

    if ($date === null) {
      throw new \Exception('Invalid date format: ' . $this->columnValue($row, 'date'));
    }

    // Only write fields configured for this project's Live Activity mappings.
    $data = [
      'rider_id'   => $rider->id,
      'd_rider_id' => $riderIdValue,
      'date'       => $date,
    ];

    if ($this->isMapped('payout_type')) {
      $data['payout_type'] = $this->columnValue($row, 'payout_type');
    }
    if ($this->isMapped('delivered_orders')) {
      $data['delivered_orders'] = (int) ($this->columnValue($row, 'delivered_orders') ?? 0);
    }
    if ($this->isMapped('ontime_orders_percentage')) {
      $ontimePercentage = $this->columnValue($row, 'ontime_orders_percentage');
      $data['ontime_orders_percentage'] = (float) str_replace('%', '', (string) ($ontimePercentage ?? 0));
    }
    if ($this->isMapped('rejected_orders')) {
      $data['rejected_orders'] = (int) ($this->columnValue($row, 'rejected_orders') ?? 0);
    }
    if ($this->isMapped('login_hr')) {
      $data['login_hr'] = (float) ($this->columnValue($row, 'login_hr') ?? 0);
    }
    if ($this->isMapped('delivery_rating')) {
      $data['delivery_rating'] = $this->columnValue($row, 'delivery_rating') ?? '-';
    }

    $result = liveactivities::updateOrCreate(
      [
        'rider_id' => $rider->id,
      ],
      $data
    );

    if (!$result || !$result->id) {
      throw new \Exception('Failed to save live activity for Rider ID: ' . $riderIdValue . ', Date: ' . $date);
    }

    if ($this->importedDateFrom === null || $date < $this->importedDateFrom) {
      $this->importedDateFrom = $date;
    }
    if ($this->importedDateTo === null || $date > $this->importedDateTo) {
      $this->importedDateTo = $date;
    }

    return $result;
  }

  private function isMapped(string $field): bool
  {
    return array_key_exists($field, $this->columnMappings);
  }

  /**
   * Normalize Excel date cells (serial numbers, DateTime, strings) to Y-m-d.
   */
  private function parseDateValue($value): ?string
  {
    return ExcelDate::format($value, 'Y-m-d');
  }

  private function columnValue($row, string $field)
  {
    if (! $this->isMapped($field)) {
      return null;
    }

    $index = (int) $this->columnMappings[$field];
    $value = data_get($row, $index);

    if ($value === null || $value === '') {
      return null;
    }

    return is_string($value) ? trim($value) : $value;
  }
}
