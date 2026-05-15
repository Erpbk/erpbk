<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BikeAssignFieldAssignment extends BaseModel
{
    protected $table = 'bike_assign_field_assignments';

    protected $fillable = [
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
        return $this->belongsTo(BikeCustomField::class, 'custom_field_id', 'id');
    }

    public function resolvedLabel(): string
    {
        if ($this->display_label) {
            return $this->display_label;
        }

        if ($this->kind === 'custom' && $this->customField) {
            return $this->customField->label;
        }

        return BikeCustomField::humanizeFieldKey((string) $this->field_key);
    }

    /**
     * Built-in assign fields that need modal-specific markup (warehouse context, assign groups, etc.).
     *
     * @return list<string>
     */
    public static function assignSpecialFieldKeys(): array
    {
        return [
            'warehouse',
            'assign_type',
            'rider_id',
            'rental_company_id',
            'designation',
            'customer_id',
            'visa_sponsor',
        ];
    }

    /**
     * Input spec for assign modals (mirrors bike fixed-field spec shape).
     *
     * @return array{type: string, required: bool, readonly: bool, assign_group: ?string, options: mixed, assign_options: ?array}
     */
    public function resolvedInputSpec(): array
    {
        $catalog = collect(BikeCustomField::defaultAssignFieldCatalog())
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

        $key = (string) $this->field_key;
        if (!in_array($key, self::assignSpecialFieldKeys(), true)) {
            return false;
        }

        $type = $this->resolvedInputSpec()['type'] ?? 'text';

        return match ($key) {
            'warehouse' => true,
            'assign_type', 'rider_id', 'rental_company_id', 'customer_id' => in_array($type, ['select', 'dropdown'], true),
            'designation', 'visa_sponsor' => true,
            default => false,
        };
    }

    /**
     * @return array<string, string>
     */
    public function resolvedSelectOptions(): array
    {
        $spec = $this->resolvedInputSpec();

        if (is_array($spec['assign_options'] ?? null) && $spec['assign_options'] !== []) {
            return ['' => 'Select'] + $spec['assign_options'];
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

        $defaults = match ((string) $this->field_key) {
            'assign_type' => ['' => 'Select Type', 'rider' => 'Rider', 'company' => 'Company'],
            'rider_id' => \App\Models\Riders::dropdown(),
            'rental_company_id' => \App\Models\BikeRentCompany::pluck('name', 'id')->prepend('Select', ''),
            'customer_id' => \App\Models\Customers::dropdown(),
            default => ['' => 'Select'],
        };

        return $defaults instanceof \Illuminate\Support\Collection
            ? $defaults->all()
            : (array) $defaults;
    }
}
