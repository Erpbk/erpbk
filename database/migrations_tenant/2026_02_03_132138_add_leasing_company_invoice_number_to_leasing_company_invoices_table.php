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
        Schema::table('leasing_company_invoices', function (Blueprint $table) {
            $table->string('leasing_company_invoice_number', 255)->nullable()->after('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leasing_company_invoices', function (Blueprint $table) {
            $table->dropColumn('leasing_company_invoice_number');
        });
    }
};
