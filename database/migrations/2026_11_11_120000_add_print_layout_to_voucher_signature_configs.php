<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('voucher_signature_configs')) {
            return;
        }

        Schema::table('voucher_signature_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('voucher_signature_configs', 'header_settings')) {
                $table->json('header_settings')->nullable()->after('voucher_type_code');
            }
            if (!Schema::hasColumn('voucher_signature_configs', 'signature_position')) {
                $table->string('signature_position', 20)->default('bottom')->after('header_settings');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('voucher_signature_configs')) {
            return;
        }

        Schema::table('voucher_signature_configs', function (Blueprint $table) {
            if (Schema::hasColumn('voucher_signature_configs', 'signature_position')) {
                $table->dropColumn('signature_position');
            }
            if (Schema::hasColumn('voucher_signature_configs', 'header_settings')) {
                $table->dropColumn('header_settings');
            }
        });
    }
};
