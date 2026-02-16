<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;

class ErpSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the central ERP settings page (menu labels editable from here).
     */
    public function index(Request $request)
    {
        $menuLabels = Settings::getMenuLabels();
        $defaults = config('menu_labels.defaults', []);
        // Ensure all config keys appear in the form (use stored or default)
        $menuLabels = array_merge($defaults, $menuLabels);

        return view('settings.erp_settings', compact('menuLabels'));
    }

    /**
     * Save menu label overrides to Settings.
     */
    public function store(Request $request)
    {
        $defaults = config('menu_labels.defaults', []);
        $labels = $request->input('menu_labels', []);

        foreach ($labels as $key => $value) {
            if (!array_key_exists($key, $defaults)) {
                continue;
            }
            $value = is_string($value) ? trim($value) : (string) $value;
            if ($value !== '') {
                Settings::updateOrCreate(
                    ['name' => 'menu_label_' . $key],
                    ['value' => $value]
                );
            }
        }

        Settings::clearMenuLabelsCache();

        $route = $request->route() && str_starts_with($request->route()->getName(), 'settings-panel.')
            ? 'settings-panel.erp'
            : 'settings.erp';

        return redirect()->route($route)->with('success', 'Menu labels saved successfully.');
    }
}
