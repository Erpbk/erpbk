<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow optional employee fields when "Required" is disabled in Employee Settings (same as riders).
     */
    public function up(): void
    {
        if (!Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'name')) {
                $table->string('name')->nullable()->change();
            }
            if (Schema::hasColumn('employees', 'company_email')) {
                $table->string('company_email')->nullable()->change();
            }
            if (Schema::hasColumn('employees', 'personal_email')) {
                $table->string('personal_email')->nullable()->change();
            }
            if (Schema::hasColumn('employees', 'nationality_id')) {
                $table->unsignedBigInteger('nationality_id')->nullable()->change();
            }
            if (Schema::hasColumn('employees', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->change();
            }
            if (Schema::hasColumn('employees', 'doj')) {
                $table->date('doj')->nullable()->change();
            }
            if (Schema::hasColumn('employees', 'dob')) {
                $table->date('dob')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'name')) {
                $table->string('name')->nullable(false)->change();
            }
            if (Schema::hasColumn('employees', 'company_email')) {
                $table->string('company_email')->nullable(false)->change();
            }
            if (Schema::hasColumn('employees', 'personal_email')) {
                $table->string('personal_email')->nullable(false)->change();
            }
            if (Schema::hasColumn('employees', 'nationality_id')) {
                $table->unsignedBigInteger('nationality_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('employees', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('employees', 'doj')) {
                $table->date('doj')->nullable(false)->change();
            }
            if (Schema::hasColumn('employees', 'dob')) {
                $table->date('dob')->nullable(false)->change();
            }
        });
    }
};
