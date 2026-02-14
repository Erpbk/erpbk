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
            $table->unsignedBigInteger('garage_id')->after('rider_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bike_maintenances', function (Blueprint $table) {
            $table->dropColumn('garage_id');
        });
    }
};
