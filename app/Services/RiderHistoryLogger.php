<?php

namespace App\Services;

use App\Models\RiderHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class RiderHistoryLogger
{
    public static function record(
        int $riderId,
        string $eventType,
        string $title,
        ?string $details = null,
        array $meta = [],
        ?string $effectiveDate = null
    ): void {
        if (!Schema::hasTable('rider_histories')) {
            return;
        }

        RiderHistory::create([
            'rider_id' => $riderId,
            'event_type' => $eventType,
            'title' => $title,
            'details' => $details,
            'meta' => $meta ?: null,
            'effective_date' => $effectiveDate ?: now()->toDateString(),
            'created_by' => Auth::id(),
        ]);
    }

    public static function projectChange(
        int $riderId,
        ?string $oldCustomerId,
        ?string $newCustomerId,
        ?string $oldProjectName,
        ?string $newProjectName,
        string $effectiveDate,
        ?string $source = null
    ): void {
        if ((string) $oldCustomerId === (string) $newCustomerId) {
            return;
        }
        $title = 'Project updated';
        $details = trim(
            'From: ' . ($oldProjectName ?: '—') . ' → To: ' . ($newProjectName ?: '—')
        );
        self::record($riderId, 'project_change', $title, $details, [
            'old_customer_id' => $oldCustomerId,
            'new_customer_id' => $newCustomerId,
            'old_project_name' => $oldProjectName,
            'new_project_name' => $newProjectName,
            'source' => $source,
        ], $effectiveDate);
    }
}
