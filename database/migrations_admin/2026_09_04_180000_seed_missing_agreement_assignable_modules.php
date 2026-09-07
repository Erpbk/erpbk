<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_admin';

    public function up(): void
    {
        if (! Schema::connection('mysql_admin')->hasTable('admin_agreement_assignable_modules')) {
            return;
        }

        $keys = ['customer_invoices', 'bike_on_rent', 'garages_customers'];
        $maxOrder = (int) DB::connection('mysql_admin')
            ->table('admin_agreement_assignable_modules')
            ->max('sort_order');
        $now = now();

        foreach ($keys as $i => $key) {
            DB::connection('mysql_admin')->table('admin_agreement_assignable_modules')->updateOrInsert(
                ['module_key' => $key],
                [
                    'enabled' => true,
                    'sort_order' => $maxOrder + $i + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::connection('mysql_admin')->hasTable('admin_agreement_assignable_modules')) {
            return;
        }

        DB::connection('mysql_admin')
            ->table('admin_agreement_assignable_modules')
            ->whereIn('module_key', ['customer_invoices', 'bike_on_rent', 'garages_customers'])
            ->delete();
    }
};
