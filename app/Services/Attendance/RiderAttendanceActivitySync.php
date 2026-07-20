<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\RiderActivities;
use App\Models\Riders;
use Carbon\Carbon;

class RiderAttendanceActivitySync
{
    /** @var list<string> */
    public const RIDER_METRIC_KEYS = [
        'total_orders',
        'working_hours',
        'cancelled_orders',
        'rejected_orders',
    ];

    /** @var list<string> */
    public const RIDER_ACTIVITY_EXTRA_KEYS = [
        'ontime_orders_percentage',
    ];

    /**
     * Mark or update rider attendance from imported / activity row data.
     *
     * @param  array<string, int|float|null>  $metricData
     */
    public static function syncAttendanceFromActivity(
        Riders $rider,
        string $date,
        array $metricData,
        string $status = 'present'
    ): Attendance {
        $payload = [
            'ref_id' => $rider->id,
            'ref_type' => 'rider',
            'branch_id' => $rider->branch_id,
            'date' => self::normalizeDate($date),
            'status' => self::normalizeAttendanceStatus($status),
        ];

        foreach (self::RIDER_METRIC_KEYS as $key) {
            if (!array_key_exists($key, $metricData)) {
                continue;
            }
            $payload[$key] = self::castMetricValue($key, $metricData[$key]);
        }

        return Attendance::updateOrCreate(
            [
                'ref_id' => $rider->id,
                'ref_type' => 'rider',
                'date' => self::normalizeDate($date),
            ],
            $payload
        );
    }

    /**
     * Persist rider activity when attendance is saved for a rider.
     *
     * @param  array<string, int|float|null>  $syncData
     */
    public static function syncActivityFromAttendance(
        int $riderId,
        string $date,
        array $syncData = []
    ): ?RiderActivities {
        $rider = Riders::find($riderId);
        if (!$rider) {
            return null;
        }

        $normalizedDate = self::normalizeDate($date);
        $activityPayload = [];

        $displayRiderId = self::resolveDisplayRiderId($rider);
        if ($displayRiderId !== null) {
            $activityPayload['d_rider_id'] = $displayRiderId;
        }

        if (!empty($rider->company_id)) {
            $activityPayload['company_id'] = $rider->company_id;
        }

        $deliveredOrders = null;
        $loginHr = null;

        if (array_key_exists('total_orders', $syncData) && $syncData['total_orders'] !== null) {
            $deliveredOrders = (int) $syncData['total_orders'];
            $activityPayload['delivered_orders'] = $deliveredOrders;
        }
        if (array_key_exists('rejected_orders', $syncData) && $syncData['rejected_orders'] !== null) {
            $activityPayload['rejected_orders'] = (int) $syncData['rejected_orders'];
        }
        if (array_key_exists('working_hours', $syncData) && $syncData['working_hours'] !== null) {
            $loginHr = (float) $syncData['working_hours'];
            $activityPayload['login_hr'] = $loginHr;
        }
        if (array_key_exists('ontime_orders_percentage', $syncData) && $syncData['ontime_orders_percentage'] !== null) {
            $activityPayload['ontime_orders_percentage'] = self::normalizeOntimePercentage(
                $syncData['ontime_orders_percentage']
            );
        }

        if ($deliveredOrders !== null || $loginHr !== null) {
            $activityPayload['delivery_rating'] = self::resolveDeliveryRating(
                (float) ($loginHr ?? 0),
                (int) ($deliveredOrders ?? 0)
            );
        }

        return RiderActivities::updateOrCreate(
            [
                'rider_id' => $rider->id,
                'date' => $normalizedDate,
            ],
            $activityPayload
        );
    }

    /**
     * Build rider activity sync data from saved attendance and request input.
     *
     * @return array<string, int|float|null>
     */
    public static function syncDataFromAttendance(Attendance $attendance, array $input = []): array
    {
        $syncData = self::syncDataFromRequest($input);

        $fallback = [
            'total_orders' => $attendance->total_orders,
            'working_hours' => $attendance->working_hours,
            'rejected_orders' => $attendance->rejected_orders,
            'cancelled_orders' => $attendance->cancelled_orders,
        ];

        foreach ($fallback as $key => $value) {
            if ((!array_key_exists($key, $syncData) || $syncData[$key] === null) && $value !== null && $value !== '') {
                $syncData[$key] = self::castMetricValue($key, $value);
            }
        }

        if (
            (!array_key_exists('working_hours', $syncData) || $syncData['working_hours'] === null)
            && $attendance->check_in
            && $attendance->check_out
        ) {
            $syncData['working_hours'] = self::calculateWorkingHours(
                $attendance->check_in,
                $attendance->check_out
            );
        }

        return $syncData;
    }

    /**
     * @return array<string, int|float|null>
     */
    public static function metricDataFromRequest(array $input): array
    {
        return self::extractSyncData($input, self::RIDER_METRIC_KEYS);
    }

    /**
     * @return array<string, int|float|null>
     */
    public static function syncDataFromRequest(array $input): array
    {
        return self::extractSyncData(
            $input,
            array_merge(self::RIDER_METRIC_KEYS, self::RIDER_ACTIVITY_EXTRA_KEYS)
        );
    }

    public static function normalizeAttendanceStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match (true) {
            str_contains($value, 'absent') => 'absent',
            str_contains($value, 'late') => 'late',
            str_contains($value, 'half') => 'half day',
            str_contains($value, 'weekend') => 'weekend',
            str_contains($value, 'holiday') => 'weekend',
            str_contains($value, 'leave') => 'on leave',
            default => 'present',
        };
    }

    public static function normalizeDate(string $date): string
    {
        return Carbon::parse($date)->format('Y-m-d');
    }

    public static function formatOntimePercentageDisplay(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numeric = (float) $value;

        return number_format($numeric <= 1 ? $numeric * 100 : $numeric, 2);
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, int|float|null>
     */
    private static function extractSyncData(array $input, array $keys): array
    {
        $data = [];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $input)) {
                continue;
            }

            $value = $input[$key];
            if ($value === '' || $value === null) {
                continue;
            }

            if ($key === 'ontime_orders_percentage') {
                $data[$key] = self::normalizeOntimePercentage($value);
                continue;
            }

            $data[$key] = self::castMetricValue($key, $value);
        }

        return $data;
    }

    private static function normalizeOntimePercentage(mixed $value): ?float
    {
        if ($value === '' || $value === null) {
            return null;
        }

        $normalized = (float) str_replace('%', '', (string) $value);
        if ($normalized > 1) {
            $normalized /= 100;
        }

        return max(0, min(1, $normalized));
    }

    private static function castMetricValue(string $key, mixed $value): int|float|null
    {
        if ($value === '' || $value === null) {
            return null;
        }

        if ($key === 'working_hours') {
            return max(0, (float) $value);
        }

        return max(0, (int) $value);
    }

    private static function calculateWorkingHours(mixed $checkIn, mixed $checkOut): float
    {
        $start = Carbon::parse($checkIn);
        $end = Carbon::parse($checkOut);

        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }

    private static function resolveDeliveryRating(float $loginHr, int $deliveredOrders): ?string
    {
        if ($loginHr <= 0) {
            return null;
        }

        if (($deliveredOrders >= 5 && $loginHr >= 10) || $deliveredOrders >= 10) {
            return 'Yes';
        }

        return 'No';
    }

    private static function resolveDisplayRiderId(Riders $rider): ?string
    {
        $riderId = trim((string) ($rider->rider_id ?? ''));

        return $riderId !== '' ? $riderId : null;
    }
}
