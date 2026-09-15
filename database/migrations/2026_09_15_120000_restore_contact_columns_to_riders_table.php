<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('riders')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            if (! Schema::hasColumn('riders', 'personal_contact')) {
                $table->string('personal_contact', 191)->nullable()->after('rider_id');
            }
            if (! Schema::hasColumn('riders', 'company_contact')) {
                $table->string('company_contact', 191)->nullable()->after('personal_contact');
            }
        });

        $this->prefillCompanyContactFromAssignedSims();
        $this->migratePersonalContactCustomField();
    }

    public function down(): void
    {
        if (! Schema::hasTable('riders')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            $drop = [];
            foreach (['personal_contact', 'company_contact'] as $column) {
                if (Schema::hasColumn('riders', $column)) {
                    $drop[] = $column;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }

    private function prefillCompanyContactFromAssignedSims(): void
    {
        if (! Schema::hasTable('sims')) {
            return;
        }

        $assignedSims = DB::table('sims')
            ->select(['assign_to', 'number'])
            ->where('assign_type', 'rider')
            ->whereNotNull('assign_to')
            ->whereNotNull('number')
            ->where('number', '!=', '')
            ->where(function ($query) {
                $query->where('status', 1);
                if (Schema::hasColumn('sims', 'deleted_at')) {
                    $query->whereNull('deleted_at');
                }
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        foreach ($assignedSims as $sim) {
            DB::table('riders')
                ->where('id', (int) $sim->assign_to)
                ->update(['company_contact' => trim((string) $sim->number)]);
        }
    }

    private function migratePersonalContactCustomField(): void
    {
        if (! Schema::hasTable('rider_custom_fields')) {
            return;
        }

        $customFields = DB::table('rider_custom_fields')
            ->whereRaw('LOWER(TRIM(label)) = ?', ['personal contact'])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        if ($customFields->isEmpty()) {
            return;
        }

        $hasAssignmentsTable = Schema::hasTable('rider_field_category_assignments');
        $assignmentHasCompanyId = $hasAssignmentsTable && Schema::hasColumn('rider_field_category_assignments', 'company_id');
        $customFieldHasCompanyId = Schema::hasColumn('rider_custom_fields', 'company_id');
        $ridersHaveCompanyId = Schema::hasColumn('riders', 'company_id');

        foreach ($customFields as $customField) {
            $fieldId = (int) $customField->id;
            $companyId = $customFieldHasCompanyId ? ($customField->company_id ?? null) : null;

            if ($hasAssignmentsTable) {
                $assignmentQuery = [
                    'field_key' => 'personal_contact',
                ];
                if ($assignmentHasCompanyId && $companyId !== null) {
                    $assignmentQuery['company_id'] = $companyId;
                }

                $assignmentPayload = [
                    'category_id' => $customField->category_id,
                    'display_order' => (int) ($customField->display_order ?? 0),
                    'display_label' => $customField->label,
                    'is_visible' => (bool) ($customField->is_visible ?? true),
                    'is_required' => (bool) ($customField->is_mandatory ?? false),
                    'input_type' => $this->mapCustomFieldInputType((string) ($customField->data_type ?? 'text')),
                    'input_config' => $this->decodeJsonColumn($customField->config ?? null),
                    'updated_at' => now(),
                ];

                if ($assignmentHasCompanyId && $companyId !== null) {
                    $assignmentPayload['company_id'] = $companyId;
                }

                $existingAssignment = DB::table('rider_field_category_assignments')
                    ->where($assignmentQuery)
                    ->first();

                if ($existingAssignment) {
                    DB::table('rider_field_category_assignments')
                        ->where('id', $existingAssignment->id)
                        ->update($assignmentPayload);
                } else {
                    DB::table('rider_field_category_assignments')->insert(array_merge(
                        $assignmentQuery,
                        $assignmentPayload,
                        ['created_at' => now()]
                    ));
                }
            }

            $ridersQuery = DB::table('riders')->select(['id', 'custom_field_values']);
            if ($ridersHaveCompanyId && $companyId !== null) {
                $ridersQuery->where('company_id', $companyId);
            }

            $ridersQuery->orderBy('id')->chunkById(200, function ($riders) use ($fieldId) {
                foreach ($riders as $rider) {
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

    private function mapCustomFieldInputType(string $dataType): string
    {
        return match ($dataType) {
            'dropdown' => 'dropdown',
            'checkbox' => 'checkbox',
            'textarea' => 'textarea',
            'number', 'decimal' => 'number',
            'date' => 'date',
            'datetime' => 'datetime',
            'email' => 'email',
            'url' => 'url',
            default => 'text',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonColumn(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }
};
