<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class BikeCustomField extends BaseModel
{
    protected $table = 'bike_custom_fields';

    protected $fillable = [
        'label',
        'help_text',
        'data_privacy',
        'prevent_duplicate_values',
        'default_value',
        'input_format',
        'data_type',
        'is_mandatory',
        'config',
        'category_id',
        'display_order',
    ];

    protected $casts = [
        'data_privacy' => 'array',
        'config' => 'array',
        'prevent_duplicate_values' => 'boolean',
        'is_mandatory' => 'boolean',
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

    public function category()
    {
        return $this->belongsTo(BikeCategory::class, 'category_id', 'id');
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
        ];
    }

    public static function humanizeFieldKey(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }

    /**
     * Input spec per fixed bike field (defaults for the bike form renderer).
     * Unknown keys gracefully fall back to a basic text input.
     */
    public static function fixedFieldInputSpecs(): array
    {
        return [
            'vehicle_type' => ['type' => 'select', 'dropdown' => 'vehicle_models'],
            'branch_id' => ['type' => 'select', 'dropdown' => 'branch'],
            'company' => ['type' => 'select', 'dropdown' => 'leasing_companies'],
            'rider_id' => ['type' => 'select', 'dropdown' => 'riders'],
            'customer_id' => ['type' => 'select', 'dropdown' => 'customers'],
            'warehouse' => ['type' => 'select', 'dropdown' => 'warehouse'],
            'status' => ['type' => 'checkbox'],

            // Central app uses a dropdown stored in `dropdowns` table under this key.
            'emirates' => ['type' => 'select', 'dropdown' => 'emirates-hub'],

            'registration_date' => ['type' => 'date'],
            'expiry_date' => ['type' => 'date'],
            'insurance_expiry' => ['type' => 'date'],

            'notes' => ['type' => 'textarea', 'rows' => 3],
        ];
    }

    /**
     * Fixed bike fields grouped by category (used by UI fallback / legacy display).
     * Primarily used when there are no assignment rows yet.
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
                'contract_number',
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
     */
    public static function fieldsByCategoryForForm(): array
    {
        $categories = BikeCategory::orderBy('display_order')->orderBy('id')->get();
        $categoryIds = $categories->pluck('id')->all();

        // Fixed field assignments for all categories.
        $assignmentsAll = BikeFieldCategoryAssignment::query()
            ->whereIn('category_id', $categoryIds)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $assignmentsVisible = $assignmentsAll->filter(function ($a) {
            $rawVisible = $a->getRawOriginal('is_visible');
            return $rawVisible === null || (int) $rawVisible === 1;
        })->values();

        $specs = self::fixedFieldInputSpecs();

        $customFieldsAll = self::query()
            ->whereIn('category_id', $categoryIds)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->groupBy('category_id');

        $result = [];

        foreach ($categories as $cat) {
            $fields = [];

            // Fixed fields
            $catFixedAssignments = $assignmentsVisible->where('category_id', $cat->id)->values();
            if ($catFixedAssignments->isNotEmpty()) {
                foreach ($catFixedAssignments as $a) {
                    $fieldKey = (string) $a->field_key;
                    $label = !empty($a->display_label)
                        ? trim((string) $a->display_label)
                        : self::humanizeFieldKey($fieldKey);

                    $spec = $specs[$fieldKey] ?? ['type' => 'text'];

                    // Settings can override fixed input type + config.
                    if (!empty($a->input_type)) {
                        $spec['type'] = $a->input_type === 'dropdown' ? 'select' : $a->input_type;
                    }

                    if (is_array($a->input_config) && array_key_exists('options', $a->input_config)) {
                        $spec['options'] = $a->input_config['options'];
                    }

                    $spec['dropdown'] = $spec['dropdown'] ?? $a->input_config['dropdown'] ?? null;

                    $fields[] = (object) [
                        'kind' => 'fixed',
                        'field_key' => $fieldKey,
                        'label' => $label,
                        'spec' => $spec,
                    ];
                }
            } else {
                // If there are no fixed assignments for this category, fall back to slug map.
                foreach (self::fixedFieldsSlugMap()[$cat->slug] ?? [] as $fieldKey) {
                    if (!in_array($fieldKey, self::allFixedFieldKeys(), true)) {
                        continue;
                    }
                    $fields[] = (object) [
                        'kind' => 'fixed',
                        'field_key' => $fieldKey,
                        'label' => self::humanizeFieldKey($fieldKey),
                        'spec' => $specs[$fieldKey] ?? ['type' => 'text'],
                    ];
                }
            }

            // Custom fields for this category
            foreach (($customFieldsAll[$cat->id] ?? collect()) as $cf) {
                $fields[] = (object) [
                    'kind' => 'custom',
                    'field' => $cf,
                ];
            }

            $result[] = (object) [
                'category' => $cat,
                'fields' => $fields,
            ];
        }

        return $result;
    }
}

