<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('asset_categories')) {
            Schema::create('asset_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->string('depreciation_method', 30)->default('straight_line');
                $table->unsignedInteger('useful_life_months')->default(60);
                $table->decimal('salvage_value_percent', 5, 2)->default(0);
                $table->unsignedBigInteger('asset_account_id')->nullable();
                $table->unsignedBigInteger('accumulated_depreciation_account_id')->nullable();
                $table->unsignedBigInteger('depreciation_expense_account_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable();

                $table->unique(['company_id', 'name']);
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('fixed_assets')) {
            Schema::create('fixed_assets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('asset_category_id')->index();
                $table->string('asset_code')->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('serial_number')->nullable();
                $table->string('location')->nullable();
                $table->date('acquisition_date');
                $table->decimal('acquisition_cost', 15, 2);
                $table->decimal('salvage_value', 15, 2)->default(0);
                $table->string('depreciation_method', 30)->default('straight_line');
                $table->unsignedInteger('useful_life_months')->default(60);
                $table->unsignedBigInteger('asset_account_id')->nullable();
                $table->unsignedBigInteger('accumulated_depreciation_account_id')->nullable();
                $table->unsignedBigInteger('depreciation_expense_account_id')->nullable();
                $table->enum('status', ['active', 'fully_depreciated', 'disposed'])->default('active')->index();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable();

                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
                $table->foreign('asset_category_id')->references('id')->on('asset_categories')->restrictOnDelete();
            });
        }

        if (!Schema::hasTable('asset_depreciation_schedules')) {
            Schema::create('asset_depreciation_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('fixed_asset_id')->index();
                $table->unsignedInteger('period_number');
                $table->date('period_date');
                $table->decimal('depreciation_amount', 15, 2);
                $table->decimal('accumulated_depreciation', 15, 2);
                $table->decimal('book_value', 15, 2);
                $table->enum('status', ['pending', 'posted', 'skipped'])->default('pending')->index();
                $table->unsignedBigInteger('voucher_id')->nullable();
                $table->timestamps();

                $table->unique(['fixed_asset_id', 'period_number']);
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
                $table->foreign('fixed_asset_id')->references('id')->on('fixed_assets')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_schedules');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('asset_categories');
    }
};
