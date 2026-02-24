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
        Schema::table('riders', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')
                  ->nullable()
                  ->after('id');
        });

        // Add single branch_id to bikes table
        Schema::table('bikes', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')
                  ->nullable()
                  ->after('id');
        });

        // Add single branch_id to transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')
                  ->nullable()
                  ->after('id');
        });

        // Add single branch_id to vouchers table
        Schema::table('vouchers', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')
                  ->nullable()
                  ->after('id');
        });

        // Add JSON column for multiple branch assignments to users table
        Schema::table('users', function (Blueprint $table) {
            $table->json('branch_ids')
                  ->nullable()
                  ->after('id')
                  ->comment('JSON array of branch IDs the user has access to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys first, then columns
        Schema::table('riders', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('bikes', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_branch_id']);
            $table->dropColumn(['branch_ids', 'default_branch_id']);
        });
    }
};