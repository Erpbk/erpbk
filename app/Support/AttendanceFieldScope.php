<?php

namespace App\Support;

use App\Models\ModuleFieldCategoryAssignment;
use Illuminate\Support\Collection;

/**
 * Attendance fields that apply only when ref_type is rider.
 */
class AttendanceFieldScope
{
    public const MODULE_KEY = 'attendance';

    /** @var list<string> */
    public const RIDER_ONLY_FIELDS = [
        'total_orders',
        'working_hours',
        'cancelled_orders',
        'rejected_orders',
    ];

    public static function isRiderOnlyField(string $fieldKey): bool
    {
        return in_array($fieldKey, self::RIDER_ONLY_FIELDS, true);
    }

    /**
     * @param  list<string>  $fieldKeys
     * @return list<string>
     */
    public static function filterFieldKeysForRefType(array $fieldKeys, ?string $refType): array
    {
        if ($refType === 'rider') {
            return $fieldKeys;
        }

        return array_values(array_filter(
            $fieldKeys,
            fn (string $key) => !self::isRiderOnlyField($key)
        ));
    }

    /**
     * @param  array<string, string>  $fieldMap
     * @return array<string, string>
     */
    public static function filterFieldMapForRefType(array $fieldMap, ?string $refType): array
    {
        if ($refType === 'rider') {
            return $fieldMap;
        }

        return array_filter(
            $fieldMap,
            fn (string $label, string $key) => !self::isRiderOnlyField($key),
            ARRAY_FILTER_USE_BOTH
        );
    }

    public static function assignmentAppliesToRefType(ModuleFieldCategoryAssignment $assignment, ?string $refType): bool
    {
        if (!self::isRiderOnlyField((string) $assignment->field_key)) {
            return true;
        }

        return $refType === 'rider';
    }

    /**
     * @param  Collection<int, ModuleFieldCategoryAssignment>  $assignments
     * @return Collection<int, ModuleFieldCategoryAssignment>
     */
    public static function filterAssignmentsForRefType(Collection $assignments, ?string $refType): Collection
    {
        return $assignments->filter(
            fn (ModuleFieldCategoryAssignment $row) => self::assignmentAppliesToRefType($row, $refType)
        )->values();
    }

    public static function riderScopeInputConfig(): array
    {
        return ['ref_type_scope' => 'rider'];
    }

    public static function ensureRiderScopeOnAssignment(ModuleFieldCategoryAssignment $assignment): void
    {
        if (!self::isRiderOnlyField((string) $assignment->field_key)) {
            return;
        }

        $config = is_array($assignment->input_config) ? $assignment->input_config : [];
        if (($config['ref_type_scope'] ?? null) !== 'rider') {
            $assignment->input_config = array_merge($config, self::riderScopeInputConfig());
            $assignment->save();
        }
    }

    public static function labelForField(string $fieldKey): string
    {
        return match ($fieldKey) {
            'total_orders' => 'Total Orders',
            'working_hours' => 'Working Hours',
            'cancelled_orders' => 'Cancelled Orders',
            'rejected_orders' => 'Rejected Orders',
            default => ModuleFieldSource::humanizeFieldKey($fieldKey),
        };
    }
};
