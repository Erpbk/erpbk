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
        Schema::create('leasing_company_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inv_id');
            $table->unsignedBigInteger('bike_id');
            $table->decimal('rental_amount', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('inv_id')->references('id')->on('leasing_company_invoices')->onDelete('cascade');
            $table->foreign('bike_id')->references('id')->on('bikes')->onDelete('restrict');
            $table->index('inv_id');
            $table->index('bike_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leasing_company_invoice_items');
    }
};
