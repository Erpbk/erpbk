<?php

namespace App\Http\Controllers;

use App\Models\VisaRenewalCategory;
use App\Models\VisaStatus;
use App\Support\CompanyAuthRedirect;
use App\Support\VisaRenewalCategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        // Check permissions
        if (!user_can('visaexpense_view')) {
            abort(403, 'Unauthorized action.');
        }

        VisaRenewalCategoryService::ensureDefaultExists();
        $visaRenewalCategories = VisaRenewalCategory::query()
            ->withCount('visaStatuses')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $selectedCategoryId = (int) $request->input('category_id');
        if ($selectedCategoryId <= 0 || ! $visaRenewalCategories->contains('id', $selectedCategoryId)) {
            $selectedCategoryId = (int) ($visaRenewalCategories->first()->id ?? 0);
        }
        $selectedCategory = $visaRenewalCategories->firstWhere('id', $selectedCategoryId);

        $query = VisaStatus::query()->with('renewalCategory');
        if ($selectedCategoryId > 0) {
            $query->where('visa_renewal_category_id', $selectedCategoryId);
        }

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', (int) $request->status);
        }
        if ($request->has('is_required') && $request->is_required !== '') {
            $query->where('is_required', (int) $request->is_required);
        }

        $visaStatuses = $query->orderBy('display_order')->orderBy('name')->get();

        $visaRoute = str_replace('.index', '', $request->route()->getName());

        $companySlug = (string) ($request->route('company_slug') ?? session('company_slug') ?? '');
        $indexUrl = $this->visaStatusesIndexUrl($selectedCategoryId);
        $visaRenewalCategoryReturnUrl = $indexUrl;

        if ($request->ajax()) {
            $tableData = view('visa_statuses.table', [
                'visaStatuses' => $visaStatuses,
                'visaRoute' => $visaRoute,
                'visaStatusReturnTo' => $indexUrl,
                'selectedCategoryId' => $selectedCategoryId,
            ])->render();
            return response()->json([
                'tableData' => $tableData,
                'selectedCategoryId' => $selectedCategoryId,
                'statusCount' => $visaStatuses->count(),
                'addStatusUrl' => $selectedCategoryId > 0
                    ? route($visaRoute . '.create', array_filter(['company_slug' => $companySlug ?: null])) . '?category_id=' . $selectedCategoryId
                    : null,
            ]);
        }

        return view('visa_statuses.index', compact(
            'visaStatuses',
            'visaRoute',
            'visaRenewalCategories',
            'visaRenewalCategoryReturnUrl',
            'selectedCategoryId',
            'selectedCategory'
        ));
    }

    /**
     * Show the form for creating a new visa status.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // Check permissions
        if (!user_can('visaexpense_create')) {
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

        return view('visa_statuses.create', compact('categories', 'selectedCategoryId'));
    }

    /**
     * Display a single visa status.
     * Kept for resource-route compatibility so accidental GETs to /visa-statuses/{id}
     * do not crash with "show does not exist".
     */
    public function show($company_slug, $id)
    {
        if (!auth()->check()) {
            return redirect()->route($this->visaStatusesIndexRoute());
        }

        if (!user_can('visaexpense_view')) {
            abort(403, 'Unauthorized action.');
        }

        // If user can edit, send them to edit page; otherwise back to index.
        if (user_can('visaexpense_edit')) {
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
        // Check permissions
        if (!user_can('visaexpense_create')) {
            abort(403, 'Unauthorized action.');
        }

        $categoryId = (int) $request->input('visa_renewal_category_id');
        $validated = $request->validate([
            'visa_renewal_category_id' => [
                'required',
                'integer',
                Rule::exists('visa_renewal_categories', 'id')->where(function ($q) {
                    $companyId = \App\Support\CompanyContext::id();
                    if ($companyId !== null && \Illuminate\Support\Facades\Schema::hasColumn('visa_renewal_categories', 'company_id')) {
                        $q->where('company_id', $companyId);
                    }
                }),
            ],
            'name' => ['required', 'string', 'max:255', VisaStatus::uniqueNameRule($categoryId)],
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'default_fee' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|in:Document,Permit,License,Insurance,Other',
            'is_active' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:1',
        ], [
            'visa_renewal_category_id.required' => 'Select a visa category. Create a category first if none exist.',
            'name.unique' => 'A visa status with this name already exists in the selected visa category.',
        ]);

        try {
            DB::beginTransaction();

            $visaStatus = new VisaStatus();
            $visaStatus->visa_renewal_category_id = (int) $validated['visa_renewal_category_id'];
            $visaStatus->name = $validated['name'];
            $visaStatus->code = $validated['code'] ?? null;
            $visaStatus->description = $validated['description'] ?? null;
            $visaStatus->default_fee = $validated['default_fee'] ?? 0;
            $visaStatus->category = $validated['category'] ?? 'Other';
            $visaStatus->is_active = $request->has('is_active');
            $visaStatus->is_required = $request->has('is_required');

            // If display_order is not provided, set it to the next available order within the category
            if (empty($validated['display_order'])) {
                $maxOrder = VisaStatus::query()
                    ->where('visa_renewal_category_id', $visaStatus->visa_renewal_category_id)
                    ->max('display_order') ?? 0;
                $visaStatus->display_order = $maxOrder + 1;
            } else {
                $visaStatus->display_order = $validated['display_order'];
            }

            // Set created_by
            $visaStatus->created_by = auth()->id();

            $visaStatus->save();

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
        // Check permissions
        if (!user_can('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $visaStatus = VisaStatus::findOrFail($id);
        VisaRenewalCategoryService::ensureDefaultExists();
        $categories = VisaRenewalCategoryService::allOrdered();
        $selectedCategoryId = (int) ($visaStatus->visa_renewal_category_id ?: ($categories->first()->id ?? 0));

        return view('visa_statuses.edit', compact('visaStatus', 'categories', 'selectedCategoryId'));
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
        // Check permissions
        if (!user_can('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $visaStatus = VisaStatus::findOrFail($id);

        $categoryId = (int) $request->input('visa_renewal_category_id', $visaStatus->visa_renewal_category_id);
        $validated = $request->validate([
            'visa_renewal_category_id' => [
                'required',
                'integer',
                Rule::exists('visa_renewal_categories', 'id')->where(function ($q) {
                    $companyId = \App\Support\CompanyContext::id();
                    if ($companyId !== null && \Illuminate\Support\Facades\Schema::hasColumn('visa_renewal_categories', 'company_id')) {
                        $q->where('company_id', $companyId);
                    }
                }),
            ],
            'name' => ['required', 'string', 'max:255', VisaStatus::uniqueNameRule($categoryId, (int) $id)],
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'default_fee' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|in:Document,Permit,License,Insurance,Other',
            'is_active' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:1',
        ], [
            'visa_renewal_category_id.required' => 'Select a visa category. Create a category first if none exist.',
            'name.unique' => 'A visa status with this name already exists in the selected visa category.',
        ]);

        try {
            DB::beginTransaction();

            $visaStatus->visa_renewal_category_id = (int) $validated['visa_renewal_category_id'];
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
     * Remove the specified visa status from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $company_slug, $id)
    {
        // Check permissions
        if (!user_can('visaexpense_delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $visaStatus = VisaStatus::findOrFail($id);

            // Active references block permanent deletion, so soft-delete the status instead.
            $hasActiveReferences = \App\Support\CompanyQuery::table('visa_expenses')
                ->where('visa_status', $visaStatus->name)
                ->when($visaStatus->visa_renewal_category_id, function ($q) use ($visaStatus) {
                    $q->where('renewal_category_id', $visaStatus->visa_renewal_category_id);
                })
                ->whereNull('deleted_at')
                ->exists();

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
     * Toggle the active status of the specified visa status.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleActive($company_slug, $id)
    {
        // Check permissions
        if (!user_can('visaexpense_edit')) {
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
     * Reorder visa statuses (drag-and-drop). Expects order[] with ids in new order.
     */
    public function reorder(Request $request)
    {
        if (!user_can('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $order = $request->input('order', []);
        if (!is_array($order) || empty($order)) {
            return response()->json(['success' => false, 'message' => 'Invalid order.'], 422);
        }

        foreach ($order as $position => $id) {
            VisaStatus::where('id', (int) $id)->update(['display_order' => $position + 1]);
        }

        return response()->json(['success' => true]);
    }

    private function visaStatusesIndexRoute(): string
    {
        $name = request()->route()?->getName() ?? '';
        return str_starts_with($name, 'settings-panel.') ? 'settings-panel.visa-statuses.index' : 'visa-statuses.index';
    }

    private function visaStatusesRouteBase(): string
    {
        $name = request()->route()?->getName() ?? '';
        return str_starts_with($name, 'settings-panel.') ? 'settings-panel.visa-statuses' : 'visa-statuses';
    }

    private function visaStatusesIndexUrl(?int $categoryId = null): string
    {
        $url = route($this->visaStatusesIndexRoute());
        $id = (int) ($categoryId ?: request()->input('visa_renewal_category_id', request()->input('category_id')));
        if ($id > 0) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'category_id=' . $id;
        }

        return $url;
    }

    private function redirectAfterAction(Request $request): RedirectResponse
    {
        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            return redirect()->to($returnTo);
        }

        $categoryId = (int) $request->input('visa_renewal_category_id', $request->input('category_id'));

        return redirect()->to($this->visaStatusesIndexUrl($categoryId));
    }
}
