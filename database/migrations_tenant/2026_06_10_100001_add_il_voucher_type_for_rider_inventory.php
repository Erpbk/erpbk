<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('voucher_types')) {
            return;
        }

        DB::table('voucher_types')->updateOrInsert(
            ['code' => 'IL'],
            [
                'label' => 'Inventory Loss',
                'display_order' => (int) (DB::table('voucher_types')->max('display_order') ?? 0) + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if (!Schema::hasTable('voucher_type_module_assignments')) {
            return;
        }

        $type = DB::table('voucher_types')->where('code', 'IL')->first();
        if (!$type) {
            return;
        }

        $modules = ['rider_inventory', 'vouchers', 'riders'];
        $allowedModules = array_keys(config('voucher_modules.modules', []));

        foreach ($modules as $moduleKey) {
            if (!in_array($moduleKey, $allowedModules, true)) {
                continue;
            }

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
        if (!Schema::hasTable('voucher_types')) {
            return;
        }

        $type = DB::table('voucher_types')->where('code', 'IL')->first();
        if ($type && Schema::hasTable('voucher_type_module_assignments')) {
            DB::table('voucher_type_module_assignments')
                ->where('voucher_type_id', $type->id)
                ->delete();
        }

        DB::table('voucher_types')->where('code', 'IL')->delete();
    }
};
