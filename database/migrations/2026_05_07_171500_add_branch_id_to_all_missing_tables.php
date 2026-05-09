<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables intentionally excluded from global branch_id patching.
     */
    private const EXCLUDED_TABLES = [
        'migrations',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'jobs',
        'job_batches',
        'cache',
        'cache_locks',
        'sessions',
    ];

    public function up(): void
    {
        $database = DB::getDatabaseName();
        $rows = DB::select("SHOW FULL TABLES FROM `{$database}` WHERE Table_type = 'BASE TABLE'");

        foreach ($rows as $row) {
            $table = array_values((array) $row)[0] ?? null;
            if (!$table || in_array($table, self::EXCLUDED_TABLES, true)) {
                continue;
            }

            if (!Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $column = $blueprint->unsignedBigInteger('branch_id')->nullable();
                    if (Schema::hasColumn($table, 'id')) {
                        $column->after('id');
                    }
                });
            }
        }
    }

    public function down(): void
    {
        $database = DB::getDatabaseName();
        $rows = DB::select("SHOW FULL TABLES FROM `{$database}` WHERE Table_type = 'BASE TABLE'");

        foreach ($rows as $row) {
            $table = array_values((array) $row)[0] ?? null;
            if (!$table || in_array($table, self::EXCLUDED_TABLES, true)) {
                continue;
            }

            if (Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('branch_id');
                });
            }
        }
    }
};
