<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('supplier_invoices', 'garage_id')) {
            Schema::table('supplier_invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('garage_id')->nullable()->after('supplier_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('supplier_invoices', 'garage_id')) {
            Schema::table('supplier_invoices', function (Blueprint $table) {
                $table->dropColumn('garage_id');
            });
        }
    }
};
