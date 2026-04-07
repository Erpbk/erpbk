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
        if (!Schema::hasTable('vouchers')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('vouchers') || !Schema::hasColumn('vouchers', 'reference_number')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('reference_number');
        });
    }
};
