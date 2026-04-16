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
        if (!Schema::hasTable('payments')) {
            return;
        }

        // Central schema may already contain these columns.
        if (Schema::hasColumn('payments', 'bank_charges')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedDecimal('bank_charges', 8, 2)->nullable();
            $table->unsignedBigInteger('bank_charges_account')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        if (!Schema::hasColumn('payments', 'bank_charges') && !Schema::hasColumn('payments', 'bank_charges_account')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'bank_charges')) {
                $table->dropColumn('bank_charges');
            }
            if (Schema::hasColumn('payments', 'bank_charges_account')) {
                $table->dropColumn('bank_charges_account');
            }
        });
    }
};
