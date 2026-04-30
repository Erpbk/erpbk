<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('riders')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            // Keep recruiter_id intact; remove only legacy text recruiter columns.
            if (Schema::hasColumn('riders', 'recruiter')) {
                $table->dropColumn('recruiter');
            }

            if (Schema::hasColumn('riders', 'recuriter')) {
                $table->dropColumn('recuriter');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('riders')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            if (! Schema::hasColumn('riders', 'recruiter')) {
                $table->string('recruiter')->nullable()->after('flowup');
            }

            if (! Schema::hasColumn('riders', 'recuriter')) {
                $table->string('recuriter')->nullable()->after('flowup');
            }
        });
    }
};

