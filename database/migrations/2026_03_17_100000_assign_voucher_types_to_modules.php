<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assign each voucher type to the modules where it is used across the ERP.
     * Ensures EXP exists. Sets can_edit/can_delete for vouchers module (true by default).
     */
    public function up(): void
    {
        if (!Schema::hasTable('voucher_types') ||
            !Schema::hasTable('voucher_type_module_assignments')) {
            return;
        }

        // Defensive guard for fresh environments where settings may be created later.
        // This migration intentionally avoids Settings model usage to stay order-safe.
        $settingsTableExists = Schema::hasTable('settings');

        // Ensure Expense Voucher type exists (used by ExpenseController)
        DB::table('voucher_types')->updateOrInsert(
            ['code' => 'EXP'],
            [
                'label' => 'Expense Voucher',
                'display_order' => (int) (DB::table('voucher_types')->max('display_order') ?? 0) + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $assignments = [
            // code => [ [module_key, can_edit_in_vouchers, can_delete_in_vouchers], ... ]
            // When module is 'vouchers', can_edit/can_delete apply; other modules get true/true in DB.
            'JV'   => [['vouchers', true, true], ['rta_fines', true, true], ['visa_expense', true, true]],
            'LV'   => [['vouchers', true, true], ['visa_expense', true, true]],
            'RFV'  => [['vouchers', true, true], ['rta_fines', true, true]],
            'VL'   => [['vouchers', true, true], ['visa_expense', true, true]],
            'AL'   => [['riders', true, true], ['vouchers', true, true]],
            'SV'   => [['vouchers', true, true], ['rta_saliks', true, true]],
            'COD'  => [['riders', true, true], ['vouchers', true, true]],
            'PN'   => [['riders', true, true], ['vouchers', true, true]],
            'INC'  => [['riders', true, true], ['vouchers', true, true]],
            'PAY'  => [['riders', true, true], ['vouchers', true, true]],
            'VC'   => [['riders', true, true], ['vouchers', true, true]],
            'RI'   => [['riders_list', true, true], ['invoices', true, true], ['vouchers', true, true]],
            'GV'   => [['garage_items', true, true], ['vouchers', true, true]],
            'RV'   => [['cash_banks', true, true], ['vouchers', true, true]],
            'PV'   => [['cash_banks', true, true], ['cheques', true, true], ['vouchers', true, true]],
            'EXP'  => [['expenses', true, true], ['vouchers', true, true]],
            'VP'   => [['vat', true, true], ['vouchers', true, true]],
        ];

        $allowedModules = array_keys(config('voucher_modules.modules', []));

        foreach ($assignments as $code => $moduleList) {
            $type = DB::table('voucher_types')->where('code', $code)->first();
            if (!$type) {
                continue;
            }

            if (!$settingsTableExists && empty($allowedModules)) {
                continue;
            }

            $moduleKeys = [];
            $canEditByModule = [];
            $canDeleteByModule = [];
            foreach ($moduleList as $triple) {
                $moduleKey = $triple[0];
                if (!in_array($moduleKey, $allowedModules, true)) {
                    continue;
                }
                $moduleKeys[] = $moduleKey;
                $canEditByModule[$moduleKey] = (bool) ($triple[1] ?? true);
                $canDeleteByModule[$moduleKey] = (bool) ($triple[2] ?? true);
            }
            if (!empty($moduleKeys)) {
                DB::table('voucher_type_module_assignments')
                    ->where('voucher_type_id', $type->id)
                    ->whereNotIn('module_key', $moduleKeys)
                    ->delete();

                foreach ($moduleKeys as $moduleKey) {
                    DB::table('voucher_type_module_assignments')->updateOrInsert(
                        [
                            'voucher_type_id' => $type->id,
                            'module_key' => $moduleKey,
                        ],
                        [
                            'can_edit' => (bool) ($canEditByModule[$moduleKey] ?? true),
                            'can_delete' => (bool) ($canDeleteByModule[$moduleKey] ?? true),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        // Optionally clear assignments; we don't remove EXP as it may be in use
    }
};
