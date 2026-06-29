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
            if (! Schema::hasColumn('agreement_categories', 'letterhead_margins')) {
                $table->json('letterhead_margins')->nullable()->after('letterhead_path');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('agreement_categories')) {
            return;
        }

        Schema::table('agreement_categories', function (Blueprint $table): void {
            if (Schema::hasColumn('agreement_categories', 'letterhead_margins')) {
                $table->dropColumn('letterhead_margins');
            }
        });
    }
};
