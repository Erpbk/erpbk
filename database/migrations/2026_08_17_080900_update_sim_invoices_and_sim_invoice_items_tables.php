<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sim_invoices') && Schema::hasColumn('sim_invoices', 'sim_invoice_number')) {
            Schema::table('sim_invoices', function (Blueprint $table) {
                $table->dropColumn('sim_invoice_number');
            });
        }

        if (! Schema::hasTable('sim_invoice_items')) {
            return;
        }

        if (Schema::hasColumn('sim_invoice_items', 'days')) {
            Schema::table('sim_invoice_items', function (Blueprint $table) {
                $table->dropColumn('days');
            });
        }

        if (! Schema::hasColumn('sim_invoice_items', 'additional_charges')) {
            Schema::table('sim_invoice_items', function (Blueprint $table) {
                $table->decimal('additional_charges', 10, 2)->default(0)->after('rental_amount');
            });
        }

        if (! Schema::hasColumn('sim_invoice_items', 'international_usage_charges')) {
            Schema::table('sim_invoice_items', function (Blueprint $table) {
                $table->decimal('international_usage_charges', 10, 2)->default(0)->after('additional_charges');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sim_invoices') && ! Schema::hasColumn('sim_invoices', 'sim_invoice_number')) {
            Schema::table('sim_invoices', function (Blueprint $table) {
                $table->string('sim_invoice_number', 255)->nullable()->after('reference_number');
            });
        }

        if (! Schema::hasTable('sim_invoice_items')) {
            return;
        }

        if (Schema::hasColumn('sim_invoice_items', 'international_usage_charges')) {
            Schema::table('sim_invoice_items', function (Blueprint $table) {
                $table->dropColumn('international_usage_charges');
            });
        }

        if (Schema::hasColumn('sim_invoice_items', 'additional_charges')) {
            Schema::table('sim_invoice_items', function (Blueprint $table) {
                $table->dropColumn('additional_charges');
            });
        }

        if (! Schema::hasColumn('sim_invoice_items', 'days')) {
            Schema::table('sim_invoice_items', function (Blueprint $table) {
                $table->unsignedTinyInteger('days')->default(1)->after('sim_id');
            });
        }
    }
};
