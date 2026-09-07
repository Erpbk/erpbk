<?php

namespace App\Http\Controllers;

use App\Models\ExpenseAccount;
use App\Models\VisaRenewalCategory;
use App\Support\CompanyAuthRedirect;
use App\Support\VisaRenewalCategoryService;
use Illuminate\Http\Request;
use Flash;
use DB;

class VisaRenewalCategoryController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!user_can('visaexpense_view')) {
            abort(403, 'Unauthorized action.');
        }

        $categories = VisaRenewalCategory::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $routePrefix = str_replace('.index', '', $request->route()->getName());

        if ($request->ajax()) {
            return response()->json([
                'tableData' => view('visa_renewal_categories.table', [
                    'categories' => $categories,
                    'routePrefix' => $routePrefix,
                ])->render(),
            ]);
        }

        return view('visa_renewal_categories.index', compact('categories', 'routePrefix'));
    }

    public function store(Request $request)
    {
        if (!user_can('visaexpense_create')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:visa_renewal_categories,name',
            'display_order' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $maxOrder = (int) VisaRenewalCategory::max('display_order');
            $category = VisaRenewalCategory::create([
                'name' => $validated['name'],
                'display_order' => $validated['display_order'] ?? ($maxOrder + 1),
                'is_default' => false,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => auth()->id(),
            ]);

            $seeded = VisaRenewalCategoryService::seedStatusesForCategory($category);

            DB::commit();
            if ($seeded['count'] > 0 && $seeded['source']) {
                Flash::success('Visa category created. ' . $seeded['count'] . ' statuses copied from ' . $seeded['source'] . '.');
            } elseif ($seeded['count'] > 0) {
                Flash::success('Visa category created. ' . $seeded['count'] . ' visa statuses were added.');
            } else {
                Flash::success('Visa category created successfully.');
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
        if (!user_can('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $category = VisaRenewalCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:visa_renewal_categories,name,' . $id,
            'display_order' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $oldName = $category->name;
            $category->name = $validated['name'];
            if (!empty($validated['display_order'])) {
                $category->display_order = (int) $validated['display_order'];
            }
            if (!$category->is_default) {
                $category->is_active = $request->boolean('is_active', $category->is_active);
            }
            $category->updated_by = auth()->id();
            $category->save();

            if ($oldName !== $category->name) {
                $this->syncExpenseAccountNames($category, $oldName);
            }

            DB::commit();
            Flash::success('Visa category updated successfully.');

            return $this->redirectAfterAction($request, (int) $category->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function destroy(Request $request, $company_slug, $id)
    {
        if (!user_can('visaexpense_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $category = VisaRenewalCategory::findOrFail($id);

        if ($category->is_default) {
            $message = 'Cannot delete the default visa category (New Visa).';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }

        if ($category->visaExpenses()->exists()
            || ExpenseAccount::query()->visa()->where('renewal_category_id', $category->id)->exists()
        ) {
            $message = 'Cannot delete this category because visa expenses or accounts are linked to it.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }

        try {
            DB::beginTransaction();
            $category->visaStatuses()->withTrashed()->forceDelete();
            $category->delete();
            DB::commit();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Visa category deleted successfully.',
                    'id' => (int) $id,
                ]);
            }
            Flash::success('Visa category deleted successfully.');

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
        if (!user_can('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:visa_renewal_categories,id',
        ]);

        foreach ($validated['order'] as $index => $categoryId) {
            VisaRenewalCategory::where('id', $categoryId)->update([
                'display_order' => $index + 1,
                'updated_by' => auth()->id(),
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Order updated.']);
        }

        Flash::success('Renewal category order updated.');

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
            $url = route('settings-panel.visa-statuses.index', ['company_slug' => $companySlug]);
            $id = $categoryId ?? (int) $request->input('category_id');
            if ($id > 0) {
                $url .= '?category_id=' . $id;
            }

            return redirect()->to($url);
        }

        return redirect()->back();
    }

    private function syncExpenseAccountNames(VisaRenewalCategory $category, string $oldName): void
    {
        $oldSuffix = ' - ' . $oldName;
        $newSuffix = ' - ' . $category->name;

        ExpenseAccount::query()
            ->visa()
            ->where('renewal_category_id', $category->id)
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
