<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;

class ModuleSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show module settings (General tab only for now).
     */
    public function index(string $module)
    {
        $modules = config('erp_modules.modules', []);
        if (!isset($modules[$module])) {
            abort(404, 'Module not found.');
        }
        $defaultLabel = $modules[$module];
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
    public function storeModuleLabel(Request $request, string $module)
    {
        $modules = config('erp_modules.modules', []);
        if (!isset($modules[$module])) {
            abort(404, 'Module not found.');
        }
        $request->validate(['module_label' => 'required|string|max:100']);
        Settings::updateOrCreate(
            ['name' => 'menu_label_' . $module],
            ['value' => trim($request->input('module_label'))]
        );
        Settings::clearMenuLabelsCache();

        return redirect()
            ->route('settings-panel.module-settings.index', ['module' => $module])
            ->with('success', 'Module name updated.');
    }
}
