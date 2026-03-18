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
        // Add soft delete columns to items table
        if (!Schema::hasColumn('items', 'deleted_at')) {
            Schema::table('items', function (Blueprint $table) {
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }

        // Add soft delete columns to rider_item_prices table
        if (!Schema::hasColumn('rider_item_prices', 'deleted_at')) {
            Schema::table('rider_item_prices', function (Blueprint $table) {
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove soft delete columns from items table
        if (Schema::hasColumn('items', 'deleted_at')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn(['deleted_at', 'deleted_by']);
            });
        }

        // Remove soft delete columns from rider_item_prices table
        if (Schema::hasColumn('rider_item_prices', 'deleted_at')) {
            Schema::table('rider_item_prices', function (Blueprint $table) {
                $table->dropColumn(['deleted_at', 'deleted_by']);
            });
        }
    }
};
