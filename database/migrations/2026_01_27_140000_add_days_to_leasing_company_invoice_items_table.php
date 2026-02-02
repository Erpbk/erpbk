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
        Schema::table('leasing_company_invoice_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('days')->default(1)->after('bike_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leasing_company_invoice_items', function (Blueprint $table) {
            $table->dropColumn('days');
        });
    }
};
