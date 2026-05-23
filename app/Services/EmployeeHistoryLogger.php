<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeHistory;
use App\Models\Sims;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class EmployeeHistoryLogger
{
    public static function resolveBranchId(?Employee $employee = null): ?int
    {
        if ($employee && !empty($employee->branch_id)) {
            return (int) $employee->branch_id;
        }

        return null;
    }

    public static function record(
        int $employeeId,
        string $eventType,
        string $title,
        ?string $details = null,
        array $meta = [],
        ?string $effectiveDate = null,
        ?int $branchId = null
    ): void {
        if (!Schema::hasTable('employee_histories')) {
            return;
        }

        $payload = [
            'employee_id' => $employeeId,
            'event_type' => $eventType,
            'title' => $title,
            'details' => $details,
            'meta' => $meta ?: null,
            'effective_date' => $effectiveDate ?: now()->toDateString(),
            'created_by' => Auth::id(),
        ];

        if (Schema::hasColumn('employee_histories', 'branch_id') && $branchId !== null) {
            $payload['branch_id'] = $branchId;
        }

        EmployeeHistory::create($payload);
    }

    public static function simAssigned(Employee $employee, Sims $sim, ?string $noteDate = null, ?string $notes = null): void
    {
        $simNumber = $sim->number ?? (string) $sim->id;
        $details = 'SIM ' . $simNumber . ' assigned';
        if ($notes !== null && trim($notes) !== '') {
            $details .= ' — ' . trim($notes);
        }

        self::record(
            (int) $employee->id,
            'sim_assign',
            'SIM assigned',
            $details,
            [
                'sim_id' => $sim->id,
                'sim_number' => $simNumber,
                'action' => 'assigned',
            ],
            $noteDate ?: now()->toDateString(),
            self::resolveBranchId($employee)
        );
    }

    public static function statusChange(
        Employee $employee,
        ?string $previousStatus,
        string $newStatus,
        ?string $effectiveDate = null
    ): void {
        $prevLabel = $previousStatus ? ucfirst(str_replace('_', ' ', $previousStatus)) : '—';
        $newLabel = ucfirst(str_replace('_', ' ', $newStatus));

        self::record(
            (int) $employee->id,
            'status_change',
            'Employment status: ' . $newLabel,
            $prevLabel . ' → ' . $newLabel,
            [
                'column' => 'status',
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
            ],
            $effectiveDate,
            self::resolveBranchId($employee)
        );
    }

    public static function profileFieldChange(
        Employee $employee,
        string $column,
        $previousValue,
        $newValue,
        ?string $categoryName = null,
        ?string $effectiveDate = null
    ): void {
        $fieldLabel = $categoryName ?: $column;
        $prevText = $previousValue !== null && $previousValue !== '' ? (string) $previousValue : '—';
        $newText = $newValue !== null && $newValue !== '' ? (string) $newValue : '—';
        $cleared = $newValue === null || $newValue === '';

        self::record(
            (int) $employee->id,
            $cleared ? 'field_cleared' : 'field_change',
            $cleared ? ($fieldLabel . ' cleared') : ($fieldLabel . ': ' . $newText),
            $prevText . ' → ' . $newText,
            [
                'column' => $column,
                'category' => $categoryName,
                'previous_value' => $previousValue,
                'new_value' => $newValue,
            ],
            $effectiveDate,
            self::resolveBranchId($employee)
        );
    }

    public static function simReturned(Employee $employee, Sims $sim, ?string $returnDate = null, ?string $notes = null): void
    {
        $simNumber = $sim->number ?? (string) $sim->id;
        $details = 'SIM ' . $simNumber . ' returned';
        if ($notes !== null && trim($notes) !== '') {
            $details .= ' — ' . trim($notes);
        }

        self::record(
            (int) $employee->id,
            'sim_return',
            'SIM returned',
            $details,
            [
                'sim_id' => $sim->id,
                'sim_number' => $simNumber,
                'action' => 'returned',
            ],
            $returnDate ?: now()->toDateString(),
            self::resolveBranchId($employee)
        );
    }
}
