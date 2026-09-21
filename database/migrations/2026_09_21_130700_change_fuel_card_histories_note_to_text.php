<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fuel_card_histories') && Schema::hasColumn('fuel_card_histories', 'note')) {
            DB::statement('ALTER TABLE `fuel_card_histories` MODIFY `note` TEXT NULL');
        }

        if (Schema::hasTable('sim_histories') && Schema::hasColumn('sim_histories', 'notes')) {
            DB::statement('ALTER TABLE `sim_histories` MODIFY `notes` TEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fuel_card_histories') && Schema::hasColumn('fuel_card_histories', 'note')) {
            DB::statement('ALTER TABLE `fuel_card_histories` MODIFY `note` VARCHAR(255) NULL');
        }

        // sim_histories.notes was created as TEXT; leave it as TEXT on rollback.
    }
};
