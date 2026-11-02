<?php

namespace App\Http\Controllers;

use App\Models\LicenseCategory;
use App\Models\LicenseStatus;
use App\Support\CompanyAuthRedirect;
use App\Support\LicenseCategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Flash;
use DB;

class LicenseStatusController extends Controller
{
    /**
     * Display a listing of the License Statuses.
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
        if (!user_can('licenseexpense_view')) {
            abort(403, 'Unauthorized action.');
        }

        LicenseCategoryService::ensureDefaultExists();
        $licenseCategories = LicenseCategory::query()
            ->withCount('licenseStatuses')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $selectedCategoryId = (int) $request->input('category_id');
        if ($selectedCategoryId <= 0 || ! $licenseCategories->contains('id', $selectedCategoryId)) {
            $selectedCategoryId = (int) ($licenseCategories->first()->id ?? 0);
        }
        $selectedCategory = $licenseCategories->firstWhere('id', $selectedCategoryId);

        $query = LicenseStatus::query()->with('licenseCategory');
        if ($selectedCategoryId > 0) {
            $query->where('license_category_id', $selectedCategoryId);
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

        $licenseStatuses = $query->orderBy('display_order')->orderBy('name')->get();

        $licenseRoute = str_replace('.index', '', $request->route()->getName());

        $companySlug = (string) ($request->route('company_slug') ?? session('company_slug') ?? '');
        $indexUrl = $this->licenseStatusesIndexUrl($selectedCategoryId);
        $licenseCategoryReturnUrl = $indexUrl;

        if ($request->ajax()) {
            $tableData = view('license_statuses.table', [
                'licenseStatuses' => $licenseStatuses,
                'licenseRoute' => $licenseRoute,
                'licenseStatusReturnTo' => $indexUrl,
                'selectedCategoryId' => $selectedCategoryId,
            ])->render();
            return response()->json([
                'tableData' => $tableData,
                'selectedCategoryId' => $selectedCategoryId,
                'statusCount' => $licenseStatuses->count(),
                'addStatusUrl' => $selectedCategoryId > 0
                    ? route($licenseRoute . '.create', array_filter(['company_slug' => $companySlug ?: null])) . '?category_id=' . $selectedCategoryId
                    : null,
            ]);
        }

        return view('license_statuses.index', compact(
            'licenseStatuses',
            'licenseRoute',
            'licenseCategories',
            'licenseCategoryReturnUrl',
            'selectedCategoryId',
            'selectedCategory'
        ));
    }

    /**
     * Show the form for creating a new License Status.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // Check permissions
        if (!user_can('licenseexpense_create')) {
            abort(403, 'Unauthorized action.');
        }

        LicenseCategoryService::ensureDefaultExists();
        $categories = LicenseCategoryService::allOrdered();
        if ($categories->isEmpty()) {
            Flash::error('Create a License Category first before adding license statuses.');
            return redirect()->route($this->licenseStatusesIndexRoute());
        }

        $selectedCategoryId = (int) $request->input('category_id', $categories->first()->id);
        if (! $categories->contains('id', $selectedCategoryId)) {
            $selectedCategoryId = (int) $categories->first()->id;
        }

        return view('license_statuses.create', compact('categories', 'selectedCategoryId'));
    }

    /**
     * Display a single License Status.
     * Kept for resource-route compatibility so accidental GETs to /license-statuses/{id}
     * do not crash with "show does not exist".
     */
    public function show($company_slug, $id)
    {
        if (!auth()->check()) {
            return redirect()->route($this->licenseStatusesIndexRoute());
        }

        if (!user_can('licenseexpense_view')) {
            abort(403, 'Unauthorized action.');
        }

        // If user can edit, send them to edit page; otherwise back to index.
        if (user_can('licenseexpense_edit')) {
            return redirect()->route($this->licenseStatusesRouteBase() . '.edit', ['license_status' => $id]);
        }

        return redirect()->route($this->licenseStatusesIndexRoute());
    }

    /**
     * Store a newly created License Status in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check permissions
        if (!user_can('licenseexpense_create')) {
            abort(403, 'Unauthorized action.');
        }

        $categoryId = (int) $request->input('license_category_id');
        $validated = $request->validate([
            'license_category_id' => [
                'required',
                'integer',
                Rule::exists('license_categories', 'id')->where(function ($q) {
                    $companyId = \App\Support\CompanyContext::id();
                    if ($companyId !== null && Schema::hasColumn('license_categories', 'company_id')) {
                        $q->where('company_id', $companyId);
                    }
                }),
            ],
            'name' => ['required', 'string', 'max:255', LicenseStatus::uniqueNameRule($categoryId)],
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'default_fee' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|in:Document,Permit,License,Insurance,Other',
            'is_active' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:1',
        ], [
            'license_category_id.required' => 'Select a license category. Create a category first if none exist.',
            'name.unique' => 'A license status with this name already exists in the selected license category.',
        ]);

        try {
            DB::beginTransaction();

            $licenseStatus = new LicenseStatus();
            $licenseStatus->license_category_id = (int) $validated['license_category_id'];
            $licenseStatus->name = $validated['name'];
            $licenseStatus->code = $validated['code'] ?? null;
            $licenseStatus->description = $validated['description'] ?? null;
            $licenseStatus->default_fee = $validated['default_fee'] ?? 0;
            $licenseStatus->category = $validated['category'] ?? 'Other';
            $licenseStatus->is_active = $request->has('is_active');
            $licenseStatus->is_required = $request->has('is_required');

            // If display_order is not provided, set it to the next available order within the category
            if (empty($validated['display_order'])) {
                $maxOrder = LicenseStatus::query()
                    ->where('license_category_id', $licenseStatus->license_category_id)
                    ->max('display_order') ?? 0;
                $licenseStatus->display_order = $maxOrder + 1;
            } else {
                $licenseStatus->display_order = $validated['display_order'];
            }

            // Set created_by
            $licenseStatus->created_by = auth()->id();

            $licenseStatus->save();

            DB::commit();

            Flash::success('License Status added successfully.');
            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified License Status.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($company_slug, $id)
    {
        // Check permissions
        if (!user_can('licenseexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $LicenseStatus = LicenseStatus::findOrFail($id);
        LicenseCategoryService::ensureDefaultExists();
        $categories = LicenseCategoryService::allOrdered();
        $selectedCategoryId = (int) ($LicenseStatus->license_category_id ?: ($categories->first()->id ?? 0));

        return view('license_statuses.edit', compact('LicenseStatus', 'categories', 'selectedCategoryId'));
    }

    /**
     * Update the specified License Status in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $company_slug, $id)
    {
        // Check permissions
        if (!user_can('licenseexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $licenseStatus = LicenseStatus::findOrFail($id);

        $categoryId = (int) $request->input('license_category_id', $licenseStatus->license_category_id);
        $validated = $request->validate([
            'license_category_id' => [
                'required',
                'integer',
                Rule::exists('license_categories', 'id')->where(function ($q) {
                    $companyId = \App\Support\CompanyContext::id();
                    if ($companyId !== null && Schema::hasColumn('license_categories', 'company_id')) {
                        $q->where('company_id', $companyId);
                    }
                }),
            ],
            'name' => ['required', 'string', 'max:255', LicenseStatus::uniqueNameRule($categoryId, (int) $id)],
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'default_fee' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|in:Document,Permit,License,Insurance,Other',
            'is_active' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:1',
        ], [
            'license_category_id.required' => 'Select a license category. Create a category first if none exist.',
            'name.unique' => 'A license status with this name already exists in the selected license category.',
        ]);

        try {
            DB::beginTransaction();

            $licenseStatus->license_category_id = (int) $validated['license_category_id'];
            $licenseStatus->name = $validated['name'];
            $licenseStatus->code = $validated['code'] ?? $licenseStatus->code;
            $licenseStatus->description = $validated['description'] ?? null;
            $licenseStatus->default_fee = $validated['default_fee'] ?? $licenseStatus->default_fee;
            $licenseStatus->category = $validated['category'] ?? $licenseStatus->category;
            $licenseStatus->is_active = $request->has('is_active');
            $licenseStatus->is_required = $request->has('is_required');
            $licenseStatus->display_order = $validated['display_order'] ?? $licenseStatus->display_order;
            $licenseStatus->updated_by = auth()->id();
            $licenseStatus->save();

            DB::commit();

            Flash::success('License Status updated successfully.');
            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified License Status from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $company_slug, $id)
    {
        // Check permissions
        if (!user_can('licenseexpense_delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $licenseStatus = LicenseStatus::findOrFail($id);

            // Active references block permanent deletion, so soft-delete the status instead.
            $hasActiveReferences = \App\Support\CompanyQuery::table('license_expenses')
                ->where('license_status', $licenseStatus->name)
                ->when(
                    $licenseStatus->license_category_id
                        && Schema::hasColumn('license_expenses', 'license_category_id'),
                    function ($q) use ($licenseStatus) {
                        $q->where('license_category_id', $licenseStatus->license_category_id);
                    }
                )
                ->whereNull('deleted_at')
                ->exists();

            if ($hasActiveReferences) {
                $licenseStatus->delete();
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'License Status is still referenced by active license expenses and was soft deleted instead.',
                        'id' => (int) $id,
                        'soft_deleted' => true,
                    ]);
                }
                Flash::success('License Status is still referenced by active license expenses and was soft deleted instead.');
                return $this->redirectAfterAction($request);
            }

            $licenseStatus->forceDelete();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'License Status deleted successfully.',
                    'id' => (int) $id,
                ]);
            }
            Flash::success('License Status deleted successfully.');
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
     * Toggle the active status of the specified License Status.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleActive($company_slug, $id)
    {
        // Check permissions
        if (!user_can('licenseexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $licenseStatus = LicenseStatus::findOrFail($id);
            $licenseStatus->is_active = !$licenseStatus->is_active;
            $licenseStatus->save();

            $status = $licenseStatus->is_active ? 'activated' : 'deactivated';
            Flash::success("License Status {$status} successfully.");
            return $this->redirectAfterAction(request());
        } catch (\Exception $e) {
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Reorder License Statuses (drag-and-drop). Expects order[] with ids in new order.
     */
    public function reorder(Request $request)
    {
        if (!user_can('licenseexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $order = $request->input('order', []);
        if (!is_array($order) || empty($order)) {
            return response()->json(['success' => false, 'message' => 'Invalid order.'], 422);
        }

        foreach ($order as $position => $id) {
            LicenseStatus::where('id', (int) $id)->update(['display_order' => $position + 1]);
        }

        return response()->json(['success' => true]);
    }

    private function licenseStatusesIndexRoute(): string
    {
        $name = request()->route()?->getName() ?? '';
        return str_starts_with($name, 'settings-panel.') ? 'settings-panel.license-statuses.index' : 'license-statuses.index';
    }

    private function licenseStatusesRouteBase(): string
    {
        $name = request()->route()?->getName() ?? '';
        return str_starts_with($name, 'settings-panel.') ? 'settings-panel.license-statuses' : 'license-statuses';
    }

    private function licenseStatusesIndexUrl(?int $categoryId = null): string
    {
        $url = route($this->licenseStatusesIndexRoute());
        $id = (int) ($categoryId ?: request()->input('license_category_id', request()->input('category_id')));
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

        $categoryId = (int) $request->input('license_category_id', $request->input('category_id'));

        return redirect()->to($this->licenseStatusesIndexUrl($categoryId));
    }
}
