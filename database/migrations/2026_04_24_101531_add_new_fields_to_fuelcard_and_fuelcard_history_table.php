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
        Schema::table('fuel_cards', function (Blueprint $table) {
            $table->string('bike_no')->nullable()->after('branch_id');
            $table->string('attachment')->nullable()->after('bike_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_cards', function (Blueprint $table) {
            $table->dropColumn(['bike_no', 'attachment']);
        });
    }
};
