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
        if (!Schema::hasTable('bike_maintenance_items')) {
            return;
        }

        if (Schema::hasColumn('bike_maintenance_items', 'charge_to')) {
            return;
        }
        Schema::table('bike_maintenance_items', function (Blueprint $table) {
            $table->string('charge_to')->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('bike_maintenance_items')) {
            return;
        }

        if (!Schema::hasColumn('bike_maintenance_items', 'charge_to')) {
            return;
        }
        Schema::table('bike_maintenance_items', function (Blueprint $table) {
            $table->dropColumn('charge_to');
        });
    }
};
