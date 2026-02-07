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
            $table->decimal('current_km', 10, 3)->nullable()->after('customer_id');
            $table->decimal('previous_km', 10, 3)->nullable()->after('current_km');
            $table->decimal('maintenance_km',10,3)->nullable()->after('previous_km');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bikes', function (Blueprint $table) {
            $table->dropColumn(['current_km', 'previous_km', 'maintenance_km']);
        });
    }
};
