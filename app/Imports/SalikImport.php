<?php

namespace App\Imports;

use App\Helpers\Account;
use App\Models\Salik;
use App\Models\Bikes;
use App\Models\Riders;
use App\Models\BikeHistory;
use App\Models\Vouchers;
use App\Models\FailedSalikImport;
use App\Services\TransactionService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use DB;
use Auth;
use Carbon\Carbon;

class SalikImport implements ToCollection
{
    protected $salikAccountId;
    protected $adminChargePerSalik;
    protected $importBatchId;

    public function __construct($salikAccountId, $adminChargePerSalik = 0)
    {
        $this->salikAccountId = $salikAccountId;
        $this->adminChargePerSalik = $adminChargePerSalik;
        $this->importBatchId = 'batch_' . time() . '_' . Auth::id();
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $importedSalikIds = [];
            $groupedData = []; // group by bike + rider
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
                    $salik_account_id   = $row[10] ?? null;
                    $adminCharge         = $row[11] ?? null;
                    $details             = $row[12] ?? null;
                    $debit               = $row[13] ?? null;
                    // Use amount field for the actual transaction amount
                    $transactionAmount = $amount ?: $debit;


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

                    // Check for duplicates in database (but still import if it's the first occurrence in Excel)
                    $existsInDatabase = Salik::where('transaction_id', $transactionId)->exists();
                    if ($existsInDatabase) {
                        \Log::info("Transaction ID {$transactionId} already exists in database, but importing first occurrence from Excel file");
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
                        'rider_id'         => $rider->id,
                        'payer_account'    => $payerAccount,
                        'salik_account_id' => $salik_account_id,
                        'admin_charges'    => $this->adminChargePerSalik,
                        'total_amount'     => $transactionAmount + $this->adminChargePerSalik,
                        'status'           => 'paid',
                        'billing_month'    =>  $billingMonthForStore,
                        'trans_date'       => Carbon::today(),
                        'trans_code'       => Account::trans_code(),
                        'created_by'       => Auth::user()->id,
                    ];
                    // Determine if we're using current rider or last rider from history
                    $isCurrentRider = ($bike->rider_id == $rider->id);
                    $riderSource = $isCurrentRider ? 'current rider' : 'last rider from history';

                    \Log::info("Creating Salik record for Transaction ID: {$transactionId}, Amount: {$transactionAmount}, Rider: {$rider->name} (using {$riderSource})");

                    if ($existsInDatabase) {
                        // Update existing record with new data from Excel
                        $existingSalik = Salik::where('transaction_id', $transactionId)->first();
                        $existingSalik->update($salikData);
                        $salik = $existingSalik;
                        \Log::info("Updated existing Salik record with ID: {$salik->id}");
                    } else {
                        // Create new record
                        $salik = Salik::create($salikData);
                        \Log::info("Successfully created new Salik record with ID: {$salik->id}");
                    }

                    $importedSalikIds[] = $salik->id;

                    // Create individual transactions for each Salik record
                    $this->createIndividualTransactions($salik, $rider, $riderAccountId, $payerAccount, $transactionAmount, $tripDateForStorage);

                    // Still group for summary tracking
                    $groupKey = $bike->id . '-' . $rider->id;
                    if (!isset($groupedData[$groupKey])) {
                        $groupedData[$groupKey] = [
                            'bike'              => $bike,
                            'rider'             => $rider,
                            'rider_account_id'  => $riderAccountId,
                            'payer_account'     => $payerAccount,
                            'saliks'            => [],
                            'total_amount'      => 0,
                            'total_admin_charges' => 0,
                            'count'             => 0,
                            'billing_month'     => $billingMonthForStore,
                            'billing_month_display' => $billingMonthForLog
                        ];
                    }

                    $groupedData[$groupKey]['saliks'][] = $salik;
                    $groupedData[$groupKey]['payer_account'] = $payerAccount;
                    $groupedData[$groupKey]['total_amount'] += $transactionAmount;
                    $groupedData[$groupKey]['total_admin_charges'] += $this->adminChargePerSalik;
                    $groupedData[$groupKey]['count']++;
                } catch (\Exception $e) {
                    \Log::error("Error processing row {$rowCount}: " . $e->getMessage());
                    \Log::error("Row data: " . json_encode($row->toArray()));
                    \Log::error("Stack trace: " . $e->getTraceAsString());
                    $this->storeFailedImport($row, $rowCount, 'Processing error', $e->getMessage());
                    $missingDataCount++;
                    continue; // Skip this record and continue with next
                }
            }

            // Create summary voucher for rider debit and admin charges
            if (!empty($groupedData)) {
                $this->createSummaryVoucherForRider($groupedData);
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
     * Normalize trip date from various Excel formats to Carbon (start of day)
     */
    private function parseTripDate($tripDate)
    {
        if (empty($tripDate)) {
            return null;
        }

        try {
            if (is_numeric($tripDate)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($tripDate))->startOfDay();
            }

            return Carbon::parse($tripDate)->startOfDay();
        } catch (\Exception $e) {
            \Log::warning("Unable to parse trip date value: {$tripDate}. Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Normalize billing month from Excel to start-of-month Carbon
     */
    private function parseBillingMonth($billingMonth)
    {
        if (empty($billingMonth)) {
            return null;
        }

        try {
            if ($billingMonth instanceof Carbon) {
                return $billingMonth->copy()->startOfMonth();
            }

            if (is_numeric($billingMonth)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($billingMonth))->startOfMonth();
            }

            return Carbon::parse($billingMonth)->startOfMonth();
        } catch (\Exception $e) {
            \Log::warning("Unable to parse billing month value: {$billingMonth}. Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Normalize trip time from various Excel formats to "h:i:s A" format (e.g., "11:38:28 PM")
     */
    private function parseTripTime($tripTime)
    {
        if (empty($tripTime)) {
            return null;
        }

        try {
            // If it's a numeric value (Excel time format as decimal)
            if (is_numeric($tripTime)) {
                $dateTime = ExcelDate::excelToDateTimeObject($tripTime);
                return Carbon::instance($dateTime)->format('h:i:s A');
            }

            // If it's already a Carbon instance
            if ($tripTime instanceof Carbon) {
                return $tripTime->format('h:i:s A');
            }

            // Try to parse as time string
            $parsed = Carbon::parse($tripTime);
            return $parsed->format('h:i:s A');
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
        if (empty($transactionPostDate)) {
            return null;
        }

        try {
            if ($transactionPostDate instanceof Carbon) {
                $date = $transactionPostDate;
            } elseif (is_numeric($transactionPostDate)) {
                $date = Carbon::instance(ExcelDate::excelToDateTimeObject($transactionPostDate));
            } else {
                $date = Carbon::parse($transactionPostDate);
            }

            return $date->format('d M Y');
        } catch (\Exception $e) {
            \Log::warning("Unable to parse transaction post date value: {$transactionPostDate}. Error: " . $e->getMessage());
            return null;
        }
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

    private function createIndividualTransactions($salik, $rider, $riderAccountId, $payerAccount, $amount, $tripDate)
    {
        // Store individual transaction details to be used by summary voucher
        if (!isset($this->individualTransactions)) {
            $this->individualTransactions = [];
        }

        $this->individualTransactions[] = [
            'salik' => $salik,
            'rider' => $rider,
            'rider_account_id' => $riderAccountId,
            'payer_account' => $payerAccount,
            'amount' => $amount,
            'trip_date' => $tripDate
        ];
    }

    private function createSummaryVoucherForRider($groups)
    {
        $transactionService = new TransactionService();
        $adminAccountId = 1003;

        foreach ($groups as $group) {
            $rider          = $group['rider'];
            $riderAccountId = $group['rider_account_id'];
            $totalAmount    = $group['total_amount'];
            $totalAdmin     = $group['total_admin_charges'];
            $count          = $group['count'];
            $firstSalik     = $group['saliks'][0];
            $billingMonth   = $group['billing_month'];
            $billingMonthDisplay = $group['billing_month_display'] ?? $billingMonth;

            $transCode = Account::trans_code();
            $transDate = now();

            // 1. Debit Rider for total amount + admin charges
            $transactionService->recordTransaction([
                'account_id'     => $riderAccountId,
                'reference_id'   => $firstSalik->id,
                'reference_type' => 'Salik Voucher',
                'trans_code'     => $transCode,
                'trans_date'     => $transDate,
                'narration'      => "salik charges month of $billingMonthDisplay ($count transactions)",
                'debit'          => $totalAmount + $totalAdmin,
                'billing_month'  => $billingMonth,
            ]);

            // 2. Credit Salik Account for EACH individual transaction (48 separate entries)
            foreach ($group['saliks'] as $salik) {
                $transactionService->recordTransaction([
                    'account_id'     => $this->salikAccountId,
                    'reference_id'   => $salik->id,
                    'reference_type' => 'Salik Voucher',
                    'trans_code'     => $transCode,
                    'trans_date'     => $transDate,
                    'narration'      => "salik charges month of $billingMonthDisplay ($count transactions) - Reference Number: {$salik->transaction_id}",
                    'credit'         => $salik->amount,
                    'billing_month'  => $billingMonth,
                ]);
            }

            // 3. Credit Admin Charges (summary)
            if ($totalAdmin > 0) {
                $transactionService->recordTransaction([
                    'account_id'     => $adminAccountId,
                    'reference_id'   => $firstSalik->id,
                    'reference_type' => 'Salik Voucher',
                    'trans_code'     => $transCode,
                    'trans_date'     => $transDate,
                    'narration'      => "salik charges month of $billingMonthDisplay ($count × {$this->adminChargePerSalik}) - Reference Number: {$firstSalik->transaction_id}",
                    'credit'         => $totalAdmin,
                    'billing_month'  => $billingMonth,
                ]);
            }

            // Create main voucher record
            Vouchers::create([
                'trans_date'    => $transDate,
                'trans_code'    => $transCode,
                'payment_type'  => 1,
                'billing_month' => $billingMonth,
                'amount'        => $totalAmount + $totalAdmin,
                'voucher_type'  => 'SV',
                'reference_number' => $firstSalik->transaction_id,
                'remarks'       => "salik charges month of $billingMonthDisplay - Reference Number: {$firstSalik->transaction_id}",
                'ref_id'        => $firstSalik->id,
                'rider_id'      => $rider->id,
                'payment_to'    => $this->salikAccountId,
                'payment_from'  => $riderAccountId,
                'Created_By'    => Auth::user()->id,
                'custom_field_values' => [],
            ]);
        }
    }

    /**
     * Store failed import record
     */
    private function storeFailedImport($row, $rowNumber, $reason, $details)
    {
        try {
            $transactionId = $row[0] ?? null;
            $tripDate = $row[2] ?? null;
            $plateNumber = $row[7] ?? null;  // Updated to match new mapping
            $amount = $row[8] ?? null;       // Updated to match new mapping

            FailedSalikImport::create([
                'transaction_id' => $transactionId,
                'trip_date' => $tripDate ? Carbon::parse($tripDate)->format('Y-m-d') : null,
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
