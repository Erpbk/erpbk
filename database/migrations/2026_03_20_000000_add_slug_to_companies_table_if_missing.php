<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The initial create_companies_table migration did not include `slug`; add it if missing.
     */
    public function up(): void
    {
        if (!Schema::hasTable('companies') || Schema::hasColumn('companies', 'slug')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'slug')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
