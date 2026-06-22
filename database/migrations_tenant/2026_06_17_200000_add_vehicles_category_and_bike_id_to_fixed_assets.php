<?php

use App\Models\Company;
use App\Services\FixedAssets\VehiclesCategoryService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asset_categories') && !Schema::hasColumn('asset_categories', 'is_system')) {
            Schema::table('asset_categories', function (Blueprint $table) {
                $table->boolean('is_system')->default(false)->after('is_active');
            });
        }

        if (Schema::hasTable('fixed_assets') && !Schema::hasColumn('fixed_assets', 'bike_id')) {
            Schema::table('fixed_assets', function (Blueprint $table) {
                $table->unsignedBigInteger('bike_id')->nullable()->after('asset_category_id')->index();
            });

            if (Schema::hasTable('bikes')) {
                Schema::table('fixed_assets', function (Blueprint $table) {
                    $table->foreign('bike_id')->references('id')->on('bikes')->nullOnDelete();
                });
            }
        }

        if (!Schema::hasTable('asset_categories') || !Schema::hasTable('companies')) {
            return;
        }

        $service = app(VehiclesCategoryService::class);

        $companyIds = DB::table('companies')
            ->when(Schema::hasColumn('companies', 'status'), function ($query) {
                $query->where('status', Company::STATUS_APPROVED);
            })
            ->pluck('id');

        foreach ($companyIds as $companyId) {
            try {
                $service->ensureForCompany((int) $companyId);
            } catch (\Throwable $e) {
                // Continue seeding other companies if one fails.
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fixed_assets') && Schema::hasColumn('fixed_assets', 'bike_id')) {
            Schema::table('fixed_assets', function (Blueprint $table) {
                $table->dropForeign(['bike_id']);
                $table->dropColumn('bike_id');
            });
        }

        if (Schema::hasTable('asset_categories') && Schema::hasColumn('asset_categories', 'is_system')) {
            Schema::table('asset_categories', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }
    }
};
