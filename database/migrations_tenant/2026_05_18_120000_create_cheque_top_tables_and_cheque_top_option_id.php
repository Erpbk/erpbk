<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cheque_top_categories')) {
            Schema::create('cheque_top_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('name');
                $table->string('cheque_column', 80)->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('show_in_top_bar')->default(true);
                $table->boolean('show_in_view_cards')->default(false);
                $table->timestamps();
                $table->index(['company_id', 'cheque_column'], 'idx_cheque_top_categories_company_column');
            });
        }

        if (!Schema::hasTable('cheque_top_options')) {
            Schema::create('cheque_top_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('category_id')->constrained('cheque_top_categories')->cascadeOnDelete();
                $table->string('name');
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('cheques') && !Schema::hasColumn('cheques', 'cheque_top_option_id')) {
            Schema::table('cheques', function (Blueprint $table) {
                $table->foreignId('cheque_top_option_id')->nullable()->constrained('cheque_top_options')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cheques') && Schema::hasColumn('cheques', 'cheque_top_option_id')) {
            Schema::table('cheques', function (Blueprint $table) {
                $table->dropConstrainedForeignId('cheque_top_option_id');
            });
        }

        Schema::dropIfExists('cheque_top_options');
        Schema::dropIfExists('cheque_top_categories');
    }
};
