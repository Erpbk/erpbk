<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('visa_expenses') || Schema::hasColumn('visa_expenses', 'expiry_date')) {
            return;
        }

        Schema::table('visa_expenses', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('pay_account');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('visa_expenses') || !Schema::hasColumn('visa_expenses', 'expiry_date')) {
            return;
        }

        Schema::table('visa_expenses', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
        });
    }
};
