<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_admin';

    /** @var list<string> */
    private array $moduleKeys = [
        'passport_handover',
        'rider_inventory',
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
