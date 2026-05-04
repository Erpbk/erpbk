<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leasing_company_billing_invoices')) {
            return;
        }

        Schema::create('leasing_company_billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->date('inv_date');
            $table->unsignedBigInteger('customer_id')->comment('Bike on rent customer (bike_rent_companies.id)');
            $table->date('billing_month');
            $table->string('invoice_number', 255)->nullable();
            $table->string('reference_number', 255)->nullable();
            $table->string('customer_invoice_number', 255)->nullable();
            $table->text('descriptions')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('vat', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('attachment', 500)->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=Unpaid, 1=Paid');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('billing_month');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leasing_company_billing_invoices');
    }
};

