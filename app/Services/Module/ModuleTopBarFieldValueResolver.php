<?php

namespace App\Services\Module;

use App\Models\BikeFieldCategoryAssignment;
use App\Models\ChequeFieldCategoryAssignment;
use App\Models\EmployeeFieldCategoryAssignment;
use App\Models\ModuleFieldCategoryAssignment;
use App\Models\RiderFieldCategoryAssignment;
use App\Support\ErpModuleRegistry;
class ModuleTopBarFieldValueResolver
{
    /**
     * Values configured in Module Fields Settings (dropdown options, fixed select choices, etc.).
     *
     * @return list<string>
     */
    public function configuredValuesForColumn(string $moduleKey, string $column): array
    {
        $column = trim($column);
        if ($column === '') {
            return [];
        }

        $settingsModuleKey = ErpModuleRegistry::settingsFieldsModuleKey($moduleKey);

        $fromFixedChoices = $this->valuesFromFixedSelectChoices($settingsModuleKey, $column);
        if ($fromFixedChoices !== []) {
            return $fromFixedChoices;
        }

        $fromDedicated = $this->valuesFromDedicatedAssignment($settingsModuleKey, $column);
        if ($fromDedicated !== []) {
            return $fromDedicated;
        }

        return $this->valuesFromModuleAssignment($settingsModuleKey, $column);
    }

    /**
     * Configured values first, then distinct values already stored on records.
     *
     * @param  list<string>  $tableValues
     * @return list<string>
     */
    public function mergeConfiguredAndTableValues(array $configuredValues, array $tableValues): array
    {
        return collect($configuredValues)
            ->concat($tableValues)
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function parseOptionsFromInputConfig(?array $inputConfig): array
    {
        if (!is_array($inputConfig) || !array_key_exists('options', $inputConfig)) {
            return [];
        }

        $raw = $inputConfig['options'];
        $lines = is_array($raw) ? $raw : preg_split("/\r\n|\n|\r/", (string) $raw);

        return collect($lines)
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    protected function valuesFromFixedSelectChoices(string $settingsModuleKey, string $column): array
    {
        if (!class_exists(\App\Models\BikeCustomField::class)) {
            return [];
        }

        if (!in_array($settingsModuleKey, ['bike_list', 'bikes', 'bike_settings'], true)) {
            return [];
        }

        $choices = \App\Models\BikeCustomField::fixedFieldSelectChoices($column);
        if ($choices === []) {
            return [];
        }

        return collect($choices)
            ->map(function (array $choice) {
                $value = trim((string) ($choice['value'] ?? ''));
                if ($value !== '') {
                    return $value;
                }

                return trim((string) ($choice['label'] ?? ''));
            })
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    protected function valuesFromModuleAssignment(string $settingsModuleKey, string $column): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('module_field_category_assignments')) {
            return [];
        }

        $assignment = ModuleFieldCategoryAssignment::query()
            ->where('module_key', $settingsModuleKey)
            ->where('field_key', $column)
            ->first();

        return $this->parseOptionsFromInputConfig(is_array($assignment?->input_config) ? $assignment->input_config : null);
    }

    /**
     * @return list<string>
     */
    protected function valuesFromDedicatedAssignment(string $settingsModuleKey, string $column): array
    {
        $modelClass = match ($settingsModuleKey) {
            'riders', 'rider_settings' => RiderFieldCategoryAssignment::class,
            'bike_list', 'bikes', 'bike_settings' => BikeFieldCategoryAssignment::class,
            'cheques', 'cheques_settings' => ChequeFieldCategoryAssignment::class,
            'employees', 'employee_settings' => EmployeeFieldCategoryAssignment::class,
            default => null,
        };

        if ($modelClass === null || !class_exists($modelClass)) {
            return [];
        }

        $assignment = $modelClass::query()
            ->where('field_key', $column)
            ->first();

        return $this->parseOptionsFromInputConfig(is_array($assignment?->input_config) ? $assignment->input_config : null);
    }
}
