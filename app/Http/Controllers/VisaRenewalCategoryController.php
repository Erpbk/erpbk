<?php

namespace App\Http\Controllers;

use App\Models\ExpenseAccount;
use App\Models\VisaRenewalCategory;
use App\Models\VisaStatus;
use App\Support\CompanyAuthRedirect;
use App\Support\VisaExpenseAccess;
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

        if (!VisaExpenseAccess::canManageAccounts('view')) {
            abort(403, 'Unauthorized action.');
        }

        $categories = VisaRenewalCategory::query()
            ->with(['visaStatuses' => fn ($q) => $q->select('visa_statuses.id')])
            ->withCount('visaStatuses')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $assignableStatuses = VisaStatus::query()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        $routePrefix = str_replace('.index', '', $request->route()->getName());

        if ($request->ajax()) {
            return response()->json([
                'tableData' => view('visa_renewal_categories.table', [
                    'categories' => $categories,
                    'routePrefix' => $routePrefix,
                ])->render(),
                'assignableStatuses' => $assignableStatuses,
            ]);
        }

        return view('visa_renewal_categories.index', compact('categories', 'routePrefix', 'assignableStatuses'));
    }

    public function store(Request $request)
    {
        if (!VisaExpenseAccess::canManageAccounts('create')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:visa_renewal_categories,name',
            'display_order' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'visa_status_ids' => 'nullable|array',
            'visa_status_ids.*' => 'integer|exists:visa_statuses,id',
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

            $attached = VisaRenewalCategoryService::attachStatusesToCategory(
                $category,
                $validated['visa_status_ids'] ?? []
            );

            DB::commit();
            if ($attached > 0) {
                Flash::success('Visa category created. ' . $attached . ' existing status(es) assigned.');
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
        if (!VisaExpenseAccess::canManageAccounts('edit')) {
            abort(403, 'Unauthorized action.');
        }

        $category = VisaRenewalCategory::findOrFail($id);

        if (record_is_pending_deletion($category)) {
            $message = 'This visa category is Pending Deletion and cannot be edited until the delete request is reviewed.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 423);
            }
            abort(403, $message);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:visa_renewal_categories,name,' . $id,
            'display_order' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'visa_status_ids' => 'nullable|array',
            'visa_status_ids.*' => 'integer|exists:visa_statuses,id',
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

            VisaRenewalCategoryService::syncStatusesForCategory(
                $category,
                $validated['visa_status_ids'] ?? []
            );

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
        if (!VisaExpenseAccess::canManageAccounts('delete')) {
            abort(403, 'Unauthorized action.');
        }

        $category = VisaRenewalCategory::findOrFail($id);

        if (record_is_pending_deletion($category)) {
            $message = 'A delete request for this visa category is already pending approval.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 423);
            }
            Flash::warning($message);

            return redirect()->back();
        }

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
            // Soft-delete / delete-approval only — do not detach pivots until the row is actually removed.
            $deleted = $category->delete();
            if ($deleted) {
                $category->visaStatuses()->detach();
            }
            DB::commit();

            if (wants_delete_json() || $request->ajax() || $request->wantsJson()) {
                return delete_json_response('Visa category');
            }

            Flash::success(delete_outcome_message('Visa category'));

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
        if (!VisaExpenseAccess::canManageAccounts('edit')) {
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
