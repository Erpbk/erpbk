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
        Schema::create('liveactivities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rider_id')->nullable();
            $table->unsignedBigInteger('d_rider_id')->nullable();
            $table->string('payout_type', 50)->nullable();
            $table->integer('delivered_orders')->nullable();
            $table->integer('ontime_orders')->nullable();
            $table->decimal('ontime_orders_percentage', 6, 2)->nullable();
            $table->decimal('avg_time', 6, 2)->nullable();
            $table->integer('rejected_orders')->nullable();
            $table->decimal('rejected_orders_percentage', 6, 2)->nullable();
            $table->decimal('login_hr', 6, 2)->nullable();
            $table->date('date')->nullable();
            $table->string('delivery_rating')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liveactivities');
    }
};
