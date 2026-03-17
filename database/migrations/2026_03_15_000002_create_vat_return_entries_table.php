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
        Schema::create('vat_return_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vat_return_id');
            $table->unsignedBigInteger('transaction_id');
            $table->timestamps();

            $table->foreign('vat_return_id')->references('id')->on('vat_returns')->cascadeOnDelete();
            $table->index('transaction_id');
            $table->unique(['vat_return_id', 'transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vat_return_entries');
    }
};
