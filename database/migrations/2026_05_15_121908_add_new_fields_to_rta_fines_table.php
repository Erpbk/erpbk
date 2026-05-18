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
        Schema::table('rta_fines', function (Blueprint $table) {
            $table->unsignedBigInteger('rental_company_id')->nullable()->after('rider_id');
            $table->decimal('fine_vat')->after('admin_fee')->default(0);
            $table->decimal('service_vat')->after('fine_vat')->default(0);
            $table->decimal('admin_vat')->after('service_vat')->default(0);
            $table->unsignedBigInteger('voucher_id')->after('status')->nullable();
            $table->unsignedBigInteger('paid_voucher_id')->after('voucher_id')->nullable();
            $table->unsignedInteger('black_points')->nullable()->after('total_amount')->default(0);
            $table->boolean('is_impound')->nullable()->after('black_points')->default(false);

            $table->index('rental_company_id');
            $table->index('is_impound');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rta_fines', function (Blueprint $table) {
            $table->dropColumn(['rental_company_id','fine_vat','service_vat','admin_vat','voucher_id','paid_voucher_id']);
        });
    }
};
