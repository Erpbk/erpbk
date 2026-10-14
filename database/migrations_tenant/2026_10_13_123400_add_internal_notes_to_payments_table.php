<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (Schema::hasColumn('payments', 'internal_notes')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->text('internal_notes')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (! Schema::hasColumn('payments', 'internal_notes')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('internal_notes');
        });
    }
};
