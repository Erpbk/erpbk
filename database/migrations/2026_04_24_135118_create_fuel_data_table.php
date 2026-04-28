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
        Schema::create('fuel_data', function (Blueprint $table) {
            $table->id();
            $table->string('inv_id');
            $table->string('trans_no');
            $table->dateTime('trans_date');
            $table->date('billing_month');
            $table->unsignedBigInteger('rider_id');
            $table->string('bike_no');
            $table->string('card_no');
            $table->string('auth_code');
            $table->string('site');
            $table->string('product');
            $table->decimal('qty', 10, 2);
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_data');
    }
};
