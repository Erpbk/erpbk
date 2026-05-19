<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Module\ModuleLabelService;
use App\Support\ErpModuleRegistry;
use Illuminate\Http\Request;

trait SavesModuleDisplayLabel
{
    protected function saveModuleDisplayLabel(Request $request, string $menuKey): void
    {
        $request->validate(['module_label' => 'required|string|max:100']);
        $value = trim((string) $request->input('module_label'));
        $menuKey = ErpModuleRegistry::normalizeKey($menuKey);

        app(ModuleLabelService::class)->saveLabel($menuKey, $value);
    }
}
