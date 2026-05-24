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
            if (!Schema::hasColumn('attendance', 'working_hours')) {
                $table->decimal('working_hours', 8, 2)->nullable()->after('total_orders');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendance')) {
            return;
        }

        Schema::table('attendance', function (Blueprint $table) {
            if (Schema::hasColumn('attendance', 'working_hours')) {
                $table->dropColumn('working_hours');
            }
        });
    }
};
