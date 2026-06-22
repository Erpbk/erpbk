<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Services\FixedAssets\AssetCategoryAccountService;
use Illuminate\Http\Request;
use Flash;
use DB;

class AssetCategoryController extends Controller
{
    public function __construct(
        private readonly AssetCategoryAccountService $accountService
    ) {
    }

    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('asset_view')) {
            abort(403, 'Unauthorized action.');
        }

        $categories = AssetCategory::query()->orderBy('name')->get();

        if ($request->ajax()) {
            return response()->json([
                'tableData' => view('asset_categories.table', ['categories' => $categories])->render(),
            ]);
        }

        return view('asset_categories.index', compact('categories'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('asset_create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('asset_categories.create', [
            'depreciationMethods' => AssetCategory::depreciationMethods(),
            'depreciationFrequencies' => AssetCategory::depreciationFrequencies(),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('asset_create')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'depreciation_method' => 'required|string|in:straight_line,declining_balance,double_declining_balance',
            'depreciation_frequency' => 'required|string|in:monthly,yearly',
            'useful_life_months' => 'required|integer|min:1|max:600',
            'salvage_value_percent' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $category = new AssetCategory();
            $category->name = $validated['name'];
            $category->code = $validated['code'] ?? null;
            $category->description = $validated['description'] ?? null;
            $category->depreciation_method = $validated['depreciation_method'];
            $category->depreciation_frequency = $validated['depreciation_frequency'];
            $category->useful_life_months = (int) $validated['useful_life_months'];
            $category->salvage_value_percent = $validated['salvage_value_percent'] ?? 0;
            $category->is_active = $request->has('is_active');
            $category->created_by = auth()->id();
            $category->save();

            $this->accountService->createAccountsForCategory($category);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Asset category created successfully.',
                    'reload' => true,
                ], 200);
            }

            Flash::success('Asset category created successfully.');
            return redirect()->route('fixed-assets.index');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
            }

            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function edit(string $company_slug, int $id)
    {
        if (!auth()->user()->hasPermissionTo('asset_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $category = AssetCategory::findOrFail($id);

        return view('asset_categories.edit', [
            'category' => $category,
            'depreciationMethods' => AssetCategory::depreciationMethods(),
            'depreciationFrequencies' => AssetCategory::depreciationFrequencies(),
        ]);
    }

    public function update(Request $request, string $company_slug, int $id)
    {
        if (!auth()->user()->hasPermissionTo('asset_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $category = AssetCategory::findOrFail($id);
        $oldName = $category->name;

        if ($category->isSystemLocked()) {
            $validated = $request->validate([
                'code' => 'nullable|string|max:50',
                'description' => 'nullable|string|max:1000',
                'depreciation_method' => 'required|string|in:straight_line,declining_balance,double_declining_balance',
                'depreciation_frequency' => 'required|string|in:monthly,yearly',
                'useful_life_months' => 'required|integer|min:1|max:600',
                'salvage_value_percent' => 'nullable|numeric|min:0|max:100',
                'is_active' => 'nullable|boolean',
            ]);
            $validated['name'] = $category->name;
        } else {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50',
                'description' => 'nullable|string|max:1000',
                'depreciation_method' => 'required|string|in:straight_line,declining_balance,double_declining_balance',
                'depreciation_frequency' => 'required|string|in:monthly,yearly',
                'useful_life_months' => 'required|integer|min:1|max:600',
                'salvage_value_percent' => 'nullable|numeric|min:0|max:100',
                'is_active' => 'nullable|boolean',
            ]);
        }

        try {
            DB::beginTransaction();

            if (!$category->isSystemLocked()) {
                $category->name = $validated['name'];
            }
            $category->code = $validated['code'] ?? $category->code;
            $category->description = $validated['description'] ?? null;
            $category->depreciation_method = $validated['depreciation_method'];
            $category->depreciation_frequency = $validated['depreciation_frequency'];
            $category->useful_life_months = (int) $validated['useful_life_months'];
            $category->salvage_value_percent = $validated['salvage_value_percent'] ?? 0;
            $category->is_active = $request->has('is_active');
            $category->updated_by = auth()->id();
            $category->save();

            $this->accountService->updateAccountNames($category, $oldName);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Asset category updated successfully.',
                    'reload' => true,
                ], 200);
            }

            Flash::success('Asset category updated successfully.');
            return redirect()->route('fixed-assets.index');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
            }

            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy(Request $request, string $company_slug, int $id)
    {
        if (!auth()->user()->hasPermissionTo('asset_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $category = AssetCategory::findOrFail($id);

        if ($category->isSystemLocked()) {
            $message = 'The Vehicles category is a system category and cannot be deleted.';
            if ($request->ajax()) {
                return response()->json(['message' => $message], 422);
            }
            Flash::error($message);
            return redirect()->back();
        }

        if ($category->fixedAssets()->exists()) {
            $message = 'Cannot delete category while fixed assets are assigned to it.';
            if ($request->ajax()) {
                return response()->json(['message' => $message], 422);
            }
            Flash::error($message);
            return redirect()->back();
        }

        try {
            DB::beginTransaction();

            $this->accountService->deleteAccountsForCategory($category);

            $category->deleted_by = auth()->id();
            $category->save();
            $category->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            $message = 'Error: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['message' => $message], 500);
            }
            Flash::error($message);
            return redirect()->back();
        }

        if ($request->ajax()) {
            return response()->json(['message' => 'Asset category deleted successfully.', 'reload' => true]);
        }

        Flash::success('Asset category deleted successfully.');
        return redirect()->route('fixed-assets.index');
    }
}
