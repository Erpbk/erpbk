<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $targetCompanyId = 5;
            $tables = Schema::getTableListing();

            foreach ($tables as $table) {
                if (! Schema::hasColumn($table, 'company_id')) {
                    continue;
                }

                DB::table($table)
                    ->where(function ($query) use ($targetCompanyId) {
                        $query->whereNull('company_id')
                            ->orWhere('company_id', '!=', $targetCompanyId);
                    })
                    ->update(['company_id' => $targetCompanyId]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is intentionally not reversible because previous
        // company_id values are overwritten globally.
    }
};
