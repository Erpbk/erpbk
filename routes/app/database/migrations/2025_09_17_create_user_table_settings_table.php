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
        Schema::create('user_table_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('table_identifier'); // e.g., 'riders_table', 'users_table', etc.
            $table->json('visible_columns')->nullable(); // Array of visible column keys
            $table->json('column_order')->nullable(); // Array of column keys in order
            $table->json('additional_settings')->nullable(); // For future extensibility
            $table->timestamps();

            // Ensure one setting per user per table
            $table->unique(['user_id', 'table_identifier']);

            // Add indexes for performance
            $table->index(['user_id', 'table_identifier']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_table_settings');
    }
};
