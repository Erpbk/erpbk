<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bike_maintenances') && Schema::hasColumn('bike_maintenances', 'maintenance_at')) {
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE bike_maintenances MODIFY maintenance_at DECIMAL(10,3) NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE bike_maintenances ALTER COLUMN maintenance_at DROP NOT NULL');
            } elseif ($driver === 'sqlite') {
                // SQLite does not support altering column nullability directly.
                Schema::table('bike_maintenances', function (Blueprint $table) {
                    $table->decimal('maintenance_at', 10, 3)->nullable()->change();
                });
            } else {
                Schema::table('bike_maintenances', function (Blueprint $table) {
                    $table->decimal('maintenance_at', 10, 3)->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bike_maintenances') && Schema::hasColumn('bike_maintenances', 'maintenance_at')) {
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE bike_maintenances MODIFY maintenance_at DECIMAL(10,3) NOT NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE bike_maintenances ALTER COLUMN maintenance_at SET NOT NULL');
            } elseif ($driver === 'sqlite') {
                Schema::table('bike_maintenances', function (Blueprint $table) {
                    $table->decimal('maintenance_at', 10, 3)->nullable(false)->change();
                });
            } else {
                Schema::table('bike_maintenances', function (Blueprint $table) {
                    $table->decimal('maintenance_at', 10, 3)->nullable(false)->change();
                });
            }
        }
    }
};
