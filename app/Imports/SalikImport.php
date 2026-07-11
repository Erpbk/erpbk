<?php

namespace App\Imports;

use App\Models\salik;
use App\Models\Bikes;
use App\Models\Riders;
use App\Models\BikeHistory;
use App\Models\FailedSalikImport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use DB;
use Auth;
use Carbon\Carbon;

class SalikImport implements ToCollection
{
    protected $adminChargePerSalik;
    protected $importBatchId;
    protected $affectedInvoiceGroups = [];

    public function __construct($adminChargePerSalik = 0)
    {
        $this->adminChargePerSalik = $adminChargePerSalik;
        $this->importBatchId = 'batch_' . time() . '_' . Auth::id();
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $importedSalikIds = [];
            $rowCount = 0;
            $skippedCount = 0;
            $duplicateCount = 0;
            $missingDataCount = 0;
            $noBikeCount = 0;
            $noRiderCount = 0;
            $processedTransactionIds = []; // Track transaction IDs processed in this import

            foreach ($rows->skip(1) as $rowIndex => $row) {
                $rowCount++;
                if (empty($row[0])) continue;

                try {

                    // --- Safe mapping (index based) ---
                    $transactionId       = $row[0] ?? null;
                    $tripDateRaw         = $row[1] ?? null;
                    $transactionPostDateRaw = $row[3] ?? null;
                    $tripDate            = $this->parseTripDate($tripDateRaw);
                    $tripDateForStorage  = $tripDate ? $tripDate->format('d M Y') : null;
                    $tripDateForQueries  = $tripDate ? $tripDate->toDateString() : null;
                    $tripTimeRaw         = $row[2] ?? null;
                    $tripTime            = $this->parseTripTime($tripTimeRaw);
                    $transactionPostDate = $this->parseTransactionPostDate($transactionPostDateRaw);
                    $tollGate            = $row[4] ?? null;
                    $direction           = $row[5] ?? null;
                    $tagNumber           = $row[6] ?? null;
                    $plateNumber         = $row[7] ?? null;
                    $amount              = $row[8] ?? null;
                    $billingMonthRaw     = $row[9] ?? null;
                    $salikVatPercent     = (float) ($row[10] ?? 0);
                    $adminCharge         = (float) ($row[11] ?? $this->adminChargePerSalik);
                    $adminVatPercent     = (float) ($row[12] ?? 0);
                    $details             = $row[13] ?? null;
                    $transactionAmount = (float) ($amount ?: ($row[14] ?? 0));
                    $salikVatAmount = round($transactionAmount * $salikVatPercent / 100, 2);
                    $adminVatAmount = round($adminCharge * $adminVatPercent / 100, 2);
                    $totalVat = $salikVatAmount + $adminVatAmount;
                    $totalAmount = $transactionAmount + $adminCharge + $totalVat;


                    if (empty($transactionId) || empty($tripDateForStorage) || empty($plateNumber) || empty($transactionAmount)) {
                        \Log::warning("Missing required fields in row {$rowCount} - Skipping this record. Transaction ID: {$transactionId}, Trip Date: {$tripDate}, Plate: {$plateNumber}, Amount: {$transactionAmount}");
                        $this->storeFailedImport($row, $rowCount, 'Missing required fields', "Transaction ID: {$transactionId}, Trip Date: {$tripDate}, Plate: {$plateNumber}, Amount: {$transactionAmount}");
                        $missingDataCount++;
                        continue; // Skip this record and continue with next
                    }

                    // Check for duplicates within the same Excel file first
                    if (in_array($transactionId, $processedTransactionIds)) {
                        \Log::warning("Duplicate Transaction ID found in Excel file: {$transactionId} - Storing as failed import");
                        $this->storeFailedImport($row, $rowCount, 'Duplicate transaction ID in Excel file', "Transaction ID {$transactionId} appears multiple times in the same Excel file. Only the first occurrence will be imported.");
                        $duplicateCount++;
                        continue; // Skip this record and continue with next
                    }

                    // Add to processed list (before checking database)
                    $processedTransactionIds[] = $transactionId;

                    // Existing DB row: skip if paid; allow update if unpaid
                    $existingSalik = salik::where('transaction_id', $transactionId)->first();
                    if ($existingSalik && strtolower((string) $existingSalik->status) === 'paid') {
                        \Log::warning("Transaction ID {$transactionId} already exists in database and is paid - Skipping this record");
                        $this->storeFailedImport(
                            $row,
                            $rowCount,
                            'Salik already exists in database and is paid',
                            "Transaction ID {$transactionId} already exists in the database and is marked as paid"
                        );
                        $duplicateCount++;
                        continue;
                    }

                    $bike = Bikes::where('plate', $plateNumber)->first();
                    if (!$bike) {
                        \Log::warning("Bike not found for plate: {$plateNumber} in row {$rowCount} - Skipping this record");
                        $this->storeFailedImport($row, $rowCount, 'No bike found with this plate number', "Plate {$plateNumber} does not exist in the bikes table");
                        $noBikeCount++;
                        continue; // Skip this record and continue with next
                    }

                    $rider = $this->findRiderForTripDate($bike->id, $tripDateForQueries, $plateNumber);
                    if (!$rider) {
                        \Log::warning("No rider found for bike {$plateNumber} on date {$tripDate} in row {$rowCount} - No current rider and no history found - Skipping this record");
                        $this->storeFailedImport($row, $rowCount, 'No rider assigned for this trip date and no history found', "Bike {$plateNumber} has no rider assigned on {$tripDate} and no previous rider found in history");
                        $noRiderCount++;
                        continue; // Skip this record and continue with next
                    }

                    $riderAccountId = $this->getRiderAccountId($rider->id);
                    if (!$riderAccountId) {
                        \Log::warning("No account found for rider: {$rider->name} (Rider ID: {$rider->id}) - Skipping Transaction ID: {$transactionId}");
                        $this->storeFailedImport($row, $rowCount, 'No account found for rider', "Rider {$rider->name} has no associated account in the accounts table");
                        $skippedCount++;
                        continue; // Skip this record and continue with next
                    }

                    // Get payer account from the bike or rider
                    $payerAccount = $bike->rider_id ? $this->getRiderAccountId($bike->rider_id) : null;

                    $billingMonthCarbon    = $this->parseBillingMonth($billingMonthRaw ?: $tripDate);
                    $billingMonthForStore  = $billingMonthCarbon ? $billingMonthCarbon->toDateString() : null; // store as Y-m-d
                    $billingMonthForLog    = $billingMonthCarbon ? $billingMonthCarbon->format('d M Y') : null; // human readable

                    $salikData = [
                        'transaction_id'   => $transactionId,
                        'trip_date'        => $tripDateForStorage,
                        'trip_time'        => $tripTime,
                        'transaction_post_date' => $transactionPostDate,
                        'toll_gate'        => $tollGate,
                        'direction'        => $direction,
                        'tag_number'       => $tagNumber,
                        'plate'            => $plateNumber,
                        'bike_id'          => $bike->id,
                        'amount'           => $transactionAmount,
                        'salik_vat'        => $salikVatPercent,
                        'salik_vat_amount' => $salikVatAmount,
                        'rider_id'         => $rider->id,
                        'admin_charges'    => $adminCharge,
                        'admin_vat'        => $adminVatPercent,
                        'admin_vat_amount' => $adminVatAmount,
                        'vat'              => $totalVat,
                        'total_amount'     => $totalAmount,
                        'details'          => $details,
                        'status'           => 'unpaid',
                        'branch_id'        => $bike->branch_id,
                        'billing_month'    => $billingMonthForStore,
                        'trans_date'       => Carbon::today(),
                        'created_by'       => Auth::user()->id,
                    ];
                    // Determine if we're using current rider or last rider from history
                    $isCurrentRider = ($bike->rider_id == $rider->id);
                    $riderSource = $isCurrentRider ? 'current rider' : 'last rider from history';

                    \Log::info("Creating Salik record for Transaction ID: {$transactionId}, Amount: {$transactionAmount}, Rider: {$rider->name} (using {$riderSource})");

                    if ($existingSalik) {
                        // Update existing unpaid record with new data from Excel
                        $existingSalik->update($salikData);
                        $salik = $existingSalik;
                        \Log::info("Updated existing unpaid Salik record with ID: {$salik->id}");
                    } else {
                        // Create new record
                        $salik = salik::create($salikData);
                        \Log::info("Successfully created new Salik record with ID: {$salik->id}");
                    }

                    $importedSalikIds[] = $salik->id;

                    $this->affectedInvoiceGroups[$rider->id . '|' . $billingMonthForStore] = [
                        'rider_id' => $rider->id,
                        'billing_month' => $billingMonthForStore,
                        'rental_company_id' => null,
                    ];
                } catch (\Exception $e) {
                    \Log::error("Error processing row {$rowCount}: " . $e->getMessage());
                    \Log::error("Row data: " . json_encode($row->toArray()));
                    \Log::error("Stack trace: " . $e->getTraceAsString());
                    $this->storeFailedImport($row, $rowCount, 'Processing error', $e->getMessage());
                    $missingDataCount++;
                    continue; // Skip this record and continue with next
                }
            }

            $salikController = app(\App\Http\Controllers\SalikController::class);
            foreach ($this->affectedInvoiceGroups as $group) {
                $salikController->syncMonthlyInvoiceTransactions(
                    $group['rider_id'],
                    $group['billing_month'],
                    $group['rental_company_id']
                );
            }

            \Log::info("Import Summary - Total Rows: {$rowCount}, Imported: " . count($importedSalikIds) . ", Skipped - Missing Data: {$missingDataCount}, Duplicates (Excel only): {$duplicateCount}, No Bike: {$noBikeCount}, No Rider: {$noRiderCount}, No Account: {$skippedCount}");
            \Log::info("Unique Transaction IDs processed in this import: " . count($processedTransactionIds));

            DB::commit();
            \Log::info("Salik import completed successfully. Imported " . count($importedSalikIds) . " records.");
            return $importedSalikIds;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Salik import failed with error: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Parse any Excel date/time cell value into Carbon.
     * Handles serial numbers, DateTime objects, and common string formats.
     */
    private function parseExcelDate($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof Carbon) {
                return $value->copy();
            }

            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value);
            }

            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    return null;
                }
            }

            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            }

            if (is_string($value)) {
                foreach ($this->excelDateFormats() as $format) {
                    $parsed = $this->tryParseWithFormat($value, $format);
                    if ($parsed) {
                        return $parsed;
                    }
                }
            }

            return Carbon::parse($value);
        } catch (\Exception $e) {
            \Log::warning("Unable to parse Excel date value: {$value}. Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Date string formats commonly found in Salik exports and regional Excel files.
     */
    private function excelDateFormats(): array
    {
        return [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'd-m-Y',
            'm/d/Y H:i:s',
            'm/d/Y H:i',
            'm/d/Y',
            'd M Y H:i:s',
            'd M Y',
            'd M y',
            'j M Y',
            'j M y',
            'd-M-Y',
            'd-M-y',
            'M d, Y',
            'M d Y',
            'd/m/y',
            'm/d/y',
            'n/j/Y',
            'j/n/Y',
            'Y/m/d',
        ];
    }

    /**
     * Strictly parse a date string with a single format.
     */
    private function tryParseWithFormat(string $value, string $format): ?Carbon
    {
        $dateTime = \DateTime::createFromFormat('!' . $format, $value);
        if ($dateTime === false) {
            return null;
        }

        $errors = \DateTime::getLastErrors();
        if ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        return Carbon::instance($dateTime);
    }

    /**
     * Normalize trip date from various Excel formats to Carbon (start of day)
     */
    private function parseTripDate($tripDate)
    {
        $parsed = $this->parseExcelDate($tripDate);

        return $parsed ? $parsed->startOfDay() : null;
    }

    /**
     * Normalize billing month from Excel to start-of-month Carbon
     */
    private function parseBillingMonth($billingMonth)
    {
        $parsed = $this->parseExcelDate($billingMonth);

        return $parsed ? $parsed->startOfMonth() : null;
    }

    /**
     * Normalize trip time from various Excel formats to "h:i:s A" format (e.g., "11:38:28 PM")
     */
    private function parseTripTime($tripTime)
    {
        if ($tripTime === null || $tripTime === '') {
            return null;
        }

        try {
            if ($tripTime instanceof Carbon) {
                return $tripTime->format('h:i:s A');
            }

            if ($tripTime instanceof \DateTimeInterface) {
                return Carbon::instance($tripTime)->format('h:i:s A');
            }

            if (is_string($tripTime)) {
                $tripTime = trim($tripTime);
                if ($tripTime === '') {
                    return null;
                }
            }

            if (is_numeric($tripTime)) {
                $dateTime = ExcelDate::excelToDateTimeObject((float) $tripTime);
                return Carbon::instance($dateTime)->format('h:i:s A');
            }

            if (is_string($tripTime)) {
                foreach (['H:i:s', 'H:i', 'h:i:s A', 'h:i A', 'g:i A', 'g:i:s A'] as $format) {
                    $parsed = $this->tryParseWithFormat($tripTime, $format);
                    if ($parsed) {
                        return $parsed->format('h:i:s A');
                    }
                }
            }

            return Carbon::parse($tripTime)->format('h:i:s A');
        } catch (\Exception $e) {
            \Log::warning("Unable to parse trip time value: {$tripTime}. Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Normalize transaction post date to "d M Y" (e.g., "01 Oct 2025")
     */
    private function parseTransactionPostDate($transactionPostDate)
    {
        $parsed = $this->parseExcelDate($transactionPostDate);

        return $parsed ? $parsed->format('d M Y') : null;
    }

    private function findRiderForTripDate($bikeId, $tripDate, $plateNumber)
    {
        $bike = Bikes::find($bikeId);
        if (!$bike) return null;

        // 1. History check karo - rider who was assigned on or before trip date
        $history = BikeHistory::where('bike_id', $bikeId)
            ->whereDate('note_date', '<=', $tripDate)
            ->where(function ($q) use ($tripDate) {
                $q->whereNull('return_date')
                    ->orWhereDate('return_date', '>=', $tripDate);
            })
            ->orderBy('note_date', 'desc')
            ->first();

        if ($history && $history->rider_id) {
            return Riders::find($history->rider_id); // Rider return karega
        }

        // 2. Agar history mein rider nahi mila to bike ka current rider_id use karo
        if ($bike->rider_id) {
            return Riders::find($bike->rider_id);
        }

        // 3. NEW: If bike has no current rider, find the last rider from bike history
        $lastRiderHistory = BikeHistory::where('bike_id', $bikeId)
            ->whereNotNull('rider_id')
            ->orderBy('note_date', 'desc')
            ->orderBy('id', 'desc') // In case same note_date, get the latest entry
            ->first();

        if ($lastRiderHistory && $lastRiderHistory->rider_id) {
            \Log::info("No current rider for bike {$plateNumber}. Using last rider from history: Rider ID {$lastRiderHistory->rider_id} (History Date: {$lastRiderHistory->note_date})");
            return Riders::find($lastRiderHistory->rider_id);
        }

        // 4. Fallback: Plate number se rider find karo (if somehow different bike record exists)
        $bikeByPlate = Bikes::where('plate', $plateNumber)->first();
        return $bikeByPlate ? Riders::find($bikeByPlate->rider_id) : null;
    }


    private function getRiderAccountId($riderId)
    {
        $account = \App\Models\Accounts::where('ref_id', $riderId)->first();
        return $account ? $account->id : null;
    }

    /**
     * Store failed import record
     */
    private function storeFailedImport($row, $rowNumber, $reason, $details)
    {
        try {
            $transactionId = $row[0] ?? null;
            $tripDate = $this->parseTripDate($row[1] ?? null);
            $plateNumber = $row[7] ?? null;  // Updated to match new mapping
            $amount = $row[8] ?? null;       // Updated to match new mapping

            FailedSalikImport::create([
                'transaction_id' => $transactionId,
                'trip_date' => $tripDate ? $tripDate->format('Y-m-d') : null,
                'plate_number' => $plateNumber,
                'amount' => $amount,
                'reason' => $reason,
                'details' => $details,
                'row_number' => $rowNumber,
                'raw_data' => $row->toArray(),
                'import_batch_id' => $this->importBatchId
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to store failed import record: " . $e->getMessage());
        }
    }
}
