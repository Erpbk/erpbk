<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rider_inventory_items')) {
            Schema::create('rider_inventory_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('name');
                $table->decimal('item_price', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('display_order')->default(1)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('rider_inventory_assignments')) {
            Schema::create('rider_inventory_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('rider_id')->index();
                $table->unsignedBigInteger('inventory_item_id')->index();
                $table->date('assigned_date');
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->enum('status', ['assigned', 'returned', 'lost'])->default('assigned')->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->date('return_date')->nullable();
                $table->unsignedBigInteger('returned_by')->nullable();
                $table->text('remarks')->nullable();
                $table->string('trans_code')->nullable()->index();
                $table->unsignedBigInteger('voucher_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
                $table->foreign('rider_id')
                    ->references('id')
                    ->on('riders')
                    ->cascadeOnDelete();
                $table->foreign('inventory_item_id')
                    ->references('id')
                    ->on('rider_inventory_items')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_inventory_assignments');
        Schema::dropIfExists('rider_inventory_items');
    }
};
