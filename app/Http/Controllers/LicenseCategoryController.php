<?php

namespace App\Http\Controllers;

use App\Models\ExpenseAccount;
use App\Models\LicenseCategory;
use App\Support\CompanyAuthRedirect;
use App\Support\LicenseCategoryService;
use Illuminate\Http\Request;
use Flash;
use DB;

class LicenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (! user_can('licenseexpense_view')) {
            abort(403, 'Unauthorized action.');
        }

        $categories = LicenseCategory::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $routePrefix = str_replace('.index', '', $request->route()->getName());

        if ($request->ajax()) {
            return response()->json([
                'tableData' => view('license_categories.table', [
                    'categories' => $categories,
                    'routePrefix' => $routePrefix,
                ])->render(),
            ]);
        }

        return view('license_categories.index', compact('categories', 'routePrefix'));
    }

    public function store(Request $request)
    {
        if (! user_can('licenseexpense_create')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:license_categories,name',
            'display_order' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $maxOrder = (int) LicenseCategory::max('display_order');
            $category = LicenseCategory::create([
                'name' => $validated['name'],
                'display_order' => $validated['display_order'] ?? ($maxOrder + 1),
                'is_default' => false,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => auth()->id(),
            ]);

            $seeded = LicenseCategoryService::seedStatusesForCategory($category);

            DB::commit();
            if ($seeded['count'] > 0 && $seeded['source']) {
                Flash::success('License category created. ' . $seeded['count'] . ' statuses copied from ' . $seeded['source'] . '.');
            } elseif ($seeded['count'] > 0) {
                Flash::success('License category created. ' . $seeded['count'] . ' license statuses were added.');
            } else {
                Flash::success('License category created successfully.');
            }

            return $this->redirectAfterAction($request, (int) $category->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request, $company_slug, $id)
    {
        if (! user_can('licenseexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $category = LicenseCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:license_categories,name,' . $id,
            'display_order' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $oldName = $category->name;
            $category->name = $validated['name'];
            if (! empty($validated['display_order'])) {
                $category->display_order = (int) $validated['display_order'];
            }
            if (! $category->is_default) {
                $category->is_active = $request->boolean('is_active', $category->is_active);
            }
            $category->updated_by = auth()->id();
            $category->save();

            if ($oldName !== $category->name) {
                $this->syncExpenseAccountNames($category, $oldName);
            }

            DB::commit();
            Flash::success('License category updated successfully.');

            return $this->redirectAfterAction($request, (int) $category->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function destroy(Request $request, $company_slug, $id)
    {
        if (! user_can('licenseexpense_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $category = LicenseCategory::findOrFail($id);

        if ($category->is_default) {
            $message = 'Cannot delete the default license category (New License).';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }

        if ($category->licenseExpenses()->exists()
            || ExpenseAccount::query()->license()->where('license_category_id', $category->id)->exists()
        ) {
            $message = 'Cannot delete this category because license expenses or accounts are linked to it.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }

        try {
            DB::beginTransaction();
            $category->licenseStatuses()->withTrashed()->forceDelete();
            $category->delete();
            DB::commit();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'License category deleted successfully.',
                    'id' => (int) $id,
                ]);
            }
            Flash::success('License category deleted successfully.');

            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            }
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back();
        }
    }

    public function reorder(Request $request)
    {
        if (! user_can('licenseexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:license_categories,id',
        ]);

        foreach ($validated['order'] as $index => $categoryId) {
            LicenseCategory::where('id', $categoryId)->update([
                'display_order' => $index + 1,
                'updated_by' => auth()->id(),
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Order updated.']);
        }

        Flash::success('License category order updated.');

        return redirect()->back();
    }

    protected function redirectAfterAction(Request $request, ?int $categoryId = null)
    {
        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            $id = $categoryId ?? (int) $request->input('category_id');
            if ($id > 0 && ! str_contains($returnTo, 'category_id=')) {
                $returnTo .= (str_contains($returnTo, '?') ? '&' : '?') . 'category_id=' . $id;
            }

            return redirect()->to($returnTo);
        }

        $companySlug = (string) ($request->route('company_slug') ?? session('company_slug') ?? '');
        if ($companySlug !== '' && str_starts_with((string) $request->route()?->getName(), 'settings-panel.')) {
            $url = route('settings-panel.license-statuses.index', ['company_slug' => $companySlug]);
            $id = $categoryId ?? (int) $request->input('category_id');
            if ($id > 0) {
                $url .= '?category_id=' . $id;
            }

            return redirect()->to($url);
        }

        return redirect()->back();
    }

    private function syncExpenseAccountNames(LicenseCategory $category, string $oldName): void
    {
        $oldSuffix = ' - ' . $oldName;
        $newSuffix = ' - ' . $category->name;

        ExpenseAccount::query()
            ->license()
            ->where('license_category_id', $category->id)
            ->get()
            ->each(function (ExpenseAccount $account) use ($oldSuffix, $newSuffix) {
                $name = (string) $account->name;
                if ($oldSuffix !== '' && str_ends_with($name, $oldSuffix)) {
                    $account->name = substr($name, 0, -strlen($oldSuffix)) . $newSuffix;
                    $account->save();
                }
            });
    }
}
