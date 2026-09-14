<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('visa_installment_plans') && !Schema::hasColumn('visa_installment_plans', 'paid_amount')) {
            Schema::table('visa_installment_plans', function (Blueprint $table) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('amount');
            });
        }

        if (Schema::hasTable('license_installment_plans') && !Schema::hasColumn('license_installment_plans', 'paid_amount')) {
            Schema::table('license_installment_plans', function (Blueprint $table) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('visa_installment_plans', 'paid_amount')) {
            Schema::table('visa_installment_plans', function (Blueprint $table) {
                $table->dropColumn('paid_amount');
            });
        }

        if (Schema::hasColumn('license_installment_plans', 'paid_amount')) {
            Schema::table('license_installment_plans', function (Blueprint $table) {
                $table->dropColumn('paid_amount');
            });
        }
    }
};
