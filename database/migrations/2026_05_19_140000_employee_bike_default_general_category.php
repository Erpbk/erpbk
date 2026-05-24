<?php

use App\Services\Bike\BikeDefaultCategoryService;
use App\Services\Employee\EmployeeDefaultCategoryService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_categories')) {
            $employeeService = app(EmployeeDefaultCategoryService::class);
            $employeeService->bootstrap();
            $employeeService->relocateVisibleFieldsToDefaultCategory();
        }

        if (Schema::hasTable('bike_categories')) {
            $bikeService = app(BikeDefaultCategoryService::class);
            $bikeService->bootstrap();
            $bikeService->relocateVisibleFieldsToDefaultCategory();
        }
    }

    public function down(): void
    {
        //
    }
};
