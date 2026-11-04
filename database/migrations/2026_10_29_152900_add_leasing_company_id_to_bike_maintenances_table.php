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
        Schema::table('bike_maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('bike_maintenances', 'garage_id')) {
                $table->unsignedBigInteger('garage_id')->nullable()->change();
            }
            if (! Schema::hasColumn('bike_maintenances', 'leasing_company_id')) {
                $table->unsignedBigInteger('leasing_company_id')->nullable()->after('garage_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bike_maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('bike_maintenances', 'leasing_company_id')) {
                $table->dropColumn('leasing_company_id');
            }
            if (Schema::hasColumn('bike_maintenances', 'garage_id')) {
                $table->unsignedBigInteger('garage_id')->nullable(false)->change();
            }
        });
    }
};
