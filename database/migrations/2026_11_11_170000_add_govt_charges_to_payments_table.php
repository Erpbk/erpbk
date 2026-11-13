<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'govt_charges')) {
                $table->unsignedDecimal('govt_charges', 8, 2)->nullable()->after('bank_charges_account');
            }
            if (! Schema::hasColumn('payments', 'govt_charges_account')) {
                $table->unsignedBigInteger('govt_charges_account')->nullable()->after('govt_charges');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('payments', 'govt_charges')) {
                $cols[] = 'govt_charges';
            }
            if (Schema::hasColumn('payments', 'govt_charges_account')) {
                $cols[] = 'govt_charges_account';
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
