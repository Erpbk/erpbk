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
        if (!Schema::hasTable('leasing_company_invoices')) {
            return;
        }

        if (Schema::hasColumn('leasing_company_invoices', 'reference_number')) {
            return;
        }
        Schema::table('leasing_company_invoices', function (Blueprint $table) {
            $table->string('reference_number', 255)->nullable()->after('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('leasing_company_invoices')) {
            return;
        }

        if (!Schema::hasColumn('leasing_company_invoices', 'reference_number')) {
            return;
        }
        Schema::table('leasing_company_invoices', function (Blueprint $table) {
            $table->dropColumn('reference_number');
        });
    }
};
