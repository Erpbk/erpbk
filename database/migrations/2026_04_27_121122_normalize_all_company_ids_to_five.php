<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
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

            // Some tables (e.g. permissions) have FK constraints to companies.id.
            // Ensure company #5 exists before mass-updating all company_id values.
            if (Schema::hasTable('companies')) {
                $companyExists = DB::table('companies')->where('id', $targetCompanyId)->exists();
                if (! $companyExists) {
                    $now = Carbon::now();
                    DB::table('companies')->insert([
                        'id' => $targetCompanyId,
                        'name' => 'Default Company',
                        'email' => 'default-company-'.$targetCompanyId.'@example.com',
                        'country' => 'N/A',
                        'phone' => '0000000000',
                        'password' => bcrypt('temporary-password-change-me'),
                        'status' => 'approved',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

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
