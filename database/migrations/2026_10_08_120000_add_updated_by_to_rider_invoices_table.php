<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rider_invoices') && ! Schema::hasColumn('rider_invoices', 'updated_by')) {
            Schema::table('rider_invoices', function (Blueprint $table) {
                $after = Schema::hasColumn('rider_invoices', 'deleted_by') ? 'deleted_by' : 'status';
                $table->unsignedBigInteger('updated_by')->nullable()->after($after);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rider_invoices') && Schema::hasColumn('rider_invoices', 'updated_by')) {
            Schema::table('rider_invoices', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }
    }
};
