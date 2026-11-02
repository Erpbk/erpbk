<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['visa_installment_plans', 'license_installment_plans'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'partial_paid_amount')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->json('partial_paid_amount')->nullable()->after('paid_amount');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['visa_installment_plans', 'license_installment_plans'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'partial_paid_amount')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('partial_paid_amount');
                });
            }
        }
    }
};
