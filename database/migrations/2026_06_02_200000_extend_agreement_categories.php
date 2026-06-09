<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agreement_categories')) {
            return;
        }

        Schema::table('agreement_categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('agreement_categories', 'agreement_code')) {
                $table->string('agreement_code', 80)->nullable()->after('company_id');
            }

            if (! Schema::hasColumn('agreement_categories', 'description')) {
                $table->longText('description')->nullable()->after('agreement_code');
            }

            if (! Schema::hasColumn('agreement_categories', 'assigned_modules')) {
                // Example: ["riders","employees"]
                $table->json('assigned_modules')->nullable()->after('description');
            }

            // Unique constraint to safely enforce agreement codes per company.
            // (MySQL allows multiple NULL values under a unique index.)
            $table->unique(['company_id', 'agreement_code'], 'agreement_categories_company_code_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('agreement_categories')) {
            return;
        }

        Schema::table('agreement_categories', function (Blueprint $table): void {
            if (Schema::hasColumn('agreement_categories', 'assigned_modules')) {
                $table->dropColumn('assigned_modules');
            }
            if (Schema::hasColumn('agreement_categories', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('agreement_categories', 'agreement_code')) {
                $table->dropColumn('agreement_code');
            }
        });
    }
};

