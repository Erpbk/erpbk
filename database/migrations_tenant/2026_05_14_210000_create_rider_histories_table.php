<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rider_histories')) {
            return;
        }
        Schema::create('rider_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rider_id');
            $table->string('event_type', 64);
            $table->string('title', 255);
            $table->text('details')->nullable();
            $table->json('meta')->nullable();
            $table->date('effective_date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['rider_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_histories');
    }
};
