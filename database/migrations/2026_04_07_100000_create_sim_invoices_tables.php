<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sim_invoices')) {
            Schema::create('sim_invoices', function (Blueprint $table) {
                $table->id();
                $table->date('inv_date');
                $table->integer('vendor_id');
                $table->date('billing_month');
                $table->string('invoice_number', 255)->nullable();
                $table->string('reference_number', 255)->nullable();
                $table->string('sim_invoice_number', 255)->nullable();
                $table->text('descriptions')->nullable();
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->decimal('vat', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->text('notes')->nullable();
                $table->string('attachment', 500)->nullable();
                $table->tinyInteger('status')->default(0)->comment('0=Unpaid, 1=Paid');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('restrict');
                $table->index('vendor_id');
                $table->index('billing_month');
                $table->index('status');
            });
        }

        if (!Schema::hasTable('sim_invoice_items')) {
            Schema::create('sim_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('inv_id');
                $table->unsignedBigInteger('sim_id');
                $table->unsignedTinyInteger('days')->default(1);
                $table->decimal('rental_amount', 10, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->timestamps();

                $table->foreign('inv_id')->references('id')->on('sim_invoices')->onDelete('cascade');
                $table->index('inv_id');
                $table->index('sim_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_invoice_items');
        Schema::dropIfExists('sim_invoices');
    }
};
