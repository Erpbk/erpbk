<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rider_categories')) {
            return;
        }

        Schema::table('rider_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('rider_categories', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rider_categories')) {
            return;
        }

        Schema::table('rider_categories', function (Blueprint $table) {
            if (Schema::hasColumn('rider_categories', 'company_id')) {
                $table->dropColumn('company_id');
            }
        });
    }
};

