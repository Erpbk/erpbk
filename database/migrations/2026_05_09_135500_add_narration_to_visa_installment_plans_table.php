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

        Schema::table('visa_installment_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('visa_installment_plans', 'narration')) {
                $table->text('narration')->nullable()->after('reference_number');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('visa_installment_plans')) {
            return;
        }

        Schema::table('visa_installment_plans', function (Blueprint $table) {
            if (Schema::hasColumn('visa_installment_plans', 'narration')) {
                $table->dropColumn('narration');
            }
        });
    }
};
