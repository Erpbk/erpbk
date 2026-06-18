<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_inventory_assignments')) {
            return;
        }

        if (! Schema::hasColumn('rider_inventory_assignments', 'qty')) {
            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                $table->unsignedInteger('qty')->default(1)->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('rider_inventory_assignments')) {
            return;
        }

        if (Schema::hasColumn('rider_inventory_assignments', 'qty')) {
            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                $table->dropColumn('qty');
            });
        }
    }
};
