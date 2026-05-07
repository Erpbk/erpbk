<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expense_accounts')) {
            Schema::table('expense_accounts', function (Blueprint $table) {
                if (!Schema::hasColumn('expense_accounts', 'name')) {
                    $table->string('name')->nullable()->after('account_id');
                }
                if (!Schema::hasColumn('expense_accounts', 'rider_id')) {
                    $table->unsignedBigInteger('rider_id')->nullable()->after('name');
                    $table->index('rider_id', 'expense_accounts_rider_id_index');
                }
                if (!Schema::hasColumn('expense_accounts', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('rider_id');
                    $table->index('company_id', 'expense_accounts_company_id_index');
                }
            });

            // Backward-compatible: allow new rows without legacy account_id.
            try {
                DB::statement('ALTER TABLE expense_accounts MODIFY account_id BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
                // Ignore if platform/sql mode does not support this operation.
            }
        }

        if (Schema::hasTable('visa_expenses') && !Schema::hasColumn('visa_expenses', 'expense_account_id')) {
            Schema::table('visa_expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('expense_account_id')->nullable()->after('rider_id');
                $table->index('expense_account_id', 'visa_expenses_expense_account_id_index');
            });
        }

        if (Schema::hasTable('visa_expenses') && Schema::hasTable('expense_accounts')) {
            DB::statement("
                UPDATE visa_expenses ve
                LEFT JOIN expense_accounts ea ON ea.account_id = ve.rider_id
                SET ve.expense_account_id = ea.id
                WHERE ve.expense_account_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('visa_expenses') && Schema::hasColumn('visa_expenses', 'expense_account_id')) {
            Schema::table('visa_expenses', function (Blueprint $table) {
                $table->dropIndex('visa_expenses_expense_account_id_index');
                $table->dropColumn('expense_account_id');
            });
        }
    }
};

