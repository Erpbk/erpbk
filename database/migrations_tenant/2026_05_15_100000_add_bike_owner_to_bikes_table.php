<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bikes')) {
            return;
        }
        Schema::table('bikes', function (Blueprint $table) {
            if (!Schema::hasColumn('bikes', 'bike_owner')) {
                $table->string('bike_owner', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bikes')) {
            return;
        }
        Schema::table('bikes', function (Blueprint $table) {
            if (Schema::hasColumn('bikes', 'bike_owner')) {
                $table->dropColumn('bike_owner');
            }
        });
    }
};
