<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loans')) {
            return;
        }

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('loan_number', 50)->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->unsignedBigInteger('receiving_bank_id')->nullable();
            $table->unsignedBigInteger('paying_bank_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->decimal('principal_amount', 15, 2)->default(0);
            $table->decimal('disbursed_amount', 15, 2)->nullable();
            $table->decimal('processing_fee', 15, 2)->default(0);
            $table->decimal('interest_rate', 8, 4)->default(0);
            $table->string('interest_calculation_method', 30)->default('reducing_balance');
            $table->unsignedInteger('term_months')->default(0);
            $table->string('repayment_frequency', 20)->default('monthly');
            $table->date('disbursement_date')->nullable();
            $table->date('first_payment_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('outstanding_principal', 15, 2)->default(0);
            $table->decimal('emi_amount', 15, 2)->nullable();
            $table->string('agreement_ref')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index('company_id');
            $table->index('bank_id');
            $table->index('status');
            $table->index('loan_number');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
