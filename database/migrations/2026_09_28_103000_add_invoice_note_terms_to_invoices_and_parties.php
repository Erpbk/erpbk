<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $invoiceTables = [
        'rider_invoices',
        'employee_invoices',
        'supplier_invoices',
        'sim_invoices',
        'leasing_company_invoices',
        'leasing_company_billing_invoices',
    ];

    /** @var list<string> */
    private array $partyTables = [
        'riders',
        'employees',
        'suppliers',
        'sim_companies',
        'leasing_companies',
        'bike_rent_companies',
    ];

    public function up(): void
    {
        foreach ($this->invoiceTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'customer_note')) {
                    $blueprint->text('customer_note')->nullable()->after('notes');
                }
                if (! Schema::hasColumn($table, 'terms_and_conditions')) {
                    $after = Schema::hasColumn($table, 'customer_note') ? 'customer_note' : 'notes';
                    $blueprint->text('terms_and_conditions')->nullable()->after($after);
                }
            });
        }

        foreach ($this->partyTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'invoice_note')) {
                    $blueprint->text('invoice_note')->nullable();
                }
                if (! Schema::hasColumn($table, 'invoice_note_label')) {
                    $blueprint->string('invoice_note_label', 100)->nullable();
                }
                if (! Schema::hasColumn($table, 'terms_and_conditions')) {
                    $blueprint->text('terms_and_conditions')->nullable();
                }
                if (! Schema::hasColumn($table, 'terms_and_conditions_label')) {
                    $blueprint->string('terms_and_conditions_label', 100)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->invoiceTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'terms_and_conditions')) {
                    $blueprint->dropColumn('terms_and_conditions');
                }
                if (Schema::hasColumn($table, 'customer_note')) {
                    $blueprint->dropColumn('customer_note');
                }
            });
        }

        foreach ($this->partyTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                foreach (['terms_and_conditions_label', 'terms_and_conditions', 'invoice_note_label', 'invoice_note'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $blueprint->dropColumn($col);
                    }
                }
            });
        }
    }
};
