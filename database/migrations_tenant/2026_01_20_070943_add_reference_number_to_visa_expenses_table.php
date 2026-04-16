<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('visa_expenses')) {
            return;
        }

        if (Schema::hasColumn('visa_expenses', 'reference_number')) {
            return;
        }

        Schema::table('visa_expenses', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('detail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('visa_expenses') || !Schema::hasColumn('visa_expenses', 'reference_number')) {
            return;
        }

        Schema::table('visa_expenses', function (Blueprint $table) {
            $table->dropColumn('reference_number');
        });
    }
};
