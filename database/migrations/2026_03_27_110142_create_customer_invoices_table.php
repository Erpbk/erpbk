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
        Schema::create('customer_invoices', function (Blueprint $table) {
            $table->id();
            $table->date('inv_date');
            $table->unsignedBigInteger('customer_id');
            $table->date('billing_month');
            $table->date('date_from');
            $table->date('date_to');
            $table->string('reference')->nullable();
            $table->string('description')->nullable();
            $table->string('notes')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('attachment')->nullable();
            $table->softDeletes(); // adds deleted_at column
            $table->timestamps();

            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_invoices');
    }
};
