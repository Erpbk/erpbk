<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expense_accounts')) {
            return;
        }

        if (!Schema::hasColumn('expense_accounts', 'module')) {
            Schema::table('expense_accounts', function (Blueprint $table) {
                $table->string('module', 32)->default('visa')->after('rider_id');
                $table->index('module', 'expense_accounts_module_index');
            });
        }

        DB::table('expense_accounts')
            ->where(function ($q) {
                $q->whereNull('module')->orWhere('module', '');
            })
            ->update(['module' => 'visa']);

        if (!Schema::hasTable('license_expenses')) {
            return;
        }

        // Accounts that license_expenses point at directly, and that have no visa_expenses.
        DB::statement("
            UPDATE expense_accounts ea
            SET ea.module = 'license',
                ea.renewal_category_id = NULL
            WHERE EXISTS (
                SELECT 1 FROM license_expenses le
                WHERE le.expense_account_id = ea.id
                  AND le.deleted_at IS NULL
            )
            AND NOT EXISTS (
                SELECT 1 FROM visa_expenses ve
                WHERE ve.expense_account_id = ea.id
                  AND ve.deleted_at IS NULL
            )
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('expense_accounts') || !Schema::hasColumn('expense_accounts', 'module')) {
            return;
        }

        Schema::table('expense_accounts', function (Blueprint $table) {
            $table->dropIndex('expense_accounts_module_index');
            $table->dropColumn('module');
        });
    }
};
