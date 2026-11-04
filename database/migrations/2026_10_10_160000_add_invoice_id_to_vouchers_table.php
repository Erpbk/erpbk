<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vouchers')) {
            return;
        }

        if (Schema::hasColumn('vouchers', 'invoice_id')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->after('rider_id')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vouchers') || ! Schema::hasColumn('vouchers', 'invoice_id')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('invoice_id');
        });
    }
};
