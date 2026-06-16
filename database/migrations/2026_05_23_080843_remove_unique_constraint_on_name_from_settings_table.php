<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropUnique('settings_name_unique');
            });
        } catch (\Exception $e) {
            // index doesn't exist, ignore
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unique('name');
        });
    }
};
