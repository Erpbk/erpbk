<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\RiderActivities;
use App\Models\Riders;

class RiderAttendanceActivitySync
{
    /** @var list<string> */
    public const RIDER_METRIC_KEYS = [
        'total_orders',
        'working_hours',
        'cancelled_orders',
        'rejected_orders',
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
            'date' => $date,
            'status' => $status,
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
                'date' => $date,
            ],
            $payload
        );
    }

    /**
     * Persist rider activity metrics when attendance is saved manually for a rider.
     *
     * @param  array<string, int|float|null>  $metricData
     */
    public static function syncActivityFromAttendance(
        int $riderId,
        string $date,
        array $metricData
    ): ?RiderActivities {
        $rider = Riders::find($riderId);
        if (!$rider) {
            return null;
        }

        $hasData = false;
        foreach (self::RIDER_METRIC_KEYS as $key) {
            if (array_key_exists($key, $metricData)) {
                $hasData = true;
                break;
            }
        }

        if (!$hasData) {
            return null;
        }

        $activityPayload = [
            'd_rider_id' => $rider->rider_id,
        ];

        if (array_key_exists('total_orders', $metricData)) {
            $activityPayload['delivered_orders'] = (int) ($metricData['total_orders'] ?? 0);
        }
        if (array_key_exists('rejected_orders', $metricData)) {
            $activityPayload['rejected_orders'] = (int) ($metricData['rejected_orders'] ?? 0);
        }
        if (array_key_exists('working_hours', $metricData)) {
            $activityPayload['login_hr'] = (float) ($metricData['working_hours'] ?? 0);
        }

        return RiderActivities::updateOrCreate(
            [
                'rider_id' => $rider->id,
                'date' => $date,
            ],
            $activityPayload
        );
    }

    /**
     * @return array<string, int|float|null>
     */
    public static function metricDataFromRequest(array $input): array
    {
        $data = [];

        foreach (self::RIDER_METRIC_KEYS as $key) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $value = $input[$key];
            if ($value === '' || $value === null) {
                $data[$key] = null;
            } else {
                $data[$key] = self::castMetricValue($key, $value);
            }
        }

        return $data;
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
}
