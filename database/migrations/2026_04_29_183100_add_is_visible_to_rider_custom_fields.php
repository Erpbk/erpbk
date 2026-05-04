<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rider_custom_fields') && !Schema::hasColumn('rider_custom_fields', 'is_visible')) {
            Schema::table('rider_custom_fields', function (Blueprint $table) {
                $table->boolean('is_visible')->default(true)->after('is_mandatory');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rider_custom_fields') && Schema::hasColumn('rider_custom_fields', 'is_visible')) {
            Schema::table('rider_custom_fields', function (Blueprint $table) {
                $table->dropColumn('is_visible');
            });
        }
    }
};

