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
            if (! Schema::hasColumn('agreement_categories', 'letterhead_path')) {
                $table->string('letterhead_path', 500)->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('agreement_categories')) {
            return;
        }

        Schema::table('agreement_categories', function (Blueprint $table): void {
            if (Schema::hasColumn('agreement_categories', 'letterhead_path')) {
                $table->dropColumn('letterhead_path');
            }
        });
    }
};
