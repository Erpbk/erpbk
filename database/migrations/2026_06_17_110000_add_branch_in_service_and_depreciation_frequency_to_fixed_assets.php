<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asset_categories') && !Schema::hasColumn('asset_categories', 'depreciation_frequency')) {
            Schema::table('asset_categories', function (Blueprint $table) {
                $table->string('depreciation_frequency', 20)->default('monthly')->after('useful_life_months');
            });
        }

        if (!Schema::hasTable('fixed_assets')) {
            return;
        }

        Schema::table('fixed_assets', function (Blueprint $table) {
            if (!Schema::hasColumn('fixed_assets', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('serial_number')->index();
            }
            if (!Schema::hasColumn('fixed_assets', 'in_service_date')) {
                $table->date('in_service_date')->nullable()->after('acquisition_date');
            }
            if (!Schema::hasColumn('fixed_assets', 'depreciation_frequency')) {
                $table->string('depreciation_frequency', 20)->default('monthly')->after('useful_life_months');
            }
        });

        if (Schema::hasColumn('fixed_assets', 'in_service_date')) {
            DB::table('fixed_assets')
                ->whereNull('in_service_date')
                ->update(['in_service_date' => DB::raw('acquisition_date')]);
        }

        if (Schema::hasColumn('fixed_assets', 'location')) {
            Schema::table('fixed_assets', function (Blueprint $table) {
                $table->dropColumn('location');
            });
        }

        if (Schema::hasTable('branches') && Schema::hasColumn('fixed_assets', 'branch_id')) {
            Schema::table('fixed_assets', function (Blueprint $table) {
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fixed_assets')) {
            Schema::table('fixed_assets', function (Blueprint $table) {
                if (Schema::hasColumn('fixed_assets', 'branch_id')) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                }
                if (!Schema::hasColumn('fixed_assets', 'location')) {
                    $table->string('location')->nullable();
                }
                if (Schema::hasColumn('fixed_assets', 'in_service_date')) {
                    $table->dropColumn('in_service_date');
                }
                if (Schema::hasColumn('fixed_assets', 'depreciation_frequency')) {
                    $table->dropColumn('depreciation_frequency');
                }
            });
        }

        if (Schema::hasTable('asset_categories') && Schema::hasColumn('asset_categories', 'depreciation_frequency')) {
            Schema::table('asset_categories', function (Blueprint $table) {
                $table->dropColumn('depreciation_frequency');
            });
        }
    }
};
