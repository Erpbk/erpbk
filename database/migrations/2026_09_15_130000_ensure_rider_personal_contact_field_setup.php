<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent follow-up for environments where the contact restore migration
 * partially ran (columns present) but personal_contact field assignment /
 * custom-field retirement did not complete.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('riders') || ! Schema::hasColumn('riders', 'personal_contact')) {
            return;
        }

        $this->ensurePersonalContactAssignments();
        $this->retirePersonalContactCustomFields();
    }

    public function down(): void
    {
        // Non-destructive follow-up; nothing to reverse.
    }

    private function ensurePersonalContactAssignments(): void
    {
        if (! Schema::hasTable('rider_field_category_assignments') || ! Schema::hasTable('rider_categories')) {
            return;
        }

        $hasCompanyId = Schema::hasColumn('rider_field_category_assignments', 'company_id');
        $categoriesHaveCompanyId = Schema::hasColumn('rider_categories', 'company_id');

        $categoryQuery = DB::table('rider_categories')->orderBy('display_order')->orderBy('id');
        $categories = $categoryQuery->get();
        if ($categories->isEmpty()) {
            return;
        }

        $grouped = $categoriesHaveCompanyId
            ? $categories->groupBy(fn ($row) => $row->company_id ?? 0)
            : collect([0 => $categories]);

        foreach ($grouped as $companyId => $companyCategories) {
            $defaultCategory = $companyCategories->first(fn ($row) => ($row->slug ?? null) === 'general')
                ?? $companyCategories->first(fn ($row) => ($row->slug ?? null) === 'rider_info')
                ?? $companyCategories->first();

            if (! $defaultCategory) {
                continue;
            }

            $assignmentQuery = DB::table('rider_field_category_assignments')
                ->where('field_key', 'personal_contact');

            if ($hasCompanyId && $categoriesHaveCompanyId && $companyId) {
                $assignmentQuery->where('company_id', $companyId);
            }

            if ($assignmentQuery->exists()) {
                continue;
            }

            $payload = [
                'field_key' => 'personal_contact',
                'category_id' => $defaultCategory->id,
                'display_order' => 1,
                'display_label' => 'Personal Contact',
                'is_visible' => true,
                'is_required' => false,
                'input_type' => 'text',
                'input_config' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($hasCompanyId && $categoriesHaveCompanyId && $companyId) {
                $payload['company_id'] = $companyId;
            } elseif ($hasCompanyId && $categoriesHaveCompanyId && $defaultCategory->company_id) {
                $payload['company_id'] = $defaultCategory->company_id;
            }

            DB::table('rider_field_category_assignments')->insert($payload);
        }
    }

    private function retirePersonalContactCustomFields(): void
    {
        if (! Schema::hasTable('rider_custom_fields')) {
            return;
        }

        $customFields = DB::table('rider_custom_fields')
            ->whereRaw('LOWER(TRIM(label)) = ?', ['personal contact'])
            ->orderBy('id')
            ->get();

        foreach ($customFields as $customField) {
            $fieldId = (int) $customField->id;

            DB::table('riders')
                ->select(['id', 'personal_contact', 'custom_field_values'])
                ->orderBy('id')
                ->chunkById(200, function ($riders) use ($fieldId) {
                    foreach ($riders as $rider) {
                        if ($rider->personal_contact !== null && trim((string) $rider->personal_contact) !== '') {
                            continue;
                        }

                        $value = $this->customFieldValueForRider($rider->custom_field_values ?? null, $fieldId);
                        if ($value === null || trim($value) === '') {
                            continue;
                        }

                        DB::table('riders')
                            ->where('id', $rider->id)
                            ->update(['personal_contact' => trim($value)]);
                    }
                });

            DB::table('rider_custom_fields')
                ->where('id', $fieldId)
                ->update([
                    'category_id' => null,
                    'is_visible' => false,
                    'updated_at' => now(),
                ]);
        }
    }

    private function customFieldValueForRider(mixed $customFieldValues, int $fieldId): ?string
    {
        if ($customFieldValues === null || $customFieldValues === '') {
            return null;
        }

        $decoded = is_array($customFieldValues)
            ? $customFieldValues
            : json_decode((string) $customFieldValues, true);

        if (! is_array($decoded)) {
            return null;
        }

        $value = $decoded[$fieldId] ?? $decoded[(string) $fieldId] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
};
