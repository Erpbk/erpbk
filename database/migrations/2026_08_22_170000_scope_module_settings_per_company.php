<?php

use App\Services\Bike\BikeDefaultCategoryService;
use App\Services\Employee\EmployeeDefaultCategoryService;
use App\Services\Module\ModuleDefaultCategoryService;
use App\Services\Rider\RiderDefaultCategoryService;
use App\Services\Settings\CloneSharedModuleSettingsToCompanies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app(CloneSharedModuleSettingsToCompanies::class)->run();

        if (Schema::hasTable('bike_categories')) {
            app(BikeDefaultCategoryService::class)->bootstrap();
        }
        if (Schema::hasTable('rider_categories')) {
            app(RiderDefaultCategoryService::class)->bootstrap();
        }
        if (Schema::hasTable('employee_categories')) {
            app(EmployeeDefaultCategoryService::class)->bootstrap();
        }
        if (Schema::hasTable('module_setting_categories')) {
            app(ModuleDefaultCategoryService::class)->bootstrapAllModules();
        }
    }

    public function down(): void
    {
        // Non-destructive: cloned per-company settings rows are left in place.
    }
};
