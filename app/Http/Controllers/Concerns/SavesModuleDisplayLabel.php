<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Module\ModuleLabelService;
use App\Support\ErpModuleRegistry;
use App\Support\MenuDropdownRegistry;
use Illuminate\Http\Request;

trait SavesModuleDisplayLabel
{
    /**
     * Save main sidebar menu label(s) for a module. When the module uses a dropdown
     * submenu (e.g. RTA Fines → Unpaid / Paid), saves the parent heading and each item separately.
     */
    protected function saveModuleDisplayLabel(Request $request, string $menuKey): void
    {
        $rules = ['module_label' => 'required|string|max:100'];
        if (MenuDropdownRegistry::contextForModuleKey($menuKey) !== null) {
            $rules['submenu_labels'] = 'sometimes|array';
            $rules['submenu_labels.*'] = 'nullable|string|max:100';
        }
        $request->validate($rules);

        $menuKey = ErpModuleRegistry::normalizeKey($menuKey);
        $labelService = app(ModuleLabelService::class);
        $context = MenuDropdownRegistry::contextForModuleKey($menuKey);

        if ($context === null) {
            $labelService->saveLabel($menuKey, trim((string) $request->input('module_label')));

            return;
        }

        $labelService->saveLabel($context['parent_key'], trim((string) $request->input('module_label')));

        $defaults = config('menu_labels.defaults', []);
        $submenu = $request->input('submenu_labels', []);
        if (! is_array($submenu)) {
            return;
        }

        foreach ($context['children'] as $child) {
            $key = $child['key'];
            if (! array_key_exists($key, $defaults)) {
                continue;
            }
            $value = trim((string) ($submenu[$key] ?? ''));
            if ($value !== '') {
                $labelService->saveLabel($key, $value);
            }
        }
    }
}
