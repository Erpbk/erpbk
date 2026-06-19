<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fixed_assets')) {
            return;
        }

        Schema::table('fixed_assets', function (Blueprint $table) {
            if (!Schema::hasColumn('fixed_assets', 'acquisition_type')) {
                $table->string('acquisition_type', 30)->default('new_purchase')->after('in_service_date')->index();
            }
            if (!Schema::hasColumn('fixed_assets', 'opening_accumulated_depreciation')) {
                $table->decimal('opening_accumulated_depreciation', 15, 2)->default(0)->after('salvage_value');
            }
            if (!Schema::hasColumn('fixed_assets', 'depreciation_as_of_date')) {
                $table->date('depreciation_as_of_date')->nullable()->after('opening_accumulated_depreciation');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fixed_assets')) {
            return;
        }

        Schema::table('fixed_assets', function (Blueprint $table) {
            foreach (['acquisition_type', 'opening_accumulated_depreciation', 'depreciation_as_of_date'] as $column) {
                if (Schema::hasColumn('fixed_assets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
