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
        Schema::create('customer_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inv_id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_name');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('vat', 5, 2)->default(0); // VAT percentage (e.g., 5.00 for 5%)
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('inv_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_invoice_items');
    }
};
