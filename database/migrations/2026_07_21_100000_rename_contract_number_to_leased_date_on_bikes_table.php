<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bikes') || !Schema::hasColumn('bikes', 'contract_number')) {
            return;
        }

        // Old column held contract codes (varchar); clear anything that is not in Y-m-d
        // form. A regex is used instead of STR_TO_DATE because strict SQL mode makes
        // STR_TO_DATE raise error 1411 on invalid values instead of returning NULL.
        DB::table('bikes')
            ->whereNotNull('contract_number')
            ->whereRaw("contract_number NOT REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'")
            ->update(['contract_number' => null]);

        // Relax strict mode for the ALTER so residual invalid dates (e.g. 2023-02-30)
        // become zero dates instead of aborting, then null them out.
        $sqlMode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        DB::statement("SET SESSION sql_mode = ''");
        try {
            DB::statement('ALTER TABLE `bikes` CHANGE `contract_number` `leased_date` DATE NULL DEFAULT NULL');
            DB::statement("UPDATE `bikes` SET `leased_date` = NULL WHERE CAST(`leased_date` AS CHAR) = '0000-00-00'");
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$sqlMode]);
        }

        if (Schema::hasTable('bike_field_category_assignments')) {
            DB::table('bike_field_category_assignments')
                ->where('field_key', 'contract_number')
                ->update(['field_key' => 'leased_date', 'display_label' => null, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('bikes') || !Schema::hasColumn('bikes', 'leased_date')) {
            return;
        }

        DB::statement('ALTER TABLE `bikes` CHANGE `leased_date` `contract_number` VARCHAR(50) NULL DEFAULT NULL');

        if (Schema::hasTable('bike_field_category_assignments')) {
            DB::table('bike_field_category_assignments')
                ->where('field_key', 'leased_date')
                ->update(['field_key' => 'contract_number', 'display_label' => null, 'updated_at' => now()]);
        }
    }
};
