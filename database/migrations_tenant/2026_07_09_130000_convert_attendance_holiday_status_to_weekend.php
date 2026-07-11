<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendance') || !Schema::hasColumn('attendance', 'status')) {
            return;
        }

        DB::table('attendance')->where('status', 'holiday')->update(['status' => 'weekend']);

        DB::statement(
            "ALTER TABLE `attendance` MODIFY COLUMN `status` ENUM('present', 'absent', 'late', 'half day', 'on leave', 'weekend') NOT NULL DEFAULT 'absent'"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendance') || !Schema::hasColumn('attendance', 'status')) {
            return;
        }

        DB::statement(
            "ALTER TABLE `attendance` MODIFY COLUMN `status` ENUM('present', 'absent', 'late', 'half day', 'on leave', 'holiday', 'weekend') NOT NULL DEFAULT 'absent'"
        );

        DB::table('attendance')->where('status', 'weekend')->update(['status' => 'holiday']);
    }
};
