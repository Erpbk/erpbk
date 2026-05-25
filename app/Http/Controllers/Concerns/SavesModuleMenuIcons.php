<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Module\ModuleIconService;
use App\Support\MenuDropdownRegistry;
use Illuminate\Http\Request;

trait SavesModuleMenuIcons
{
    protected function saveModuleMenuIcons(Request $request, string $moduleMenuKey): void
    {
        $moduleMenuKey = \App\Support\ErpModuleRegistry::normalizeKey($moduleMenuKey);
        $context = MenuDropdownRegistry::contextForModuleKey($moduleMenuKey);
        $keys = $context
            ? array_merge([$context['parent_key']], array_column($context['children'], 'key'))
            : [$moduleMenuKey];

        $allowedMimes = implode(',', config('menu_icons.allowed_mimes', ['png', 'jpg', 'jpeg']));
        $maxKb = (int) config('menu_icons.max_upload_kb', 512);

        $request->validate([
            'icon_reset' => 'sometimes|array',
            'icon_reset.*' => 'nullable|in:1',
            'icon_class' => 'sometimes|array',
            'icon_class.*' => 'nullable|string|max:80',
            'icon_image' => 'sometimes|array',
            'icon_image.*' => 'nullable|image|mimes:' . $allowedMimes . '|max:' . $maxKb,
        ]);

        $service = app(ModuleIconService::class);
        $allowedKeys = config('menu_icons.defaults', []);

        foreach ($keys as $key) {
            if (! array_key_exists($key, $allowedKeys)) {
                continue;
            }
            if ($request->input('icon_reset.' . $key)) {
                $service->removeIcon($key);
                continue;
            }

            $uploaded = $request->file('icon_image.' . $key);
            if ($uploaded) {
                $service->saveImageIcon($key, $uploaded);
                continue;
            }

            $class = trim((string) $request->input('icon_class.' . $key, ''));
            if ($class !== '') {
                $service->saveClassIcon($key, $class);
            }
        }
    }
}
