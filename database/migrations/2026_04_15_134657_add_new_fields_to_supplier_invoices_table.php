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
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->decimal('subtotal',10,2,true);
            $table->decimal('vat',8,2,true);
            $table->string('partial_paid_amount')->nullable();
            $table->string('status')->default('unpaid');
            $table->boolean('is_order')->default(false);
            $table->boolean('is_invoice')->default(false);
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->date('order_date')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropColumn(['subtotal','vat','partial_paid_amount','status','is_order','is_invoice','attachment']);
        });
    }
};
