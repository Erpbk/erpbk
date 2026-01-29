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
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            
            // Basic cheque information
            $table->string('cheque_number')->unique();
            $table->unsignedBigInteger('bank_id');
            $table->decimal('amount', 15, 2);
            $table->string('payee_account')->nullable();
            $table->string('payee_name')->nullable();
            $table->string('payer_account')->nullable();
            $table->string('payer_name')->nullable();
            $table->string('reference')->nullable();
            $table->string('attachment')->nullable();
            $table->string('description')->nullable();
            
            // Dates
            $table->date('issue_date');
            $table->date('cleared_date')->nullable();
            $table->date('returned_date')->nullable();
            $table->date('stop_payment_date')->nullable();
            $table->date('billing_month')->nullable();
            
            // Status and tracking
            $table->enum('status', [
                'Issued',        // Cheque created
                'Cleared',       // Successfully cleared
                'Returned',      // Returned by bank
                'Stop Payment',  // Stop payment requested
                'Lost',          // Lost cheque
            ])->default('Issued');
            
            $table->string('return_reason')->nullable();
            $table->string('stop_payment_reason')->nullable();
            $table->enum('type', ['payable', 'receiveable']);
            $table->foreignId('voucher_id')
                    ->nullable()
                    ->constrained('vouchers')
                    ->onDelete('cascade');            
            // Audit trail
            $table->string('issued_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            
            // Soft deletes
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['cheque_number']);
            $table->index(['bank_id', 'status']);
            $table->index(['issue_date']);
            $table->index(['status', 'cleared_date']);
            $table->index(['payee_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
