<?php

namespace App\Models;

use App\Helpers\Common;
use App\Support\ModuleFieldSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BikeCustomField extends BaseModel
{
    protected $table = 'bike_custom_fields';

    protected $fillable = [
        'company_id',
        'label',
        'help_text',
        'data_privacy',
        'prevent_duplicate_values',
        'default_value',
        'input_format',
        'data_type',
        'is_mandatory',
        'is_visible',
        'config',
        'category_id',
        'display_order',
    ];

    protected $casts = [
        'data_privacy' => 'array',
        'config' => 'array',
        'prevent_duplicate_values' => 'boolean',
        'is_mandatory' => 'boolean',
        'is_visible' => 'boolean',
    ];

    /**
     * Data types supported for bike custom fields.
     * (Mirrors the rider/account/voucher custom field system.)
     */
    public static function dataTypes(): array
    {
        return [
            'text' => [
                'label' => 'Text',
                'config' => [
                    ['key' => 'max_length', 'label' => 'Max length', 'type' => 'number', 'default' => 255],
                    ['key' => 'placeholder', 'label' => 'Placeholder', 'type' => 'text'],
                ],
            ],
            'textarea' => [
                'label' => 'Textarea',
                'config' => [
                    ['key' => 'max_length', 'label' => 'Max length', 'type' => 'number', 'default' => 1000],
                    ['key' => 'rows', 'label' => 'Rows', 'type' => 'number', 'default' => 4],
                ],
            ],
            'number' => [
                'label' => 'Number',
                'config' => [
                    ['key' => 'min', 'label' => 'Minimum', 'type' => 'number'],
                    ['key' => 'max', 'label' => 'Maximum', 'type' => 'number'],
                    ['key' => 'step', 'label' => 'Step', 'type' => 'number', 'default' => 1],
                ],
            ],
            'decimal' => [
                'label' => 'Decimal',
                'config' => [
                    ['key' => 'min', 'label' => 'Minimum', 'type' => 'number'],
                    ['key' => 'max', 'label' => 'Maximum', 'type' => 'number'],
                    ['key' => 'decimals', 'label' => 'Decimal places', 'type' => 'number', 'default' => 2],
                ],
            ],
            'date' => [
                'label' => 'Date',
                'config' => [
                    ['key' => 'format', 'label' => 'Display format', 'type' => 'text', 'default' => 'Y-m-d'],
                ],
            ],
            'datetime' => [
                'label' => 'Date & Time',
                'config' => [
                    ['key' => 'format', 'label' => 'Display format', 'type' => 'text', 'default' => 'Y-m-d H:i'],
                ],
            ],
            'dropdown' => [
                'label' => 'Dropdown',
                'config' => [
                    ['key' => 'options', 'label' => 'Options (one per line)', 'type' => 'textarea', 'placeholder' => "Option 1\nOption 2"],
                ],
            ],
            'checkbox' => [
                'label' => 'Checkbox',
                'config' => [
                    ['key' => 'default_checked', 'label' => 'Default checked', 'type' => 'checkbox', 'default' => false],
                ],
            ],
            'email' => [
                'label' => 'Email',
                'config' => [
                    ['key' => 'placeholder', 'label' => 'Placeholder', 'type' => 'text'],
                ],
            ],
            'url' => [
                'label' => 'URL',
                'config' => [
                    ['key' => 'placeholder', 'label' => 'Placeholder', 'type' => 'text'],
                ],
            ],
        ];
    }

    /**
     * Shared add/edit custom-field form values (config options, privacy, duplicates).
     *
     * @return array{config: array, data_privacy: array{pii: bool, ephi: bool}, prevent_duplicate_values: bool, is_mandatory: bool, is_visible: bool, help_text: ?string, default_value: ?string, input_format: ?string}
     */
    public static function formMetaFromRequest(\Illuminate\Http\Request $request): array
    {
        $config = $request->input('config');
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            $config = is_array($decoded) ? $decoded : [];
        } elseif (! is_array($config)) {
            $config = [];
        }

        if ($config === [] && $request->filled('config_options')) {
            $config = ['options' => (string) $request->input('config_options')];
        }

        return [
            'config' => $config,
            'data_privacy' => [
                'pii' => $request->boolean('data_privacy_pii'),
                'ephi' => $request->boolean('data_privacy_ephi'),
            ],
            'prevent_duplicate_values' => $request->boolean('prevent_duplicate_values'),
            'is_mandatory' => $request->boolean('is_mandatory'),
            'is_visible' => $request->boolean('is_visible', true),
            'help_text' => $request->input('help_text'),
            'default_value' => $request->input('default_value'),
            'input_format' => $request->input('input_format'),
        ];
    }

    /**
     * Keep only columns that exist on the destination table.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function payloadForTable(string $table, array $payload): array
    {
        foreach (array_keys($payload) as $column) {
            if (! Schema::hasColumn($table, $column)) {
                unset($payload[$column]);
            }
        }

        return $payload;
    }

    /**
     * Merge assignment input_config (help, defaults, type options) into a fixed-field spec.
     *
     * @param  array<string, mixed>  $spec
     * @param  array<string, mixed>|null  $inputConfig
     * @return array<string, mixed>
     */
    public static function applyAssignmentConfigToSpec(array $spec, ?array $inputConfig): array
    {
        if (! is_array($inputConfig) || $inputConfig === []) {
            return $spec;
        }

        if (array_key_exists('options', $inputConfig)) {
            $spec['options'] = $inputConfig['options'];
        }
        if (! empty($inputConfig['dropdown'])) {
            $spec['dropdown'] = $spec['dropdown'] ?? $inputConfig['dropdown'];
        }
        if (! empty($inputConfig['help_text'])) {
            $spec['help_text'] = $inputConfig['help_text'];
        }
        if (array_key_exists('default_value', $inputConfig) && $inputConfig['default_value'] !== null && $inputConfig['default_value'] !== '') {
            $spec['default'] = $inputConfig['default_value'];
        }
        if (! empty($inputConfig['max_length'])) {
            $spec['maxlength'] = (int) $inputConfig['max_length'];
        }
        if (! empty($inputConfig['rows'])) {
            $spec['rows'] = (int) $inputConfig['rows'];
        }
        if (! empty($inputConfig['placeholder'])) {
            $spec['placeholder'] = $inputConfig['placeholder'];
        }
        if (! empty($inputConfig['input_format'])) {
            $spec['input_format'] = $inputConfig['input_format'];
        }
        if (array_key_exists('default_checked', $inputConfig)) {
            $spec['default_checked'] = (bool) $inputConfig['default_checked'];
        }
        foreach (['min', 'max', 'step', 'decimals', 'format'] as $key) {
            if (isset($inputConfig[$key]) && $inputConfig[$key] !== '') {
                $spec[$key] = $inputConfig[$key];
            }
        }

        return $spec;
    }

    /**
     * Type config plus help/privacy/default values stored on a fixed-field assignment.
     *
     * @return array<string, mixed>
     */
    public static function assignmentInputConfigFromRequest(\Illuminate\Http\Request $request, ?string $inputType): array
    {
        $meta = self::formMetaFromRequest($request);
        $sanitized = [];
        $typeMeta = $inputType ? (self::dataTypes()[$inputType] ?? null) : null;
        foreach (($typeMeta['config'] ?? []) as $cfg) {
            $key = $cfg['key'] ?? null;
            if (! $key || ! array_key_exists($key, $meta['config'])) {
                continue;
            }
            $value = $meta['config'][$key];
            if (is_string($value)) {
                $value = trim($value);
            }
            $sanitized[$key] = $value;
        }

        $sanitized['help_text'] = $meta['help_text'];
        $sanitized['default_value'] = $meta['default_value'];
        $sanitized['input_format'] = $meta['input_format'];
        $sanitized['data_privacy'] = $meta['data_privacy'];
        $sanitized['prevent_duplicate_values'] = $meta['prevent_duplicate_values'] ? 1 : 0;

        return $sanitized;
    }

    public function category()
    {
        return $this->belongsTo(BikeCategory::class, 'category_id', 'id');
    }

    public static function bootstrapFieldCategories(): void
    {
        app(\App\Services\Bike\BikeDefaultCategoryService::class)->bootstrap();
    }

    /**
     * Columns that are not allowed for assigning via Bike Fields settings.
     */
    public static function removedBikeColumns(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'created_by',
            'updated_by',
            'deleted_by',
            'custom_field_values',
            // Hidden from bike settings + bike add/edit form by requirement.
            'company_id',
            'current_km',
            'maintanence_km',
            'maintenance_km',
            'previous_km',
            'customer_id',
            'rider_id',
            'bike_owner',
            'warehouse',
            'rental_company_id',
            'leased_return_company_id',
            'leased_return_by',
            'leased_return_date',
            'bike_top_option_id',
        ];
    }

    public static function humanizeFieldKey(string $key): string
    {
        return ModuleFieldSource::humanizeFieldKey($key);
    }

    /**
     * @return array<string, string>
     */
    public static function bikeOwnerSelectOptions(): array
    {
        return [
            '' => 'Select',
            'Owned' => 'Owned',
            'Leased' => 'Leased',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function emiratesHubSelectOptions(): array
    {
        return [
            '' => 'Select',
            'DXB' => 'DXB',
            'AUH' => 'AUH',
            'UAQ' => 'UAQ',
            'RAK' => 'RAK',
            'SHJ' => 'SHJ',
            'FUJ' => 'FUJ',
            'AJM' => 'AJM',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function bikeOwnerFieldSpec(): array
    {
        return [
            'type' => 'select',
            'options' => "Owned\nLeased",
            'required' => true,
        ];
    }

    public static function isAlwaysRequiredFixedField(string $fieldKey): bool
    {
        // Ownership is chosen via the company select ("own" vs leasing company).
        return $fieldKey === 'company';
    }

    /**
     * Merge bike_field_category_assignment overrides into the default fixed-field spec
     * (same rules as resources/views/bikes/_form_field.blade.php).
     */
    public static function resolvedFixedFieldSpec(string $fieldKey): array
    {
        $specs = self::fixedFieldInputSpecs();
        $spec = ModuleFieldSource::mergeFixedFieldSpec($fieldKey, $specs[$fieldKey] ?? null);

        $assignment = BikeFieldCategoryAssignment::where('field_key', $fieldKey)->first();
        if ($assignment) {
            if (!empty($assignment->input_type)) {
                $spec['type'] = $assignment->input_type === 'dropdown' ? 'select' : $assignment->input_type;
            }
            $spec = self::applyAssignmentConfigToSpec($spec, is_array($assignment->input_config) ? $assignment->input_config : null);
        }

        if ($fieldKey === 'bike_owner') {
            return array_merge($spec, self::bikeOwnerFieldSpec());
        }

        return $spec;
    }

    public static function isSelectPlaceholderLabel(?string $label): bool
    {
        $lower = mb_strtolower(trim((string) $label));
        if ($lower === '') {
            return true;
        }

        return in_array($lower, [
            'select',
            'select model',
            'select warehouse',
            'select recruiter',
            'all',
            'choose your option',
            'choose an option',
            'select value',
            'select...',
            'please select',
        ], true);
    }

    /**
     * Drop empty / "Select" / "All" entries so the form can use a single placeholder.
     *
     * @param  array<int|string, mixed>  $opts
     * @return array<int|string, string>
     */
    public static function withoutPlaceholderSelectOptions(array $opts): array
    {
        $clean = [];
        foreach ($opts as $value => $label) {
            if ($value === null || $value === '') {
                continue;
            }
            $labelStr = trim((string) $label);
            if (self::isSelectPlaceholderLabel($labelStr)) {
                continue;
            }
            $clean[$value] = $labelStr;
        }

        return $clean;
    }

    public static function selectPlaceholderLabel(?string $fieldLabel): string
    {
        $fieldLabel = trim((string) $fieldLabel);

        return $fieldLabel !== '' ? ('Select ' . $fieldLabel) : 'Select';
    }

    /**
     * Select options for a fixed bikes column when it renders as a select on the bike form:
     * explicit newline options, or related-table / Common::Dropdowns sources (same as _form_field.blade.php).
     *
     * @return list<array{value: string, label: string}>
     */
    public static function fixedFieldSelectChoices(string $fieldKey): array
    {
        $spec = self::resolvedFixedFieldSpec($fieldKey);
        if (($spec['type'] ?? 'text') !== 'select') {
            return [];
        }

        if ($fieldKey === 'bike_owner') {
            $choices = [];
            foreach (self::bikeOwnerSelectOptions() as $value => $label) {
                if ($value === '') {
                    continue;
                }
                $choices[] = ['value' => (string) $value, 'label' => (string) $label];
            }

            return $choices;
        }

        $rawOptions = $spec['options'] ?? null;
        $parsedOptions = [];
        if ($rawOptions !== null && $rawOptions !== '') {
            $lines = is_array($rawOptions) ? $rawOptions : preg_split('/\r\n|\r|\n/', (string) $rawOptions);
            foreach ($lines as $line) {
                $line = trim((string) $line);
                if ($line !== '') {
                    $parsedOptions[] = $line;
                }
            }
        }

        if ($parsedOptions !== []) {
            $choices = [];
            foreach ($parsedOptions as $opt) {
                $choices[] = ['value' => $opt, 'label' => $opt];
            }

            return $choices;
        }

        $dropdownKey = $spec['dropdown'] ?? null;
        $opts = [];

        switch ($dropdownKey) {
            case 'vehicle_models':
                $opts = \App\Support\CompanyQuery::table('vehicle_models')->where('status', 1)->pluck('name', 'id')->toArray();
                $opts = ['' => 'Select Model'] + $opts;
                break;
            case 'branch':
                $opts = Branch::dropdown();
                break;
            case 'leasing_companies':
                $opts = $fieldKey === 'company'
                    ? LeasingCompanies::dropdownWithOwnOption()
                    : LeasingCompanies::dropdown();
                break;
            case 'customers':
                $opts = Customers::pluck('name', 'id')->prepend('Select', '')->toArray();
                break;
            case 'riders':
                $opts = Riders::dropdown();
                break;
            case 'warehouse':
                $opts = [
                    '' => 'Select Warehouse',
                    'Active' => 'Active',
                    'Return' => 'Return',
                    'Vacation' => 'Vacation',
                    'Express Garage' => 'Express Garage',
                    'Absconded' => 'Absconded',
                ];
                break;
            case 'emirates-hub':
                $opts = Common::Dropdowns('emirates-hub');
                if (empty($opts)) {
                    $opts = self::emiratesHubSelectOptions();
                } else {
                    $opts = ['' => 'Select'] + $opts;
                }
                break;
            default:
                $opts = !empty($dropdownKey) ? Common::Dropdowns($dropdownKey) : [];
                if (empty($opts) && !empty($dropdownKey)) {
                    $opts = ['' => 'Select'] + (array) Common::Dropdowns($dropdownKey);
                }
                if (empty($opts)) {
                    $opts = ['' => 'Select'];
                }
                break;
        }

        $choices = [];
        foreach (self::withoutPlaceholderSelectOptions($opts) as $value => $label) {
            $choices[] = ['value' => (string) $value, 'label' => $label];
        }

        return $choices;
    }

    /**
     * Human-readable label for a stored bikes column value (e.g. FK id → company name).
     * Uses the same select sources as fixedFieldSelectChoices(); falls back to the raw stored value.
     */
    public static function displayLabelForFixedFieldValue(?string $fieldKey, string $storedValue): string
    {
        $trimmed = trim($storedValue);
        if ($trimmed === '' || $fieldKey === null || $fieldKey === '') {
            return $storedValue;
        }

        static $valueToLabelByField = [];
        if (!array_key_exists($fieldKey, $valueToLabelByField)) {
            $map = [];
            foreach (self::fixedFieldSelectChoices($fieldKey) as $row) {
                $map[(string) ($row['value'] ?? '')] = (string) ($row['label'] ?? $row['value'] ?? '');
            }
            $valueToLabelByField[$fieldKey] = $map;
        }

        $map = $valueToLabelByField[$fieldKey];
        if ($map === []) {
            return $storedValue;
        }

        return $map[$trimmed] ?? $storedValue;
    }

    public static function fixedFieldInputSpecs(): array
    {
        return [
            'vehicle_type' => ['type' => 'select', 'dropdown' => 'vehicle_models'],
            'branch_id' => ['type' => 'select', 'dropdown' => 'branch'],
            'company' => ['type' => 'select', 'dropdown' => 'leasing_companies'],
            'bike_owner' => self::bikeOwnerFieldSpec(),
            'rider_id' => ['type' => 'select', 'dropdown' => 'riders'],
            'customer_id' => ['type' => 'select', 'dropdown' => 'customers'],
            'warehouse' => ['type' => 'select', 'dropdown' => 'warehouse'],
            'status' => ['type' => 'checkbox'],

            // Central app uses a dropdown stored in `dropdowns` table under this key.
            'emirates' => ['type' => 'select', 'dropdown' => 'emirates-hub'],

            'registration_date' => ['type' => 'date'],
            'expiry_date' => ['type' => 'date'],
            'insurance_expiry' => ['type' => 'date'],
            'leased_date' => ['type' => 'date'],

            'notes' => ['type' => 'textarea', 'rows' => 3],
        ];
    }

    /**
     * Legacy slug → field keys map (settings / migrations). Bike create/edit forms
     * use bike_field_category_assignments only — not this map.
     */
    public static function fixedFieldsSlugMap(): array
    {
        return [
            'bike_info' => [
                'plate',
                'bike_code',
                'chassis_number',
                'engine',
                'vehicle_type',
                'model',
                'model_type',
                'color',
                'emirates',
                'branch_id',
                'company',
                'bike_owner',
                'rider_id',
                'warehouse',
                'traffic_file_number',
                'registration_date',
                'expiry_date',
                'notes',
                'status',
            ],
            'insurance_info' => [
                'insurance_expiry',
                'insurance_co',
                'policy_no',
            ],
            'documents_info' => [
                'leased_date',
            ],
            'other' => [],
        ];
    }

    /**
     * Flat list of assignable bike table columns.
     */
    public static function allFixedFieldKeys(): array
    {
        if (!Schema::hasTable('bikes')) {
            return [];
        }

        $columns = Schema::getColumnListing('bikes');
        $excluded = array_flip(array_merge(
            ['deleted_at'], // soft delete column
            self::removedBikeColumns()
        ));

        $keys = array_values(array_filter($columns, function ($col) use ($excluded) {
            return !isset($excluded[$col]);
        }));

        // Keep stable order
        sort($keys);
        return $keys;
    }

    /**
     * Build fields grouped by category for the Bike create/edit form.
     * Returns items: kind=fixed|custom.
     *
     * Every configured custom field is returned; per-user visibility is decided by the renderer
     * through Role Field Permissions, so filtering here would make granted fields unreachable.
     */
    public static function fieldsByCategoryForForm(): array
    {
        $categories = BikeCategory::orderBy('display_order')->orderBy('id')->get();
        $categoryIds = $categories->pluck('id')->all();
        $allowedFixedLookup = array_flip(self::allFixedFieldKeys());

        // Fixed field assignments for all categories.
        $assignmentsAll = BikeFieldCategoryAssignment::query()
            ->whereIn('category_id', $categoryIds)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $assignmentsVisible = $assignmentsAll;
        $specs = self::fixedFieldInputSpecs();

        $assignOnlyIds = self::assignOnlyCustomFieldIds();
        $customFieldsQuery = self::query()
            ->whereIn('category_id', $categoryIds)
            ->when($assignOnlyIds !== [], fn($q) => $q->whereNotIn('id', $assignOnlyIds));

        // Visibility/required are enforced per user via Role Field Permissions at render time.

        $customFieldsAll = $customFieldsQuery
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->groupBy('category_id');

        $result = [];

        foreach ($categories as $cat) {
            $fields = [];

            // Fixed fields: only explicit category assignments (no slug-map fallback).
            $catFixedAssignments = $assignmentsVisible->where('category_id', $cat->id)->values();
            foreach ($catFixedAssignments as $a) {
                $fieldKey = (string) $a->field_key;
                if (!isset($allowedFixedLookup[$fieldKey])) {
                    continue;
                }
                $label = !empty($a->display_label)
                    ? trim((string) $a->display_label)
                    : self::humanizeFieldKey($fieldKey);

                $spec = ModuleFieldSource::mergeFixedFieldSpec($fieldKey, $specs[$fieldKey] ?? null);

                // Settings can override fixed input type + config.
                if (!empty($a->input_type)) {
                    $spec['type'] = $a->input_type === 'dropdown' ? 'select' : $a->input_type;
                }

                $spec['required'] = self::isAlwaysRequiredFixedField($fieldKey);
                $spec = self::applyAssignmentConfigToSpec($spec, is_array($a->input_config) ? $a->input_config : null);

                if ($fieldKey === 'bike_owner') {
                    $spec = array_merge($spec, self::bikeOwnerFieldSpec());
                }

                $fields[] = (object) [
                    'kind' => 'fixed',
                    'field_key' => $fieldKey,
                    'label' => $label,
                    'spec' => $spec,
                ];
            }

            foreach (($customFieldsAll[$cat->id] ?? collect()) as $cf) {
                $fields[] = (object) [
                    'kind' => 'custom',
                    'field' => $cf,
                ];
            }

            if ($fields === []) {
                continue;
            }

            $result[] = (object) [
                'category' => $cat,
                'fields' => $fields,
            ];
        }

        return $result;
    }

    /**
     * Built-in assign-modal fields (not all are bikes table columns).
     */
    public static function defaultAssignFieldCatalog(): array
    {
        return [
            ['field_key' => 'warehouse', 'kind' => 'virtual', 'display_label' => 'Status', 'input_type' => 'text', 'display_order' => 0, 'show_on_active' => true, 'show_on_change' => true, 'is_required' => true],
            ['field_key' => 'assign_type', 'kind' => 'virtual', 'display_label' => 'Assign To', 'input_type' => 'select', 'display_order' => 1, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => true, 'input_config' => ['assign_options' => ['rider' => 'Rider', 'rental' => 'Rental customer', 'garage' => 'Garage customer']]],
            ['field_key' => 'rider_id', 'kind' => 'virtual', 'display_label' => 'Rider', 'input_type' => 'select', 'display_order' => 2, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => false, 'input_config' => ['assign_group' => 'rider']],
            ['field_key' => 'rental_company_id', 'kind' => 'virtual', 'display_label' => 'Rental customer', 'input_type' => 'select', 'display_order' => 3, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => false, 'input_config' => ['assign_group' => 'rental']],
            ['field_key' => 'designation', 'kind' => 'virtual', 'display_label' => 'Designation', 'input_type' => 'text', 'display_order' => 4, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => false, 'input_config' => ['assign_group' => 'rider', 'readonly' => true]],
            ['field_key' => 'customer_id', 'kind' => 'virtual', 'display_label' => 'Project', 'input_type' => 'select', 'display_order' => 5, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => false, 'input_config' => ['assign_group' => 'rider']],
            ['field_key' => 'note_date', 'kind' => 'virtual', 'display_label' => 'Date', 'input_type' => 'date', 'display_order' => 6, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => true],
            ['field_key' => 'return_date', 'kind' => 'virtual', 'display_label' => 'Date', 'input_type' => 'date', 'display_order' => 7, 'show_on_active' => false, 'show_on_change' => true, 'is_required' => true],
            ['field_key' => 'visa_sponsor', 'kind' => 'virtual', 'display_label' => 'Visa Sponsor', 'input_type' => 'text', 'display_order' => 8, 'show_on_active' => false, 'show_on_change' => true, 'is_required' => false, 'input_config' => ['readonly' => true]],
            ['field_key' => 'notes', 'kind' => 'virtual', 'display_label' => 'Notes', 'input_type' => 'textarea', 'display_order' => 9, 'show_on_active' => true, 'show_on_change' => true, 'is_required' => false],
        ];
    }

    /**
     * Custom field IDs reserved for assign modals (not Bike add/edit form or Bike Fields settings).
     *
     * @return list<int>
     */
    public static function assignOnlyCustomFieldIds(): array
    {
        if (!Schema::hasTable('bike_assign_field_assignments')) {
            return [];
        }

        return BikeAssignFieldAssignment::query()
            ->whereNotNull('custom_field_id')
            ->pluck('custom_field_id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();
    }

    public static function syncBikeAssignFieldAssignments(): void
    {
        if (!Schema::hasTable('bike_assign_field_assignments')) {
            return;
        }

        foreach (self::defaultAssignFieldCatalog() as $def) {
            $key = $def['field_key'];
            $companyId = \App\Support\CompanyContext::id();
            $lookup = ['field_key' => $key];
            $attributes = [
                'kind' => $def['kind'],
                'display_label' => $def['display_label'],
                'input_type' => $def['input_type'] ?? null,
                'input_config' => $def['input_config'] ?? null,
                'display_order' => $def['display_order'] ?? 0,
                'is_visible' => true,
                'is_required' => $def['is_required'] ?? false,
                'show_on_active' => $def['show_on_active'] ?? false,
                'show_on_change' => $def['show_on_change'] ?? false,
            ];
            if ($companyId !== null && Schema::hasColumn((new BikeAssignFieldAssignment())->getTable(), 'company_id')) {
                $lookup['company_id'] = $companyId;
                $attributes['company_id'] = $companyId;
            }

            BikeAssignFieldAssignment::query()->firstOrCreate($lookup, $attributes);
        }

        $assignType = BikeAssignFieldAssignment::query()->where('field_key', 'assign_type')->first();
        if ($assignType) {
            $config = is_array($assignType->input_config) ? $assignType->input_config : [];
            $options = $config['assign_options'] ?? [];
            if (isset($options['company']) || ! isset($options['rental'])) {
                $config['assign_options'] = [
                    'rider' => 'Rider',
                    'rental' => 'Rental customer',
                    'garage' => 'Garage customer',
                ];
                $assignType->input_config = $config;
                $assignType->save();
            }
        }

        BikeAssignFieldAssignment::query()
            ->where('field_key', 'rental_company_id')
            ->where(function ($q) {
                $q->whereNull('display_label')->orWhere('display_label', 'Company');
            })
            ->update(['display_label' => 'Rental customer']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, BikeAssignFieldAssignment>
     */
    public static function assignModalFields(string $context): \Illuminate\Support\Collection
    {
        if (!Schema::hasTable('bike_assign_field_assignments')) {
            return collect();
        }

        self::syncBikeAssignFieldAssignments();

        $query = BikeAssignFieldAssignment::query()
            ->with('customField')
            ->orderBy('display_order')
            ->orderBy('id');

        if ($context === 'change') {
            $query->where('show_on_change', true);
        } else {
            $query->where('show_on_active', true);
        }

        return $query->get();
    }
}
