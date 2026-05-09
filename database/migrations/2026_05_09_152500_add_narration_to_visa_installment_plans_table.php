<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('visa_installment_plans')) {
            return;
        }

        if (!Schema::hasColumn('visa_installment_plans', 'narration')) {
            Schema::table('visa_installment_plans', function (Blueprint $table) {
                $table->text('narration')->nullable()->after('reference_number');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('visa_installment_plans') || !Schema::hasColumn('visa_installment_plans', 'narration')) {
            return;
        }

        Schema::table('visa_installment_plans', function (Blueprint $table) {
            $table->dropColumn('narration');
        });
    }
};
