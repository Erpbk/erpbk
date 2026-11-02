<?php

namespace App\Http\Controllers;

use App\Support\DashboardCardRegistry;
use Illuminate\Http\Request;

class DashboardSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(string $company_slug)
    {
        $definitions = DashboardCardRegistry::definitions();
        $selected = DashboardCardRegistry::selectedKeysForUser(auth()->user());
        $selectedSet = array_flip($selected);

        return view('settings.dashboard_settings.index', [
            'pageTitle' => __('Dashboard Settings'),
            'definitions' => $definitions,
            'selectedSet' => $selectedSet,
            'selectedOrder' => $selected,
            'maxVisibleCards' => DashboardCardRegistry::maxVisibleCards(),
        ]);
    }

    public function update(Request $request, string $company_slug)
    {
        $allowed = array_keys(DashboardCardRegistry::definitions());
        $request->validate([
            'cards' => ['nullable', 'array', 'max:' . DashboardCardRegistry::maxVisibleCards()],
            'cards.*' => ['string', \Illuminate\Validation\Rule::in($allowed)],
        ]);

        $keys = array_values(array_unique(array_map('strval', $request->input('cards', []))));
        DashboardCardRegistry::saveSelectedKeysForUser(auth()->user(), $keys);

        return redirect()
            ->route('settings-panel.module-settings.index', [
                'company_slug' => $company_slug,
                'module' => 'dashboard',
            ])
            ->with('success', __('Dashboard display preferences saved.'));
    }
}
