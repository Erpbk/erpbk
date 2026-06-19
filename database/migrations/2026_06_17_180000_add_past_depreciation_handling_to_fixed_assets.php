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
            if (!Schema::hasColumn('fixed_assets', 'past_depreciation_handling')) {
                $table->string('past_depreciation_handling', 30)->nullable()->after('depreciation_as_of_date');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fixed_assets')) {
            return;
        }

        Schema::table('fixed_assets', function (Blueprint $table) {
            if (Schema::hasColumn('fixed_assets', 'past_depreciation_handling')) {
                $table->dropColumn('past_depreciation_handling');
            }
        });
    }
};
