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
        Schema::create('bike_maintenance_items', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('bike_maintenance_id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_name');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('vat', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('bike_maintenance_id');
            $table->index('item_id');
            $table->index(['bike_maintenance_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bike_maintenance_items');
    }
};
