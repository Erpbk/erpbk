<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leasing_company_billing_invoices')) {
            return;
        }

        if (!Schema::hasColumn('leasing_company_billing_invoices', 'leasing_company_id')) {
            return;
        }

        DB::statement('ALTER TABLE `leasing_company_billing_invoices` CHANGE `leasing_company_id` `customer_id` BIGINT UNSIGNED NOT NULL');

        if (Schema::hasColumn('leasing_company_billing_invoices', 'leasing_company_invoice_number')) {
            DB::statement('ALTER TABLE `leasing_company_billing_invoices` CHANGE `leasing_company_invoice_number` `customer_invoice_number` VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('leasing_company_billing_invoices')) {
            return;
        }

        if (!Schema::hasColumn('leasing_company_billing_invoices', 'customer_id')) {
            return;
        }

        DB::statement('ALTER TABLE `leasing_company_billing_invoices` CHANGE `customer_id` `leasing_company_id` INT NOT NULL');

        if (Schema::hasColumn('leasing_company_billing_invoices', 'customer_invoice_number')) {
            DB::statement('ALTER TABLE `leasing_company_billing_invoices` CHANGE `customer_invoice_number` `leasing_company_invoice_number` VARCHAR(255) NULL');
        }
    }
};
