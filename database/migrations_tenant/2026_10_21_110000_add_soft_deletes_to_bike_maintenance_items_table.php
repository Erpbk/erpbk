<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bike_maintenance_items')) {
            return;
        }

        Schema::table('bike_maintenance_items', function (Blueprint $table) {
            if (! Schema::hasColumn('bike_maintenance_items', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bike_maintenance_items')) {
            return;
        }

        Schema::table('bike_maintenance_items', function (Blueprint $table) {
            if (Schema::hasColumn('bike_maintenance_items', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
