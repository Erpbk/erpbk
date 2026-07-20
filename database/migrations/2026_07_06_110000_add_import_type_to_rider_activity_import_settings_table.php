<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rider_activity_import_settings')) {
            return;
        }

        if (!Schema::hasColumn('rider_activity_import_settings', 'import_type')) {
            Schema::table('rider_activity_import_settings', function (Blueprint $table) {
                $table->string('import_type', 20)->default('rider')->after('customer_id');
            });

            DB::table('rider_activity_import_settings')->update(['import_type' => 'rider']);
        }

        $this->replaceUniqueConstraint();
    }

    public function down(): void
    {
        if (!Schema::hasTable('rider_activity_import_settings')) {
            return;
        }

        if (Schema::hasColumn('rider_activity_import_settings', 'import_type')) {
            Schema::table('rider_activity_import_settings', function (Blueprint $table) {
                $table->dropUnique('rider_activity_import_company_customer_type_unique');
                $table->unique(['company_id', 'customer_id'], 'rider_activity_import_company_customer_unique');
                $table->dropColumn('import_type');
            });
        }
    }

    private function replaceUniqueConstraint(): void
    {
        try {
            Schema::table('rider_activity_import_settings', function (Blueprint $table) {
                $table->dropUnique('rider_activity_import_company_customer_unique');
            });
        } catch (\Throwable $e) {
            // Constraint may already be replaced.
        }

        try {
            Schema::table('rider_activity_import_settings', function (Blueprint $table) {
                $table->unique(
                    ['company_id', 'customer_id', 'import_type'],
                    'rider_activity_import_company_customer_type_unique'
                );
            });
        } catch (\Throwable $e) {
            // Constraint may already exist.
        }
    }
};
