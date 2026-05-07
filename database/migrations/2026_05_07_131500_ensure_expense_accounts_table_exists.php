<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expense_accounts')) {
            Schema::create('expense_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id')->nullable()->unique();
                $table->string('name')->nullable();
                $table->unsignedBigInteger('rider_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->timestamps();

                $table->index('rider_id', 'expense_accounts_rider_id_index');
                $table->index('company_id', 'expense_accounts_company_id_index');
            });
            return;
        }

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
    }

    public function down(): void
    {
        // Intentionally keep table for safety in live environments.
    }
};

