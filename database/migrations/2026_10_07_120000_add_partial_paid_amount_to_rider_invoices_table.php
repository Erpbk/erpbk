<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rider_invoices') && ! Schema::hasColumn('rider_invoices', 'partial_paid_amount')) {
            Schema::table('rider_invoices', function (Blueprint $table) {
                $table->string('partial_paid_amount')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rider_invoices') && Schema::hasColumn('rider_invoices', 'partial_paid_amount')) {
            Schema::table('rider_invoices', function (Blueprint $table) {
                $table->dropColumn('partial_paid_amount');
            });
        }
    }
};
