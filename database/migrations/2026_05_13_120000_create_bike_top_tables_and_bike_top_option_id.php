<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bike_top_categories')) {
            Schema::create('bike_top_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('name');
                $table->string('bike_column', 80)->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('show_in_top_bar')->default(true);
                $table->boolean('show_in_view_cards')->default(false);
                $table->timestamps();
                $table->index(['company_id', 'bike_column'], 'idx_bike_top_categories_company_column');
            });
        }

        if (!Schema::hasTable('bike_top_options')) {
            Schema::create('bike_top_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('category_id')->constrained('bike_top_categories')->cascadeOnDelete();
                $table->string('name');
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('show_in_top_bar')->default(true);
                $table->boolean('show_in_view_cards')->default(false);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('bikes') && !Schema::hasColumn('bikes', 'bike_top_option_id')) {
            Schema::table('bikes', function (Blueprint $table) {
                $table->foreignId('bike_top_option_id')->nullable()->constrained('bike_top_options')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bikes') && Schema::hasColumn('bikes', 'bike_top_option_id')) {
            Schema::table('bikes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('bike_top_option_id');
            });
        }

        Schema::dropIfExists('bike_top_options');
        Schema::dropIfExists('bike_top_categories');
    }
};
