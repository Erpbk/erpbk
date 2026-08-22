<?php

namespace App\Models;

use App\Support\SimAssignFields;

class SimAssignFieldAssignment extends BaseModel
{
    protected $table = 'sim_assign_field_assignments';

    protected $fillable = [
        'company_id',
        'field_key',
        'custom_field_id',
        'kind',
        'display_label',
        'input_type',
        'input_config',
        'display_order',
        'is_visible',
        'is_required',
        'show_on_active',
        'show_on_change',
    ];

    protected $casts = [
        'input_config' => 'array',
        'is_visible' => 'boolean',
        'is_required' => 'boolean',
        'show_on_active' => 'boolean',
        'show_on_change' => 'boolean',
    ];

    public function customField()
    {
        return $this->belongsTo(ModuleCustomField::class, 'custom_field_id', 'id');
    }

    public function resolvedLabel(): string
    {
        if ($this->display_label) {
            return $this->display_label;
        }

        if ($this->kind === 'custom' && $this->customField) {
            return $this->customField->label;
        }

        return SimAssignFields::humanizeFieldKey((string) $this->field_key);
    }

    /**
     * @return list<string>
     */
    public static function assignSpecialFieldKeys(): array
    {
        return [
            'number',
            'assign_to_display',
            'assignee_type',
            'assign_to_rider',
            'assign_to_employee',
        ];
    }

    /**
     * @return array{type: string, required: bool, readonly: bool, assign_group: ?string, options: mixed, assign_options: ?array}
     */
    public function resolvedInputSpec(): array
    {
        $catalog = collect(SimAssignFields::defaultAssignFieldCatalog())
            ->firstWhere('field_key', $this->field_key);

        $catalogConfig = is_array($catalog['input_config'] ?? null) ? $catalog['input_config'] : [];
        $assignmentConfig = is_array($this->input_config) ? $this->input_config : [];
        $config = array_merge($catalogConfig, $assignmentConfig);

        $spec = [
            'type' => 'text',
            'required' => (bool) $this->is_required,
            'readonly' => !empty($config['readonly']),
            'assign_group' => $config['assign_group'] ?? null,
            'options' => $config['options'] ?? null,
            'assign_options' => $config['assign_options'] ?? null,
        ];

        if ($this->kind === 'custom' && $this->customField) {
            $cf = $this->customField;
            $rawType = $this->input_type ?: $cf->data_type ?: 'text';
            $spec['type'] = $rawType === 'dropdown' ? 'select' : $rawType;
            $spec['required'] = (bool) $this->is_required;
            if ($cf->data_type === 'dropdown' && is_array($cf->config) && isset($cf->config['options'])) {
                $spec['options'] = $spec['options'] ?? $cf->config['options'];
            }

            return $spec;
        }

        $rawType = $this->input_type ?: ($catalog['input_type'] ?? 'text');
        $spec['type'] = $rawType === 'dropdown' ? 'select' : $rawType;

        return $spec;
    }

    public function usesAssignSpecialRenderer(): bool
    {
        if ($this->kind === 'custom') {
            return false;
        }

        return in_array((string) $this->field_key, self::assignSpecialFieldKeys(), true);
    }

    /**
     * @param  array<string, string>  $branchScopedOptions  rider/employee dropdowns from controller
     * @return array<string, string>
     */
    public function resolvedSelectOptions(array $branchScopedOptions = []): array
    {
        $spec = $this->resolvedInputSpec();
        $fieldKey = (string) $this->field_key;

        if (isset($branchScopedOptions[$fieldKey]) && is_array($branchScopedOptions[$fieldKey])) {
            return $branchScopedOptions[$fieldKey];
        }

        if (
            $fieldKey === 'assignee_type'
            && is_array($spec['assign_options'] ?? null)
            && $spec['assign_options'] !== []
        ) {
            return $spec['assign_options'];
        }

        $parsed = [];
        $rawOptions = $spec['options'] ?? null;
        if ($rawOptions !== null && $rawOptions !== '') {
            $lines = is_array($rawOptions) ? $rawOptions : preg_split('/\r\n|\r|\n/', (string) $rawOptions);
            foreach ($lines as $line) {
                $line = trim((string) $line);
                if ($line !== '') {
                    $parsed[$line] = $line;
                }
            }
        }

        if ($parsed !== []) {
            return ['' => 'Select'] + $parsed;
        }

        return match ($fieldKey) {
            'assignee_type' => ['rider' => 'Rider', 'employee' => 'Employee'],
            default => ['' => 'Select'],
        };
    }
}
