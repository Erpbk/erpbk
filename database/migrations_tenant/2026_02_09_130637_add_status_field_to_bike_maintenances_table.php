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
        if (!Schema::hasTable('bike_maintenances')) {
            return;
        }

        if (Schema::hasColumn('bike_maintenances', 'status')) {
            return;
        }
        Schema::table('bike_maintenances', function (Blueprint $table) {
            $table->boolean('status')->after('attachment')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('bike_maintenances')) {
            return;
        }

        if (!Schema::hasColumn('bike_maintenances', 'status')) {
            return;
        }
        Schema::table('bike_maintenances', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
