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
        Schema::table('bike_rent_companies', function (Blueprint $table) {
            $table->string('customer_type')->default('bike_rental')->after('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bike_rent_companies', function (Blueprint $table) {
            $table->dropColumn('customer_type');
        });
    }
};
