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
        $databaseName = DB::getDatabaseName();

        $tables = DB::table('information_schema.columns')
            ->select('table_name')
            ->where('table_schema', $databaseName)
            ->where('column_name', 'company_id')
            ->pluck('table_name');

        foreach ($tables as $tableName) {
            DB::table($tableName)->update(['company_id' => 5]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This data migration is intentionally irreversible.
    }
};
