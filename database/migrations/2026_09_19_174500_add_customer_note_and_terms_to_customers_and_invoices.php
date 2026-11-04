<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (! Schema::hasColumn('customers', 'customer_note')) {
                    $table->text('customer_note')->nullable()->after('tax_percentage');
                }
                if (! Schema::hasColumn('customers', 'terms_and_conditions')) {
                    $table->text('terms_and_conditions')->nullable()->after('customer_note');
                }
            });
        }

        if (Schema::hasTable('customer_invoices')) {
            Schema::table('customer_invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('customer_invoices', 'customer_note')) {
                    $table->text('customer_note')->nullable()->after('notes');
                }
                if (! Schema::hasColumn('customer_invoices', 'terms_and_conditions')) {
                    $table->text('terms_and_conditions')->nullable()->after('customer_note');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (Schema::hasColumn('customers', 'terms_and_conditions')) {
                    $table->dropColumn('terms_and_conditions');
                }
                if (Schema::hasColumn('customers', 'customer_note')) {
                    $table->dropColumn('customer_note');
                }
            });
        }

        if (Schema::hasTable('customer_invoices')) {
            Schema::table('customer_invoices', function (Blueprint $table) {
                if (Schema::hasColumn('customer_invoices', 'terms_and_conditions')) {
                    $table->dropColumn('terms_and_conditions');
                }
                if (Schema::hasColumn('customer_invoices', 'customer_note')) {
                    $table->dropColumn('customer_note');
                }
            });
        }
    }
};
