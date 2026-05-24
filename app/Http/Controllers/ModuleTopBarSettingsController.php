<?php

namespace App\Http\Controllers;

use App\Services\Module\ModuleTopBarSettingsService;
use App\Support\ErpModuleRegistry;
use Illuminate\Http\Request;

class ModuleTopBarSettingsController extends Controller
{
    public function __construct(
        protected ModuleTopBarSettingsService $topBarSettings
    ) {
        $this->middleware('auth');
    }

    public function accordionBody(string $company_slug, string $module)
    {
        $module = ErpModuleRegistry::resolveTopBarModuleKey($this->topBarSettings->normalizeModuleKey($module));
        if (!ErpModuleRegistry::showTopBarTabInModuleSettings($module)) {
            abort(404);
        }

        $topBarCategories = $this->topBarSettings->categoriesForModule($module);
        $topBarSelectableColumns = $this->topBarSettings->selectableColumns($module);
        $filterTypes = config('top_bar_filters.filter_types', []);

        return view('settings.partials.top_bar.accordion', compact(
            'topBarCategories',
            'topBarSelectableColumns',
            'filterTypes',
            'module'
        ));
    }

    public function storeCategory(Request $request, string $company_slug, string $module)
    {
        $module = $this->topBarSettings->normalizeModuleKey($module);
        if (!ErpModuleRegistry::showTopBarTabInModuleSettings($module)) {
            return response()->json(['success' => false, 'message' => 'Top bar not available for this module.'], 404);
        }

        try {
            $this->topBarSettings->storeCategory($module, $request->all());
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Top bar category added.']);
    }

    public function updateCategoryVisibility(Request $request, string $company_slug, string $module, int $id)
    {
        $module = $this->topBarSettings->normalizeModuleKey($module);
        $category = \App\Models\ErpModuleTopCategory::query()
            ->where('module_key', $module)
            ->where('id', $id)
            ->firstOrFail();

        if ($request->has('show_in_top_bar')) {
            $category->show_in_top_bar = $request->boolean('show_in_top_bar');
        }
        if ($request->has('show_in_view_cards')) {
            $category->show_in_view_cards = $request->boolean('show_in_view_cards');
        }
        $category->save();

        return response()->json([
            'success' => true,
            'message' => 'Display options updated.',
            'show_in_top_bar' => (bool) $category->show_in_top_bar,
            'show_in_view_cards' => (bool) $category->show_in_view_cards,
        ]);
    }

    public function updateCategory(Request $request, string $company_slug, string $module, int $id)
    {
        $module = $this->topBarSettings->normalizeModuleKey($module);
        $category = \App\Models\ErpModuleTopCategory::query()
            ->where('module_key', $module)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->name = trim($validated['name']);
        $category->save();

        return response()->json(['success' => true, 'message' => 'Category updated.']);
    }

    public function destroyCategory(string $company_slug, string $module, int $id)
    {
        $module = $this->topBarSettings->normalizeModuleKey($module);
        \App\Models\ErpModuleTopCategory::query()
            ->where('module_key', $module)
            ->where('id', $id)
            ->firstOrFail()
            ->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted.']);
    }

    public function storeOption(Request $request, string $company_slug, string $module)
    {
        $module = $this->topBarSettings->normalizeModuleKey($module);
        $created = $this->topBarSettings->storeOptions($module, $request->all());

        if ($created === 0) {
            return response()->json(['success' => false, 'message' => 'Please select at least one value.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Top bar option(s) added.']);
    }

    public function categoryFieldValues(string $company_slug, string $module, int $id)
    {
        $module = $this->topBarSettings->normalizeModuleKey($module);
        $result = $this->topBarSettings->categoryFieldValues($module, $id);
        $status = ($result['success'] ?? false) ? 200 : 422;

        return response()->json($result, $status);
    }

    public function updateOption(Request $request, string $company_slug, string $module, int $id)
    {
        $module = $this->topBarSettings->normalizeModuleKey($module);
        $option = \App\Models\ErpModuleTopOption::query()
            ->whereHas('category', fn($q) => $q->where('module_key', $module))
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate(['name' => 'required|string|max:255']);
        $option->name = trim($validated['name']);
        $option->save();

        return response()->json(['success' => true, 'message' => 'Option updated.']);
    }

    public function destroyOption(string $company_slug, string $module, int $id)
    {
        $module = $this->topBarSettings->normalizeModuleKey($module);
        \App\Models\ErpModuleTopOption::query()
            ->whereHas('category', fn($q) => $q->where('module_key', $module))
            ->where('id', $id)
            ->firstOrFail()
            ->delete();

        return response()->json(['success' => true, 'message' => 'Option deleted.']);
    }

    public function reorderCategories(Request $request, string $company_slug, string $module)
    {
        $module = $this->topBarSettings->normalizeModuleKey($module);
        $order = $request->validate(['order' => 'required|array', 'order.*' => 'integer'])['order'];

        foreach ($order as $index => $id) {
            \App\Models\ErpModuleTopCategory::query()
                ->where('module_key', $module)
                ->where('id', (int) $id)
                ->update(['display_order' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }
}
