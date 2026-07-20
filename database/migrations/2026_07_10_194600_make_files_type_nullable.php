<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('files')) {
            return;
        }

        DB::statement('ALTER TABLE `files` MODIFY `type` varchar(50) NULL');
        DB::statement('ALTER TABLE `files` MODIFY `type_id` int(11) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('files')) {
            return;
        }

        DB::statement('ALTER TABLE `files` MODIFY `type` varchar(50) NOT NULL');
        DB::statement('ALTER TABLE `files` MODIFY `type_id` int(11) NOT NULL');
    }
};
