<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('voucher_types') || !Schema::hasTable('companies')) {
            return;
        }

        $companyIds = DB::table('companies')->pluck('id');
        if ($companyIds->isEmpty()) {
            return;
        }

        $types = [
            ['code' => 'FAV', 'label' => 'Fixed Asset Acquisition'],
            ['code' => 'FDV', 'label' => 'Fixed Asset Depreciation'],
        ];

        $hasAssignmentCompanyId = Schema::hasTable('voucher_type_module_assignments')
            && Schema::hasColumn('voucher_type_module_assignments', 'company_id');

        foreach ($companyIds as $companyId) {
            foreach ($types as $typeData) {
                $displayOrder = (int) (DB::table('voucher_types')
                    ->where('company_id', $companyId)
                    ->max('display_order') ?? 0) + 1;

                DB::table('voucher_types')->updateOrInsert(
                    [
                        'company_id' => $companyId,
                        'code' => $typeData['code'],
                    ],
                    [
                        'label' => $typeData['label'],
                        'display_order' => $displayOrder,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $type = DB::table('voucher_types')
                    ->where('company_id', $companyId)
                    ->where('code', $typeData['code'])
                    ->first();

                if (!$type || !Schema::hasTable('voucher_type_module_assignments')) {
                    continue;
                }

                $allowedModules = array_keys(config('voucher_modules.modules', []));
                foreach (['assets', 'vouchers'] as $moduleKey) {
                    if (!in_array($moduleKey, $allowedModules, true)) {
                        continue;
                    }

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
            }
        }

        $orphanTypeIds = DB::table('voucher_types')
            ->whereNull('company_id')
            ->whereIn('code', ['FAV', 'FDV'])
            ->pluck('id');

        if ($orphanTypeIds->isNotEmpty() && Schema::hasTable('voucher_type_module_assignments')) {
            DB::table('voucher_type_module_assignments')
                ->whereIn('voucher_type_id', $orphanTypeIds)
                ->delete();
        }

        DB::table('voucher_types')
            ->whereNull('company_id')
            ->whereIn('code', ['FAV', 'FDV'])
            ->delete();
    }

    public function down(): void
    {
        if (!Schema::hasTable('voucher_types')) {
            return;
        }

        $typeIds = DB::table('voucher_types')
            ->whereIn('code', ['FAV', 'FDV'])
            ->pluck('id');

        if ($typeIds->isNotEmpty() && Schema::hasTable('voucher_type_module_assignments')) {
            DB::table('voucher_type_module_assignments')
                ->whereIn('voucher_type_id', $typeIds)
                ->whereIn('module_key', ['assets', 'vouchers'])
                ->delete();
        }

        DB::table('voucher_types')->whereIn('code', ['FAV', 'FDV'])->delete();
    }
};
