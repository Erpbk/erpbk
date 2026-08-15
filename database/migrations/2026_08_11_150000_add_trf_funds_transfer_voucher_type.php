<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voucher_types')) {
            return;
        }

        $hasCompanyId = Schema::hasColumn('voucher_types', 'company_id');
        $hasAssignmentCompanyId = Schema::hasTable('voucher_type_module_assignments')
            && Schema::hasColumn('voucher_type_module_assignments', 'company_id');
        $allowedModules = array_keys(config('voucher_modules.modules', []));
        $moduleKeys = array_values(array_intersect(['cash_banks', 'vouchers'], $allowedModules));

        $seedType = function (?int $companyId) use ($hasCompanyId, $hasAssignmentCompanyId, $moduleKeys): void {
            $displayOrderQuery = DB::table('voucher_types');
            if ($hasCompanyId && $companyId !== null) {
                $displayOrderQuery->where('company_id', $companyId);
            }
            $displayOrder = (int) ($displayOrderQuery->max('display_order') ?? 0) + 1;

            $keys = ['code' => 'TRF'];
            $values = [
                'label' => 'Funds Transfer',
                'display_order' => $displayOrder,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($hasCompanyId) {
                $keys['company_id'] = $companyId;
                $values['company_id'] = $companyId;
            }

            DB::table('voucher_types')->updateOrInsert($keys, $values);

            $typeQuery = DB::table('voucher_types')->where('code', 'TRF');
            if ($hasCompanyId) {
                $typeQuery->where('company_id', $companyId);
            }
            $type = $typeQuery->first();
            if (! $type || ! Schema::hasTable('voucher_type_module_assignments') || empty($moduleKeys)) {
                return;
            }

            foreach ($moduleKeys as $moduleKey) {
                $assignmentKeys = [
                    'voucher_type_id' => $type->id,
                    'module_key' => $moduleKey,
                ];
                if ($hasAssignmentCompanyId) {
                    $assignmentKeys['company_id'] = $companyId;
                }

                DB::table('voucher_type_module_assignments')->updateOrInsert(
                    $assignmentKeys,
                    [
                        'can_edit' => true,
                        'can_delete' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        };

        if ($hasCompanyId && Schema::hasTable('companies')) {
            $companyIds = DB::table('companies')->pluck('id');
            if ($companyIds->isNotEmpty()) {
                foreach ($companyIds as $companyId) {
                    $seedType((int) $companyId);
                }

                $orphanTypeIds = DB::table('voucher_types')
                    ->whereNull('company_id')
                    ->where('code', 'TRF')
                    ->pluck('id');
                if ($orphanTypeIds->isNotEmpty() && Schema::hasTable('voucher_type_module_assignments')) {
                    DB::table('voucher_type_module_assignments')
                        ->whereIn('voucher_type_id', $orphanTypeIds)
                        ->delete();
                }
                DB::table('voucher_types')->whereNull('company_id')->where('code', 'TRF')->delete();

                return;
            }
        }

        $seedType(null);
    }

    public function down(): void
    {
        if (! Schema::hasTable('voucher_types')) {
            return;
        }

        $typeIds = DB::table('voucher_types')->where('code', 'TRF')->pluck('id');
        if ($typeIds->isNotEmpty() && Schema::hasTable('voucher_type_module_assignments')) {
            DB::table('voucher_type_module_assignments')
                ->whereIn('voucher_type_id', $typeIds)
                ->delete();
        }

        DB::table('voucher_types')->where('code', 'TRF')->delete();
    }
};
