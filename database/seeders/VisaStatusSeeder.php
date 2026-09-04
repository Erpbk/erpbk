<?php

namespace Database\Seeders;

use App\Models\VisaStatus;
use App\Support\VisaRenewalCategoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class VisaStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!Schema::hasTable('visa_statuses')) {
            $this->command?->warn('Skipping VisaStatusSeeder: table visa_statuses does not exist yet.');
            return;
        }

        $defaultCategory = VisaRenewalCategoryService::ensureDefaultExists();
        $defaultCategoryId = (int) $defaultCategory->id;

        foreach (VisaRenewalCategoryService::defaultStatusTemplates() as $status) {
            VisaStatus::updateOrCreate(
                [
                    'name' => $status['name'],
                    'visa_renewal_category_id' => $defaultCategoryId,
                ],
                [
                    'code' => $status['code'],
                    'description' => $status['description'],
                    'default_fee' => $status['default_fee'],
                    'category' => $status['category'],
                    'visa_renewal_category_id' => $defaultCategoryId,
                    'is_required' => $status['is_required'],
                    'display_order' => $status['display_order'],
                    'is_active' => true,
                    'created_by' => 1,
                ]
            );
        }
    }
}
