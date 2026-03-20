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

        if (Schema::hasColumn('leasing_company_invoices', 'attachment')) {
            return;
        }
        Schema::table('leasing_company_invoices', function (Blueprint $table) {
            $table->string('attachment', 500)->nullable()->after('notes');
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

        if (!Schema::hasColumn('leasing_company_invoices', 'attachment')) {
            return;
        }
        Schema::table('leasing_company_invoices', function (Blueprint $table) {
            $table->dropColumn('attachment');
        });
    }
};
