<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bike_registrations')) {
            return;
        }

        Schema::create('bike_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('trans_date')->nullable();
            $table->string('trans_code')->nullable();
            $table->string('date')->nullable();
            $table->string('rider_id')->nullable();
            $table->unsignedBigInteger('bike_registration_account_id')->nullable();
            $table->string('registration_status')->nullable();
            $table->string('detail')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('amount')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('billing_month')->nullable();
            $table->string('pay_account')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('rider_id', 'bike_registrations_rider_id_index');
            $table->index('trans_code', 'bike_registrations_trans_code_index');
            $table->index('registration_status', 'bike_registrations_registration_status_index');
            $table->index('payment_status', 'bike_registrations_payment_status_index');
            $table->index('billing_month', 'bike_registrations_billing_month_index');
            $table->index('trans_date', 'bike_registrations_trans_date_index');
            $table->index('deleted_at', 'bike_registrations_deleted_at_index');
            $table->index('bike_registration_account_id', 'bike_registrations_bike_registration_account_id_index');
            $table->index('company_id', 'idx_bike_registrations_company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bike_registrations');
    }
};
