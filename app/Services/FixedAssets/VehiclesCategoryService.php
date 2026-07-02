<?php

namespace App\Services\FixedAssets;

use App\Models\AssetCategory;
use Illuminate\Support\Facades\DB;

class VehiclesCategoryService
{
    public function __construct(
        private readonly AssetCategoryAccountService $accountService
    ) {
    }

    public function ensureForCompany(int $companyId): AssetCategory
    {
        $existing = AssetCategory::query()
            ->withoutGlobalScopes(['company'])
            ->where('company_id', $companyId)
            ->where(function ($query) {
                $query->where('code', AssetCategory::SYSTEM_CODE_VEHICLES)
                    ->orWhere('name', AssetCategory::SYSTEM_NAME_VEHICLES);
            })
            ->orderByRaw('is_system DESC')
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $this->normalize($existing);
        }

        return DB::transaction(function () use ($companyId) {
            $category = new AssetCategory();
            $category->company_id = $companyId;
            $category->name = AssetCategory::SYSTEM_NAME_VEHICLES;
            $category->code = AssetCategory::SYSTEM_CODE_VEHICLES;
            $category->description = 'System category for company vehicles linked to bikes.';
            $category->depreciation_method = AssetCategory::METHOD_STRAIGHT_LINE;
            $category->depreciation_frequency = AssetCategory::FREQUENCY_MONTHLY;
            $category->useful_life_months = 60;
            $category->salvage_value_percent = 0;
            $category->is_active = true;
            $category->is_system = true;

            if (!$category->asset_account_id || !$category->accumulated_depreciation_account_id || !$category->depreciation_expense_account_id) {
                $category->asset_account_id = \App\Models\GlobalAccount::id('VEHICLES_ASSET');
                $category->accumulated_depreciation_account_id = \App\Models\GlobalAccount::id('VEHICLES_ACCUMULATED_DEPRECIATION');
                $category->depreciation_expense_account_id = \App\Models\GlobalAccount::id('VEHICLES_DEPRECIATION_EXPENSE');
            }
            
            $category->save();
            return $category->fresh();
        });
    }

    private function normalize(AssetCategory $category): AssetCategory
    {
        $dirty = false;

        if (!$category->is_system) {
            $category->is_system = true;
            $dirty = true;
        }

        if ($category->code !== AssetCategory::SYSTEM_CODE_VEHICLES) {
            $category->code = AssetCategory::SYSTEM_CODE_VEHICLES;
            $dirty = true;
        }

        if ($category->name !== AssetCategory::SYSTEM_NAME_VEHICLES) {
            $category->name = AssetCategory::SYSTEM_NAME_VEHICLES;
            $dirty = true;
        }

        if (!$category->asset_account_id || !$category->accumulated_depreciation_account_id || !$category->depreciation_expense_account_id) {
           $category->asset_account_id = \App\Models\GlobalAccount::id('VEHICLES_ASSET');
           $category->accumulated_depreciation_account_id = \App\Models\GlobalAccount::id('VEHICLES_ACCUMULATED_DEPRECIATION');
           $category->depreciation_expense_account_id = \App\Models\GlobalAccount::id('VEHICLES_DEPRECIATION_EXPENSE');
           $dirty = true;
        }

        if ($dirty) {
            $category->save();
        }
        $category->refresh();
        return $category;
    }
}
