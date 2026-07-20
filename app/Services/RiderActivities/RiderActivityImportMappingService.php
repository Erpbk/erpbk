<?php

namespace App\Services\RiderActivities;

use App\Models\RiderActivityImportSetting;

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

    /**
     * @return array{customer_id: int, import_type: string, header_rows_to_skip: int, column_mappings: array<string, int>}
     */
    public function resolve(int $customerId, string $importType = self::TYPE_RIDER): array
    {
        $importType = self::normalizeImportType($importType);

        $defaults = [
            'customer_id' => $customerId,
            'import_type' => $importType,
            'header_rows_to_skip' => self::defaultHeaderRowsToSkip(),
            'column_mappings' => self::defaultColumnMappings($importType),
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

    /**
     * @param  array<string, mixed>  $inputMappings
     * @return array<string, int>
     */
    public function sanitizeColumnMappings(array $inputMappings, string $importType = self::TYPE_RIDER): array
    {
        $importType = self::normalizeImportType($importType);
        $sanitized = [];

        foreach (self::defaultColumnMappings($importType) as $field => $defaultIndex) {
            $value = $inputMappings[$field] ?? $defaultIndex;
            $sanitized[$field] = max(0, (int) $value);
        }

        return $sanitized;
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
     * @return array{customer_id: int, import_type: string, header_rows_to_skip: int, column_mappings: array<string, int>}
     */
    private function mergeWithDefaults(RiderActivityImportSetting $stored, int $customerId, string $importType): array
    {
        return [
            'customer_id' => $customerId,
            'import_type' => $importType,
            'header_rows_to_skip' => max(0, (int) ($stored->header_rows_to_skip ?? self::defaultHeaderRowsToSkip())),
            'column_mappings' => $this->sanitizeColumnMappings($stored->column_mappings ?? [], $importType),
        ];
    }
}
