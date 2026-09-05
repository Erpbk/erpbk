<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_admin';

    /** @var list<string> */
    private array $moduleKeys = [
        'cash_banks',
        'loans',
        'attendance',
        'items',
        'leads',
        'customer_invoices',
        'rta_fines',
        'rta_saliks',
        'inventory',
        'visa_expense',
        'installments',
        'license_expense',
        'legal_case',
        'expenses',
        'assets',
        'cheques',
    ];

    public function up(): void
    {
        if (! Schema::connection('mysql_admin')->hasTable('admin_agreement_assignable_modules')) {
            return;
        }

        DB::connection('mysql_admin')
            ->table('admin_agreement_assignable_modules')
            ->whereIn('module_key', $this->moduleKeys)
            ->update([
                'enabled' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::connection('mysql_admin')->hasTable('admin_agreement_assignable_modules')) {
            return;
        }

        DB::connection('mysql_admin')
            ->table('admin_agreement_assignable_modules')
            ->whereIn('module_key', $this->moduleKeys)
            ->update([
                'enabled' => true,
                'updated_at' => now(),
            ]);
    }
};
