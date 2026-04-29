<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('module_setting_categories')) {
            Schema::create('module_setting_categories', function (Blueprint $table) {
                $table->id();
                $table->string('module_key', 80)->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('label');
                $table->string('slug', 120)->nullable();
                $table->boolean('is_system')->default(false);
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('module_field_category_assignments')) {
            Schema::create('module_field_category_assignments', function (Blueprint $table) {
                $table->id();
                $table->string('module_key', 80)->index();
                $table->string('field_key', 120);
                $table->string('field_label', 255)->nullable();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->string('display_label', 255)->nullable();
                $table->boolean('is_visible')->default(true);
                $table->boolean('is_required')->default(false);
                $table->string('input_type', 50)->nullable();
                $table->json('input_config')->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();
                $table->unique(['module_key', 'field_key']);
            });
        }

        if (!Schema::hasTable('module_custom_fields')) {
            Schema::create('module_custom_fields', function (Blueprint $table) {
                $table->id();
                $table->string('module_key', 80)->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->string('label', 255);
                $table->text('help_text')->nullable();
                $table->string('data_type', 50)->default('text');
                $table->boolean('is_mandatory')->default(false);
                $table->string('default_value', 500)->nullable();
                $table->string('input_format', 100)->nullable();
                $table->json('config')->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('module_document_types')) {
            Schema::create('module_document_types', function (Blueprint $table) {
                $table->id();
                $table->string('module_key', 80)->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('key', 80);
                $table->string('label', 255)->nullable();
                $table->enum('type', ['single', 'dual'])->default('single');
                $table->string('front_label', 255)->nullable();
                $table->string('back_label', 255)->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['module_key', 'key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('module_document_types');
        Schema::dropIfExists('module_custom_fields');
        Schema::dropIfExists('module_field_category_assignments');
        Schema::dropIfExists('module_setting_categories');
    }
};
