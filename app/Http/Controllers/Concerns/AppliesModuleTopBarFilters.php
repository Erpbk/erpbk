<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Module\TopBarListingService;
use App\Support\ErpModuleRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait AppliesModuleTopBarFilters
{
    protected function applyModuleTopBarFilters(Builder $query, Request $request, string $moduleKey): void
    {
        $moduleKey = ErpModuleRegistry::resolveTopBarModuleKey($moduleKey);
        if (!ErpModuleRegistry::hasTopBar($moduleKey)) {
            return;
        }

        app(TopBarListingService::class)->applyFilters($query, $request, $moduleKey);
    }

    /**
     * @return array<string, mixed>
     */
    protected function moduleTopBarListingData(Request $request, string $moduleKey): array
    {
        return app(TopBarListingService::class)->listingViewData($moduleKey, $request);
    }
}
