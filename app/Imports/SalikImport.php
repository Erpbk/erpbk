<?php

namespace App\Imports;

use App\Models\salik;
use App\Models\Bikes;
use App\Models\Riders;
use App\Models\BikeHistory;
use App\Models\BikeRentCompany;
use App\Models\FailedSalikImport;
use App\Support\ExcelSlashDateFormat;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use DB;
use Auth;
use Carbon\Carbon;

class SalikImport extends DefaultValueBinder implements ToCollection, WithCustomValueBinder
{
    protected const END_OF_DATA_THRESHOLD = 5;

    protected $adminChargePerSalik;
    protected $salikVatPercent;
    protected $adminVatPercent;
    protected $columnMap;
    protected $importBatchId;
    protected $affectedInvoiceGroups = [];
    /** @var string ExcelSlashDateFormat::ORDER_DMY|ORDER_MDY */
    protected $slashDateOrder = ExcelSlashDateFormat::ORDER_DMY;

    /** Summary counts available after import (for UI / controller). */
    public int $importedCount = 0;
    public int $updatedCount = 0;
    public int $missingDataCount = 0;
    public int $duplicateCount = 0;
    public int $noBikeCount = 0;
    public int $noRiderCount = 0;
    public int $noAccountCount = 0;
    public int $notSalikCount = 0;
    public int $rowCount = 0;
    public int $uniqueTransactionCount = 0;

    public function __construct($adminChargePerSalik = 0, array $columnMap = [], $salikVatPercent = 0, $adminVatPercent = 0)
    {
        $this->adminChargePerSalik = (float) $adminChargePerSalik;
        $this->salikVatPercent = (float) $salikVatPercent;
        $this->adminVatPercent = (float) $adminVatPercent;
        $this->columnMap = $columnMap ?: $this->defaultColumnMap();
        $this->importBatchId = 'batch_' . time() . '_' . Auth::id();
    }

    /**
     * Keep slash/dash date strings as text so we can parse them as d/m/Y (not US m/d/Y).
     * Excel serial numbers are still accepted via numeric path in parseExcelDate.
     */
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            // d/m/Y or d-m-Y (optional time, optional am/pm) — do not let PhpSpreadsheet auto-cast to DateTime
            if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}(?:\s+\d{1,2}:\d{2}(?::\d{2})?(?:\s*[AaPp][Mm])?)?$/', $trimmed)) {
                $cell->setValueExplicit($trimmed, DataType::TYPE_STRING);
                return true;
            }
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * Default 1-based columns matching the downloadable template.
     */
    private function defaultColumnMap(): array
    {
        return [
            'transaction_id' => 1,
            'trip_date' => 2,
            'trip_time' => 3,
            'transaction_post_date' => 4,
            'toll_gate' => 5,
            'direction' => 6,
            'tag_number' => 7,
            'plate' => 8,
            'amount' => 9,
            'billing_month' => 10,
            'admin_charges' => 12,
            'details' => 14,
        ];
    }

    /**
     * Read a cell by 1-based mapped column key.
     */
    private function cell($row, string $key)
    {
        $col = $this->columnMap[$key] ?? null;
        if ($col === null || $col === '') {
            return null;
        }

        $index = ((int) $col) - 1;
        return $row[$index] ?? null;
    }

    private function isMapped(string $key): bool
    {
        $col = $this->columnMap[$key] ?? null;
        return $col !== null && $col !== '';
    }

    private function isBlank($value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $this->slashDateOrder = $this->detectSheetSlashDateOrder($rows);

            $importedSalikIds = [];
            $rowCount = 0;
            $skippedCount = 0;
            $duplicateCount = 0;
            $missingDataCount = 0;
            $noBikeCount = 0;
            $noRiderCount = 0;
            $notSalikCount = 0;
            $updatedCount = 0;
            $processedTransactionIds = [];
            $consecutiveEmpty = 0;

            foreach ($rows->skip(1) as $rowIndex => $row) {
                $rowCount++;
                // Excel row 1 is the header; first data row is Excel row 2
                $excelRowNumber = is_numeric($rowIndex)
                    ? ((int) $rowIndex + 1)
                    : ($rowCount + 1);

                $transactionIdRaw = $this->cell($row, 'transaction_id');
                $transactionIdStr = $transactionIdRaw === null ? '' : trim((string) $transactionIdRaw);

                // Only truly empty Trans ID cells count toward end-of-data stop
                if ($transactionIdStr === '') {
                    $consecutiveEmpty++;
                    if ($consecutiveEmpty >= self::END_OF_DATA_THRESHOLD) {
                        break;
                    }
                    continue;
                }

                // Non-numeric Trans ID still has data — do not count as empty / do not stop import
                if (!preg_match('/^\d+$/', $transactionIdStr)) {
                    $consecutiveEmpty = 0;
                    $this->storeFailedImport(
                        $row,
                        $excelRowNumber,
                        'Not Salik Transaction',
                        'Transaction ID cell does not contain a pure number',
                        false
                    );
                    $notSalikCount++;
                    continue;
                }

                $consecutiveEmpty = 0;
                $transactionId = $transactionIdStr;

                // Only parse remaining columns after Trans ID is confirmed numeric
                try {
                    $tripDateRaw = $this->cell($row, 'trip_date');
                    $tripTimeRaw = $this->cell($row, 'trip_time');
                    $tripDate = $this->parseTripDate($tripDateRaw);
                    $tripDateForStorage = $tripDate ? $tripDate->format('d M Y') : null;
                    $tripDateForQueries = $tripDate ? $tripDate->toDateString() : null;
                    $tripTime = $this->parseTripTime($tripTimeRaw);

                    $postDateRaw = $this->isMapped('transaction_post_date')
                        ? $this->cell($row, 'transaction_post_date')
                        : null;
                    $transactionPostDate = !$this->isBlank($postDateRaw)
                        ? $this->parseTransactionPostDate($postDateRaw)
                        : ($tripDate ? $tripDate->format('d M Y') : null);

                    $tollGate = $this->cell($row, 'toll_gate');
                    $direction = $this->cell($row, 'direction');
                    $tagNumber = $this->cell($row, 'tag_number');
                    $plateRaw = $this->cell($row, 'plate');
                    $plateNumber = $this->extractPlateNumber($plateRaw);
                    $amountRaw = $this->cell($row, 'amount');
                    $transactionAmount = (float) ($amountRaw ?: 0);

                    $billingMonthRaw = $this->isMapped('billing_month')
                        ? $this->cell($row, 'billing_month')
                        : null;

                    if ($this->isMapped('admin_charges')) {
                        $adminCell = $this->cell($row, 'admin_charges');
                        // Non-empty Excel value wins (including 0); empty cell → global default
                        $adminCharge = !$this->isBlank($adminCell)
                            ? (float) $adminCell
                            : (float) $this->adminChargePerSalik;
                    } else {
                        $adminCharge = (float) $this->adminChargePerSalik;
                    }

                    $salikVatPercent = (float) $this->salikVatPercent;
                    $adminVatPercent = (float) $this->adminVatPercent;
                    $salikVatAmount = round($transactionAmount * $salikVatPercent / 100, 2);
                    $adminVatAmount = round($adminCharge * $adminVatPercent / 100, 2);

                    $detailsRaw = $this->isMapped('details') ? $this->cell($row, 'details') : null;
                    $details = !$this->isBlank($detailsRaw)
                        ? $detailsRaw
                        : ($tripDate ? 'Salik Charges - ' . $tripDate->format('M-Y') : 'Salik Charges');

                    $totalVat = $salikVatAmount + $adminVatAmount;
                    $totalAmount = $transactionAmount + $adminCharge + $totalVat;

                    if (empty($tripDateForStorage) || empty($plateNumber) || empty($transactionAmount)) {
                        $this->storeFailedImport(
                            $row,
                            $excelRowNumber,
                            'Missing required fields',
                            "Transaction ID: {$transactionId}, Trip Date: {$tripDateForStorage}, Plate: {$plateNumber}, Amount: {$transactionAmount}"
                        );
                        $missingDataCount++;
                        continue;
                    }

                    if (in_array($transactionId, $processedTransactionIds, true)) {
                        $this->storeFailedImport(
                            $row,
                            $excelRowNumber,
                            'Duplicate transaction ID in Excel file',
                            "Transaction ID {$transactionId} appears multiple times in the same Excel file. Only the first occurrence will be imported."
                        );
                        $duplicateCount++;
                        continue;
                    }

                    $processedTransactionIds[] = $transactionId;

                    $existingSalik = salik::where('transaction_id', $transactionId)->first();
                    if ($existingSalik && $existingSalik->isPaid()) {
                        $this->storeFailedImport(
                            $row,
                            $excelRowNumber,
                            'Salik already exists in database and is paid',
                            "Transaction ID {$transactionId} already exists in the database and is marked as paid"
                        );
                        $duplicateCount++;
                        continue;
                    }

                    $bike = Bikes::where('plate', $plateNumber)->first();
                    if (!$bike) {
                        $this->storeFailedImport(
                            $row,
                            $excelRowNumber,
                            'No bike found with this plate number',
                            "Plate {$plateNumber} does not exist in the bikes table (raw: {$plateRaw})"
                        );
                        $noBikeCount++;
                        continue;
                    }

                    $assignment = $this->findChargePartyForTripDate($bike->id, $tripDateForQueries);
                    if (!$assignment) {
                        $this->storeFailedImport(
                            $row,
                            $excelRowNumber,
                            'No user was assigned for this trip date',
                            "Bike {$plateNumber} has no user assigned on trip date {$tripDateForQueries}"
                        );
                        $noRiderCount++;
                        continue;
                    }

                    $riderId = $assignment['rider_id'];
                    $rentalCompanyId = $assignment['rental_company_id'];

                    if ($riderId) {
                        $riderAccountId = $this->getRiderAccountId($riderId);
                        if (!$riderAccountId) {
                            $rider = Riders::find($riderId);
                            $riderName = $rider ? $rider->name : (string) $riderId;
                            $this->storeFailedImport(
                                $row,
                                $excelRowNumber,
                                'No account found for rider',
                                "Rider {$riderName} has no account_id set on the rider record"
                            );
                            $skippedCount++;
                            continue;
                        }
                    } else {
                        $company = BikeRentCompany::find($rentalCompanyId);
                        if (!$company || empty($company->account_id)) {
                            $companyName = $company ? $company->name : (string) $rentalCompanyId;
                            $this->storeFailedImport(
                                $row,
                                $excelRowNumber,
                                'No account found for rental company',
                                "Rental company {$companyName} has no associated account"
                            );
                            $skippedCount++;
                            continue;
                        }
                    }

                    $billingMonthCarbon = $this->parseBillingMonth(
                        !$this->isBlank($billingMonthRaw) ? $billingMonthRaw : $tripDate
                    );
                    $billingMonthForStore = $billingMonthCarbon ? $billingMonthCarbon->toDateString() : null;

                    $salikData = [
                        'transaction_id' => $transactionId,
                        'trip_date' => $tripDateForStorage,
                        'trip_time' => $tripTime,
                        'transaction_post_date' => $transactionPostDate,
                        'toll_gate' => $tollGate,
                        'direction' => $direction,
                        'tag_number' => $tagNumber,
                        'plate' => $plateNumber,
                        'bike_id' => $bike->id,
                        'amount' => $transactionAmount,
                        'salik_vat' => $salikVatPercent,
                        'salik_vat_amount' => $salikVatAmount,
                        'rider_id' => $riderId,
                        'rental_company_id' => $rentalCompanyId,
                        'admin_charges' => $adminCharge,
                        'admin_vat' => $adminVatPercent,
                        'admin_vat_amount' => $adminVatAmount,
                        'vat' => $totalVat,
                        'total_amount' => $totalAmount,
                        'details' => $details,
                        'branch_id' => $bike->branch_id,
                        'billing_month' => $billingMonthForStore,
                        'trans_date' => Carbon::today(),
                    ];

                    if ($existingSalik) {
                        $oldRiderId = $existingSalik->rider_id;
                        $oldBillingMonth = $existingSalik->billing_month;
                        $oldRentalCompanyId = $existingSalik->rental_company_id;

                        $salikData['updated_by'] = Auth::user()->id;
                        $existingSalik->update($salikData);
                        $salik = $existingSalik;
                        $updatedCount++;

                        $this->queueAffectedInvoiceGroup($riderId, $billingMonthForStore, $rentalCompanyId);
                        if (
                            (int) ($oldRiderId ?? 0) !== (int) ($riderId ?? 0)
                            || (int) ($oldRentalCompanyId ?? 0) !== (int) ($rentalCompanyId ?? 0)
                            || salik::normalizeBillingMonth($oldBillingMonth) !== salik::normalizeBillingMonth($billingMonthForStore)
                        ) {
                            $this->queueAffectedInvoiceGroup($oldRiderId, $oldBillingMonth, $oldRentalCompanyId);
                        }
                    } else {
                        $salikData['status'] = 'unpaid';
                        $salikData['created_by'] = Auth::user()->id;
                        $salik = salik::create($salikData);
                        $this->queueAffectedInvoiceGroup($riderId, $billingMonthForStore, $rentalCompanyId);
                    }

                    $importedSalikIds[] = $salik->id;
                } catch (\Exception $e) {
                    $this->storeFailedImport($row, $excelRowNumber, 'Processing error', $e->getMessage());
                    $missingDataCount++;
                    continue;
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

            $this->rowCount = $rowCount;
            $this->importedCount = count($importedSalikIds);
            $this->updatedCount = $updatedCount;
            $this->missingDataCount = $missingDataCount;
            $this->duplicateCount = $duplicateCount;
            $this->noBikeCount = $noBikeCount;
            $this->noRiderCount = $noRiderCount;
            $this->noAccountCount = $skippedCount;
            $this->notSalikCount = $notSalikCount;
            $this->uniqueTransactionCount = count($processedTransactionIds);

            \Log::info("Import Summary - Total Rows: {$rowCount}, Imported: " . count($importedSalikIds) . ", Skipped - Missing Data: {$missingDataCount}, Duplicates: {$duplicateCount}, No Bike: {$noBikeCount}, No User Assigned: {$noRiderCount}, No Account: {$skippedCount}, Not Salik: {$notSalikCount}");
            \Log::info("Unique Transaction IDs processed in this import: " . count($processedTransactionIds));

            DB::commit();
            \Log::info("Salik import completed successfully. Imported " . count($importedSalikIds) . " records.");
            return $importedSalikIds;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Salik import failed with error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract plate number as the longest contiguous digit run from a cell.
     */
    private function extractPlateNumber($raw): ?string
    {
        if ($this->isBlank($raw)) {
            return null;
        }

        $value = trim((string) $raw);
        if (preg_match_all('/\d+/', $value, $matches) && !empty($matches[0])) {
            usort($matches[0], fn ($a, $b) => strlen($b) <=> strlen($a));
            return $matches[0][0];
        }

        return null;
    }

    /**
     * Scan mapped date columns on the sheet to detect d/m/Y vs m/d/Y.
     */
    private function detectSheetSlashDateOrder(Collection $rows): string
    {
        $dateKeys = ['trip_date', 'trip_time', 'transaction_post_date', 'billing_month'];
        $samples = [];

        foreach ($rows->skip(1) as $row) {
            foreach ($dateKeys as $key) {
                if (!$this->isMapped($key)) {
                    continue;
                }

                $value = $this->cell($row, $key);
                if (!$this->isBlank($value) && ExcelSlashDateFormat::extractDayMonthParts($value) !== null) {
                    $samples[] = $value;
                }
            }

            if (count($samples) >= 50) {
                break;
            }
        }

        return ExcelSlashDateFormat::detectOrder($samples);
    }

    /**
     * Parse any Excel date/time cell value into Carbon.
     * Slash/dash dates use sheet-detected d/m/Y or m/d/Y order.
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

            // Excel serial number (real date cell)
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            }

            if (!is_string($value)) {
                return null;
            }

            // Slash/dash numeric dates — use detected sheet order (dmy or mdy)
            if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $value)) {
                $parsed = ExcelSlashDateFormat::parse($value, $this->slashDateOrder);
                if ($parsed) {
                    return $parsed;
                }

                return null;
            }

            foreach ($this->excelDateFormats() as $format) {
                $parsed = $this->tryParseWithFormat($value, $format);
                if ($parsed) {
                    return $parsed;
                }
            }

            // Avoid Carbon::parse() on slash dates — it defaults to m/d/Y
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function excelDateFormats(): array
    {
        return [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd M Y H:i:s',
            'd M Y',
            'd M y',
            'j M Y',
            'j M y',
            'd-M-Y',
            'd-M-y',
            'M d, Y',
            'M d Y',
            'Y/m/d',
        ];
    }

    private function tryParseWithFormat(string $value, string $format): ?Carbon
    {
        $dateTime = \DateTime::createFromFormat('!' . $format, $value);
        if ($dateTime === false) {
            return null;
        }

        $errors = \DateTime::getLastErrors();
        if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        return Carbon::instance($dateTime);
    }

    private function parseTripDate($tripDate)
    {
        $parsed = $this->parseExcelDate($tripDate);

        return $parsed ? $parsed->startOfDay() : null;
    }

    private function parseBillingMonth($billingMonth)
    {
        $parsed = $this->parseExcelDate($billingMonth);

        return $parsed ? $parsed->startOfMonth() : null;
    }

    /**
     * Normalize trip time from various Excel formats to "h:i:s A".
     * Works on time-only cells and combined datetime cells.
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

                // Combined datetime string — extract time portion
                $parsedDate = $this->parseExcelDate($tripTime);
                if ($parsedDate) {
                    return $parsedDate->format('h:i:s A');
                }
            }

            return Carbon::parse($tripTime)->format('h:i:s A');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseTransactionPostDate($transactionPostDate)
    {
        $parsed = $this->parseExcelDate($transactionPostDate);

        return $parsed ? $parsed->format('d M Y') : null;
    }

    /**
     * Resolve charge party for trip date from bike history.
     * Prefer rider when present; otherwise rental company.
     *
     * @return array{rider_id: ?int, rental_company_id: ?int}|null
     */
    private function findChargePartyForTripDate($bikeId, $tripDate): ?array
    {
        $trip = Carbon::parse($tripDate)->startOfDay();

        $history = BikeHistory::where('bike_id', $bikeId)
            ->where(function ($q) {
                $q->whereNotNull('rider_id')
                    ->orWhereNotNull('rental_company_id');
            })
            ->whereDate('note_date', '<=', $trip)
            ->where(function ($q) use ($trip) {
                $q->whereNull('return_date')
                    ->orWhereDate('return_date', '>=', $trip);
            })
            ->orderBy('note_date', 'desc')
            ->first();

        if (!$history) {
            return null;
        }

        if ($history->rider_id) {
            return [
                'rider_id' => (int) $history->rider_id,
                'rental_company_id' => null,
            ];
        }

        if ($history->rental_company_id) {
            return [
                'rider_id' => null,
                'rental_company_id' => (int) $history->rental_company_id,
            ];
        }

        return null;
    }

    private function getRiderAccountId($riderId)
    {
        $rider = Riders::find($riderId);
        return ($rider && $rider->account_id) ? (int) $rider->account_id : null;
    }

    private function queueAffectedInvoiceGroup($riderId, $billingMonth, $rentalCompanyId = null): void
    {
        $normalizedMonth = salik::normalizeBillingMonth($billingMonth) ?: $billingMonth;
        $key = ($riderId ?: 'c' . ($rentalCompanyId ?? '0')) . '|' . $normalizedMonth;

        $this->affectedInvoiceGroups[$key] = [
            'rider_id' => $riderId ? (int) $riderId : null,
            'billing_month' => $normalizedMonth,
            'rental_company_id' => $rentalCompanyId ? (int) $rentalCompanyId : null,
        ];
    }

    /**
     * Store a failed import row.
     * When $parseSalikFields is false (non-transaction / header / junk), only store
     * the raw Trans ID text plus reason/details/raw_data — do not treat other cells as Salik fields.
     */
    private function storeFailedImport($row, $rowNumber, $reason, $details, bool $parseSalikFields = true)
    {
        try {
            $transactionId = $this->cell($row, 'transaction_id');
            $tripDate = null;
            $plateNumber = null;
            $amount = null;

            if ($parseSalikFields) {
                $parsedTripDate = $this->parseTripDate($this->cell($row, 'trip_date'));
                $tripDate = $parsedTripDate ? $parsedTripDate->format('Y-m-d') : null;
                $plateNumber = $this->extractPlateNumber($this->cell($row, 'plate'));
                $amount = $this->sanitizeFailedAmount($this->cell($row, 'amount'));
            }

            FailedSalikImport::create([
                'transaction_id' => $transactionId !== null ? trim((string) $transactionId) : null,
                'trip_date' => $tripDate,
                'plate_number' => $plateNumber,
                'amount' => $amount,
                'reason' => $reason,
                'details' => $details,
                'row_number' => $rowNumber,
                'raw_data' => $row->toArray(),
                'import_batch_id' => $this->importBatchId,
            ]);
        } catch (\Exception $e) {
            // Do not break the import loop if a failed-row log cannot be stored
        }
    }

    /**
     * Amount column for failed_salik_imports must be numeric or null.
     */
    private function sanitizeFailedAmount($value): ?float
    {
        if ($this->isBlank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $cleaned = preg_replace('/[^0-9.\-]/', '', $value);
            if ($cleaned !== '' && is_numeric($cleaned)) {
                return (float) $cleaned;
            }
        }

        return null;
    }
}
