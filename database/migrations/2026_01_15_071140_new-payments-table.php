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
        // Drop existing payments table if it exists
        if (Schema::hasTable('payments')) {
            Schema::drop('payments');
        }
        
        // Create new payments table
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('bank_id');
            $table->string('amount_type');
            $table->string('payee_account_id');
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->date('date_of_invoice')->nullable();
            $table->date('date_of_payment');
            $table->date('billing_month');
            $table->string('description');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};