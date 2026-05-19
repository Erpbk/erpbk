<?php

use App\Support\CompanyIdColumnEnsurer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

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
        // Intentionally non-destructive: dropping company_id from all tables is unsafe in production.
    }
};
