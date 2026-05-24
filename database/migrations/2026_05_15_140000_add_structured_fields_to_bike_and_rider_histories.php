<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bike_histories')) {
            Schema::table('bike_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('bike_histories', 'fleet_supervisor')) {
                    $table->string('fleet_supervisor', 50)->nullable()->after('branch_id');
                }
                if (!Schema::hasColumn('bike_histories', 'bike_number')) {
                    $table->string('bike_number', 100)->nullable()->after('fleet_supervisor');
                }
                if (!Schema::hasColumn('bike_histories', 'history_status')) {
                    $table->string('history_status', 50)->nullable()->after('bike_number');
                }
            });
        }

        if (Schema::hasTable('rider_histories')) {
            Schema::table('rider_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('rider_histories', 'customer_id')) {
                    $table->string('customer_id', 255)->nullable()->after('branch_id');
                }
                if (!Schema::hasColumn('rider_histories', 'fleet_supervisor')) {
                    $table->string('fleet_supervisor', 50)->nullable()->after('customer_id');
                }
                if (!Schema::hasColumn('rider_histories', 'bike_number')) {
                    $table->string('bike_number', 100)->nullable()->after('fleet_supervisor');
                }
                if (!Schema::hasColumn('rider_histories', 'history_status')) {
                    $table->string('history_status', 50)->nullable()->after('bike_number');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bike_histories')) {
            Schema::table('bike_histories', function (Blueprint $table) {
                foreach (['history_status', 'bike_number', 'fleet_supervisor'] as $col) {
                    if (Schema::hasColumn('bike_histories', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('rider_histories')) {
            Schema::table('rider_histories', function (Blueprint $table) {
                foreach (['history_status', 'bike_number', 'fleet_supervisor', 'customer_id'] as $col) {
                    if (Schema::hasColumn('rider_histories', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
