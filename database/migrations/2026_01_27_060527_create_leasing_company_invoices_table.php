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
        if (Schema::hasTable('leasing_company_invoices')) {
            return;
        }
        
        Schema::create('leasing_company_invoices', function (Blueprint $table) {
            $table->id();
            $table->date('inv_date');
            $table->unsignedBigInteger('leasing_company_id');
            $table->date('billing_month');
            $table->string('invoice_number', 255)->nullable();
            $table->text('descriptions')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('vat', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=Unpaid, 1=Paid');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('leasing_company_id')->references('id')->on('leasing_companies')->onDelete('restrict');
            $table->index('leasing_company_id');
            $table->index('billing_month');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leasing_company_invoices');
    }
};
