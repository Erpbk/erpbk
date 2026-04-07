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
        if (!Schema::hasColumn('customer_invoices', 'status')) {
            Schema::table('customer_invoices', function (Blueprint $table) {
                $table->string('status')->default('pending')->after('attachment');
            });
        }

        if (!Schema::hasColumn('customer_invoices', 'partial_paid_amount')) {
            Schema::table('customer_invoices', function (Blueprint $table) {
                $table->string('partial_paid_amount')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('customer_invoices', 'partial_paid_amount')) {
            Schema::table('customer_invoices', function (Blueprint $table) {
                $table->dropColumn('partial_paid_amount');
            });
        }

        if (Schema::hasColumn('customer_invoices', 'status')) {
            Schema::table('customer_invoices', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
