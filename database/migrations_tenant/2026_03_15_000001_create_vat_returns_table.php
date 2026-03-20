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
        if (Schema::hasTable('vat_returns')) {
            return;
        }
        Schema::create('vat_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter_slot'); // 1-4
            $table->string('quarter_label', 100)->nullable();
            $table->timestamp('filed_at')->nullable();
            $table->string('status', 20)->default('unpaid'); // paid, unpaid
            $table->unsignedBigInteger('filed_by')->nullable();
            $table->timestamps();

            $table->index(['year', 'quarter_slot']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vat_returns');
    }
};
