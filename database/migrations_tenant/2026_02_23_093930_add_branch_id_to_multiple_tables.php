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
        // Add single branch_id to riders table
        if (Schema::hasTable('riders') && !Schema::hasColumn('riders', 'branch_id')) {
            Schema::table('riders', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')
                      ->nullable()
                      ->after('id');
            });
        }

        // Add single branch_id to bikes table
        if (Schema::hasTable('bikes') && !Schema::hasColumn('bikes', 'branch_id')) {
            Schema::table('bikes', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')
                      ->nullable()
                      ->after('id');
            });
        }

        // Add single branch_id to transactions table
        if (Schema::hasTable('transactions') && !Schema::hasColumn('transactions', 'branch_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')
                      ->nullable()
                      ->after('id');
            });
        }

        // Add single branch_id to vouchers table
        if (Schema::hasTable('vouchers') && !Schema::hasColumn('vouchers', 'branch_id')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')
                      ->nullable()
                      ->after('id');
            });
        }

        // Add JSON column for multiple branch assignments to users table
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'branch_ids')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('branch_ids')
                      ->nullable()
                      ->after('id')
                      ->comment('JSON array of branch IDs the user has access to');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys first, then columns
        if (Schema::hasTable('riders') && Schema::hasColumn('riders', 'branch_id')) {
            Schema::table('riders', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }

        if (Schema::hasTable('bikes') && Schema::hasColumn('bikes', 'branch_id')) {
            Schema::table('bikes', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }

        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'branch_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }

        if (Schema::hasTable('vouchers') && Schema::hasColumn('vouchers', 'branch_id')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'branch_ids')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('branch_ids');
            });
        }
    }
};