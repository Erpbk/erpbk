<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('legal_case_accounts')) {
            Schema::table('legal_case_accounts', function (Blueprint $table) {
                if (!Schema::hasColumn('legal_case_accounts', 'branch_id')) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('rider_id');
                    $table->index('branch_id', 'legal_case_accounts_branch_id_index');
                }
                if (!Schema::hasColumn('legal_case_accounts', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable()->after('branch_id');
                    $table->index('employee_id', 'legal_case_accounts_employee_id_index');
                }
            });
        }

        if (Schema::hasTable('legal_cases') && !Schema::hasColumn('legal_cases', 'employee_id')) {
            Schema::table('legal_cases', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('rider_id');
                $table->index('employee_id', 'legal_cases_employee_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('legal_case_accounts')) {
            Schema::table('legal_case_accounts', function (Blueprint $table) {
                if (Schema::hasColumn('legal_case_accounts', 'employee_id')) {
                    $table->dropIndex('legal_case_accounts_employee_id_index');
                    $table->dropColumn('employee_id');
                }
                if (Schema::hasColumn('legal_case_accounts', 'branch_id')) {
                    $table->dropIndex('legal_case_accounts_branch_id_index');
                    $table->dropColumn('branch_id');
                }
            });
        }

        if (Schema::hasTable('legal_cases') && Schema::hasColumn('legal_cases', 'employee_id')) {
            Schema::table('legal_cases', function (Blueprint $table) {
                $table->dropIndex('legal_cases_employee_id_index');
                $table->dropColumn('employee_id');
            });
        }
    }
};
