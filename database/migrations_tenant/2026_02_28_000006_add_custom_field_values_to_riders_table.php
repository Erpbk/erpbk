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
            $table->json('custom_field_values')->nullable()->after('updated_by');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('riders')) {
            return;
        }
        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn('custom_field_values');
        });
    }
};
