<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('legal_case_statuses')) {
            Schema::create('legal_case_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code', 20)->nullable()->index();
                $table->string('description', 500)->nullable();
                $table->enum('category', ['Document', 'Permit', 'License', 'Insurance', 'Other'])->default('Other');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_required')->default(false);
                $table->unsignedInteger('display_order')->default(1)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('legal_case_accounts')) {
            Schema::create('legal_case_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('account_id')->nullable();
                $table->string('name')->nullable();
                $table->unsignedBigInteger('rider_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('legal_cases')) {
            Schema::create('legal_cases', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('legal_case_account_id')->nullable()->index();
                $table->unsignedBigInteger('rider_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('date')->nullable();
                $table->string('billing_month')->nullable()->index();
                $table->string('case_status')->nullable()->index();
                $table->string('detail', 500)->nullable();
                $table->string('reference_number')->nullable();
                $table->date('expiry_date')->nullable();
                $table->enum('step_status', ['pending', 'completed'])->default('pending')->index();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
                $table->foreign('legal_case_account_id')
                    ->references('id')
                    ->on('legal_case_accounts')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_cases');
        Schema::dropIfExists('legal_case_accounts');
        Schema::dropIfExists('legal_case_statuses');
    }
};
