<?php

namespace App\Models;

use App\Support\ModuleFieldSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class RiderCustomField extends BaseModel
{
    public static function bootstrapFieldCategories(): void
    {
        app(\App\Services\Rider\RiderDefaultCategoryService::class)->bootstrap();
    }

    private static function scopedRiderCategoriesQuery()
    {
        return RiderCategory::query();
    }

    /**
     * Legacy rider columns removed from the schema (never shown in settings or forms).
     *
     * @return list<string>
     */
    public static function removedRiderColumns(): array
    {
        return [
            'company_id',
            'account_id',
            'courier_id',
            'personal_contact',
            'company_contact',
            'NFDID',
            'cdm_deposit_id',
            'emirate_hub',
            'mashreq_id',
            'PID',
            'DEPT',
            'visa_status',
            'branded_plate_no',
            'vaccine_status',
            'attach_documents',
            'other_details',
            'VID',
            'visa_sponser',
            'visa_sponsor',
            'visa_occupation',
            'TAID',
            'passport_handover',
            'noon_no',
            'c3_card',
            'contract',
            'designation',
            'rider_status_option',
            'salary_model',
            'rider_reference',
            'job_status',
            'insurance',
            'insurance_expiry',
            'policy_no',
            'shift',
            'vat',
            'attendance_date',
            'created_by',
            'updated_by',
            'deleted_by',
            'mol',
            'recuriter',
            'walker',
            'vacation',
            'cancel',
            'pro',
            'absconder',
            'flowup',
            'l_license',
        ];
    }

    /**
     * Columns that remain in the DB for system/other modules but are not rider form fields.
     *
     * @return list<string>
     */
    public static function hiddenRiderColumns(): array
    {
        return [
            'custom_field_values',
            'rider_top_option_id',
            'display_status',
            'status',
            'rider_status',
            'image_name',
            'attendance',
            'customer_id',
        ];
    }

    /**
     * @return list<string>
     */
    public static function excludedFromFieldSettings(): array
    {
        return array_values(array_unique(array_merge(
            ['id', 'created_at', 'updated_at', 'deleted_at'],
            self::removedRiderColumns(),
            self::hiddenRiderColumns(),
        )));
    }
    protected $table = 'rider_custom_fields';

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
        return $this->belongsTo(RiderCategory::class, 'category_id', 'id');
    }

    /**
     * Essential rider fields grouped by category (matches resources/views/riders/fields/*).
     *
     * @return array<string, list<string>>
     */
    public static function fixedFieldsSlugMap(): array
    {
        return [
            'rider_info' => [
                'rider_id',
                'name',
                'doj',
                'email',
                'nationality',
                'passport',
                'passport_expiry',
                'ethnicity',
                'dob',
            ],
            'visa_info' => [
                'license_no',
                'license_expiry',
                'road_permit',
                'road_permit_expiry',
            ],
            'job_info' => [
                'emirate_id',
                'emirate_exp',
                'fleet_supervisor',
                'recruiter_id',
                'branch_id',
            ],
            'labor_info' => [
                'person_code',
                'labor_card_number',
                'labor_card_expiry',
                'wps',
            ],
            'additional_info' => [],
            'other' => [],
        ];
    }

    /**
     * Assignable fixed field keys for Rider Settings and rider create/edit forms.
     *
     * @return list<string>
     */
    public static function allFixedFieldKeys(): array
    {
        if (! Schema::hasTable('riders')) {
            return [];
        }

        $columns = array_flip(Schema::getColumnListing('riders'));
        $keys = [];

        foreach (self::fixedFieldsSlugMap() as $slugKeys) {
            foreach ($slugKeys as $fieldKey) {
                if (isset($columns[$fieldKey])) {
                    $keys[] = $fieldKey;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Fixed rider fields grouped by category (explicit assignments only).
     * Returns list of [ 'id' => categoryId, 'label' => ..., 'fields' => [...] ].
     */
    public static function fixedRiderFieldsByCategory(): array
    {
        $categories = self::scopedRiderCategoriesQuery()->orderBy('display_order')->orderBy('id')->get();
        $assignments = RiderFieldCategoryAssignment::with('category')->orderBy('display_order')->orderBy('id')->get()->groupBy('category_id');

        $result = [];
        foreach ($categories as $cat) {
            $fields = [];
            foreach ($assignments->get($cat->id, collect()) as $a) {
                $fields[] = [
                    'key' => $a->field_key,
                    'label' => self::humanizeFieldKey($a->field_key),
                ];
            }
            if ($fields === []) {
                continue;
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
        return ModuleFieldSource::humanizeFieldKey($key);
    }

    /**
     * Input spec per fixed rider field for the rider form (type, dropdown, required, etc.).
     */
    public static function fixedFieldInputSpecs(): array
    {
        $specs = [
            'rider_id' => ['type' => 'text', 'required' => true],
            'name' => ['type' => 'text', 'required' => true, 'maxlength' => 191],
            'doj' => ['type' => 'date', 'required' => true],
            'personal_contact' => ['type' => 'tel', 'maxlength' => 10, 'placeholder' => '05XXXXXXXX'],
            'nationality' => ['type' => 'select', 'dropdown' => 'countries', 'required' => true],
            'passport' => ['type' => 'text', 'maxlength' => 50],
            'passport_expiry' => ['type' => 'date'],
            'ethnicity' => ['type' => 'select', 'dropdown' => 'ethnicity'],
            'dob' => ['type' => 'date'],
            'company_contact' => ['type' => 'tel', 'readonly' => true],
            'email' => ['type' => 'email'],
            'courier_id' => ['type' => 'text'],
            'image_name' => ['type' => 'text'],
            'rider_top_option_id' => ['type' => 'text'],
            'custom_field_values' => ['type' => 'textarea', 'rows' => 2],
            'status' => ['type' => 'text'],
            'emirate_hub' => ['type' => 'text'],
            'emirate_id' => ['type' => 'text', 'required' => true, 'maxlength' => 18, 'placeholder' => '784-2000-6871718-8'],
            'emirate_exp' => ['type' => 'date', 'required' => true],
            'license_no' => ['type' => 'text', 'maxlength' => 50],
            'license_expiry' => ['type' => 'date'],
            'road_permit' => ['type' => 'text', 'maxlength' => 50],
            'road_permit_expiry' => ['type' => 'date'],
            'visa_status' => ['type' => 'select', 'dropdown' => 'visa-status'],
            'passport_handover' => ['type' => 'select', 'dropdown' => 'passport-handover'],
            'visa_sponsor' => ['type' => 'text', 'maxlength' => 50],
            'visa_occupation' => ['type' => 'text', 'required' => true, 'maxlength' => 50],
            'VID' => ['type' => 'select', 'dropdown' => 'vendors', 'required' => true],
            'salary_model' => ['type' => 'select', 'dropdown' => 'salary-model', 'required' => true],
            'fleet_supervisor' => ['type' => 'select', 'dropdown' => 'fleet-supervisor', 'required' => true],
            'rider_reference' => ['type' => 'text', 'required' => true],
            'recruiter_id' => ['type' => 'select', 'dropdown' => 'recruiters'],
            'DEPT' => ['type' => 'text'],
            'PID' => ['type' => 'text'],
            'job_status' => ['type' => 'text'],
            'customer_id' => ['type' => 'select', 'dropdown' => 'customers'],
            'recuriter' => ['type' => 'text'],
            'shift' => ['type' => 'text'],
            'attendance' => ['type' => 'text'],
            'branch_id' => ['type' => 'select', 'dropdown' => 'branch', 'required' => true],
            'vat' => ['type' => 'checkbox'],
            'person_code' => ['type' => 'text', 'maxlength' => 50],
            'labor_card_number' => ['type' => 'text', 'maxlength' => 100],
            'labor_card_expiry' => ['type' => 'date'],
            'insurance' => ['type' => 'select', 'dropdown' => 'insurance'],
            'insurance_expiry' => ['type' => 'date'],
            'policy_no' => ['type' => 'text', 'maxlength' => 255],
            'wps' => ['type' => 'select', 'dropdown' => 'wps'],
            'c3_card' => ['type' => 'select', 'dropdown' => 'c3-card'],
            'contract' => ['type' => 'text'],
            'NFDID' => ['type' => 'text'],
            'cdm_deposit_id' => ['type' => 'text'],
            'mashreq_id' => ['type' => 'text'],
            'branded_plate_no' => ['type' => 'text'],
            'vaccine_status' => ['type' => 'select', 'dropdown' => 'vaccine-status'],
            'absconder' => ['type' => 'checkbox'],
            'flowup' => ['type' => 'checkbox'],
            'l_license' => ['type' => 'checkbox'],
            'walker' => ['type' => 'checkbox'],
            'vacation' => ['type' => 'checkbox'],
            'cancel' => ['type' => 'checkbox'],
            'pro' => ['type' => 'checkbox'],
            'TAID' => ['type' => 'text'],
            'noon_no' => ['type' => 'text'],
            'other_details' => ['type' => 'textarea', 'rows' => 2],
            'designation' => ['type' => 'text'],
            'rider_status' => ['type' => 'text'],
            'attach_documents' => ['type' => 'text'],
        ];

        $allowed = array_flip(self::allFixedFieldKeys());
        foreach (array_keys($specs) as $fieldKey) {
            if (! isset($allowed[$fieldKey])) {
                unset($specs[$fieldKey]);
            }
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

        $categories = RiderCategory::orderBy('display_order')->orderBy('id')->get();
        $categoryIds = $categories->pluck('id')->all();
        $allowedFixedLookup = array_flip(self::allFixedFieldKeys());
        $assignmentsAll = RiderFieldCategoryAssignment::with('category')
            ->whereIn('category_id', $categoryIds)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        // Visibility/required are enforced per user via Role Field Permissions at render time.
        $customFieldsAll = self::with('category')
            ->whereIn('category_id', $categoryIds)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $specs = self::fixedFieldInputSpecs();

        $result = [];
        foreach ($categories as $cat) {
            $fields = [];
            $categoryAssignments = $assignmentsAll->where('category_id', $cat->id)->values();

            foreach ($categoryAssignments as $a) {
                if (!isset($allowedFixedLookup[$a->field_key])) {
                    continue;
                }
                $label = $a->display_label !== null && trim((string) $a->display_label) !== ''
                    ? trim($a->display_label)
                    : self::humanizeFieldKey($a->field_key);
                $spec = ModuleFieldSource::mergeFixedFieldSpec($a->field_key, $specs[$a->field_key] ?? null);

                // Rider Settings can override a fixed field's input type + config (e.g. dropdown options).
                // The rider module renderer reads from "$spec", so we merge relevant config here.
                if (!empty($a->input_type)) {
                    // The renderer expects HTML-ish types: dropdown -> select, checkbox stays checkbox.
                    $spec['type'] = $a->input_type === 'dropdown' ? 'select' : $a->input_type;
                }
                if (is_array($a->input_config) && array_key_exists('options', $a->input_config)) {
                    $spec['options'] = $a->input_config['options'];
                }

                $fields[] = (object) [
                    'kind' => 'fixed',
                    'field_key' => $a->field_key,
                    'label' => $label,
                    'spec' => $spec,
                ];
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
