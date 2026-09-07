<?php

namespace Database\Seeders;

use App\Models\LicenseStatus;
use App\Support\LicenseCategoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class LicenseStatusSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('license_statuses')) {
            $this->command?->warn('Skipping LicenseStatusSeeder: table license_statuses does not exist yet.');
            return;
        }

        $defaultCategory = LicenseCategoryService::ensureDefaultExists();
        $defaultCategoryId = (int) $defaultCategory->id;

        foreach (LicenseCategoryService::defaultStatusTemplates() as $status) {
            LicenseStatus::updateOrCreate(
                [
                    'name' => $status['name'],
                    'license_category_id' => $defaultCategoryId,
                ],
                [
                    'code' => $status['code'],
                    'description' => $status['description'],
                    'default_fee' => $status['default_fee'],
                    'category' => $status['category'],
                    'license_category_id' => $defaultCategoryId,
                    'is_required' => $status['is_required'],
                    'display_order' => $status['display_order'],
                    'is_active' => true,
                    'created_by' => 1,
                ]
            );
        }
    }
}
