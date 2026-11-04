<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permanent rider columns: residency_number + residency_expiry.
 * Also wires them into field assignments and the Residency document type.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('riders')) {
            if (!Schema::hasColumn('riders', 'residency_number')) {
                Schema::table('riders', function (Blueprint $table) {
                    $after = Schema::hasColumn('riders', 'labor_card_expiry')
                        ? 'labor_card_expiry'
                        : (Schema::hasColumn('riders', 'person_code') ? 'person_code' : null);
                    if ($after) {
                        $table->string('residency_number', 100)->nullable()->after($after);
                    } else {
                        $table->string('residency_number', 100)->nullable();
                    }
                });
            }
            if (!Schema::hasColumn('riders', 'residency_expiry')) {
                Schema::table('riders', function (Blueprint $table) {
                    if (Schema::hasColumn('riders', 'residency_number')) {
                        $table->date('residency_expiry')->nullable()->after('residency_number');
                    } else {
                        $table->date('residency_expiry')->nullable();
                    }
                });
            }
        }

        $this->ensureFieldAssignments();
        $this->ensureResidencyDocumentFields();
    }

    public function down(): void
    {
        if (Schema::hasTable('rider_field_category_assignments')) {
            DB::table('rider_field_category_assignments')
                ->whereIn('field_key', ['residency_number', 'residency_expiry'])
                ->delete();
        }

        if (Schema::hasTable('riders')) {
            Schema::table('riders', function (Blueprint $table) {
                if (Schema::hasColumn('riders', 'residency_expiry')) {
                    $table->dropColumn('residency_expiry');
                }
                if (Schema::hasColumn('riders', 'residency_number')) {
                    $table->dropColumn('residency_number');
                }
            });
        }
    }

    private function ensureFieldAssignments(): void
    {
        if (!Schema::hasTable('rider_field_category_assignments') || !Schema::hasTable('rider_categories')) {
            return;
        }

        $hasCompanyId = Schema::hasColumn('rider_field_category_assignments', 'company_id');
        $categoriesHaveCompanyId = Schema::hasColumn('rider_categories', 'company_id');
        $hasDisplayLabel = Schema::hasColumn('rider_field_category_assignments', 'display_label');
        $hasIsVisible = Schema::hasColumn('rider_field_category_assignments', 'is_visible');
        $hasIsRequired = Schema::hasColumn('rider_field_category_assignments', 'is_required');
        $hasInputType = Schema::hasColumn('rider_field_category_assignments', 'input_type');

        $categories = DB::table('rider_categories')->orderBy('display_order')->orderBy('id')->get();
        if ($categories->isEmpty()) {
            return;
        }

        $grouped = $categoriesHaveCompanyId
            ? $categories->groupBy(fn ($row) => $row->company_id ?? 0)
            : collect([0 => $categories]);

        $fields = [
            'residency_number' => ['label' => 'Residency Number', 'input_type' => 'text', 'order' => 50],
            'residency_expiry' => ['label' => 'Residency Expiry', 'input_type' => 'date', 'order' => 51],
        ];

        foreach ($grouped as $companyId => $companyCategories) {
            $category = $companyCategories->first(fn ($row) => ($row->slug ?? null) === 'visa_info')
                ?? $companyCategories->first(fn ($row) => ($row->slug ?? null) === 'labor_info')
                ?? $companyCategories->first(fn ($row) => ($row->slug ?? null) === 'general')
                ?? $companyCategories->first();

            if (!$category) {
                continue;
            }

            foreach ($fields as $fieldKey => $meta) {
                $query = DB::table('rider_field_category_assignments')->where('field_key', $fieldKey);
                if ($hasCompanyId && $categoriesHaveCompanyId && $companyId) {
                    $query->where('company_id', $companyId);
                }
                if ($query->exists()) {
                    continue;
                }

                $payload = [
                    'field_key' => $fieldKey,
                    'category_id' => $category->id,
                    'display_order' => $meta['order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if ($hasCompanyId && $categoriesHaveCompanyId) {
                    $payload['company_id'] = $companyId ?: ($category->company_id ?? null);
                }
                if ($hasDisplayLabel) {
                    $payload['display_label'] = $meta['label'];
                }
                if ($hasIsVisible) {
                    $payload['is_visible'] = true;
                }
                if ($hasIsRequired) {
                    $payload['is_required'] = false;
                }
                if ($hasInputType) {
                    $payload['input_type'] = $meta['input_type'];
                }

                DB::table('rider_field_category_assignments')->insert($payload);
            }
        }
    }

    private function ensureResidencyDocumentFields(): void
    {
        if (!Schema::hasTable('rider_document_types')
            || !Schema::hasTable('rider_document_type_subtypes')
            || !Schema::hasTable('rider_document_type_fields')) {
            return;
        }

        $now = now();
        $types = DB::table('rider_document_types')->where('key', 'residency')->get();
        if ($types->isEmpty()) {
            return;
        }

        $fields = [
            ['key' => 'residency_number', 'label' => 'Residency Number', 'input_type' => 'text', 'is_required' => true, 'rider_column' => 'residency_number', 'order' => 0],
            ['key' => 'residency_expiry', 'label' => 'Residency Expiry', 'input_type' => 'date', 'is_required' => true, 'rider_column' => 'residency_expiry', 'order' => 1],
        ];

        $riderColumns = Schema::hasTable('riders')
            ? array_flip(Schema::getColumnListing('riders'))
            : [];

        foreach ($types as $type) {
            $companyId = property_exists($type, 'company_id') ? $type->company_id : null;
            $subtypeKey = ($type->type ?? '') === 'dual' ? 'front' : 'main';
            $label = $subtypeKey === 'front'
                ? trim((string) ($type->front_label ?: 'Front'))
                : trim((string) ($type->label ?: 'Residency'));

            $subtypeId = DB::table('rider_document_type_subtypes')
                ->where('document_type_id', $type->id)
                ->where('key', $subtypeKey)
                ->value('id');

            if (!$subtypeId) {
                $subtypeId = DB::table('rider_document_type_subtypes')
                    ->where('document_type_id', $type->id)
                    ->orderBy('display_order')
                    ->orderBy('id')
                    ->value('id');
            }

            if (!$subtypeId) {
                $subtypeId = DB::table('rider_document_type_subtypes')->insertGetId([
                    'company_id' => $companyId,
                    'document_type_id' => $type->id,
                    'key' => $subtypeKey,
                    'label' => $label !== '' ? $label : 'Residency',
                    'display_order' => 0,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($fields as $field) {
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
};
