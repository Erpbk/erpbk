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
        Schema::table('leasing_companies', function (Blueprint $table) {
            // Rename rental_amount to trn_number and change type from decimal to string
            $table->renameColumn('rental_amount', 'trn_number');
        });

        // Change column type from decimal to string
        Schema::table('leasing_companies', function (Blueprint $table) {
            $table->string('trn_number', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert type from string to decimal
        Schema::table('leasing_companies', function (Blueprint $table) {
            $table->decimal('trn_number', 10, 2)->nullable()->change();
        });

        Schema::table('leasing_companies', function (Blueprint $table) {
            $table->renameColumn('trn_number', 'rental_amount');
        });
    }
};
