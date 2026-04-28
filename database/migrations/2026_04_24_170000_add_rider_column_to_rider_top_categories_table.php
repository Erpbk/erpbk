<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rider_top_categories') || Schema::hasColumn('rider_top_categories', 'rider_column')) {
            return;
        }

        Schema::table('rider_top_categories', function (Blueprint $table) {
            $table->string('rider_column', 80)->nullable()->after('name');
            $table->index(['company_id', 'rider_column'], 'idx_rider_top_categories_company_column');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rider_top_categories') || !Schema::hasColumn('rider_top_categories', 'rider_column')) {
            return;
        }

        Schema::table('rider_top_categories', function (Blueprint $table) {
            $table->dropIndex('idx_rider_top_categories_company_column');
            $table->dropColumn('rider_column');
        });
    }
};
