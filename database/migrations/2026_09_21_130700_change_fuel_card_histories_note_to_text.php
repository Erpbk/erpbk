<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fuel_card_histories') || ! Schema::hasColumn('fuel_card_histories', 'note')) {
            return;
        }

        DB::statement('ALTER TABLE `fuel_card_histories` MODIFY `note` TEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('fuel_card_histories') || ! Schema::hasColumn('fuel_card_histories', 'note')) {
            return;
        }

        DB::statement('ALTER TABLE `fuel_card_histories` MODIFY `note` VARCHAR(255) NULL');
    }
};
