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
        Schema::table('bike_maintenance_items', function (Blueprint $table) {
            $table->decimal('vat_amount',7,2)->after('vat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bike_maintenance_items', function (Blueprint $table) {
            $table->dropColumn('vat_amount');
        });
    }
};
