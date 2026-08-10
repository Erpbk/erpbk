<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loan_installments')) {
            return;
        }

        if (! Schema::hasColumn('loan_installments', 'late_payment_charges')) {
            Schema::table('loan_installments', function (Blueprint $table) {
                $table->decimal('late_payment_charges', 15, 2)->default(0)->after('total_amount');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('loan_installments') || ! Schema::hasColumn('loan_installments', 'late_payment_charges')) {
            return;
        }

        Schema::table('loan_installments', function (Blueprint $table) {
            $table->dropColumn('late_payment_charges');
        });
    }
};
