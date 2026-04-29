<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;

class ModuleSettingsController extends Controller
{
    protected function normalizeModuleKey(string $module): string
    {
        return str_replace('-', '_', strtolower(trim($module)));
    }

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show module settings (General tab only for now).
     */
    public function index(string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);

        // Bike settings are configured via this module-settings route for sidebar consistency.
        if ($module === 'bike_list') {
            return app(\App\Http\Controllers\BikeSettingsController::class)->index();
        }

        $defaultLabels = config('menu_labels.defaults', []);
        $defaultLabel = $defaultLabels[$module] ?? ucwords(str_replace('_', ' ', $module));

        $moduleLabel = Settings::getMenuLabel($module);
        $pageTitle = $moduleLabel . ' – Settings';

        return view('settings.module.index', [
            'layout' => 'layouts.settingsPanelLayout',
            'moduleKey' => $module,
            'moduleLabel' => $moduleLabel,
            'defaultLabel' => $defaultLabel,
            'pageTitle' => $pageTitle,
        ]);
    }

    /**
     * Save the module display name (menu label).
     * This value is used by the main app sidebar (resources/views/layouts/menu.blade.php)
     * via Settings::getMenuLabels(), so the menu updates on the next page load.
     */
    public function storeModuleLabel(Request $request, string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);
        $allowedLabels = config('menu_labels.defaults', []);
        if (!isset($allowedLabels[$module])) {
            return back()->with('error', __('Invalid module key.'));
        }
        $request->validate(['module_label' => 'required|string|max:100']);
        Settings::updateOrCreate(
            ['name' => 'menu_label_' . $module],
            ['value' => trim($request->input('module_label'))]
        );
        Settings::clearMenuLabelsCache();

        return redirect()
            ->route('settings-panel.module-settings.index', [
                'company_slug' => $company_slug,
                'module' => $module,
            ])
            ->with('success', 'Module name updated.');
    }
}
