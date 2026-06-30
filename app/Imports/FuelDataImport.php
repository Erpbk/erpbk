<?php

namespace App\Imports;

use App\Helpers\Account;
use App\Support\GlobalAccounts;
use App\Models\FuelData;
use App\Models\FuelCards;
use App\Models\Bikes;
use App\Models\Riders;
use App\Models\Transactions;
use App\Models\BikeHistory;
use App\Models\FuelCardHistory;
use App\Models\Vouchers;
use App\Services\TransactionService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use DB;
use Auth;
use Carbon\Carbon;

class FuelDataImport implements ToCollection
{
    protected $fuelAccountId;
    protected $vatAccountId;
    protected $serviceChargeAmount;
    protected $failedRows = [];
    protected $successCount = 0;
    protected $totalRows = 0;

    public function __construct()
    {
        $this->fuelAccountId = 1097; // Default fuel account ID, should be made dynamic
        $this->vatAccountId = 1023; // Default VAT account ID, should be made dynamic
        $this->failedRows = [];
        $this->serviceChargeAmount = 25;
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $importedFuelIds = [];
            $groupedData = []; // group by bike + rider + billing month
            $rowCount = 0;
            $processedTransactionIds = []; // Track transaction IDs processed in this import

            foreach ($rows->skip(1) as $rowIndex => $row) {
                $rowNumber = $rowIndex + 1; // +2 because skipping header and zero-based index
                $this->totalRows++;
                $rowCount++;

                if (empty($row[0])) {
                    $this->addFailedRow($rowNumber, $row, 'Empty row', 'Transaction number is empty');
                    continue;
                }

                try {
                    // --- Safe mapping (index based) ---
                    $transactionNo       = trim($row[0] ?? null);
                    $transactionDateRaw  = $row[1] ?? null;
                    $bikePlate           = trim($row[6] ?? null);
                    $cardNumber          = trim($row[12] ?? null);
                    $authCode            = $row[13] ?? null;
                    $site                = $row[15] ?? null;
                    $product             = $row[17] ?? null;
                    $qty                 = $row[18] ?? null;
                    $price               = $row[19] ?? null;
                    $subtotal            = $row[20] ?? null;
                    $vatAmount           = $row[21] ?? null;
                    $total               = $row[22] ?? null;

                    // Validate required fields
                    if (empty($transactionNo)) {
                        $this->addFailedRow($rowNumber, $row, 'Missing transaction number', 'Transaction number is required');
                        continue;
                    }

                    if (empty($transactionDateRaw)) {
                        $this->addFailedRow($rowNumber, $row, 'Missing transaction date', 'Transaction date is required');
                        continue;
                    }

                    if (empty($bikePlate)) {
                        $this->addFailedRow($rowNumber, $row, 'Missing bike plate', 'Bike plate number is required');
                        continue;
                    }

                    if (empty($cardNumber)) {
                        $this->addFailedRow($rowNumber, $row, 'Missing card number', 'Card number is required');
                        continue;
                    }

                    if (empty($qty) || empty($price)) {
                        $this->addFailedRow($rowNumber, $row, 'Missing quantity or price', 'Both quantity and price are required');
                        continue;
                    }

                    if (empty($vatAmount)) {
                        $this->addFailedRow($rowNumber, $row, 'Missing VAT amount', 'VAT amount is required');
                        continue;
                    }

                    if (empty($authCode) || empty($site) || empty($product)) {
                        $this->addFailedRow($rowNumber, $row, 'Missing auth code, site, or product Name', 'Auth code, site, and product Name are required');
                        continue;
                    }

                    // Parse dates
                    $transactionDate = $this->parseTransactionDate($transactionDateRaw);
                    if (!$transactionDate) {
                        $this->addFailedRow($rowNumber, $row, 'Invalid transaction date', "Could not parse date: {$transactionDateRaw}");
                        continue;
                    }
                    $transactionDateForStorage = $transactionDate->format('Y-m-d H:i:s');

                    $billingMonthCarbon = $this->parseBillingMonth($transactionDateForStorage);
                    if (!$billingMonthCarbon) {
                        $this->addFailedRow($rowNumber, $row, 'Invalid billing month', "Could not parse billing month from date {$transactionDateForStorage}");
                        continue;
                    }
                    $billingMonthForStore = $billingMonthCarbon->startOfMonth()->toDateString();
                    $billingMonthForLog = $billingMonthCarbon->format('F Y');

                    // Calculate values if not provided
                    $calculatedSubtotal = $subtotal ?? ($qty * $price);
                    $calculatedTotal = $total ?? ($calculatedSubtotal + ($vatAmount ?? 0));

                    // Check for duplicates within the same Excel file
                    if (in_array($transactionNo, $processedTransactionIds)) {
                        $this->addFailedRow($rowNumber, $row, 'Duplicate transaction number', "Transaction number {$transactionNo} appears multiple times in this file");
                        continue;
                    }

                    $processedTransactionIds[] = $transactionNo;

                    // Check if exists in database
                    $existsInDatabase = FuelData::where('trans_no', $transactionNo)->exists();
                    if ($existsInDatabase) {
                        $this->addFailedRow($rowNumber, $row, 'Duplicate transaction number', "Transaction number {$transactionNo} already exists in database");
                        continue;
                    }

                    // Find card
                    $card = FuelCards::where('card_number', $cardNumber)->first();
                    if (!$card) {
                        $this->addFailedRow($rowNumber, $row, 'Card not found', "No card found with number: {$cardNumber}");
                        continue;
                    }

                    // Find bike
                    $bikePlate = $this->extractBikePlate($bikePlate);

                    // Find rider for the transaction date
                    $rider = $card->findRiderForDate($transactionDate->format('Y-m-d'));
                    if (!$rider) {
                        $this->addFailedRow(
                            $rowNumber,
                            $row,
                            'No rider found',
                            "No rider assigned to card {$cardNumber} on date {$transactionDate->format('Y-m-d')}"
                        );
                        continue;
                    }

                    // Get rider account
                    $riderAccountId = $rider->account_id ?? null;
                    if (!$riderAccountId) {
                        $this->addFailedRow(
                            $rowNumber,
                            $row,
                            'No account found',
                            "No account found for rider: {$rider->name}"
                        );
                        continue;
                    }

                    $fuelData = [
                        'trans_no' => $transactionNo,
                        'trans_date' => $transactionDateForStorage,
                        'billing_month' => $billingMonthForStore,
                        'rider_id' => $rider->id,
                        'bike_no' => $bikePlate,
                        'card_no' => $cardNumber,
                        'auth_code' => $authCode,
                        'site' => $site,
                        'product' => $product,
                        'qty' => $qty,
                        'price' => $price,
                        'subtotal' => $calculatedSubtotal,
                        'vat_amount' => $vatAmount ?? 0,
                        'total' => $calculatedTotal,
                        'branch_id' => $rider->branch_id,
                    ];

                    $fuelRecord = FuelData::create($fuelData);
                    $this->successCount++;
                    $importedFuelIds[] = $fuelRecord->id;

                    // Group for creating  ledger transactions
                    $groupedData[] = [
                        'account_id' => $riderAccountId,
                        'branch_id' => $rider->branch_id,
                        'rider_name' => $rider->name,
                        'fuelData' => $fuelRecord,
                        'transCode' => Account::trans_code(),
                    ];
                } catch (\Exception $e) {
                    $this->addFailedRow($rowNumber, $row, 'Processing error', $e->getMessage());
                    continue;
                }
            }

            // Create ledger transactions
            if (!empty($groupedData)) {
                $this->recordTransactions($groupedData);
            }

            DB::commit();

            \Log::info("Fuel Data import completed. Success: {$this->successCount}, Failed: " . count($this->failedRows));

            return [
                'success_count' => $this->successCount,
                'failed_count' => count($this->failedRows),
                'failed_rows' => $this->failedRows,
                'total_rows' => $this->totalRows
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Fuel Data import failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Add failed row to collection
     */
    private function addFailedRow($rowNumber, $row, $reason, $details)
    {
        $this->failedRows[] = [
            'row_number' => $rowNumber,
            'transaction_no' => $row[0] ?? 'N/A',
            'bike_plate' => $row[6] ?? 'N/A',
            'card_number' => $row[12] ?? 'N/A',
            'reason' => $reason,
            'details' => $details,
        ];
    }

    /**
     * Get failed rows
     */
    public function getFailedRows()
    {
        return $this->failedRows;
    }

    /**
     * Get success count
     */
    public function getSuccessCount()
    {
        return $this->successCount;
    }

    /**
     * Get total rows
     */
    public function getTotalRows()
    {
        return $this->totalRows;
    }

    /**
     * Parse transaction date from Excel
     */
    private function parseTransactionDate($dateValue)
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            if (is_numeric($dateValue)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($dateValue));
            }
            return Carbon::parse($dateValue);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse billing month from Excel
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
            return null;
        }
    }

    // extract bike plate number from full string (if needed)
    private function extractBikePlate($plate)
    {
        if (empty($plate)) {
            return null;
        }

        // Split by hyphen and get the last part
        $parts = explode('-', $plate);
        return end($parts);
    }

    /**
     * Create summary voucher for fuel transactions
     */
    private function recordTransactions($groups)
    {
        \Log::info("Recording transactions for " . count($groups) . " groups of fuel data");
        $transactionService = new TransactionService();

        foreach ($groups as $group) {
            $riderAccountId = $group['account_id'];
            $branchId = $group['branch_id'];
            $fuelData = $group['fuelData'];
            $transCode = $group['transCode'];
            $riderName = $group['rider_name'];

            $serviceCharges = $fuelData->service_charges;

            if ($serviceCharges > 0) {
                // Record service charge transaction if not already recorded for this billing month
                $transactionService->recordTransaction([
                    'account_id' => $riderAccountId,
                    'branch_id' => $branchId,
                    'reference_id' => $fuelData->id,
                    'reference_type' => 'fuel',
                    'trans_code' => $transCode,
                    'trans_date' => $fuelData->trans_date->format('Y-m-d'),
                    'narration' => "Monthly service charges for fuel transactions",
                    'debit' => $this->serviceChargeAmount,
                    'credit' => 0,
                    'billing_month' => $fuelData->billing_month,
                ]);
                $transactionService->recordTransaction([
                    'account_id' => GlobalAccounts::id('FUEL_ADMIN_CHARGES'),
                    'branch_id' => $branchId,
                    'reference_id' => $fuelData->id,
                    'reference_type' => 'fuel',
                    'trans_code' => $transCode,
                    'trans_date' => $fuelData->trans_date->format('Y-m-d'),
                    'narration' => 'Monthly service charges for fuel transactions',
                    'debit' => 0,
                    'credit' => $this->serviceChargeAmount,
                    'billing_month' => $fuelData->billing_month,
                ]);
            }

            // Debit rider account
            $transactionService->recordTransaction([
                'account_id' => $riderAccountId,
                'branch_id' => $branchId,
                'reference_id' => $fuelData->id,
                'reference_type' => 'fuel',
                'trans_code' => $transCode,
                'trans_date' => $fuelData->trans_date->format('Y-m-d'),
                'narration' => "Fuel purchased by Rider ID: {$riderName}",
                'debit' => $fuelData->total,
                'credit' => 0,
                'billing_month' => $fuelData->billing_month,
            ]);

            // Credit fuel account
            $transactionService->recordTransaction([
                'account_id' => $fuelData->card->fuelCompany->account_id,
                'branch_id' => $branchId,
                'reference_id' => $fuelData->id,
                'reference_type' => 'fuel',
                'trans_code' => $transCode,
                'trans_date' => $fuelData->trans_date->format('Y-m-d'),
                'narration' => "Fuel purchased by Rider ID: {$fuelData->rider_id}",
                'debit' => 0,
                'credit' => $fuelData->total,
                'billing_month' => $fuelData->billing_month,
            ]);

            if ($fuelData->vat_amount > 0) {
                // Credit fuel account
                $transactionService->recordTransaction([
                    'account_id' => GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'),
                    'branch_id' => $branchId,
                    'reference_id' => $fuelData->id,
                    'reference_type' => 'fuel',
                    'trans_code' => $transCode,
                    'trans_date' => $fuelData->trans_date->format('Y-m-d'),
                    'narration' => "Fuel purchased by Rider ID: {$fuelData->rider_id}",
                    'debit' => $fuelData->vat_amount,
                    'credit' => 0,
                    'billing_month' => $fuelData->billing_month,
                ]);
            }
        }
    }
}
