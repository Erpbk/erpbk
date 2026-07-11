<?php

namespace App\Http\Controllers;

use App\Models\VisaRenewalCategory;
use App\Models\VisaStatus;
use App\Support\CompanyAuthRedirect;
use App\Support\VisaRenewalCategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Flash;
use DB;

class VisaStatusController extends Controller
{
    /**
     * Display a listing of the visa statuses.
     *
     * @return \Illuminate\Http\Response
     */


    public function __construct()
    {
        $this->middleware('permission:visa_expense_view')->only('index', 'show');
        $this->middleware('permission:visa_expense_create')->only('create', 'store', 'toggleActive', 'reorder');
        $this->middleware('permission:visa_expense_edit')->only('edit', 'update', 'reorder', 'toggleActive');
        $this->middleware('permission:visa_expense_delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = VisaStatus::query();

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

        VisaRenewalCategoryService::ensureDefaultExists();
        $visaRenewalCategories = VisaRenewalCategory::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $companySlug = (string) ($request->route('company_slug') ?? session('company_slug') ?? '');
        $visaRenewalCategoryReturnUrl = $visaRoute === 'settings-panel.visa-statuses' && $companySlug !== ''
            ? route('settings-panel.visa-statuses.index', ['company_slug' => $companySlug]) . '#tab-visa-renewal-categories'
            : null;

        if ($request->ajax()) {
            $tableData = view('visa_statuses.table', [
                'visaStatuses' => $visaStatuses,
                'visaRoute' => $visaRoute,
            ])->render();
            return response()->json([
                'tableData' => $tableData,
            ]);
        }

        return view('visa_statuses.index', compact(
            'visaStatuses',
            'visaRoute',
            'visaRenewalCategories',
            'visaRenewalCategoryReturnUrl'
        ));
    }

    /**
     * Show the form for creating a new visa status.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('visa_statuses.create');
    }

    /**
     * Display a single visa status.
     * Kept for resource-route compatibility so accidental GETs to /visa-statuses/{id}
     * do not crash with "show does not exist".
     */
    public function show($company_slug, $id)
    {
        // If user can edit, send them to edit page; otherwise back to index.
        if (auth()->user()->hasPermissionTo('visaexpense_edit')) {
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
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:visa_statuses',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'default_fee' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|in:Document,Permit,License,Insurance,Other',
            'is_active' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:1',
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

            // If display_order is not provided, set it to the next available order
            if (empty($validated['display_order'])) {
                $maxOrder = VisaStatus::max('display_order') ?? 0;
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
        $visaStatus = VisaStatus::findOrFail($id);
        return view('visa_statuses.edit', compact('visaStatus'));
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
        $visaStatus = VisaStatus::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:visa_statuses,name,' . $id,
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'default_fee' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|in:Document,Permit,License,Insurance,Other',
            'is_active' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:1',
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
        try {
            $visaStatus = VisaStatus::findOrFail($id);

            // Check if this status is being used in visa_expenses
            $isUsed = \App\Support\CompanyQuery::table('visa_expenses')->where('visa_status', $visaStatus->name)->exists();

            if ($isUsed) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete this visa status as it is being used in visa expenses.',
                    ], 422);
                }
                Flash::error('Cannot delete this visa status as it is being used in visa expenses.');
                return redirect()->back();
            }

            $visaStatus->delete();
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

    private function redirectAfterAction(Request $request): RedirectResponse
    {
        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            return redirect()->to($returnTo);
        }

        return redirect()->route($this->visaStatusesIndexRoute());
    }
}
