<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_activities') || ! Schema::hasColumn('rider_activities', 'd_rider_id')) {
            return;
        }

        DB::statement(
            'ALTER TABLE `rider_activities` MODIFY `d_rider_id` VARCHAR(255) NULL'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('rider_activities') || ! Schema::hasColumn('rider_activities', 'd_rider_id')) {
            return;
        }

        DB::statement(
            'ALTER TABLE `rider_activities` MODIFY `d_rider_id` BIGINT(20) NULL'
        );
    }
};
