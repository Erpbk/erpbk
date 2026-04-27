<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $databaseName = DB::connection()->getDatabaseName();

        $tables = DB::select(
            "SELECT TABLE_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND COLUMN_NAME = 'company_id'
             ORDER BY TABLE_NAME",
            [$databaseName]
        );

        foreach ($tables as $table) {
            DB::table($table->TABLE_NAME)
                ->where(function ($query) {
                    $query->whereNull('company_id')
                        ->orWhere('company_id', '<>', 5);
                })
                ->update(['company_id' => 5]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a one-way data normalization migration.
    }
};
