<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class EmployeeCustomField extends BaseModel
{
    public static function bootstrapFieldCategories(): void
    {
        $columns = array_flip(Schema::getColumnListing('employees'));
        $fieldKeys = array_values(array_filter(
            self::allFixedFieldKeys(),
            fn (string $fieldKey) => isset($columns[$fieldKey]) && ! in_array($fieldKey, self::removedEmployeeColumns(), true)
        ));

        \App\Services\Settings\FixedFieldCategoryAssignmentSync::sync(
            $fieldKeys,
            self::fixedFieldsSlugMap(),
            EmployeeFieldCategoryAssignment::class,
            fn () => self::scopedEmployeeCategoriesQuery(),
            'other',
            null,
        );

        $otherCategoryId = (int) (self::scopedEmployeeCategoriesQuery()->where('slug', 'other')->value('id') ?? 0);
        if ($otherCategoryId > 0) {
            \App\Services\Settings\FixedFieldCategoryAssignmentSync::assignCustomFieldsWithoutCategory(
                self::class,
                $otherCategoryId,
            );
        }
    }

    private static function scopedEmployeeCategoriesQuery()
    {
        return EmployeeCategory::query();
    }

    private static function removedEmployeeColumns(): array
    {
        return [
            'account_id',
            'created_by',
            'updated_by',
            'deleted_at',
        ];
    }
    protected $table = 'employee_custom_fields';

    protected $fillable = [
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
        'is_mandatory' => 'boolean',
        'is_visible' => 'boolean',
        'prevent_duplicate_values' => 'boolean',
        'data_privacy' => 'array',
        'config' => 'array',
    ];

    /**
     * Data types supported for rider custom fields (same as account/voucher custom fields).
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
        return $this->belongsTo(EmployeeCategory::class, 'category_id', 'id');
    }

    /** Slug-to-field-keys map for fixed rider fields (defaults; used for seeding and fallback). */
    public static function fixedFieldsSlugMap(): array
    {
        $map = [
            'employee_info' => [
                'name',
                'employee_id',
                'company_email',
                'personal_email',
                'personal_contact',
                'company_contact',
                'emergency_contact',
                'dob',
                'address',
                'profile_image',
            ],
            'visa_info' => [
                'emirate_id',
                'emirate_expiry',
                'passport',
                'passport_expiry',
                'visa_sponsor',
                'visa_occupation',
                'visa_expiry',
            ],
            'employment_info' => [
                'nationality_id',
                'department_id',
                'designation',
                'salary',
                'branch_id',
                'doj',
                'status',
            ],
            'additional_info' => [
                'notes',
                'custom_field_values',
            ],
            'other' => [],
        ];

        $removed = array_flip(self::removedEmployeeColumns());
        foreach ($map as $slug => $keys) {
            $map[$slug] = array_values(array_filter($keys, fn($k) => !isset($removed[$k])));
        }

        return $map;
    }

    /** All fixed rider field keys (flat list from slug map). */
    public static function allFixedFieldKeys(): array
    {
        $keys = [];
        foreach (self::fixedFieldsSlugMap() as $slugKeys) {
            foreach ($slugKeys as $key) {
                $keys[] = $key;
            }
        }
        // Include all existing employee table columns so Settings -> Rider Fields
        // always reflects real DB fields (except removed/system columns).
        $columns = Schema::getColumnListing('employees');
        $excluded = array_flip(array_merge(
            ['id', 'created_at', 'updated_at', 'deleted_at'],
            self::removedEmployeeColumns()
        ));
        foreach ($columns as $column) {
            if (isset($excluded[$column])) {
                continue;
            }
            $keys[] = $column;
        }
        // Keep these employee table fields visible in Employee Settings field list.
        foreach (['custom_field_values', 'status'] as $mustHaveKey) {
            if (in_array($mustHaveKey, $columns, true)) {
                $keys[] = $mustHaveKey;
            }
        }
        return array_values(array_unique($keys));
    }

    /**
     * Fixed rider fields grouped by category (from employee_field_category_assignments; fallback to slug map if empty).
     * Returns list of [ 'id' => categoryId, 'label' => ..., 'fields' => [...] ].
     */
    public static function fixedEmployeeFieldsByCategory(): array
    {
        $categories = self::scopedEmployeeCategoriesQuery()->orderBy('display_order')->orderBy('id')->get();
        $assignments = EmployeeFieldCategoryAssignment::with('category')->orderBy('display_order')->orderBy('id')->get()->groupBy('category_id');

        $result = [];
        foreach ($categories as $cat) {
            $fields = [];
            foreach ($assignments->get($cat->id, collect()) as $a) {
                $fields[] = [
                    'key' => $a->field_key,
                    'label' => self::humanizeFieldKey($a->field_key),
                ];
            }
            if (count($fields) === 0) {
                $map = self::fixedFieldsSlugMap();
                foreach ($map[$cat->slug] ?? [] as $fieldKey) {
                    $fields[] = [
                        'key' => $fieldKey,
                        'label' => self::humanizeFieldKey($fieldKey),
                    ];
                }
            }
            $result[] = [
                'id' => $cat->id,
                'label' => $cat->label,
                'fields' => $fields,
            ];
        }

        return $result;
    }

    public static function humanizeFieldKey(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }

    /**
     * Input spec per fixed rider field for the rider form (type, dropdown, required, etc.).
     */
    public static function fixedFieldInputSpecs(): array
    {
        $specs = [
            'employee_id' => ['type' => 'text'],
            'name' => ['type' => 'text', 'maxlength' => 191],
            'company_email' => ['type' => 'email'],
            'personal_email' => ['type' => 'email'],
            'personal_contact' => ['type' => 'tel', 'maxlength' => 20],
            'company_contact' => ['type' => 'tel', 'maxlength' => 20],
            'emergency_contact' => ['type' => 'tel', 'maxlength' => 20],
            'dob' => ['type' => 'date'],
            'doj' => ['type' => 'date'],
            'address' => ['type' => 'textarea', 'rows' => 3],
            'profile_image' => ['type' => 'text'],
            'nationality_id' => ['type' => 'select', 'dropdown' => 'countries'],
            'department_id' => ['type' => 'select', 'dropdown' => 'departments'],
            'designation' => ['type' => 'text', 'maxlength' => 100],
            'salary' => ['type' => 'number', 'step' => 0.01],
            'branch_id' => ['type' => 'select', 'dropdown' => 'branch'],
            'status' => ['type' => 'dropdown', 'options' => ['active', 'inactive', 'on_leave']],
            'emirate_id' => ['type' => 'text', 'maxlength' => 18],
            'emirate_expiry' => ['type' => 'date'],
            'passport' => ['type' => 'text', 'maxlength' => 50],
            'passport_expiry' => ['type' => 'date'],
            'visa_sponsor' => ['type' => 'text', 'maxlength' => 100],
            'visa_occupation' => ['type' => 'text', 'maxlength' => 100],
            'visa_expiry' => ['type' => 'date'],
            'notes' => ['type' => 'textarea', 'rows' => 3],
            'custom_field_values' => ['type' => 'textarea', 'rows' => 2],
        ];

        foreach (self::removedEmployeeColumns() as $removedKey) {
            unset($specs[$removedKey]);
        }

        return $specs;
    }

    /**
     * Build fields by category for the rider create/edit form: fixed fields (with display_label and order from assignments)
     * plus optional custom fields per category. Each item: kind 'fixed'|'custom', and fixed has field_key, label, spec; custom has field (model).
     *
     * @param  bool  $includeCustomFields  When false (default), only schema-backed (fixed) fields are returned.
     */
    public static function fieldsByCategoryForForm(bool $includeCustomFields = false): array
    {
        self::bootstrapFieldCategories();

        $categories = EmployeeCategory::orderBy('display_order')->orderBy('id')->get();
        $categoryIds = $categories->pluck('id')->all();
        $allowedFixedLookup = array_flip(self::allFixedFieldKeys());
        $fallbackMap = self::fixedFieldsSlugMap();
        $assignmentsAll = EmployeeFieldCategoryAssignment::with('category')
            ->whereIn('category_id', $categoryIds)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $assignmentsVisible = $assignmentsAll->filter(function ($a) {
            $rawVisible = $a->getRawOriginal('is_visible');
            return $rawVisible === null || (int) $rawVisible === 1;
        })->values();
        $customFieldsAll = self::with('category')
            ->whereIn('category_id', $categoryIds)
            ->where(function ($q) {
                $q->where('is_visible', true)->orWhereNull('is_visible');
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $specs = self::fixedFieldInputSpecs();

        $result = [];
        foreach ($categories as $cat) {
            $fields = [];
            $categoryAssignments = $assignmentsVisible->where('category_id', $cat->id)->values();

            if ($categoryAssignments->isNotEmpty()) {
                foreach ($categoryAssignments as $a) {
                    if (!isset($allowedFixedLookup[$a->field_key])) {
                        continue;
                    }
                    $label = $a->display_label !== null && trim((string) $a->display_label) !== ''
                        ? trim($a->display_label)
                        : self::humanizeFieldKey($a->field_key);
                    $spec = $specs[$a->field_key] ?? ['type' => 'text'];

                    // Employee Settings can override a fixed field's input type + config (e.g. dropdown options).
                    // The employee module renderer reads from "$spec", so we merge relevant config here.
                    if (!empty($a->input_type)) {
                        // The renderer expects HTML-ish types: dropdown -> select, checkbox stays checkbox.
                        $spec['type'] = $a->input_type === 'dropdown' ? 'select' : $a->input_type;
                    }
                    if (is_array($a->input_config) && array_key_exists('options', $a->input_config)) {
                        $spec['options'] = $a->input_config['options'];
                    }
                    if (array_key_exists('is_required', $a->getAttributes())) {
                        $rawRequired = $a->getRawOriginal('is_required');
                        if ($rawRequired !== null) {
                            $spec['required'] = (int) $rawRequired === 1;
                        }
                    }

                    $fields[] = (object) [
                        'kind' => 'fixed',
                        'field_key' => $a->field_key,
                        'label' => $label,
                        'spec' => $spec,
                    ];
                }
            } else {
                // If no visible category assignments exist, fall back to built-in fixed fields map.
                foreach ($fallbackMap[$cat->slug] ?? [] as $fieldKey) {
                    if (!isset($allowedFixedLookup[$fieldKey])) {
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

            if ($includeCustomFields) {
                foreach ($customFieldsAll->where('category_id', $cat->id)->values() as $cf) {
                    $fields[] = (object) [
                        'kind' => 'custom',
                        'field' => $cf,
                    ];
                }
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
}
