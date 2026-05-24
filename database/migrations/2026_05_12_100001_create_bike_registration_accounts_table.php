<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bike_registration_accounts')) {
            return;
        }

        Schema::create('bike_registration_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable()->unique();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('rider_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();

            $table->index('rider_id', 'bike_registration_accounts_rider_id_index');
            $table->index('company_id', 'bike_registration_accounts_company_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bike_registration_accounts');
    }
};
