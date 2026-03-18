<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('rider_invoices', 'deleted_at')) {
            Schema::table('rider_invoices', function (Blueprint $table) {
                $table->softDeletes();
                $table->index('deleted_at'); // Add index for performance
            });
        }

        // Add deleted_by column if not exists
        if (!Schema::hasColumn('rider_invoices', 'deleted_by')) {
            Schema::table('rider_invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('rider_invoices', 'deleted_by')) {
            Schema::table('rider_invoices', function (Blueprint $table) {
                $table->dropColumn('deleted_by');
            });
        }

        if (Schema::hasColumn('rider_invoices', 'deleted_at')) {
            Schema::table('rider_invoices', function (Blueprint $table) {
                $table->dropIndex(['deleted_at']); // Drop index on rollback
                $table->dropSoftDeletes();
            });
        }
    }
};
