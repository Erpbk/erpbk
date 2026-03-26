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
        if (!Schema::hasTable('bikes')) {
            return;
        }

        $hasCurrentKm = Schema::hasColumn('bikes', 'current_km');
        $hasPreviousKm = Schema::hasColumn('bikes', 'previous_km');
        $hasMaintenanceKm = Schema::hasColumn('bikes', 'maintenance_km');

        if ($hasCurrentKm && $hasPreviousKm && $hasMaintenanceKm) {
            return;
        }

        Schema::table('bikes', function (Blueprint $table) {
            if (!Schema::hasColumn('bikes', 'current_km')) {
                $table->decimal('current_km', 10, 3)->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('bikes', 'previous_km')) {
                $table->decimal('previous_km', 10, 3)->nullable()->after('current_km');
            }
            if (!Schema::hasColumn('bikes', 'maintenance_km')) {
                $table->decimal('maintenance_km', 10, 3)->nullable()->after('previous_km');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('bikes')) {
            return;
        }

        $hasCurrentKm = Schema::hasColumn('bikes', 'current_km');
        $hasPreviousKm = Schema::hasColumn('bikes', 'previous_km');
        $hasMaintenanceKm = Schema::hasColumn('bikes', 'maintenance_km');

        if (!$hasCurrentKm && !$hasPreviousKm && !$hasMaintenanceKm) {
            return;
        }

        Schema::table('bikes', function (Blueprint $table) {
            if (Schema::hasColumn('bikes', 'current_km')) {
                $table->dropColumn('current_km');
            }
            if (Schema::hasColumn('bikes', 'previous_km')) {
                $table->dropColumn('previous_km');
            }
            if (Schema::hasColumn('bikes', 'maintenance_km')) {
                $table->dropColumn('maintenance_km');
            }
        });
    }
};
