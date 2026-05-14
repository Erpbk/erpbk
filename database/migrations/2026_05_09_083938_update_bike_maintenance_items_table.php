<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bike_maintenance_items', function (Blueprint $table) {
            // Add inventory tracking columns
            $table->decimal('cost', 10, 2)->nullable()->after('total_amount')->default(0);
            $table->unsignedBigInteger('inventory_purchase_id')->nullable()->after('item_id');
            $table->decimal('total_cost', 10, 2)->nullable()->after('cost')->default(0);
            $table->decimal('profit', 10, 2)->nullable()->after('total_cost')->default(0);
            
            // Add index for better performance
            $table->index('inventory_purchase_id');
        });
    }

    public function down()
    {
        Schema::table('bike_maintenance_items', function (Blueprint $table) {
            
            // Drop columns
            $table->dropColumn(['inventory_purchase_id', 'actual_cost', 'cost', 'profit']);
        });
    }
};