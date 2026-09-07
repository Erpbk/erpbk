<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expense_accounts') && !Schema::hasColumn('expense_accounts', 'employee_id')) {
            Schema::table('expense_accounts', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('rider_id');
                $table->index('employee_id', 'expense_accounts_employee_id_index');
            });
        }

        if (Schema::hasTable('visa_expenses') && !Schema::hasColumn('visa_expenses', 'employee_id')) {
            Schema::table('visa_expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('rider_id');
                $table->index('employee_id', 'visa_expenses_employee_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('visa_expenses') && Schema::hasColumn('visa_expenses', 'employee_id')) {
            Schema::table('visa_expenses', function (Blueprint $table) {
                $table->dropIndex('visa_expenses_employee_id_index');
                $table->dropColumn('employee_id');
            });
        }

        if (Schema::hasTable('expense_accounts') && Schema::hasColumn('expense_accounts', 'employee_id')) {
            Schema::table('expense_accounts', function (Blueprint $table) {
                $table->dropIndex('expense_accounts_employee_id_index');
                $table->dropColumn('employee_id');
            });
        }
    }
};
