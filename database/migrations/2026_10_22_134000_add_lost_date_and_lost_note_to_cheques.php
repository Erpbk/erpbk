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

        if (! Schema::hasColumn('cheques', 'lost_date')) {
            DB::statement(
                'ALTER TABLE `cheques` ADD COLUMN `lost_date` DATE NULL AFTER `stop_payment_date`'
            );
        }

        if (! Schema::hasColumn('cheques', 'lost_note')) {
            DB::statement(
                'ALTER TABLE `cheques` ADD COLUMN `lost_note` VARCHAR(255) NULL AFTER `return_reason`'
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cheques')) {
            return;
        }

        if (Schema::hasColumn('cheques', 'lost_note')) {
            DB::statement('ALTER TABLE `cheques` DROP COLUMN `lost_note`');
        }

        if (Schema::hasColumn('cheques', 'lost_date')) {
            DB::statement('ALTER TABLE `cheques` DROP COLUMN `lost_date`');
        }
    }
};
