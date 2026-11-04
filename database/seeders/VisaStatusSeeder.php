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

        foreach (VisaRenewalCategoryService::defaultStatusTemplates() as $status) {
            $visaStatus = VisaStatus::updateOrCreate(
                [
                    'name' => $status['name'],
                ],
                [
                    'code' => $status['code'],
                    'description' => $status['description'],
                    'default_fee' => $status['default_fee'],
                    'category' => $status['category'],
                    'is_required' => $status['is_required'],
                    'display_order' => $status['display_order'],
                    'is_active' => true,
                    'created_by' => 1,
                ]
            );

            if (Schema::hasTable('visa_renewal_category_visa_status')) {
                VisaRenewalCategoryService::attachStatusesToCategory($defaultCategory, [(int) $visaStatus->id]);
            }
        }
    }
}
