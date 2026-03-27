<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leasing_company_billing_invoice_items')) {
            return;
        }

        Schema::create('leasing_company_billing_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inv_id');
            $table->unsignedBigInteger('bike_id');
            $table->unsignedTinyInteger('days')->default(1);
            $table->decimal('rental_amount', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->index('inv_id');
            $table->index('bike_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leasing_company_billing_invoice_items');
    }
};

