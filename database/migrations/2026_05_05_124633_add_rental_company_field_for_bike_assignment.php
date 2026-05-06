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
        Schema::table('bikes', function (Blueprint $table) {
            if (!Schema::hasColumn('bikes', 'rental_company_id')) {
                $table->unsignedBigInteger('rental_company_id')->after('rider_id')->nullable();
            }
        });

        Schema::table('bike_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('bike_histories', 'rental_company_id')) {
                $table->unsignedBigInteger('rental_company_id')->after('rider_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bikes', function (Blueprint $table) {
            if (Schema::hasColumn('bikes', 'rental_company_id')) {
                $table->dropColumn('rental_company_id');
            }
        });

        Schema::table('bike_histories', function (Blueprint $table) {
            if (Schema::hasColumn('bike_histories', 'rental_company_id')) {
                $table->dropColumn('rental_company_id');
            }
        });
    }
};
