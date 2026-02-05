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
            $table->string('attachment', 500)->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leasing_company_invoices', function (Blueprint $table) {
            $table->dropColumn('attachment');
        });
    }
};
