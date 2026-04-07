<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'slug')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            // Some existing installations already have a non-null `slug` column.
            // Make it nullable so registration can insert without providing it.
            $table->string('slug')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // In rollback scenarios we can't safely re-add NOT NULL without a default.
        // Keep it nullable on purpose.
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'slug')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug')->nullable()->change();
        });
    }
};
