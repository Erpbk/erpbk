<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_activity_import_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('rider_activity_import_settings', 'required_fields')) {
                $table->json('required_fields')->nullable()->after('column_mappings');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rider_activity_import_settings', function (Blueprint $table) {
            $table->dropColumn('required_fields');
        });
    }
};
