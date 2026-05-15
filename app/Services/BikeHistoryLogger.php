<?php

namespace App\Services;

use App\Models\Bikes;
use App\Models\Riders;
use Illuminate\Support\Facades\Schema;

class BikeHistoryLogger
{
    public static function bikeNumber(?Bikes $bike): ?string
    {
        if (!$bike) {
            return null;
        }
        $plate = trim((string) ($bike->plate ?? ''));
        if ($plate !== '') {
            return $plate;
        }
        $code = trim((string) ($bike->bike_code ?? ''));

        return $code !== '' ? $code : null;
    }

    /**
     * Map bike assign / return warehouse action to spreadsheet-style history status.
     */
    public static function historyStatusFromWarehouse(?string $warehouse): ?string
    {
        $key = trim((string) $warehouse);
        if ($key === '') {
            return null;
        }

        return match ($key) {
            'Active' => 'Joining',
            'Vacation' => 'Vacation',
            'Absconded' => 'Absconded',
            'Return' => 'Return',
            'Theft' => 'Theft',
            'Total Loss' => 'Total Loss',
            default => $key,
        };
    }

    /**
     * Structured columns for bike_histories (Date = note_date, Project = customer_id).
     */
    public static function structuredBikeHistoryFields(
        Bikes $bike,
        ?Riders $rider,
        ?string $historyStatus,
        ?string $customerId = null
    ): array {
        $fields = [];

        if (Schema::hasColumn('bike_histories', 'branch_id')) {
            $branchId = RiderHistoryLogger::resolveBranchId($rider, $bike);
            if ($branchId !== null) {
                $fields['branch_id'] = $branchId;
            }
        }
        if (Schema::hasColumn('bike_histories', 'fleet_supervisor')) {
            $fields['fleet_supervisor'] = $rider?->fleet_supervisor;
        }
        if (Schema::hasColumn('bike_histories', 'bike_number')) {
            $fields['bike_number'] = self::bikeNumber($bike);
        }
        if (Schema::hasColumn('bike_histories', 'history_status') && $historyStatus !== null && $historyStatus !== '') {
            $fields['history_status'] = $historyStatus;
        }
        if ($customerId !== null && $customerId !== '') {
            $fields['customer_id'] = $customerId;
        }

        return $fields;
    }

    public static function mergeStructuredUpdate(array $update, Bikes $bike, ?Riders $rider, ?string $historyStatus): array
    {
        return array_merge($update, self::structuredBikeHistoryFields($bike, $rider, $historyStatus, $bike->customer_id));
    }
}
