<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow inserting pending companies with `database_name = NULL`.
     * This is required because we defer tenant DB creation until admin approval.
     */
    public function up(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'database_name')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->string('database_name', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Keep it nullable to avoid accidental data loss / failing inserts.
    }
};

