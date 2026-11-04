<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure every document type with related rider columns has subtypes/fields.
     * Passport also gets nationality + dob. Expiry lives as a document field (linked).
     */
    public function up(): void
    {
        if (!Schema::hasTable('rider_document_types')
            || !Schema::hasTable('rider_document_type_subtypes')
            || !Schema::hasTable('rider_document_type_fields')) {
            return;
        }

        $now = now();

        $definitions = [
            'passport' => [
                'front' => [
                    ['key' => 'passport', 'label' => 'Passport', 'input_type' => 'text', 'is_required' => true, 'rider_column' => 'passport', 'order' => 0],
                    ['key' => 'passport_expiry', 'label' => 'Passport Expiry', 'input_type' => 'date', 'is_required' => true, 'rider_column' => 'passport_expiry', 'order' => 1],
                    ['key' => 'nationality', 'label' => 'Nationality', 'input_type' => 'text', 'is_required' => false, 'rider_column' => 'nationality', 'order' => 2],
                    ['key' => 'dob', 'label' => 'Date Of Birth', 'input_type' => 'date', 'is_required' => false, 'rider_column' => 'dob', 'order' => 3],
                ],
                'back' => [
                    ['key' => 'passport', 'label' => 'Passport', 'input_type' => 'text', 'is_required' => false, 'rider_column' => 'passport', 'order' => 0],
                    ['key' => 'passport_expiry', 'label' => 'Passport Expiry', 'input_type' => 'date', 'is_required' => false, 'rider_column' => 'passport_expiry', 'order' => 1],
                ],
            ],
            'emirates' => [
                'front' => [
                    ['key' => 'emirates_id', 'label' => 'Emirates ID', 'input_type' => 'text', 'is_required' => true, 'rider_column' => 'emirate_id', 'order' => 0],
                    ['key' => 'emirate_exp', 'label' => 'Emirate Exp', 'input_type' => 'date', 'is_required' => true, 'rider_column' => 'emirate_exp', 'order' => 1],
                    ['key' => 'occupation', 'label' => 'Occupation', 'input_type' => 'text', 'is_required' => false, 'rider_column' => null, 'order' => 2],
                    ['key' => 'employer', 'label' => 'Employer', 'input_type' => 'text', 'is_required' => false, 'rider_column' => null, 'order' => 3],
                ],
                'back' => [
                    ['key' => 'emirates_id', 'label' => 'Emirates ID', 'input_type' => 'text', 'is_required' => false, 'rider_column' => 'emirate_id', 'order' => 0],
                    ['key' => 'emirate_exp', 'label' => 'Emirate Exp', 'input_type' => 'date', 'is_required' => false, 'rider_column' => 'emirate_exp', 'order' => 1],
                ],
            ],
            'labor' => [
                'front' => [
                    ['key' => 'labor_card', 'label' => 'Labor Card', 'input_type' => 'text', 'is_required' => true, 'rider_column' => 'labor_card_number', 'order' => 0],
                    ['key' => 'person_code', 'label' => 'Person Code', 'input_type' => 'text', 'is_required' => false, 'rider_column' => 'person_code', 'order' => 1],
                    ['key' => 'labor_card_expiry', 'label' => 'Labor Card Expiry', 'input_type' => 'date', 'is_required' => true, 'rider_column' => 'labor_card_expiry', 'order' => 2],
                ],
                'back' => [
                    ['key' => 'labor_card', 'label' => 'Labor Card', 'input_type' => 'text', 'is_required' => false, 'rider_column' => 'labor_card_number', 'order' => 0],
                    ['key' => 'labor_card_expiry', 'label' => 'Labor Card Expiry', 'input_type' => 'date', 'is_required' => false, 'rider_column' => 'labor_card_expiry', 'order' => 1],
                ],
            ],
            'license' => [
                'front' => [
                    ['key' => 'license_no', 'label' => 'License Number', 'input_type' => 'text', 'is_required' => true, 'rider_column' => 'license_no', 'order' => 0],
                    ['key' => 'license_expiry', 'label' => 'License Expiry', 'input_type' => 'date', 'is_required' => true, 'rider_column' => 'license_expiry', 'order' => 1],
                ],
                'back' => [
                    ['key' => 'license_no', 'label' => 'License Number', 'input_type' => 'text', 'is_required' => false, 'rider_column' => 'license_no', 'order' => 0],
                    ['key' => 'license_expiry', 'label' => 'License Expiry', 'input_type' => 'date', 'is_required' => false, 'rider_column' => 'license_expiry', 'order' => 1],
                ],
            ],
            'nic' => [
                'front' => [
                    ['key' => 'cnic', 'label' => 'NIC Number', 'input_type' => 'text', 'is_required' => true, 'rider_column' => 'cnic', 'order' => 0],
                    ['key' => 'cnic_expiry', 'label' => 'NIC Expiry', 'input_type' => 'date', 'is_required' => true, 'rider_column' => 'cnic_expiry', 'order' => 1],
                ],
                'back' => [
                    ['key' => 'cnic', 'label' => 'NIC Number', 'input_type' => 'text', 'is_required' => false, 'rider_column' => 'cnic', 'order' => 0],
                    ['key' => 'cnic_expiry', 'label' => 'NIC Expiry', 'input_type' => 'date', 'is_required' => false, 'rider_column' => 'cnic_expiry', 'order' => 1],
                ],
            ],
            // Single types: one default subtype "main"
            'road' => [
                'main' => [
                    ['key' => 'road_permit', 'label' => 'Road Permit', 'input_type' => 'text', 'is_required' => true, 'rider_column' => 'road_permit', 'order' => 0],
                    ['key' => 'road_permit_expiry', 'label' => 'Road Permit Expiry', 'input_type' => 'date', 'is_required' => true, 'rider_column' => 'road_permit_expiry', 'order' => 1],
                ],
            ],
            'health' => [
                'main' => [
                    ['key' => 'policy_no', 'label' => 'Policy Number', 'input_type' => 'text', 'is_required' => false, 'rider_column' => 'policy_no', 'order' => 0],
                    ['key' => 'insurance_expiry', 'label' => 'Insurance Expiry', 'input_type' => 'date', 'is_required' => false, 'rider_column' => 'insurance_expiry', 'order' => 1],
                ],
            ],
        ];

        $riderColumns = Schema::hasTable('riders')
            ? array_flip(Schema::getColumnListing('riders'))
            : [];

        foreach ($definitions as $typeKey => $sides) {
            $types = DB::table('rider_document_types')->where('key', $typeKey)->get();
            foreach ($types as $type) {
                $companyId = property_exists($type, 'company_id') ? $type->company_id : null;

                foreach ($sides as $subtypeKey => $fields) {
                    $label = match ($subtypeKey) {
                        'front' => trim((string) ($type->front_label ?: 'Front')),
                        'back' => trim((string) ($type->back_label ?: 'Back')),
                        'main' => trim((string) ($type->label ?: ucfirst($typeKey))),
                        default => ucfirst($subtypeKey),
                    };

                    $subtypeId = DB::table('rider_document_type_subtypes')
                        ->where('document_type_id', $type->id)
                        ->where('key', $subtypeKey)
                        ->value('id');

                    if (!$subtypeId) {
                        $subtypeId = DB::table('rider_document_type_subtypes')->insertGetId([
                            'company_id' => $companyId,
                            'document_type_id' => $type->id,
                            'key' => $subtypeKey,
                            'label' => $label !== '' ? $label : ucfirst($subtypeKey),
                            'display_order' => $subtypeKey === 'back' ? 1 : 0,
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    foreach ($fields as $field) {
                        // Skip rider_column links when column missing from riders table
                        $riderColumn = $field['rider_column'];
                        if ($riderColumn && !isset($riderColumns[$riderColumn])) {
                            $riderColumn = null;
                        }

                        $existing = DB::table('rider_document_type_fields')
                            ->where('subtype_id', $subtypeId)
                            ->where('key', $field['key'])
                            ->first();

                        if ($existing) {
                            DB::table('rider_document_type_fields')->where('id', $existing->id)->update([
                                'label' => $field['label'],
                                'input_type' => $field['input_type'],
                                'is_required' => $field['is_required'],
                                'display_order' => $field['order'],
                                'rider_column' => $riderColumn,
                                'is_active' => true,
                                'updated_at' => $now,
                            ]);
                            continue;
                        }

                        DB::table('rider_document_type_fields')->insert([
                            'company_id' => $companyId,
                            'document_type_id' => $type->id,
                            'subtype_id' => $subtypeId,
                            'key' => $field['key'],
                            'label' => $field['label'],
                            'input_type' => $field['input_type'],
                            'is_required' => $field['is_required'],
                            'display_order' => $field['order'],
                            'rider_column' => $riderColumn,
                            'options' => null,
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Non-destructive: keep configured fields.
    }
};
