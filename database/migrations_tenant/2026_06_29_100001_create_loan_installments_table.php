<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loan_installments')) {
            return;
        }

        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedInteger('installment_no');
            $table->date('due_date');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('principal_amount', 15, 2)->default(0);
            $table->decimal('interest_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('loan_id');
            $table->index('due_date');
            $table->index('status');
            $table->index('company_id');

            $table->foreign('company_id', 'fk_loan_installments_company_id')
                ->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('loan_id', 'fk_loan_installments_loan_id')
                ->references('id')->on('loans')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
    }
};
