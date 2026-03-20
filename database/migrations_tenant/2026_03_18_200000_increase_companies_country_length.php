<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Increase `companies.country` length to avoid "Data too long" errors.
     */
    public function up(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'country')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->string('country', 255)->change();
        });
    }

    /**
     * Keep rollback safe; we won't force a smaller length automatically.
     */
    public function down(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'country')) {
            return;
        }

        // Intentionally no-op to avoid accidental truncation.
    }
};

