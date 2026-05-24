<?php

use App\Services\Rider\RiderDefaultCategoryService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_categories')) {
            return;
        }

        $service = app(RiderDefaultCategoryService::class);
        $service->bootstrap();
        $service->relocateVisibleFieldsToDefaultCategory();
    }

    public function down(): void
    {
        //
    }
};
