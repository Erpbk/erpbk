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
                // Continue seeding other companies if one fails (e.g. missing COA heads).
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fixed_assets') && Schema::hasColumn('fixed_assets', 'bike_id')) {
            Schema::table('fixed_assets', function (Blueprint $table) {
                if ($this->foreignKeyExists('fixed_assets', 'fixed_assets_bike_id_foreign')) {
                    $table->dropForeign(['bike_id']);
                }
                $table->dropColumn('bike_id');
            });
        }

        if (Schema::hasTable('asset_categories') && Schema::hasColumn('asset_categories', 'is_system')) {
            Schema::table('asset_categories', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ? LIMIT 1',
            [$database, $table, $foreignKey, 'FOREIGN KEY']
        );

        return !empty($result);
    }
};
