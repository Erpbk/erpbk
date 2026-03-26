<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('riders')) {
            return;
        }

        if (Schema::hasColumn('riders', 'rider_status_option')) {
            return;
        }
        Schema::table('riders', function (Blueprint $table) {
            $table->string('rider_status_option', 50)->nullable()->after('designation');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('riders')) {
            return;
        }

        if (!Schema::hasColumn('riders', 'rider_status_option')) {
            return;
        }
        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn('rider_status_option');
        });
    }
};
