<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToMultipleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Skip accounts table as it already has soft deletes
        
        // Add soft delete columns to customers table
        if (!Schema::hasColumn('customers', 'deleted_at')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }

        // Add soft delete columns to vendors table
        if (!Schema::hasColumn('vendors', 'deleted_at')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }

        // Add soft delete columns to suppliers table
        if (!Schema::hasColumn('suppliers', 'deleted_at')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }

        // Add soft delete columns to leasing_companies table
        if (!Schema::hasColumn('leasing_companies', 'deleted_at')) {
            Schema::table('leasing_companies', function (Blueprint $table) {
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }

        // Add soft delete columns to recruiters table
        if (!Schema::hasColumn('recruiters', 'deleted_at')) {
            Schema::table('recruiters', function (Blueprint $table) {
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove soft delete columns from customers
        if (Schema::hasColumn('customers', 'deleted_at')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn(['deleted_at', 'deleted_by']);
            });
        }

        // Remove soft delete columns from vendors
        if (Schema::hasColumn('vendors', 'deleted_at')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn(['deleted_at', 'deleted_by']);
            });
        }

        // Remove soft delete columns from suppliers
        if (Schema::hasColumn('suppliers', 'deleted_at')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn(['deleted_at', 'deleted_by']);
            });
        }

        // Remove soft delete columns from leasing_companies
        if (Schema::hasColumn('leasing_companies', 'deleted_at')) {
            Schema::table('leasing_companies', function (Blueprint $table) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn(['deleted_at', 'deleted_by']);
            });
        }

        // Remove soft delete columns from recruiters
        if (Schema::hasColumn('recruiters', 'deleted_at')) {
            Schema::table('recruiters', function (Blueprint $table) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn(['deleted_at', 'deleted_by']);
            });
        }
    }
}

