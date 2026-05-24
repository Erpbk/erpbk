<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendance')) {
            return;
        }

        Schema::table('attendance', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance', 'total_orders')) {
                $table->unsignedInteger('total_orders')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('attendance', 'rejected_orders')) {
                $table->unsignedInteger('rejected_orders')->nullable()->after('total_orders');
            }
            if (!Schema::hasColumn('attendance', 'cancelled_orders')) {
                $table->unsignedInteger('cancelled_orders')->nullable()->after('rejected_orders');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendance')) {
            return;
        }

        Schema::table('attendance', function (Blueprint $table) {
            foreach (['total_orders', 'rejected_orders', 'cancelled_orders'] as $column) {
                if (Schema::hasColumn('attendance', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
