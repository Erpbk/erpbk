<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erp_module_top_categories')) {
            Schema::create('erp_module_top_categories', function (Blueprint $table) {
                $table->id();
                $table->string('module_key', 80);
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('name');
                $table->string('db_column', 80)->nullable();
                $table->string('filter_type', 40)->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('show_in_top_bar')->default(true);
                $table->boolean('show_in_view_cards')->default(false);
                $table->timestamps();

                $table->index(['module_key', 'company_id', 'db_column'], 'idx_erp_top_cat_module_col');
                $table->index(['module_key', 'display_order'], 'idx_erp_top_cat_module_order');
            });
        }

        if (!Schema::hasTable('erp_module_top_options')) {
            Schema::create('erp_module_top_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('category_id')->constrained('erp_module_top_categories')->cascadeOnDelete();
                $table->string('name');
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('show_in_top_bar')->default(true);
                $table->boolean('show_in_view_cards')->default(false);
                $table->timestamps();

                $table->index(['category_id', 'display_order'], 'idx_erp_top_opt_cat_order');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_module_top_options');
        Schema::dropIfExists('erp_module_top_categories');
    }
};
