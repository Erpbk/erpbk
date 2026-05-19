<?php

use App\Services\Rider\RiderDefaultCategoryService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_field_category_assignments')) {
            return;
        }

        app(RiderDefaultCategoryService::class)->bootstrap();
    }

    public function down(): void
    {
        //
    }
};
