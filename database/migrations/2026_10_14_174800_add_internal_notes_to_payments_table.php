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
        if (! Schema::hasTable('payments') || Schema::hasColumn('payments', 'internal_notes')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->text('internal_notes')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'internal_notes')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('internal_notes');
        });
    }
};
