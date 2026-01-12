<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add soft deletes to visa_expenses table
        if (!Schema::hasColumn('visa_expenses', 'deleted_at')) {
            Schema::table('visa_expenses', function (Blueprint $table) {
                $table->softDeletes();
                $table->index('deleted_at'); // Add index for performance
            });
        }

        // Add deleted_by column to visa_expenses if not exists
        if (!Schema::hasColumn('visa_expenses', 'deleted_by')) {
            Schema::table('visa_expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }

        // Add soft deletes to visa_installment_plans table
        if (!Schema::hasColumn('visa_installment_plans', 'deleted_at')) {
            Schema::table('visa_installment_plans', function (Blueprint $table) {
                $table->softDeletes();
                $table->index('deleted_at'); // Add index for performance
            });
        }

        // Add deleted_by column to visa_installment_plans if not exists
        if (!Schema::hasColumn('visa_installment_plans', 'deleted_by')) {
            Schema::table('visa_installment_plans', function (Blueprint $table) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove deleted_by and soft deletes from visa_installment_plans
        if (Schema::hasColumn('visa_installment_plans', 'deleted_by')) {
            Schema::table('visa_installment_plans', function (Blueprint $table) {
                $table->dropColumn('deleted_by');
            });
        }

        if (Schema::hasColumn('visa_installment_plans', 'deleted_at')) {
            Schema::table('visa_installment_plans', function (Blueprint $table) {
                $table->dropIndex(['deleted_at']); // Drop index on rollback
                $table->dropSoftDeletes();
            });
        }

        // Remove deleted_by and soft deletes from visa_expenses
        if (Schema::hasColumn('visa_expenses', 'deleted_by')) {
            Schema::table('visa_expenses', function (Blueprint $table) {
                $table->dropColumn('deleted_by');
            });
        }

        if (Schema::hasColumn('visa_expenses', 'deleted_at')) {
            Schema::table('visa_expenses', function (Blueprint $table) {
                $table->dropIndex(['deleted_at']); // Drop index on rollback
                $table->dropSoftDeletes();
            });
        }
    }
};
