<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loans')) {
            return;
        }

        if (Schema::hasColumn('loans', 'interest_calculation_method')) {
            return;
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->string('interest_calculation_method', 30)
                ->default('reducing_balance')
                ->after('interest_rate');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('loans') || ! Schema::hasColumn('loans', 'interest_calculation_method')) {
            return;
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('interest_calculation_method');
        });
    }
};
