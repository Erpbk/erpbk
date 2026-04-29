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
        Schema::table('items', function (Blueprint $table) {
            // Rename customer_id to ref_id if table doesn't have ref_id yet
            // First check if customer_id exists and ref_id doesn't
            if (Schema::hasColumn('items', 'customer_id') && !Schema::hasColumn('items', 'ref_id')) {
                $table->renameColumn('customer_id', 'ref_id');
            }

            // Add ref_name column after ref_id column)
            $table->string('ref_name')->nullable()->after('barcode'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('ref_name');
            if (Schema::hasColumn('items', 'ref_id')) {
                $table->renameColumn('ref_id', 'customer_id');
            }
        });
    }
};
