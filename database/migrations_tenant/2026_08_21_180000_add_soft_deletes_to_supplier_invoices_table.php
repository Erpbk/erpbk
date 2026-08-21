<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_invoices')) {
            return;
        }

        if (! Schema::hasColumn('supplier_invoices', 'deleted_at')) {
            Schema::table('supplier_invoices', function (Blueprint $table) {
                $table->softDeletes();
                $table->index('deleted_at');
            });
        }

        if (! Schema::hasColumn('supplier_invoices', 'deleted_by')) {
            Schema::table('supplier_invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('supplier_invoices')) {
            return;
        }

        if (Schema::hasColumn('supplier_invoices', 'deleted_by')) {
            Schema::table('supplier_invoices', function (Blueprint $table) {
                $table->dropColumn('deleted_by');
            });
        }

        if (Schema::hasColumn('supplier_invoices', 'deleted_at')) {
            Schema::table('supplier_invoices', function (Blueprint $table) {
                $table->dropIndex(['deleted_at']);
                $table->dropSoftDeletes();
            });
        }
    }
};
