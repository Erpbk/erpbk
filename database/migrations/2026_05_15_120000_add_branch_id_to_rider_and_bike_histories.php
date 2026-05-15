<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rider_histories') && !Schema::hasColumn('rider_histories', 'branch_id')) {
            Schema::table('rider_histories', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('rider_id');
                $table->index('branch_id');
            });
        }

        if (Schema::hasTable('bike_histories') && !Schema::hasColumn('bike_histories', 'branch_id')) {
            Schema::table('bike_histories', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('bike_id');
                $table->index('branch_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rider_histories') && Schema::hasColumn('rider_histories', 'branch_id')) {
            Schema::table('rider_histories', function (Blueprint $table) {
                $table->dropIndex(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }

        if (Schema::hasTable('bike_histories') && Schema::hasColumn('bike_histories', 'branch_id')) {
            Schema::table('bike_histories', function (Blueprint $table) {
                $table->dropIndex(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
};
