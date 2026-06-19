<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fixed_assets')) {
            if (Schema::hasColumn('fixed_assets', 'status')) {
                DB::statement(
                    "ALTER TABLE fixed_assets MODIFY status ENUM('draft', 'active', 'fully_depreciated', 'disposed') NOT NULL DEFAULT 'active'"
                );
            }

            Schema::table('fixed_assets', function (Blueprint $table) {
                if (!Schema::hasColumn('fixed_assets', 'acquisition_posting')) {
                    $table->string('acquisition_posting', 30)->nullable()->after('status');
                }
                if (!Schema::hasColumn('fixed_assets', 'acquisition_voucher_id')) {
                    $table->unsignedBigInteger('acquisition_voucher_id')->nullable()->after('acquisition_posting');
                }
            });
        }

    }

    public function down(): void
    {
        if (Schema::hasTable('fixed_assets')) {
            DB::table('fixed_assets')->where('status', 'draft')->update(['status' => 'active']);

            Schema::table('fixed_assets', function (Blueprint $table) {
                if (Schema::hasColumn('fixed_assets', 'acquisition_voucher_id')) {
                    $table->dropColumn('acquisition_voucher_id');
                }
                if (Schema::hasColumn('fixed_assets', 'acquisition_posting')) {
                    $table->dropColumn('acquisition_posting');
                }
            });

            if (Schema::hasColumn('fixed_assets', 'status')) {
                DB::statement(
                    "ALTER TABLE fixed_assets MODIFY status ENUM('active', 'fully_depreciated', 'disposed') NOT NULL DEFAULT 'active'"
                );
            }
        }
    }
};
