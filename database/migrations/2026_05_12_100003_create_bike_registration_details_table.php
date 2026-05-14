<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bike_registration_details')) {
            return;
        }

        Schema::create('bike_registration_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bike_registration_id');
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('bike_registration_id')
                ->references('id')
                ->on('bike_registrations')
                ->onDelete('cascade');

            $table->index('bike_registration_id', 'bike_registration_details_registration_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bike_registration_details');
    }
};
