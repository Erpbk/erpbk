<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vouchers') && Schema::hasColumn('vouchers', 'voucher_type')) {
            DB::table('vouchers')->where('voucher_type', 'BR')->update(['voucher_type' => 'VR']);
        }

        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'reference_type')) {
            DB::table('transactions')->where('reference_type', 'BR')->update(['reference_type' => 'VR']);
        }

        if (! Schema::hasTable('voucher_types')) {
            return;
        }

        $hasCompanyId = Schema::hasColumn('voucher_types', 'company_id');
        $hasAssignmentCompanyId = Schema::hasTable('voucher_type_module_assignments')
            && Schema::hasColumn('voucher_type_module_assignments', 'company_id');
        $allowedModules = array_keys(config('voucher_modules.modules', []));
        $moduleKeys = array_values(array_intersect(['bikes', 'bike_list', 'vouchers'], $allowedModules));

        $seedOrRenameType = function (?int $companyId) use ($hasCompanyId, $hasAssignmentCompanyId, $moduleKeys): void {
            $brQuery = DB::table('voucher_types')->where('code', 'BR');
            $vrQuery = DB::table('voucher_types')->where('code', 'VR');
            if ($hasCompanyId) {
                $brQuery->where('company_id', $companyId);
                $vrQuery->where('company_id', $companyId);
            }

            $brType = $brQuery->first();
            $vrType = $vrQuery->first();

            if ($brType && ! $vrType) {
                DB::table('voucher_types')->where('id', $brType->id)->update([
                    'code' => 'VR',
                    'label' => 'Vehicle Registration Voucher',
                    'updated_at' => now(),
                ]);
                $vrType = DB::table('voucher_types')->where('id', $brType->id)->first();
            } elseif ($brType && $vrType) {
                if (Schema::hasTable('voucher_type_module_assignments')) {
                    DB::table('voucher_type_module_assignments')
                        ->where('voucher_type_id', $brType->id)
                        ->delete();
                }
                DB::table('voucher_types')->where('id', $brType->id)->delete();
            }

            if (! $vrType) {
                $displayOrderQuery = DB::table('voucher_types');
                if ($hasCompanyId && $companyId !== null) {
                    $displayOrderQuery->where('company_id', $companyId);
                }
                $displayOrder = (int) ($displayOrderQuery->max('display_order') ?? 0) + 1;

                $keys = ['code' => 'VR'];
                $values = [
                    'label' => 'Vehicle Registration Voucher',
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

                $typeQuery = DB::table('voucher_types')->where('code', 'VR');
                if ($hasCompanyId) {
                    $typeQuery->where('company_id', $companyId);
                }
                $vrType = $typeQuery->first();
            } else {
                DB::table('voucher_types')->where('id', $vrType->id)->update([
                    'label' => 'Vehicle Registration Voucher',
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            }

            if (! $vrType || ! Schema::hasTable('voucher_type_module_assignments') || empty($moduleKeys)) {
                return;
            }

            foreach ($moduleKeys as $moduleKey) {
                $assignmentKeys = [
                    'voucher_type_id' => $vrType->id,
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
                    $seedOrRenameType((int) $companyId);
                }

                $orphanTypeIds = DB::table('voucher_types')
                    ->whereNull('company_id')
                    ->whereIn('code', ['BR', 'VR'])
                    ->pluck('id');
                if ($orphanTypeIds->isNotEmpty() && Schema::hasTable('voucher_type_module_assignments')) {
                    DB::table('voucher_type_module_assignments')
                        ->whereIn('voucher_type_id', $orphanTypeIds)
                        ->delete();
                }
                DB::table('voucher_types')->whereNull('company_id')->whereIn('code', ['BR', 'VR'])->delete();

                return;
            }
        }

        $seedOrRenameType(null);
    }

    public function down(): void
    {
        if (Schema::hasTable('vouchers') && Schema::hasColumn('vouchers', 'voucher_type')) {
            DB::table('vouchers')->where('voucher_type', 'VR')->update(['voucher_type' => 'BR']);
        }

        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'reference_type')) {
            DB::table('transactions')->where('reference_type', 'VR')->update(['reference_type' => 'BR']);
        }

        if (! Schema::hasTable('voucher_types')) {
            return;
        }

        $hasCompanyId = Schema::hasColumn('voucher_types', 'company_id');

        $revertType = function (?int $companyId) use ($hasCompanyId): void {
            $vrQuery = DB::table('voucher_types')->where('code', 'VR');
            if ($hasCompanyId) {
                $vrQuery->where('company_id', $companyId);
            }
            $vrType = $vrQuery->first();
            if (! $vrType) {
                return;
            }

            DB::table('voucher_types')->where('id', $vrType->id)->update([
                'code' => 'BR',
                'label' => 'Bike Registration Voucher',
                'updated_at' => now(),
            ]);
        };

        if ($hasCompanyId && Schema::hasTable('companies')) {
            $companyIds = DB::table('companies')->pluck('id');
            if ($companyIds->isNotEmpty()) {
                foreach ($companyIds as $companyId) {
                    $revertType((int) $companyId);
                }

                return;
            }
        }

        $revertType(null);
    }
};
