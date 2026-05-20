<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_invoices')) {
            Schema::table('employee_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('employee_invoices', 'partial_paid_amount')) {
                    $table->string('partial_paid_amount')->nullable()->after('status');
                }
                if (!Schema::hasColumn('employee_invoices', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('deleted_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_invoices')) {
            Schema::table('employee_invoices', function (Blueprint $table) {
                if (Schema::hasColumn('employee_invoices', 'partial_paid_amount')) {
                    $table->dropColumn('partial_paid_amount');
                }
                if (Schema::hasColumn('employee_invoices', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
            });
        }
    }
};
