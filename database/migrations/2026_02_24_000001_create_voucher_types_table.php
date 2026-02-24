<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('label');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default types from the existing General::VoucherType() list
        $defaults = [
            ['code' => 'JV', 'label' => 'Journal', 'display_order' => 1],
            ['code' => 'LV', 'label' => 'Visa Expense', 'display_order' => 2],
            ['code' => 'RFV', 'label' => 'RTA Fine Voucher', 'display_order' => 3],
            ['code' => 'VL', 'label' => 'Loan Voucher', 'display_order' => 4],
            ['code' => 'AL', 'label' => 'Advance Loan Voucher', 'display_order' => 5],
            ['code' => 'SV', 'label' => 'Salik Voucher', 'display_order' => 6],
            ['code' => 'COD', 'label' => 'COD Voucher', 'display_order' => 7],
            ['code' => 'PN', 'label' => 'Penalty Voucher', 'display_order' => 8],
            ['code' => 'INC', 'label' => 'Incentive Voucher', 'display_order' => 9],
            ['code' => 'PAY', 'label' => 'Payment Voucher', 'display_order' => 10],
            ['code' => 'VC', 'label' => 'Vendor Charges Voucher', 'display_order' => 11],
            ['code' => 'RI', 'label' => 'Rider Invoice Voucher', 'display_order' => 12],
            ['code' => 'GV', 'label' => 'Garage Voucher', 'display_order' => 13],
            ['code' => 'RV', 'label' => 'Receipt Voucher', 'display_order' => 14],
            ['code' => 'PV', 'label' => 'Payments Voucher', 'display_order' => 15],
        ];
        foreach ($defaults as $row) {
            \DB::table('voucher_types')->insert(array_merge($row, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_types');
    }
};
