<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_adjustments')) {
            return;
        }

        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_purchase_id');
            $table->enum('adjustment_type', ['return_to_supplier', 'transfer_out', 'write_off', 'stock_correction']);
            $table->integer('quantity');
            $table->text('reason')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->unsignedBigInteger('adjusted_by')->nullable();
            $table->timestamp('adjustment_date')->useCurrent();
            $table->text('notes')->nullable();
            
            
            // Indexes
            $table->index('inventory_purchase_id');
            $table->index('adjustment_type');
            $table->index('adjustment_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};