<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ChequeCustomField extends BaseModel
{
    private static function ensureDefaultFixedAssignments(array $fieldKeys): void
    {
        $columns = Schema::getColumnListing('cheques');
        $categories = self::scopedChequeCategoriesQuery()->orderBy('display_order')->orderBy('id')->get();
        if ($categories->isEmpty()) {
            return;
        }

        $jobInfoCategoryId = (int) ($categories->firstWhere('slug', 'job_info')->id ?? 0);
        $fallbackCategoryId = (int) ($categories->firstWhere('slug', 'other')->id ?? $categories->first()->id);
        $targetCategoryId = $jobInfoCategoryId > 0 ? $jobInfoCategoryId : $fallbackCategoryId;
        if ($targetCategoryId <= 0) {
            return;
        }

        foreach ($fieldKeys as $fieldKey) {
            if (!in_array($fieldKey, $columns, true)) {
                continue;
            }
            if (in_array($fieldKey, self::removedChequeColumns(), true)) {
                continue;
            }
            $existing = ChequeFieldCategoryAssignment::where('field_key', $fieldKey)->first();
            if ($existing) {
                continue;
            }
            ChequeFieldCategoryAssignment::create([
                'field_key' => $fieldKey,
                'category_id' => $targetCategoryId,
                'display_order' => (int) ChequeFieldCategoryAssignment::where('category_id', $targetCategoryId)->max('display_order') + 1,
                'is_visible' => true,
                'is_required' => false,
            ]);
        }
    }

    private static function scopedChequeCategoriesQuery()
    {
        $query = ChequeCategory::query();
        if (Schema::hasColumn('cheque_categories', 'company_id')) {
            $companyId = auth()->user()->company_id ?? null;
            if ($companyId) {
                $query->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->orWhereNull('company_id');
                });
            }
        }
        return $query;
    }

    private static function removedChequeColumns(): array
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
            'status_option',
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
        ];
    }
    protected $table = 'cheque_custom_fields';

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
        return $this->belongsTo(ChequeCategory::class, 'category_id', 'id');
    }

    /** Slug-to-field-keys map for fixed rider fields (defaults; used for seeding and fallback). */
    public static function fixedFieldsSlugMap(): array
    {
        $map = [
            'rider_info' => [
                'name',
                'rider_id',
                'courier_id',
                'personal_contact',
                'company_contact',
                'email',
                'nationality',
                'passport',
                'passport_expiry',
                'ethnicity',
                'dob',
                'image_name',
            ],
            'visa_info' => [
                'emirate_hub',
                'emirate_id',
                'emirate_exp',
                'visa_status',
                'passport_handover',
                'visa_sponsor',
                'visa_occupation',
                'license_no',
                'license_expiry',
                'road_permit',
                'road_permit_expiry',
            ],
            'job_info' => [
                'VID',
                'salary_model',
                'fleet_supervisor',
                'rider_reference',
                'DEPT',
                'PID',
                'job_status',
                'customer_id',
                'recruiter_id',
                'recuriter',
                'shift',
                'attendance',
            ],
            'labor_info' => [
                'person_code',
                'labor_card_number',
                'labor_card_expiry',
                'insurance',
                'insurance_expiry',
                'policy_no',
                'wps',
                'c3_card',
                'contract',
            ],
            'additional_info' => [
                'NFDID',
                'cdm_deposit_id',
                'mashreq_id',
                'branded_plate_no',
                'vaccine_status',
                'absconder',
                'flowup',
                'l_license',
                'TAID',
                'noon_no',
                'vat',
                'other_details',
            ],
            'other' => [],
        ];

        $removed = array_flip(self::removedChequeColumns());
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
        // Include all existing Cheques table columns so Settings -> Rider Fields
        // always reflects real DB fields (except removed/system columns).
        $columns = Schema::getColumnListing('cheques');
        $excluded = array_flip(array_merge(
            ['id', 'created_at', 'updated_at', 'deleted_at'],
            self::removedChequeColumns()
        ));
        foreach ($columns as $column) {
            if (isset($excluded[$column])) {
                continue;
            }
            $keys[] = $column;
        }
        // Keep these Cheques table fields visible in Rider Settings field list
        // so they can be assigned and shown in Rider create/edit modules.
        foreach (['cheque_top_option_id', 'custom_field_values', 'image_name', 'status', 'attendance'] as $mustHaveKey) {
            if (in_array($mustHaveKey, $columns, true)) {
                $keys[] = $mustHaveKey;
            }
        }
        return array_values(array_unique($keys));
    }

    /**
     * Fixed rider fields grouped by category (from cheque_field_category_assignments; fallback to slug map if empty).
     * Returns list of [ 'id' => categoryId, 'label' => ..., 'fields' => [...] ].
     */
    public static function fixedChequeFieldsByCategory(): array
    {
        $categories = self::scopedChequeCategoriesQuery()->orderBy('display_order')->orderBy('id')->get();
        $assignments = ChequeFieldCategoryAssignment::with('category')->orderBy('display_order')->orderBy('id')->get()->groupBy('category_id');

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
            'rider_id' => ['type' => 'text', 'required' => true],
            'name' => ['type' => 'text', 'required' => true, 'maxlength' => 191],
            'doj' => ['type' => 'date', 'required' => true],
            'personal_contact' => ['type' => 'tel', 'maxlength' => 10, 'placeholder' => '05XXXXXXXX'],
            'nationality' => ['type' => 'select', 'dropdown' => 'countries', 'required' => true],
            'passport' => ['type' => 'text', 'maxlength' => 50],
            'passport_expiry' => ['type' => 'date'],
            'ethnicity' => ['type' => 'select', 'dropdown' => 'ethnicity'],
            'dob' => ['type' => 'date'],
            'company_contact' => ['type' => 'tel'],
            'email' => ['type' => 'email'],
            'courier_id' => ['type' => 'text'],
            'image_name' => ['type' => 'text'],
            'cheque_top_option_id' => ['type' => 'text'],
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
            'status' => ['type' => 'text'],
            'attach_documents' => ['type' => 'text'],
        ];

        foreach (self::removedChequeColumns() as $removedKey) {
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
        $categories = ChequeCategory::orderBy('display_order')->orderBy('id')->get();
        $categoryIds = $categories->pluck('id')->all();
        $allowedFixedLookup = array_flip(self::allFixedFieldKeys());
        $fallbackMap = self::fixedFieldsSlugMap();
        $assignmentsAll = ChequeFieldCategoryAssignment::with('category')
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

                    // Rider Settings can override a fixed field's input type + config (e.g. dropdown options).
                    // The cheques module renderer reads from "$spec", so we merge relevant config here.
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
