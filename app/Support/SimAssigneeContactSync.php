<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\EmployeeCustomField;
use App\Models\RiderCustomField;
use App\Models\Riders;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps rider/employee contact fields in sync when a SIM is assigned or returned.
 */
class SimAssigneeContactSync
{
    /** @var list<string> */
    public const MANAGED_FIXED_FIELD_KEYS = ['company_contact'];

    /** @var array<string, ?int> */
    private static array $customFieldIdCache = [];

    public static function isManagedFixedFieldKey(?string $fieldKey): bool
    {
        return $fieldKey !== null
            && in_array($fieldKey, self::MANAGED_FIXED_FIELD_KEYS, true);
    }

    public static function isManagedCustomFieldId(int $fieldId, string $customFieldsTable): bool
    {
        $managedId = self::contactCustomFieldId($customFieldsTable);

        return $managedId !== null && $managedId === $fieldId;
    }

    /**
     * Prevent manual edits to SIM-managed contact values on create/update.
     *
     * @param  'rider'|'employee'  $entity
     */
    public static function stripManagedContactFromRequestData(
        array $data,
        ?Model $existing = null,
        string $entity = 'rider'
    ): array {
        unset($data['company_contact']);

        $cfTable = $entity === 'rider' ? 'rider_custom_fields' : 'employee_custom_fields';
        $fieldId = self::contactCustomFieldId($cfTable);
        if ($fieldId === null || !isset($data['custom_field_values']) || !is_array($data['custom_field_values'])) {
            return $data;
        }

        if ($existing !== null) {
            $existingValues = is_array($existing->custom_field_values) ? $existing->custom_field_values : [];
            $preserved = $existingValues[$fieldId] ?? $existingValues[(string) $fieldId] ?? null;
            if ($preserved !== null && $preserved !== '') {
                $data['custom_field_values'][$fieldId] = $preserved;
            } else {
                unset($data['custom_field_values'][$fieldId], $data['custom_field_values'][(string) $fieldId]);
            }
        } else {
            unset($data['custom_field_values'][$fieldId], $data['custom_field_values'][(string) $fieldId]);
        }

        return $data;
    }

    public static function sync(Model $assignee, ?string $simNumber): void
    {
        $simNumber = $simNumber !== null ? trim($simNumber) : '';
        if ($simNumber === '') {
            return;
        }

        if ($assignee instanceof Riders) {
            self::syncRider($assignee, $simNumber);

            return;
        }

        if ($assignee instanceof Employee) {
            self::syncEmployee($assignee, $simNumber);
        }
    }

    public static function clear(Model $assignee): void
    {
        if ($assignee instanceof Riders) {
            self::clearRider($assignee);

            return;
        }

        if ($assignee instanceof Employee) {
            self::clearEmployee($assignee);
        }
    }

    private static function syncRider(Riders $rider, string $simNumber): void
    {
        if (self::setRiderColumn($rider, 'company_contact', $simNumber)) {
            return;
        }

        if (self::setRiderColumn($rider, 'personal_contact', $simNumber)) {
            return;
        }

        self::setCustomFieldValue($rider, 'riders', 'rider_custom_fields', $simNumber);
    }

    private static function clearRider(Riders $rider): void
    {
        if (self::setRiderColumn($rider, 'company_contact', null)) {
            return;
        }

        if (self::setRiderColumn($rider, 'personal_contact', null)) {
            return;
        }

        self::setCustomFieldValue($rider, 'riders', 'rider_custom_fields', null);
    }

    private static function syncEmployee(Employee $employee, string $simNumber): void
    {
        if (self::setEmployeeColumn($employee, 'company_contact', $simNumber)) {
            return;
        }

        if (self::setEmployeeColumn($employee, 'personal_contact', $simNumber)) {
            return;
        }

        self::setCustomFieldValue($employee, 'employees', 'employee_custom_fields', $simNumber);
    }

    private static function clearEmployee(Employee $employee): void
    {
        if (self::setEmployeeColumn($employee, 'company_contact', null)) {
            return;
        }

        if (self::setEmployeeColumn($employee, 'personal_contact', null)) {
            return;
        }

        self::setCustomFieldValue($employee, 'employees', 'employee_custom_fields', null);
    }

    private static function setRiderColumn(Riders $rider, string $column, ?string $value): bool
    {
        if (!Schema::hasColumn('riders', $column)) {
            return false;
        }

        $rider->forceFill([$column => $value]);
        $rider->save();

        return true;
    }

    private static function setEmployeeColumn(Employee $employee, string $column, ?string $value): bool
    {
        if (!Schema::hasColumn('employees', $column)) {
            return false;
        }

        $employee->forceFill([$column => $value]);
        $employee->save();

        return true;
    }

    /**
     * @param  Riders|Employee  $model
     */
    private static function setCustomFieldValue(
        Model $model,
        string $table,
        string $customFieldsTable,
        ?string $value
    ): void {
        if (!Schema::hasColumn($table, 'custom_field_values')) {
            return;
        }

        $fieldId = self::contactCustomFieldId($customFieldsTable);
        if ($fieldId === null) {
            return;
        }

        $values = is_array($model->custom_field_values) ? $model->custom_field_values : [];
        if ($value === null || $value === '') {
            unset($values[$fieldId], $values[(string) $fieldId]);
        } else {
            $values[$fieldId] = $value;
        }

        $model->forceFill(['custom_field_values' => $values]);
        $model->save();
    }

    private static function contactCustomFieldId(string $customFieldsTable): ?int
    {
        if (array_key_exists($customFieldsTable, self::$customFieldIdCache)) {
            return self::$customFieldIdCache[$customFieldsTable];
        }

        if (!Schema::hasTable($customFieldsTable)) {
            self::$customFieldIdCache[$customFieldsTable] = null;

            return null;
        }

        $modelClass = $customFieldsTable === 'rider_custom_fields'
            ? RiderCustomField::class
            : EmployeeCustomField::class;

        $exactLabels = ['company contact', 'contact', 'company phone', 'mobile number', 'sim number', 'phone'];

        $field = $modelClass::query()
            ->where(function ($q) use ($exactLabels) {
                foreach ($exactLabels as $label) {
                    $q->orWhereRaw('LOWER(TRIM(label)) = ?', [$label]);
                }
                $q->orWhere(function ($inner) {
                    $inner->whereRaw('LOWER(label) LIKE ?', ['%contact%'])
                        ->whereIn('data_type', ['text', 'tel', 'number']);
                });
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->first(['id']);

        self::$customFieldIdCache[$customFieldsTable] = $field?->id;

        return self::$customFieldIdCache[$customFieldsTable];
    }
}
