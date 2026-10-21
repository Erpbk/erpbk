<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cheques')) {
            return;
        }

        if (! Schema::hasColumn('cheques', 'cleared_amount')) {
            DB::statement(
                'ALTER TABLE `cheques` ADD COLUMN `cleared_amount` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `amount`'
            );
        }

        DB::statement(
            "ALTER TABLE `cheques` MODIFY COLUMN `status` ENUM('Issued','Partially Cleared','Cleared','Returned','Stop Payment','Lost') NOT NULL DEFAULT 'Issued'"
        );

        // Existing fully cleared cheques: treat as 100% cleared.
        DB::table('cheques')
            ->where('status', 'Cleared')
            ->where(function ($q) {
                $q->whereNull('cleared_amount')->orWhere('cleared_amount', 0);
            })
            ->update(['cleared_amount' => DB::raw('`amount`')]);

        if (Schema::hasTable('cheque_field_category_assignments') && Schema::hasTable('cheque_categories')) {
            $categoryId = DB::table('cheque_categories')->where('slug', 'cheque_details')->value('id')
                ?? DB::table('cheque_categories')->where('slug', 'other')->value('id');

            if ($categoryId) {
                $maxOrder = (int) DB::table('cheque_field_category_assignments')->max('display_order');
                DB::table('cheque_field_category_assignments')->updateOrInsert(
                    ['field_key' => 'cleared_amount', 'company_id' => null],
                    [
                        'category_id' => $categoryId,
                        'display_order' => $maxOrder + 1,
                        'is_visible' => true,
                        'is_required' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cheques')) {
            return;
        }

        DB::table('cheques')
            ->where('status', 'Partially Cleared')
            ->update(['status' => 'Issued']);

        DB::statement(
            "ALTER TABLE `cheques` MODIFY COLUMN `status` ENUM('Issued','Cleared','Returned','Stop Payment','Lost') NOT NULL DEFAULT 'Issued'"
        );

        if (Schema::hasColumn('cheques', 'cleared_amount')) {
            DB::statement('ALTER TABLE `cheques` DROP COLUMN `cleared_amount`');
        }

        if (Schema::hasTable('cheque_field_category_assignments')) {
            DB::table('cheque_field_category_assignments')
                ->where('field_key', 'cleared_amount')
                ->whereNull('company_id')
                ->delete();
        }
    }
};
