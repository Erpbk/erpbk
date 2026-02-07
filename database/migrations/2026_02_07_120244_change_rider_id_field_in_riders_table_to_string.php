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
        DB::statement('SET SESSION sql_mode=""');
        Schema::table('riders', function (Blueprint $table) {

            $table->string('rider_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET SESSION sql_mode=""');
        Schema::table('riders', function (Blueprint $table) {
            $table->bigInteger('rider_id')->change();
        });
    }
};
