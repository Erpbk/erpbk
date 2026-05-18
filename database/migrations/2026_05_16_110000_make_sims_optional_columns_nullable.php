<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow optional SIM fields when "Required" is disabled in module field settings.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sims')) {
            return;
        }

        Schema::table('sims', function (Blueprint $table) {
            if (Schema::hasColumn('sims', 'number')) {
                $table->string('number')->nullable()->change();
            }
            if (Schema::hasColumn('sims', 'company')) {
                $table->string('company')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sims')) {
            return;
        }

        Schema::table('sims', function (Blueprint $table) {
            if (Schema::hasColumn('sims', 'number')) {
                $table->string('number')->nullable(false)->change();
            }
            if (Schema::hasColumn('sims', 'company')) {
                $table->string('company')->nullable(false)->change();
            }
        });
    }
};
