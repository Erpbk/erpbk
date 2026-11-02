<?php

namespace App\Http\Controllers;

use App\Models\VisaRenewalCategory;
use App\Models\VisaStatus;
use App\Support\CompanyAuthRedirect;
use App\Support\VisaExpenseAccess;
use App\Support\VisaRenewalCategoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Flash;
use DB;

class VisaStatusController extends Controller
{
    /**
     * Display a listing of the visa statuses.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!VisaExpenseAccess::canManageEntries('view')) {
            abort(403, 'Unauthorized action.');
        }

        VisaRenewalCategoryService::ensureDefaultExists();
        $visaRenewalCategories = VisaRenewalCategory::query()
            ->with(['visaStatuses' => fn ($q) => $q->select('visa_statuses.id')])
            ->withCount('visaStatuses')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $viewMode = $request->input('view') === 'all' ? 'all' : 'category';

        $selectedCategoryId = (int) $request->input('category_id');
        if ($viewMode === 'category') {
            if ($selectedCategoryId <= 0 || ! $visaRenewalCategories->contains('id', $selectedCategoryId)) {
                $selectedCategoryId = (int) ($visaRenewalCategories->first()->id ?? 0);
            }
        }
        $selectedCategory = $visaRenewalCategories->firstWhere('id', $selectedCategoryId);

        $query = VisaStatus::query()->with('renewalCategories');

        if ($viewMode === 'category' && $selectedCategoryId > 0) {
            $query->whereHas('renewalCategories', function ($q) use ($selectedCategoryId) {
                $q->where('visa_renewal_categories.id', $selectedCategoryId);
            })
                ->join(
                    'visa_renewal_category_visa_status as vrcs',
                    'vrcs.visa_status_id',
                    '=',
                    'visa_statuses.id'
                )
                ->where('vrcs.visa_renewal_category_id', $selectedCategoryId)
                ->select('visa_statuses.*', 'vrcs.display_order as category_display_order')
                ->orderBy('vrcs.display_order')
                ->orderBy('visa_statuses.name');
        } else {
            $query->orderBy('display_order')->orderBy('name');
        }

        if ($request->filled('code')) {
            $query->where('visa_statuses.code', 'like', '%' . $request->code . '%');
        }
        if ($request->filled('name')) {
            $query->where('visa_statuses.name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('category')) {
            $query->where('visa_statuses.category', $request->category);
        }
        if ($request->has('status') && $request->status !== '') {
            $query->where('visa_statuses.is_active', (int) $request->status);
        }
        if ($request->has('is_required') && $request->is_required !== '') {
            $query->where('visa_statuses.is_required', (int) $request->is_required);
        }

        $visaStatuses = $query->get();

        $visaRoute = str_replace('.index', '', $request->route()->getName());

        $companySlug = (string) ($request->route('company_slug') ?? session('company_slug') ?? '');
        $indexUrl = $this->visaStatusesIndexUrl($selectedCategoryId, $viewMode);
        $visaRenewalCategoryReturnUrl = $this->visaStatusesIndexUrl(
            $selectedCategoryId > 0 ? $selectedCategoryId : (int) ($visaRenewalCategories->first()->id ?? 0),
            'category'
        );

        if ($request->ajax()) {
            $tableData = view('visa_statuses.table', [
                'visaStatuses' => $visaStatuses,
                'visaRoute' => $visaRoute,
                'visaStatusReturnTo' => $indexUrl,
                'selectedCategoryId' => $selectedCategoryId,
                'viewMode' => $viewMode,
                'visaRenewalCategories' => $visaRenewalCategories,
                'useVisaStatusModals' => true,
            ])->render();

            return response()->json([
                'tableData' => $tableData,
                'selectedCategoryId' => $selectedCategoryId,
                'viewMode' => $viewMode,
                'statusCount' => $visaStatuses->count(),
            ]);
        }

        // Settings-panel list lives on Visa Expense module settings → Visa Categories tab.
        if (str_starts_with((string) $request->route()?->getName(), 'settings-panel.')) {
            $params = [
                'company_slug' => $companySlug,
                'module' => 'visa_expense',
            ];
            if ($viewMode === 'all') {
                $params['visa_view'] = 'all';
            } elseif ($selectedCategoryId > 0) {
                $params['category_id'] = $selectedCategoryId;
            }

            return redirect()->to(
                route('settings-panel.module-settings.index', $params) . '#tab-visa-status-management'
            );
        }

        $assignableStatuses = VisaStatus::query()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return view('visa_statuses.index', compact(
            'visaStatuses',
            'visaRoute',
            'visaRenewalCategories',
            'visaRenewalCategoryReturnUrl',
            'selectedCategoryId',
            'selectedCategory',
            'viewMode',
            'indexUrl',
            'assignableStatuses'
        ));
    }

    /**
     * Show the form for creating a new visa status.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!VisaExpenseAccess::canManageEntries('create')) {
            abort(403, 'Unauthorized action.');
        }

        VisaRenewalCategoryService::ensureDefaultExists();
        $categories = VisaRenewalCategoryService::allOrdered();
        if ($categories->isEmpty()) {
            Flash::error('Create a Visa Category first before adding visa statuses.');
            return redirect()->route($this->visaStatusesIndexRoute());
        }

        $selectedCategoryId = (int) $request->input('category_id', $categories->first()->id);
        if (! $categories->contains('id', $selectedCategoryId)) {
            $selectedCategoryId = (int) $categories->first()->id;
        }
        $selectedCategoryIds = [$selectedCategoryId];

        return view('visa_statuses.create', compact('categories', 'selectedCategoryId', 'selectedCategoryIds'));
    }

    /**
     * Display a single visa status.
     */
    public function show($company_slug, $id)
    {
        if (!auth()->check()) {
            return redirect()->route($this->visaStatusesIndexRoute());
        }

        if (!VisaExpenseAccess::canManageEntries('view')) {
            abort(403, 'Unauthorized action.');
        }

        if (VisaExpenseAccess::canManageEntries('edit')) {
            return redirect()->route($this->visaStatusesRouteBase() . '.edit', ['visa_status' => $id]);
        }

        return redirect()->route($this->visaStatusesIndexRoute());
    }

    /**
     * Store a newly created visa status in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!VisaExpenseAccess::canManageEntries('create')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'visa_renewal_category_ids' => ['required', 'array', 'min:1'],
            'visa_renewal_category_ids.*' => [
                'integer',
                Rule::exists('visa_renewal_categories', 'id')->where(function ($q) {
                    $companyId = \App\Support\CompanyContext::id();
                    if ($companyId !== null && \Illuminate\Support\Facades\Schema::hasColumn('visa_renewal_categories', 'company_id')) {
                        $q->where('company_id', $companyId);
                    }
                }),
            ],
            'name' => ['required', 'string', 'max:255', VisaStatus::uniqueNameRule()],
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'default_fee' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|in:Document,Permit,License,Insurance,Other',
            'is_active' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:1',
        ], [
            'visa_renewal_category_ids.required' => 'Select at least one visa category.',
            'visa_renewal_category_ids.min' => 'Select at least one visa category.',
            'name.unique' => 'A visa status with this name already exists. Edit it to assign more categories.',
        ]);

        try {
            DB::beginTransaction();

            $visaStatus = new VisaStatus();
            $visaStatus->name = $validated['name'];
            $visaStatus->code = $validated['code'] ?? null;
            $visaStatus->description = $validated['description'] ?? null;
            $visaStatus->default_fee = $validated['default_fee'] ?? 0;
            $visaStatus->category = $validated['category'] ?? 'Other';
            $visaStatus->is_active = $request->has('is_active');
            $visaStatus->is_required = $request->has('is_required');
            $visaStatus->display_order = $validated['display_order'] ?? ((int) VisaStatus::max('display_order') + 1);
            $visaStatus->created_by = auth()->id();
            $visaStatus->save();

            VisaRenewalCategoryService::syncStatusCategories(
                $visaStatus,
                $validated['visa_renewal_category_ids']
            );

            DB::commit();

            Flash::success('Visa Status added successfully.');
            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified visa status.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($company_slug, $id)
    {
        if (!VisaExpenseAccess::canManageEntries('edit')) {
            abort(403, 'Unauthorized action.');
        }

        $visaStatus = VisaStatus::with('renewalCategories')->findOrFail($id);
        VisaRenewalCategoryService::ensureDefaultExists();
        $categories = VisaRenewalCategoryService::allOrdered();
        $selectedCategoryIds = $visaStatus->renewalCategories->pluck('id')->map(fn ($v) => (int) $v)->all();
        $selectedCategoryId = (int) ($selectedCategoryIds[0] ?? ($categories->first()->id ?? 0));

        return view('visa_statuses.edit', compact('visaStatus', 'categories', 'selectedCategoryId', 'selectedCategoryIds'));
    }

    /**
     * Update the specified visa status in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $company_slug, $id)
    {
        if (!VisaExpenseAccess::canManageEntries('edit')) {
            abort(403, 'Unauthorized action.');
        }

        $visaStatus = VisaStatus::findOrFail($id);

        $validated = $request->validate([
            'visa_renewal_category_ids' => ['required', 'array', 'min:1'],
            'visa_renewal_category_ids.*' => [
                'integer',
                Rule::exists('visa_renewal_categories', 'id')->where(function ($q) {
                    $companyId = \App\Support\CompanyContext::id();
                    if ($companyId !== null && \Illuminate\Support\Facades\Schema::hasColumn('visa_renewal_categories', 'company_id')) {
                        $q->where('company_id', $companyId);
                    }
                }),
            ],
            'name' => ['required', 'string', 'max:255', VisaStatus::uniqueNameRule((int) $id)],
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'default_fee' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|in:Document,Permit,License,Insurance,Other',
            'is_active' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:1',
        ], [
            'visa_renewal_category_ids.required' => 'Select at least one visa category.',
            'visa_renewal_category_ids.min' => 'Select at least one visa category.',
            'name.unique' => 'A visa status with this name already exists. Edit it to assign more categories.',
        ]);

        try {
            DB::beginTransaction();

            $visaStatus->name = $validated['name'];
            $visaStatus->code = $validated['code'] ?? $visaStatus->code;
            $visaStatus->description = $validated['description'] ?? null;
            $visaStatus->default_fee = $validated['default_fee'] ?? $visaStatus->default_fee;
            $visaStatus->category = $validated['category'] ?? $visaStatus->category;
            $visaStatus->is_active = $request->has('is_active');
            $visaStatus->is_required = $request->has('is_required');
            $visaStatus->display_order = $validated['display_order'] ?? $visaStatus->display_order;
            $visaStatus->updated_by = auth()->id();
            $visaStatus->save();

            VisaRenewalCategoryService::syncStatusCategories(
                $visaStatus,
                $validated['visa_renewal_category_ids']
            );

            DB::commit();

            Flash::success('Visa Status updated successfully.');
            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified visa status from storage (all categories).
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $company_slug, $id)
    {
        if (!VisaExpenseAccess::canManageEntries('delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $visaStatus = VisaStatus::findOrFail($id);

            $hasActiveReferences = \App\Support\CompanyQuery::table('visa_expenses')
                ->where('visa_status', $visaStatus->name)
                ->whereNull('deleted_at')
                ->exists();

            $visaStatus->renewalCategories()->detach();

            if ($hasActiveReferences) {
                $visaStatus->delete();
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Visa Status is still referenced by active visa expenses and was soft deleted instead.',
                        'id' => (int) $id,
                        'soft_deleted' => true,
                    ]);
                }
                Flash::success('Visa Status is still referenced by active visa expenses and was soft deleted instead.');
                return $this->redirectAfterAction($request);
            }

            $visaStatus->forceDelete();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Visa Status deleted successfully.',
                    'id' => (int) $id,
                ]);
            }
            Flash::success('Visa Status deleted successfully.');
            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ], 500);
            }
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Remove a status from one category only (keep the master ticket).
     */
    public function unassign(Request $request, $company_slug, $id)
    {
        if (!VisaExpenseAccess::canManageEntries('edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'category_id' => 'required|integer|exists:visa_renewal_categories,id',
        ]);

        $visaStatus = VisaStatus::findOrFail($id);
        $categoryId = (int) $validated['category_id'];

        $visaStatus->renewalCategories()->detach($categoryId);

        $message = 'Visa Status removed from this category only.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'id' => (int) $id,
                'category_id' => $categoryId,
            ]);
        }

        Flash::success($message);
        return $this->redirectAfterAction($request);
    }

    /**
     * Toggle the active status of the specified visa status.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleActive($company_slug, $id)
    {
        if (!VisaExpenseAccess::canManageEntries('edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $visaStatus = VisaStatus::findOrFail($id);
            $visaStatus->is_active = !$visaStatus->is_active;
            $visaStatus->save();

            $status = $visaStatus->is_active ? 'activated' : 'deactivated';
            Flash::success("Visa Status {$status} successfully.");
            return $this->redirectAfterAction(request());
        } catch (\Exception $e) {
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Reorder visa statuses within a category (drag-and-drop). Expects category_id + order[].
     */
    public function reorder(Request $request)
    {
        if (!VisaExpenseAccess::canManageEntries('edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'category_id' => 'required|integer|exists:visa_renewal_categories,id',
            'order' => 'required|array',
            'order.*' => 'integer|exists:visa_statuses,id',
        ]);

        $categoryId = (int) $validated['category_id'];
        foreach ($validated['order'] as $position => $statusId) {
            DB::table('visa_renewal_category_visa_status')
                ->where('visa_renewal_category_id', $categoryId)
                ->where('visa_status_id', (int) $statusId)
                ->update(['display_order' => $position + 1, 'updated_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    private function visaStatusesIndexRoute(): string
    {
        $name = request()->route()?->getName() ?? '';
        if (str_starts_with($name, 'settings-panel.')) {
            return 'settings-panel.visa-statuses.index';
        }

        return 'visa-statuses.index';
    }

    private function visaStatusesRouteBase(): string
    {
        return str_replace('.index', '', $this->visaStatusesIndexRoute());
    }

    private function visaStatusesIndexUrl(?int $categoryId = null, string $viewMode = 'category'): string
    {
        $companySlug = (string) (request()->route('company_slug') ?? session('company_slug') ?? '');
        $params = array_filter(['company_slug' => $companySlug ?: null]);
        $url = route($this->visaStatusesIndexRoute(), $params);

        $query = [];
        if ($viewMode === 'all') {
            $query['view'] = 'all';
        } else {
            $id = (int) ($categoryId ?: request()->input('visa_renewal_category_id', request()->input('category_id')));
            if ($id > 0) {
                $query['category_id'] = $id;
            }
        }

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    protected function redirectAfterAction(Request $request)
    {
        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            return redirect()->to($returnTo);
        }

        $categoryId = (int) $request->input('visa_renewal_category_id', $request->input('category_id'));
        $viewMode = $request->input('view') === 'all' ? 'all' : 'category';

        return redirect()->to($this->visaStatusesIndexUrl($categoryId > 0 ? $categoryId : null, $viewMode));
    }
}
