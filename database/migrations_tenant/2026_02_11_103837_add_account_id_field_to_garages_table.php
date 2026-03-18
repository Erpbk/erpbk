<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First add the column as nullable
        Schema::table('garages', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->after('detail');
        });

        DB::statement("UPDATE garages g
            JOIN accounts a 
                ON a.ref_id = g.id 
                AND a.ref_name = 'Garage'
            SET g.account_id = a.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('garages', function (Blueprint $table) {
            $table->dropColumn('account_id');
        });
    }
};
