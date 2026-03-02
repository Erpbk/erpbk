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
        Schema::create('rider_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('help_text')->nullable();
            $table->json('data_privacy')->nullable();
            $table->boolean('prevent_duplicate_values')->default(false);
            $table->string('default_value', 500)->nullable();
            $table->string('input_format', 100)->nullable();
            $table->string('data_type', 50);
            $table->boolean('is_mandatory')->default(false);
            $table->json('config')->nullable();
            $table->string('category', 50);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_custom_fields');
    }
};

