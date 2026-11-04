<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rider_document_type_subtypes')) {
            Schema::create('rider_document_type_subtypes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('document_type_id');
                $table->string('key', 80);
                $table->string('label', 255);
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['document_type_id', 'key'], 'rdt_subtypes_type_key_unique');
                $table->foreign('document_type_id', 'rdt_subtypes_document_type_fk')
                    ->references('id')
                    ->on('rider_document_types')
                    ->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('rider_document_type_fields')) {
            Schema::create('rider_document_type_fields', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('document_type_id');
                $table->unsignedBigInteger('subtype_id');
                $table->string('key', 80);
                $table->string('label', 255);
                $table->string('input_type', 30)->default('text');
                $table->boolean('is_required')->default(false);
                $table->unsignedInteger('display_order')->default(0);
                $table->string('rider_column', 100)->nullable();
                $table->json('options')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['subtype_id', 'key'], 'rdt_fields_subtype_key_unique');
                $table->foreign('document_type_id', 'rdt_fields_document_type_fk')
                    ->references('id')
                    ->on('rider_document_types')
                    ->onDelete('cascade');
                $table->foreign('subtype_id', 'rdt_fields_subtype_fk')
                    ->references('id')
                    ->on('rider_document_type_subtypes')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('files')) {
            Schema::table('files', function (Blueprint $table) {
                if (!Schema::hasColumn('files', 'document_type_id')) {
                    $table->unsignedBigInteger('document_type_id')->nullable()->after('type_id')->index();
                }
                if (!Schema::hasColumn('files', 'document_subtype_id')) {
                    $table->unsignedBigInteger('document_subtype_id')->nullable()->after('document_type_id')->index();
                }
                if (!Schema::hasColumn('files', 'field_values')) {
                    $table->json('field_values')->nullable()->after('document_subtype_id');
                }
            });
        }

        $this->seedSubtypesAndFields();
    }

    private function seedSubtypesAndFields(): void
    {
        if (!Schema::hasTable('rider_document_types') || !Schema::hasTable('rider_document_type_subtypes')) {
            return;
        }

        $now = now();
        $types = DB::table('rider_document_types')->get();

        foreach ($types as $type) {
            $companyId = property_exists($type, 'company_id') ? $type->company_id : null;

            if (($type->type ?? '') === 'dual') {
                $sides = [
                    ['key' => 'front', 'label' => trim((string) ($type->front_label ?: 'Front')), 'order' => 0],
                    ['key' => 'back', 'label' => trim((string) ($type->back_label ?: 'Back')), 'order' => 1],
                ];
                foreach ($sides as $side) {
                    $existing = DB::table('rider_document_type_subtypes')
                        ->where('document_type_id', $type->id)
                        ->where('key', $side['key'])
                        ->first();
                    if ($existing) {
                        continue;
                    }
                    DB::table('rider_document_type_subtypes')->insert([
                        'company_id' => $companyId,
                        'document_type_id' => $type->id,
                        'key' => $side['key'],
                        'label' => $side['label'] !== '' ? $side['label'] : ucfirst($side['key']),
                        'display_order' => $side['order'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        if (!Schema::hasTable('rider_document_type_fields')) {
            return;
        }

        $fieldSeeds = [
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
            'passport' => [
                'front' => [
                    ['key' => 'passport', 'label' => 'Passport Number', 'input_type' => 'text', 'is_required' => true, 'rider_column' => 'passport', 'order' => 0],
                    ['key' => 'passport_expiry', 'label' => 'Passport Expiry', 'input_type' => 'date', 'is_required' => true, 'rider_column' => 'passport_expiry', 'order' => 1],
                ],
                'back' => [
                    ['key' => 'passport', 'label' => 'Passport Number', 'input_type' => 'text', 'is_required' => false, 'rider_column' => 'passport', 'order' => 0],
                    ['key' => 'passport_expiry', 'label' => 'Passport Expiry', 'input_type' => 'date', 'is_required' => false, 'rider_column' => 'passport_expiry', 'order' => 1],
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
        ];

        foreach ($fieldSeeds as $typeKey => $sides) {
            $type = DB::table('rider_document_types')->where('key', $typeKey)->first();
            if (!$type) {
                continue;
            }
            $companyId = property_exists($type, 'company_id') ? $type->company_id : null;

            foreach ($sides as $subtypeKey => $fields) {
                $subtype = DB::table('rider_document_type_subtypes')
                    ->where('document_type_id', $type->id)
                    ->where('key', $subtypeKey)
                    ->first();
                if (!$subtype) {
                    continue;
                }

                foreach ($fields as $field) {
                    $exists = DB::table('rider_document_type_fields')
                        ->where('subtype_id', $subtype->id)
                        ->where('key', $field['key'])
                        ->exists();
                    if ($exists) {
                        continue;
                    }
                    DB::table('rider_document_type_fields')->insert([
                        'company_id' => $companyId,
                        'document_type_id' => $type->id,
                        'subtype_id' => $subtype->id,
                        'key' => $field['key'],
                        'label' => $field['label'],
                        'input_type' => $field['input_type'],
                        'is_required' => $field['is_required'],
                        'display_order' => $field['order'],
                        'rider_column' => $field['rider_column'],
                        'options' => null,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('files')) {
            Schema::table('files', function (Blueprint $table) {
                if (Schema::hasColumn('files', 'field_values')) {
                    $table->dropColumn('field_values');
                }
                if (Schema::hasColumn('files', 'document_subtype_id')) {
                    $table->dropColumn('document_subtype_id');
                }
                if (Schema::hasColumn('files', 'document_type_id')) {
                    $table->dropColumn('document_type_id');
                }
            });
        }

        Schema::dropIfExists('rider_document_type_fields');
        Schema::dropIfExists('rider_document_type_subtypes');
    }
};
