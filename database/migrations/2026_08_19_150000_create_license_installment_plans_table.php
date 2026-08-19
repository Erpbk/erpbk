<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('license_installment_plans')) {
            return;
        }

        Schema::create('license_installment_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('date')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('billing_month')->nullable()->index();
            $table->string('rider_id')->nullable()->index();
            $table->string('amount')->nullable();
            $table->string('total_amount')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('narration')->nullable();
            $table->string('status')->nullable()->index();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_installment_plans');
    }
};
