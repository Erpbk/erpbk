<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bike_registration_accounts')) {
            return;
        }
        if (Schema::hasColumn('bike_registration_accounts', 'bike_id')) {
            return;
        }

        Schema::table('bike_registration_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('bike_id')->nullable()->after('rider_id');
            $table->index('bike_id', 'bike_registration_accounts_bike_id_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bike_registration_accounts')) {
            return;
        }
        if (!Schema::hasColumn('bike_registration_accounts', 'bike_id')) {
            return;
        }

        Schema::table('bike_registration_accounts', function (Blueprint $table) {
            $table->dropIndex('bike_registration_accounts_bike_id_index');
            $table->dropColumn('bike_id');
        });
    }
};
