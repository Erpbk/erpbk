<?php

use App\Services\Module\ModuleDefaultCategoryService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_setting_categories')) {
            return;
        }

        app(ModuleDefaultCategoryService::class)->bootstrapAllModules();
    }

    public function down(): void
    {
        //
    }
};
