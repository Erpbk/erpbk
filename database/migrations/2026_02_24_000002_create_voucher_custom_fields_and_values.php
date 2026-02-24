<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->text('help_text')->nullable();
            $table->string('data_type', 50);
            $table->boolean('is_mandatory')->default(false);
            $table->json('config')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->json('data_privacy')->nullable();
            $table->boolean('prevent_duplicate_values')->default(false);
            $table->string('default_value', 500)->nullable();
            $table->string('input_format', 100)->nullable();
            $table->timestamps();
        });

        if (!Schema::hasColumn('vouchers', 'custom_field_values')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->json('custom_field_values')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_custom_fields');
        if (Schema::hasColumn('vouchers', 'custom_field_values')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->dropColumn('custom_field_values');
            });
        }
    }
};
