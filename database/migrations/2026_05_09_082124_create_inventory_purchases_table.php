<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_purchases')) {
            return;
        }

        Schema::create('inventory_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('item_name');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('garage_id');
            $table->date('purchase_date');
            $table->string('inv_id', 100)->nullable();
            $table->integer('quantity');
            $table->integer('remaining_quantity');
            $table->decimal('unit_cost', 10, 2);
            $table->string('batch_no', 100);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index('item_id');
            $table->index('supplier_id');
            $table->index('batch_no');
            $table->index('purchase_date');
            $table->index(['item_id', 'remaining_quantity']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_purchases');
    }
};