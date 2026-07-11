<?php

namespace App\Http\Controllers;

use App\Models\VisaRenewalCategory;
use App\Support\CompanyAuthRedirect;
use Illuminate\Http\Request;
use Flash;
use DB;

class VisaRenewalCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visa_expense_view')->only('index');
        $this->middleware('permission:visa_expense_create')->only('store');
        $this->middleware('permission:visa_expense_edit|visa_expense_create')->only('update', 'reorder');
        $this->middleware('permission:visa_expense_delete')->only('destroy');
    }
    public function index(Request $request)
    {
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

            DB::commit();
            Flash::success('Visa renewal category created successfully.');

            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request, $company_slug, $id)
    {
        $category = VisaRenewalCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:visa_renewal_categories,name,' . $id,
            'display_order' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            if ($category->is_default && $validated['name'] !== $category->name && $validated['name'] !== 'New Visa') {
                // Allow renaming default only if still meaningful; keep is_default flag
            }

            $category->name = $validated['name'];
            if (!empty($validated['display_order'])) {
                $category->display_order = (int) $validated['display_order'];
            }
            if (!$category->is_default) {
                $category->is_active = $request->boolean('is_active', $category->is_active);
            }
            $category->updated_by = auth()->id();
            $category->save();

            DB::commit();
            Flash::success('Visa renewal category updated successfully.');

            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function destroy(Request $request, $company_slug, $id)
    {
        $category = VisaRenewalCategory::findOrFail($id);

        if ($category->is_default) {
            $message = 'Cannot delete the default renewal category (New Visa).';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }

        if ($category->visaExpenses()->exists()) {
            $message = 'Cannot delete this category because visa expenses are linked to it.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }

        try {
            $category->delete();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Visa renewal category deleted successfully.',
                    'id' => (int) $id,
                ]);
            }
            Flash::success('Visa renewal category deleted successfully.');

            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            }
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back();
        }
    }

    public function reorder(Request $request)
    {
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

    protected function redirectAfterAction(Request $request)
    {
        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            return redirect()->to($returnTo);
        }

        $companySlug = (string) ($request->route('company_slug') ?? session('company_slug') ?? '');
        if ($companySlug !== '' && str_starts_with((string) $request->route()?->getName(), 'settings-panel.')) {
            return redirect()->to(
                route('settings-panel.visa-statuses.index', ['company_slug' => $companySlug]) . '#tab-visa-renewal-categories'
            );
        }

        return redirect()->back();
    }
}
