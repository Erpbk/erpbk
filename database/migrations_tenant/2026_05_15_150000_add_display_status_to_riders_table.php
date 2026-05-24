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
        Schema::table('riders', function (Blueprint $table) {
            if (!Schema::hasColumn('riders', 'display_status')) {
                $table->string('display_status', 120)->nullable()->after('rider_status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('riders')) {
            return;
        }
        Schema::table('riders', function (Blueprint $table) {
            if (Schema::hasColumn('riders', 'display_status')) {
                $table->dropColumn('display_status');
            }
        });
    }
};
