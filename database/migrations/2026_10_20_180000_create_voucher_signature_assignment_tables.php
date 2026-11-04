<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('voucher_signature_configs')) {
            Schema::create('voucher_signature_configs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('voucher_type_code', 20);
                $table->timestamps();

                $table->unique(
                    ['company_id', 'voucher_type_code'],
                    'voucher_sig_configs_company_type_unique'
                );
            });
        }

        if (!Schema::hasTable('voucher_signature_slots')) {
            Schema::create('voucher_signature_slots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('voucher_signature_config_id');
                $table->string('role_label', 100);
                $table->string('assignee_type', 20)->default('label_only');
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->string('designation_override', 255)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('voucher_signature_config_id', 'voucher_sig_slots_config_fk')
                    ->references('id')
                    ->on('voucher_signature_configs')
                    ->cascadeOnDelete();

                $table->index(
                    ['voucher_signature_config_id', 'sort_order'],
                    'voucher_sig_slots_config_order_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_signature_slots');
        Schema::dropIfExists('voucher_signature_configs');
    }
};
