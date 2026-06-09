<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sim_invoices') && !Schema::hasColumn('sim_invoices', 'partial_paid_amount')) {
            Schema::table('sim_invoices', function (Blueprint $table) {
                $table->json('partial_paid_amount')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sim_invoices') && Schema::hasColumn('sim_invoices', 'partial_paid_amount')) {
            Schema::table('sim_invoices', function (Blueprint $table) {
                $table->dropColumn('partial_paid_amount');
            });
        }
    }
};
