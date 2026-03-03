<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderCustomField extends Model
{
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
        'config',
        'category_id',
        'display_order',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
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

    /** Slug-to-field-keys map for fixed rider fields (defaults; used for seeding and fallback). */
    public static function fixedFieldsSlugMap(): array
    {
        return [
            'rider_info' => [
                'branch_id', 'name', 'rider_id', 'courier_id', 'personal_contact', 'company_contact',
                'personal_email', 'email', 'nationality', 'passport', 'passport_expiry', 'ethnicity', 'dob', 'image_name',
            ],
            'visa_info' => [
                'emirate_hub', 'emirate_id', 'emirate_exp', 'visa_status', 'passport_handover', 'visa_sponsor',
                'visa_occupation', 'license_no', 'license_expiry', 'road_permit', 'road_permit_expiry',
            ],
            'job_info' => [
                'VID', 'account_id', 'salary_model', 'fleet_supervisor', 'rider_reference', 'DEPT', 'PID',
                'job_status', 'customer_id', 'recruiter_id', 'recuriter', 'shift', 'attendance',
            ],
            'labor_info' => [
                'person_code', 'labor_card_number', 'labor_card_expiry', 'insurance', 'insurance_expiry',
                'policy_no', 'wps', 'c3_card', 'contract',
            ],
            'additional_info' => [
                'NFDID', 'cdm_deposit_id', 'mashreq_id', 'branded_plate_no', 'vaccine_status', 'absconder',
                'flowup', 'l_license', 'TAID', 'noon_no', 'vat', 'other_details',
            ],
            'other' => [],
        ];
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
        return array_values(array_unique($keys));
    }

    /**
     * Fixed rider fields grouped by category (from rider_field_category_assignments; fallback to slug map if empty).
     * Returns list of [ 'id' => categoryId, 'label' => ..., 'fields' => [...] ].
     */
    public static function fixedRiderFieldsByCategory(): array
    {
        $categories = RiderCategory::orderBy('display_order')->orderBy('id')->get();
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
        return [
            'rider_id' => ['type' => 'text', 'required' => true],
            'name' => ['type' => 'text', 'required' => true, 'maxlength' => 191],
            'doj' => ['type' => 'date', 'required' => true],
            'personal_contact' => ['type' => 'tel', 'maxlength' => 10, 'placeholder' => '05XXXXXXXX'],
            'personal_email' => ['type' => 'email', 'required' => true, 'maxlength' => 191],
            'nationality' => ['type' => 'select', 'dropdown' => 'countries', 'required' => true],
            'passport' => ['type' => 'text', 'maxlength' => 50],
            'passport_expiry' => ['type' => 'date'],
            'ethnicity' => ['type' => 'select', 'dropdown' => 'ethnicity'],
            'dob' => ['type' => 'date'],
            'company_contact' => ['type' => 'tel'],
            'email' => ['type' => 'email'],
            'branch_id' => ['type' => 'select', 'dropdown' => 'branch'],
            'courier_id' => ['type' => 'text'],
            'image_name' => ['type' => 'text'],
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
            'account_id' => ['type' => 'select', 'dropdown' => 'accounts'],
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
            'TAID' => ['type' => 'text'],
            'noon_no' => ['type' => 'text'],
            'other_details' => ['type' => 'textarea', 'rows' => 2],
            'designation' => ['type' => 'text'],
            'attach_documents' => ['type' => 'text'],
        ];
    }

    /**
     * Build fields by category for the rider create/edit form: fixed fields (with display_label and order from assignments)
     * plus custom fields per category. Each item: kind 'fixed'|'custom', and fixed has field_key, label, spec; custom has field (model).
     */
    public static function fieldsByCategoryForForm(): array
    {
        $categories = RiderCategory::orderBy('display_order')->orderBy('id')->get();
        $assignmentsAll = RiderFieldCategoryAssignment::with('category')
            ->where(function ($q) {
                $q->where('is_visible', '=', 1)->orWhereNull('is_visible');
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $customFieldsAll = self::with('category')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $specs = self::fixedFieldInputSpecs();

        $result = [];
        foreach ($categories as $cat) {
            $fields = [];
            foreach ($assignmentsAll->where('category_id', $cat->id)->values() as $a) {
                $label = $a->display_label !== null && trim((string) $a->display_label) !== ''
                    ? trim($a->display_label)
                    : self::humanizeFieldKey($a->field_key);
                $spec = $specs[$a->field_key] ?? ['type' => 'text'];
                $fields[] = (object) [
                    'kind' => 'fixed',
                    'field_key' => $a->field_key,
                    'label' => $label,
                    'spec' => $spec,
                ];
            }
            foreach ($customFieldsAll->where('category_id', $cat->id)->values() as $cf) {
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

