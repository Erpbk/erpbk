<?php

namespace App\Services;

use App\Helpers\General;
use App\Models\Bikes;
use App\Models\Customers;
use App\Models\RiderHistory;
use Illuminate\Http\Request;
use App\Models\Riders;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class RiderHistoryLogger
{
    /**
     * Prefer rider branch, then bike branch.
     */
    public static function resolveBranchId(?Riders $rider = null, ?Bikes $bike = null): ?int
    {
        if ($rider && !empty($rider->branch_id)) {
            return (int) $rider->branch_id;
        }
        if ($bike && !empty($bike->branch_id)) {
            return (int) $bike->branch_id;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $context  customer_id, fleet_supervisor, bike_number, history_status
     */
    public static function applyStructuredFields(array &$payload, array $context): void
    {
        if (Schema::hasColumn('rider_histories', 'customer_id') && array_key_exists('customer_id', $context)) {
            $payload['customer_id'] = $context['customer_id'];
        }
        if (Schema::hasColumn('rider_histories', 'fleet_supervisor') && array_key_exists('fleet_supervisor', $context)) {
            $payload['fleet_supervisor'] = $context['fleet_supervisor'];
        }
        if (Schema::hasColumn('rider_histories', 'bike_number') && array_key_exists('bike_number', $context)) {
            $payload['bike_number'] = $context['bike_number'];
        }
        if (Schema::hasColumn('rider_histories', 'history_status') && !empty($context['history_status'])) {
            $payload['history_status'] = $context['history_status'];
        }
    }

    public static function contextFromBikeAndRider(
        ?Riders $rider,
        ?Bikes $bike,
        ?string $historyStatus = null,
        ?string $customerId = null
    ): array {
        if ($rider && !$bike) {
            $bike = Bikes::where('rider_id', $rider->id)->first();
        }

        $resolvedStatus = $historyStatus;
        if ($resolvedStatus === null && $rider) {
            $resolvedStatus = Riders::historyStatusLabel($rider);
        }

        return [
            'customer_id' => $customerId ?? $rider?->customer_id ?? $bike?->customer_id,
            'fleet_supervisor' => $rider?->fleet_supervisor,
            'bike_number' => BikeHistoryLogger::bikeNumber($bike),
            'history_status' => $resolvedStatus,
        ];
    }

    /**
     * Snapshot project, branch, bike, fleet supervisor, and table status for rider history rows.
     *
     * @return array{context: array<string, mixed>, meta: array<string, mixed>}
     */
    public static function structuredHistorySnapshot(Riders $rider, ?Bikes $bike = null): array
    {
        $bike = $bike ?? Bikes::where('rider_id', $rider->id)->first();
        $badges = Riders::tableStatusBadges($rider);
        $projectName = $rider->customer_id
            ? optional(Customers::find($rider->customer_id))->name
            : null;

        return [
            'context' => self::contextFromBikeAndRider($rider, $bike, null, $rider->customer_id),
            'meta' => [
                'employment_status' => $rider->status,
                'employment_label' => $badges['employment']['label'],
                'employment_badge' => $badges['employment']['badge'],
                'rider_status_option' => $badges['option']['label'] ?? null,
                'rider_status_option_badge' => $badges['option']['badge'] ?? null,
                'display_status' => Riders::historyStatusLabel($rider),
                'project_name' => $projectName,
                'branch_id' => $rider->branch_id,
                'bike_id' => $bike?->id,
            ],
        ];
    }

    public static function record(
        int $riderId,
        string $eventType,
        string $title,
        ?string $details = null,
        array $meta = [],
        ?string $effectiveDate = null,
        ?int $branchId = null,
        array $context = []
    ): void {
        if (!Schema::hasTable('rider_histories')) {
            return;
        }

        $payload = [
            'rider_id' => $riderId,
            'event_type' => $eventType,
            'title' => $title,
            'details' => $details,
            'meta' => $meta ?: null,
            'effective_date' => $effectiveDate ?: now()->toDateString(),
            'created_by' => Auth::id(),
        ];

        if (Schema::hasColumn('rider_histories', 'branch_id') && $branchId !== null) {
            $payload['branch_id'] = $branchId;
        }

        self::applyStructuredFields($payload, $context);

        RiderHistory::create($payload);
    }

    public static function projectChange(
        int $riderId,
        ?string $oldCustomerId,
        ?string $newCustomerId,
        ?string $oldProjectName,
        ?string $newProjectName,
        string $effectiveDate,
        ?string $source = null,
        ?int $branchId = null,
        ?Riders $rider = null,
        ?Bikes $bike = null
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
        ], $effectiveDate, $branchId, self::contextFromBikeAndRider($rider, $bike, null, $newCustomerId));
    }

    public static function fleetSupervisorChange(
        Riders $rider,
        ?string $oldSupervisor,
        ?string $newSupervisor,
        ?string $effectiveDate = null,
        ?Bikes $bike = null
    ): void {
        if ((string) $oldSupervisor === (string) $newSupervisor) {
            return;
        }

        $bike = $bike ?? Bikes::where('rider_id', $rider->id)->first();
        $effectiveDate = $effectiveDate ?: now()->toDateString();
        $details = trim('From: ' . ($oldSupervisor ?: '—') . ' → To: ' . ($newSupervisor ?: '—'));

        $snapshot = self::structuredHistorySnapshot($rider, $bike);
        $snapshot['context']['fleet_supervisor'] = $newSupervisor;
        $snapshot['meta']['previous_fleet_supervisor'] = $oldSupervisor;
        $snapshot['meta']['new_fleet_supervisor'] = $newSupervisor;
        $snapshot['meta']['source'] = 'rider_profile';

        self::record(
            (int) $rider->id,
            'fleet_supervisor_change',
            'Fleet supervisor updated',
            $details,
            $snapshot['meta'],
            $effectiveDate,
            self::resolveBranchId($rider, $bike),
            $snapshot['context']
        );

        Riders::syncDisplayStatus($rider);
    }

    /**
     * Snapshot rider fields that change during bike assign / return flows.
     */
    public static function riderSnapshot(Riders $rider): array
    {
        return [
            'status' => $rider->status,
            'rider_status' => $rider->rider_status,
            'rider_status_option' => $rider->rider_status_option,
            'designation' => $rider->designation,
            'customer_id' => $rider->customer_id,
            'emirate_hub' => $rider->emirate_hub,
            'fleet_supervisor' => $rider->fleet_supervisor,
        ];
    }

    /**
     * User-entered note for rider_histories.details (assign/return modal `note` field only).
     */
    public static function assignModalRiderHistoryNote(\Illuminate\Http\Request $request): ?string
    {
        if (!$request->has('note')) {
            return null;
        }

        $note = $request->input('note');
        if (!is_scalar($note)) {
            return null;
        }

        $note = trim((string) $note);

        return $note !== '' ? $note : null;
    }

    /**
     * @deprecated Use assignModalRiderHistoryNote() for assign/return modals.
     */
    public static function detailsFromBikeHistoryNotes(?string $notes): ?string
    {
        $notes = $notes !== null ? trim($notes) : '';

        return $notes !== '' ? $notes : null;
    }

    /**
     * Log employment / rider status changes from bike assign or return.
     */
    public static function bikeAssignStatusChange(
        int $riderId,
        string $title,
        ?string $details,
        array $before,
        array $after,
        string $effectiveDate,
        string $source = 'bike_assign',
        ?int $branchId = null,
        array $extraMeta = [],
        ?string $historyStatus = null,
        ?Riders $rider = null,
        ?Bikes $bike = null
    ): void {
        $prevEmployment = $before['status'] ?? null;
        $newEmployment = $after['status'] ?? null;
        $prevRiderStatus = $before['rider_status'] ?? null;
        $newRiderStatus = $after['rider_status'] ?? null;

        $meta = array_merge([
            'previous_rider_status' => $prevRiderStatus,
            'new_rider_status' => $newRiderStatus,
            'previous_employment_status' => $prevEmployment,
            'new_employment_status' => $newEmployment,
            'previous_employment_label' => $prevEmployment !== null ? General::RiderStatus($prevEmployment) : null,
            'new_employment_label' => $newEmployment !== null ? General::RiderStatus($newEmployment) : null,
            'previous_designation' => $before['designation'] ?? null,
            'new_designation' => $after['designation'] ?? null,
            'previous_customer_id' => $before['customer_id'] ?? null,
            'new_customer_id' => $after['customer_id'] ?? null,
            'source' => $source,
        ], $extraMeta);

        $context = self::contextFromBikeAndRider(
            $rider,
            $bike,
            $historyStatus,
            $after['customer_id'] ?? $rider?->customer_id
        );
        if ($historyStatus) {
            $context['history_status'] = $historyStatus;
        }
        if ($rider) {
            $context['fleet_supervisor'] = $rider->fleet_supervisor;
        }

        self::record($riderId, 'status_change', $title, $details, $meta, $effectiveDate, $branchId, $context);

        if ($rider) {
            Riders::syncDisplayStatus($rider->fresh());
        }
    }
}
