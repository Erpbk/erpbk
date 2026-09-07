<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fuel_cards')) {
            return;
        }

        Schema::table('fuel_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_cards', 'monthly_limit')) {
                $table->decimal('monthly_limit', 10, 2)->nullable()->after('service_charges');
            }
            if (!Schema::hasColumn('fuel_cards', 'daily_limit')) {
                $table->decimal('daily_limit', 10, 2)->nullable()->after('monthly_limit');
            }
        });

        DB::table('fuel_cards')->update([
            'monthly_limit' => 500,
            'daily_limit' => 60,
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('fuel_cards')) {
            return;
        }

        Schema::table('fuel_cards', function (Blueprint $table) {
            foreach (['daily_limit', 'monthly_limit'] as $column) {
                if (Schema::hasColumn('fuel_cards', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
