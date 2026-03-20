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
        // Fresh tenant DBs might not have `riders` table at this migration time.
        // Guard to avoid "Base table or view not found".
        if (!Schema::hasTable('riders')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            if (!Schema::hasColumn('riders', 'road_permit')) {
                $table->string('road_permit')->nullable()->after('license_expiry');
            }
            if (!Schema::hasColumn('riders', 'road_permit_expiry')) {
                $table->date('road_permit_expiry')->nullable()->after('road_permit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('riders')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            if (Schema::hasColumn('riders', 'road_permit')) {
                $table->dropColumn(['road_permit']);
            }
            if (Schema::hasColumn('riders', 'road_permit_expiry')) {
                $table->dropColumn(['road_permit_expiry']);
            }
        });
    }
};