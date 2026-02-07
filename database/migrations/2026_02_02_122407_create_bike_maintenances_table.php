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
        Schema::create('bike_maintenances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bike_id');
            $table->unsignedBigInteger('rider_id')->nullable();
            $table->date('maintenance_date');
            $table->text('description')->nullable();
            $table->decimal('previous_km', 10, 3);
            $table->decimal('current_km', 10, 3);
            $table->decimal('maintenance_at',10,3);
            $table->decimal('overdue_km',10,3)->nullable();
            $table->decimal('overdue_cost_per_km', 6, 2)->nullable();
            $table->decimal('total_cost', 8, 2)->default(0);
            $table->enum('overdue_paidby', ['Rider', 'Company'])->nullable();
            $table->enum('paidby', ['Rider', 'Company'])->default('Company');
            $table->date('billing_month')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bike_maintenances');
    }
};
