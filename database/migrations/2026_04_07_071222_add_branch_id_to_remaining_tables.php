<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * List of tables that need branch_id column.
     */
    private const TABLES = [
        'sims', 'fuel_cards', 'rta_fines', 'saliks', 'accounts', 
        'cheques', 'payments', 'receipts', 'banks', 'customers', 
        'customer_invoices', 'vendors', 'recruiters', 'leasing_companies', 
        'garages', 'suppliers'
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('branch_id')
                          ->nullable()
                          ->after('id');
                    $table->index('branch_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};