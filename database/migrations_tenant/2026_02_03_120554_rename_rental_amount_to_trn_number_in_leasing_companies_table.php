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
        if (!Schema::hasTable('leasing_companies')) {
            return;
        }

        // Central schema may already include `trn_number`.
        if (Schema::hasColumn('leasing_companies', 'trn_number')) {
            return;
        }

        if (!Schema::hasColumn('leasing_companies', 'rental_amount')) {
            return;
        }
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
        if (!Schema::hasTable('leasing_companies')) {
            return;
        }

        if (!Schema::hasColumn('leasing_companies', 'trn_number')) {
            return;
        }

        // If central schema already has `rental_amount`, avoid duplicating.
        if (Schema::hasColumn('leasing_companies', 'rental_amount')) {
            return;
        }
        // Revert type from string to decimal
        Schema::table('leasing_companies', function (Blueprint $table) {
            $table->decimal('trn_number', 10, 2)->nullable()->change();
        });

        Schema::table('leasing_companies', function (Blueprint $table) {
            $table->renameColumn('trn_number', 'rental_amount');
        });
    }
};
