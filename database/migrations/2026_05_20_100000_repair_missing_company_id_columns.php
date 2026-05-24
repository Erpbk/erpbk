<?php

use App\Support\CompanyIdColumnEnsurer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent repair: adds company_id to any tenant table that still lacks it.
 * Run after 2026_05_19_100000 if that migration failed partway or new tables were added.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        app(CompanyIdColumnEnsurer::class)->run();
    }

    public function down(): void
    {
        // Non-destructive repair migration; do not drop columns on rollback.
    }
};
