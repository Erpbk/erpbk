<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SavesModuleMenuIcons;
use App\Services\Module\ModuleIconService;
use App\Support\ModuleMenuIcon;
use App\Support\TablerIconLibrary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModuleMenuIconController extends Controller
{
    use SavesModuleMenuIcons;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Search Tabler icon library (CDN catalog, cached server-side).
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:80',
        ]);

        $icons = TablerIconLibrary::search($request->input('q'));

        return response()->json([
            'success' => true,
            'icons' => $icons,
            'provider' => 'tabler',
            'provider_url' => 'https://tabler.io/icons',
        ]);
    }

    /**
     * Save a single menu icon (AJAX) — updates DB and returns rendered HTML for live preview.
     */
    public function store(Request $request)
    {
        $allowedKeys = array_keys(config('menu_icons.defaults', []));

        $request->validate([
            'menu_key' => ['required', 'string', 'max:80', Rule::in($allowedKeys)],
            'icon_class' => 'nullable|string|max:80',
            'reset' => 'nullable|boolean',
        ]);

        $menuKey = (string) $request->input('menu_key');
        $service = app(ModuleIconService::class);

        if ($request->boolean('reset')) {
            $service->removeIcon($menuKey);
        } else {
            $class = TablerIconLibrary::normalizeClass((string) $request->input('icon_class', ''));
            if ($class === '' || ! TablerIconLibrary::isValidClass($class)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid icon selection.',
                ], 422);
            }
            $service->saveClassIcon($menuKey, $class);
        }

        $icon = \App\Models\Settings::getMenuIcon($menuKey);

        return response()->json([
            'success' => true,
            'message' => 'Icon updated.',
            'menu_key' => $menuKey,
            'icon_class' => $icon['class'] ?? null,
            'icon_type' => $icon['type'] ?? 'class',
            'html' => ModuleMenuIcon::render($menuKey),
        ]);
    }

    /**
     * Optional image upload for one menu key (AJAX).
     */
    public function storeImage(Request $request)
    {
        $allowedKeys = array_keys(config('menu_icons.defaults', []));
        $allowedMimes = implode(',', config('menu_icons.allowed_mimes', ['png', 'jpg', 'jpeg']));
        $maxKb = (int) config('menu_icons.max_upload_kb', 512);

        $request->validate([
            'menu_key' => ['required', 'string', 'max:80', Rule::in($allowedKeys)],
            'icon_image' => 'required|image|mimes:' . $allowedMimes . '|max:' . $maxKb,
        ]);

        $menuKey = (string) $request->input('menu_key');
        app(ModuleIconService::class)->saveImageIcon($menuKey, $request->file('icon_image'));

        return response()->json([
            'success' => true,
            'message' => 'Image icon updated.',
            'menu_key' => $menuKey,
            'icon_type' => 'image',
            'html' => ModuleMenuIcon::render($menuKey),
        ]);
    }
}
