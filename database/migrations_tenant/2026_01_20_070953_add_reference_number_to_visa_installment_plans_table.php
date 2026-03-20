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
        if (!Schema::hasTable('visa_installment_plans')) {
            return;
        }

        Schema::table('visa_installment_plans', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('visa_installment_plans') || !Schema::hasColumn('visa_installment_plans', 'reference_number')) {
            return;
        }

        Schema::table('visa_installment_plans', function (Blueprint $table) {
            $table->dropColumn('reference_number');
        });
    }
};
