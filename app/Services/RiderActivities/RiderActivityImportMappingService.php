<?php

namespace App\Services\RiderActivities;

use App\Models\RiderActivityImportSetting;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class RiderActivityImportMappingService
{
    public const DEFAULT_CUSTOMER_ID = 1;

    public const TYPE_RIDER = 'rider';

    public const TYPE_LIVE = 'live';

    /**
     * @return list<string>
     */
    public static function importTypes(): array
    {
        return [self::TYPE_RIDER, self::TYPE_LIVE];
    }

    /**
     * @return array<string, string>
     */
    public static function importTypeLabels(): array
    {
        return [
            self::TYPE_RIDER => 'Rider Activities',
            self::TYPE_LIVE => 'Live Activities',
        ];
    }

    public static function normalizeImportType(?string $importType): string
    {
        $importType = strtolower(trim((string) $importType));

        return in_array($importType, self::importTypes(), true)
            ? $importType
            : self::TYPE_RIDER;
    }

    /**
     * @return array<string, int>
     */
    public static function defaultColumnMappings(string $importType = self::TYPE_RIDER): array
    {
        $mappings = [
            'date' => 0,
            'rider_id' => 1,
            'payout_type' => 5,
            'delivery_rating' => 8,
            'login_hr' => 10,
            'delivered_orders' => 14,
            'cancelled_orders' => 16,
            'rejected_orders' => 17,
            'ontime_orders_percentage' => 22,
        ];

        if (self::normalizeImportType($importType) === self::TYPE_LIVE) {
            $mappings['login_hr'] = 11;
        }

        return $mappings;
    }

    public static function defaultHeaderRowsToSkip(): int
    {
        return 2;
    }

    /**
     * @return array<string, string>
     */
    public static function fieldLabels(): array
    {
        return [
            'date' => 'Date',
            'rider_id' => 'Rider ID',
            'payout_type' => 'Payout Type',
            'delivery_rating' => 'Delivery Rating / Valid Day',
            'login_hr' => 'Login Hours',
            'delivered_orders' => 'Delivered Orders',
            'cancelled_orders' => 'Cancelled Orders',
            'rejected_orders' => 'Rejected Orders',
            'ontime_orders_percentage' => 'On-Time Orders %',
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function requiredFields(): array
    {
        return [
            'date' => true,
            'rider_id' => true,
            'payout_type' => false,
            'delivery_rating' => false,
            'login_hr' => false,
            'delivered_orders' => false,
            'cancelled_orders' => false,
            'rejected_orders' => false,
            'ontime_orders_percentage' => false,
        ];
    }

    public static function columnIndexToLetter(int $index): string
    {
        $index = max(0, $index);
        $letter = '';
        $n = $index + 1;

        while ($n > 0) {
            $n--;
            $letter = chr(65 + ($n % 26)) . $letter;
            $n = intdiv($n, 26);
        }

        return $letter;
    }

    public static function columnLetterToIndex(string $letter): int
    {
        $letter = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $letter));
        if ($letter === '') {
            return 0;
        }

        $index = 0;
        $length = strlen($letter);
        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($letter[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    /**
     * @return array<int, string>
     */
    public static function excelColumnChoices(int $maxIndex = 25): array
    {
        $maxIndex = max(25, $maxIndex);
        $choices = [];

        for ($i = 0; $i <= $maxIndex; $i++) {
            $choices[$i] = self::columnIndexToLetter($i);
        }

        return $choices;
    }

    /**
     * @return array{
     *   customer_id: int,
     *   import_type: string,
     *   header_rows_to_skip: int,
     *   column_mappings: array<string, int>,
     *   ordered_field_keys: list<string>,
     *   required_fields: array<string, bool>
     * }
     */
    public function resolve(int $customerId, string $importType = self::TYPE_RIDER): array
    {
        $importType = self::normalizeImportType($importType);
        $defaultMappings = self::defaultColumnMappings($importType);

        $defaults = [
            'customer_id' => $customerId,
            'import_type' => $importType,
            'header_rows_to_skip' => self::defaultHeaderRowsToSkip(),
            'column_mappings' => $defaultMappings,
            'ordered_field_keys' => array_keys($defaultMappings),
            'required_fields' => $this->resolveRequiredFields(null, $defaultMappings),
        ];

        if ($customerId === self::DEFAULT_CUSTOMER_ID) {
            $stored = $this->findStoredSetting($customerId, $importType);

            if ($stored) {
                return $this->mergeWithDefaults($stored, $customerId, $importType);
            }

            return $defaults;
        }

        $stored = $this->findStoredSetting($customerId, $importType);

        if (!$stored) {
            return $defaults;
        }

        return $this->mergeWithDefaults($stored, $customerId, $importType);
    }

    public function isImportReady(int $customerId, string $importType = self::TYPE_RIDER): bool
    {
        $importType = self::normalizeImportType($importType);

        if ($customerId === self::DEFAULT_CUSTOMER_ID) {
            return true;
        }

        return RiderActivityImportSetting::query()
            ->where('customer_id', $customerId)
            ->where('import_type', $importType)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * @return list<int>
     */
    public function getConfiguredCustomerIds(string $importType = self::TYPE_RIDER): array
    {
        $importType = self::normalizeImportType($importType);

        $configured = RiderActivityImportSetting::query()
            ->where('import_type', $importType)
            ->where('is_active', true)
            ->pluck('customer_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!in_array(self::DEFAULT_CUSTOMER_ID, $configured, true)) {
            $configured[] = self::DEFAULT_CUSTOMER_ID;
        }

        sort($configured);

        return $configured;
    }

    public const ORDER_META_KEY = '__order';

    /**
     * @return list<string>
     */
    public static function alwaysRequiredFieldKeys(): array
    {
        return ['date', 'rider_id'];
    }

    /**
     * Sanitize submitted mappings.
     *
     * Preserves submission/DOM order via an embedded __order meta key so MySQL
     * JSON key reordering cannot lose the project's field sort.
     * Does NOT re-add deleted optional fields unless $fillMissingDefaults is true.
     *
     * @param  array<string, mixed>  $inputMappings
     * @param  list<string>|null  $preferredOrder
     * @return array<string, int|list<string>>
     */
    public function sanitizeColumnMappings(
        array $inputMappings,
        string $importType = self::TYPE_RIDER,
        bool $fillMissingDefaults = false,
        ?array $preferredOrder = null
    ): array {
        $importType = self::normalizeImportType($importType);
        $defaults = self::defaultColumnMappings($importType);
        $allowed = array_keys($defaults);
        $sanitized = [];

        $embeddedOrder = [];
        if (isset($inputMappings[self::ORDER_META_KEY]) && is_array($inputMappings[self::ORDER_META_KEY])) {
            $embeddedOrder = $inputMappings[self::ORDER_META_KEY];
            unset($inputMappings[self::ORDER_META_KEY]);
        }

        foreach ($inputMappings as $field => $value) {
            $field = (string) $field;
            if ($field === self::ORDER_META_KEY || ! in_array($field, $allowed, true)) {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            $sanitized[$field] = max(0, (int) $value);
        }

        foreach (self::alwaysRequiredFieldKeys() as $required) {
            if (! array_key_exists($required, $sanitized)) {
                $sanitized[$required] = (int) $defaults[$required];
            }
        }

        if ($fillMissingDefaults) {
            foreach ($defaults as $field => $defaultIndex) {
                if (! array_key_exists($field, $sanitized)) {
                    $sanitized[$field] = (int) $defaultIndex;
                }
            }
        }

        $orderSource = $preferredOrder ?? $embeddedOrder;
        if (! is_array($orderSource) || $orderSource === []) {
            $orderSource = array_keys($sanitized);
        }

        $orderedKeys = [];
        foreach ($orderSource as $field) {
            $field = (string) $field;
            if (array_key_exists($field, $sanitized) && ! in_array($field, $orderedKeys, true)) {
                $orderedKeys[] = $field;
            }
        }
        foreach (array_keys($sanitized) as $field) {
            if (! in_array($field, $orderedKeys, true)) {
                $orderedKeys[] = $field;
            }
        }

        // Rebuild in order, then attach meta for durable sort persistence.
        $ordered = [];
        foreach ($orderedKeys as $field) {
            $ordered[$field] = $sanitized[$field];
        }
        $ordered[self::ORDER_META_KEY] = $orderedKeys;

        return $ordered;
    }

    /**
     * @param  array<string, mixed>  $mappings
     * @return array{column_mappings: array<string, int>, ordered_field_keys: list<string>}
     */
    public function normalizeStoredMappings(array $mappings, string $importType = self::TYPE_RIDER): array
    {
        $payload = $this->sanitizeColumnMappings($mappings, $importType, false);
        $order = [];
        if (isset($payload[self::ORDER_META_KEY]) && is_array($payload[self::ORDER_META_KEY])) {
            $order = array_values(array_map('strval', $payload[self::ORDER_META_KEY]));
            unset($payload[self::ORDER_META_KEY]);
        }

        $labels = self::fieldLabels();
        $columnMappings = [];
        foreach ($payload as $field => $index) {
            if (! is_string($field) || ! array_key_exists($field, $labels)) {
                continue;
            }
            $columnMappings[$field] = max(0, (int) $index);
        }

        if ($order === []) {
            $order = array_keys($columnMappings);
        } else {
            $order = array_values(array_filter(
                $order,
                static fn ($key) => array_key_exists($key, $columnMappings)
            ));
            foreach (array_keys($columnMappings) as $field) {
                if (! in_array($field, $order, true)) {
                    $order[] = $field;
                }
            }
        }

        $orderedMappings = [];
        foreach ($order as $field) {
            $orderedMappings[$field] = $columnMappings[$field];
        }

        return [
            'column_mappings' => $orderedMappings,
            'ordered_field_keys' => $order,
        ];
    }

    /**
     * @return array<int, array{header_rows_to_skip: int, column_mappings: array<string, int>}>
     */
    public function previewConfigsForType(string $importType = self::TYPE_RIDER): array
    {
        $importType = self::normalizeImportType($importType);
        $payload = [];

        foreach ($this->getConfiguredCustomerIds($importType) as $customerId) {
            $resolved = $this->resolve((int) $customerId, $importType);
            $payload[(int) $customerId] = [
                'header_rows_to_skip' => $resolved['header_rows_to_skip'],
                'column_mappings' => $resolved['column_mappings'],
            ];
        }

        return $payload;
    }

    /**
     * @return array{file_name: string, sheet_name: string, rows: list<list<string>>, column_count: int, row_count: int, preview_row_count: int}
     */
    public function previewUploadedFile(UploadedFile $file, int $maxRows = 40, int $maxCols = 40): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            throw new \InvalidArgumentException('Please upload a valid .xlsx, .xls, or .csv file.');
        }

        $readerType = match ($extension) {
            'csv' => 'Csv',
            'xls' => 'Xls',
            default => 'Xlsx',
        };

        $reader = IOFactory::createReader($readerType);
        // Keep number formats so date cells can be detected; preview only reads a small window.
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(false);
        }

        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $sheetName = $sheet->getTitle();
        $highestRow = (int) $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn() ?: 'A';
        $highestColIndex = max(1, (int) Coordinate::columnIndexFromString($highestColumn));
        $previewRows = min($highestRow, $maxRows);
        $previewCols = min($highestColIndex, $maxCols);

        $rows = [];
        for ($row = 1; $row <= $previewRows; $row++) {
            $cells = [];
            for ($col = 1; $col <= $previewCols; $col++) {
                $coordinate = Coordinate::stringFromColumnIndex($col) . $row;
                $cells[] = $this->formatPreviewCell($sheet->getCell($coordinate));
            }
            $rows[] = $cells;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return [
            'file_name' => $file->getClientOriginalName(),
            'sheet_name' => $sheetName,
            'rows' => $rows,
            'column_count' => $previewCols,
            'row_count' => $highestRow,
            'preview_row_count' => count($rows),
        ];
    }

    /**
     * Format a spreadsheet cell for the import file preview.
     * Converts Excel date serials to Y-m-d and rounds fractional numbers to 2 decimals.
     */
    private function formatPreviewCell(Cell $cell): string
    {
        $value = $cell->getValue();

        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            $numeric = (float) $value;

            if (ExcelDate::isDateTime($cell) || $this->looksLikeExcelDateSerial($numeric)) {
                try {
                    return ExcelDate::excelToDateTimeObject($numeric)->format('Y-m-d');
                } catch (\Throwable $e) {
                    // Fall through to numeric / formatted handling.
                }
            }

            // Login hours, on-time %, etc. — show 2 decimal places instead of float noise.
            if (abs($numeric - round($numeric)) > 0.0000001) {
                return number_format($numeric, 2, '.', '');
            }

            return (string) (int) round($numeric);
        }

        $formatted = $cell->getFormattedValue();

        return is_scalar($formatted) || $formatted === null ? (string) $formatted : '';
    }

    /**
     * Detect Excel day-serial values that lack a date number format (common in exports).
     * Range covers calendar years ~2000–2100 so order counts / hours are not treated as dates.
     */
    private function looksLikeExcelDateSerial(float $value): bool
    {
        // 2000-01-01 ≈ 36526, 2100-12-31 ≈ 73415
        if ($value < 36526 || $value > 73415) {
            return false;
        }

        try {
            $year = (int) ExcelDate::excelToDateTimeObject($value)->format('Y');

            return $year >= 2000 && $year <= 2100;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function findStoredSetting(int $customerId, string $importType): ?RiderActivityImportSetting
    {
        return RiderActivityImportSetting::query()
            ->where('customer_id', $customerId)
            ->where('import_type', $importType)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return array{
     *   customer_id: int,
     *   import_type: string,
     *   header_rows_to_skip: int,
     *   column_mappings: array<string, int>,
     *   ordered_field_keys: list<string>,
     *   required_fields: array<string, bool>
     * }
     */
    private function mergeWithDefaults(RiderActivityImportSetting $stored, int $customerId, string $importType): array
    {
        $normalized = $this->normalizeStoredMappings(
            is_array($stored->column_mappings) ? $stored->column_mappings : [],
            $importType
        );

        return [
            'customer_id' => $customerId,
            'import_type' => $importType,
            'header_rows_to_skip' => max(0, (int) ($stored->header_rows_to_skip ?? self::defaultHeaderRowsToSkip())),
            'column_mappings' => $normalized['column_mappings'],
            'ordered_field_keys' => $normalized['ordered_field_keys'],
            'required_fields' => $this->resolveRequiredFields(
                is_array($stored->required_fields) ? $stored->required_fields : null,
                $normalized['column_mappings']
            ),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $storedRequired
     * @param  array<string, int>  $columnMappings
     * @return array<string, bool>
     */
    private function resolveRequiredFields(?array $storedRequired, array $columnMappings): array
    {
        $defaults = self::requiredFields();
        $resolved = [];

        foreach (array_keys($columnMappings) as $field) {
            if (in_array($field, self::alwaysRequiredFieldKeys(), true)) {
                $resolved[$field] = true;
                continue;
            }

            if (is_array($storedRequired) && array_key_exists($field, $storedRequired)) {
                $resolved[$field] = (bool) $storedRequired[$field];
                continue;
            }

            $resolved[$field] = (bool) ($defaults[$field] ?? false);
        }

        return $resolved;
    }
}
