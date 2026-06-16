<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('voucher_types') || !Schema::hasTable('voucher_type_module_assignments')) {
            return;
        }

        DB::table('voucher_types')->updateOrInsert(
            ['code' => 'LE'],
            [
                'label' => 'License Expense Voucher',
                'display_order' => (int) (DB::table('voucher_types')->max('display_order') ?? 0) + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $type = DB::table('voucher_types')->where('code', 'LE')->first();
        if (!$type) {
            return;
        }

        $allowedModules = array_keys(config('voucher_modules.modules', []));
        $moduleKeys = array_values(array_filter(['license_expense', 'vouchers'], fn ($key) => in_array($key, $allowedModules, true)));

        foreach ($moduleKeys as $moduleKey) {
            DB::table('voucher_type_module_assignments')->updateOrInsert(
                [
                    'voucher_type_id' => $type->id,
                    'module_key' => $moduleKey,
                ],
                [
                    'can_edit' => true,
                    'can_delete' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('voucher_types') || !Schema::hasTable('voucher_type_module_assignments')) {
            return;
        }

        $type = DB::table('voucher_types')->where('code', 'LE')->first();
        if ($type) {
            DB::table('voucher_type_module_assignments')
                ->where('voucher_type_id', $type->id)
                ->where('module_key', 'license_expense')
                ->delete();
        }
    }
};
