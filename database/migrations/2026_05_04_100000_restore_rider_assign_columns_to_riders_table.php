<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bike assignment and related flows still write designation, emirate_hub, and rider_status_option.
     * They were removed by 2026_04_21_120000_drop_unused_columns_from_riders_table; restore them for live DBs.
     */
    public function up(): void
    {
        if (!Schema::hasTable('riders')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            if (!Schema::hasColumn('riders', 'designation')) {
                $table->string('designation', 50)->nullable();
            }
            if (!Schema::hasColumn('riders', 'emirate_hub')) {
                $table->string('emirate_hub', 191)->nullable();
            }
            if (!Schema::hasColumn('riders', 'rider_status_option')) {
                $table->string('rider_status_option', 50)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('riders')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            $drop = [];
            foreach (['designation', 'emirate_hub', 'rider_status_option'] as $column) {
                if (Schema::hasColumn('riders', $column)) {
                    $drop[] = $column;
                }
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
