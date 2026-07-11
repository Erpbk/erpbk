<?php

namespace App\Http\Controllers;

use App\Models\LicenseStatus;
use App\Support\CompanyAuthRedirect;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Flash;
use DB;

class LicenseStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:license_expense_view')->only('index', 'show');
        $this->middleware('permission:license_expense_create')->only('create', 'store', 'toggleActive', 'reorder');
        $this->middleware('permission:license_expense_edit')->only('edit', 'update', 'toggleActive', 'reorder');
        $this->middleware('permission:license_expense_delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = LicenseStatus::query();

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

        if ($request->ajax()) {
            $tableData = view('license_statuses.table', [
                'licenseStatuses' => $licenseStatuses,
                'licenseRoute' => $licenseRoute,
            ])->render();
            return response()->json([
                'tableData' => $tableData,
            ]);
        }

        return view('license_statuses.index', compact('licenseStatuses', 'licenseRoute'));
    }

    /**
     * Show the form for creating a new License Status.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('license_statuses.create');
    }

    /**
     * Display a single License Status.
     * Kept for resource-route compatibility so accidental GETs to /license-statuses/{id}
     * do not crash with "show does not exist".
     */
    public function show($company_slug, $id)
    {
        // If user can edit, send them to edit page; otherwise back to index.
        if (auth()->user()->hasPermissionTo('license_expense_edit')) {
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
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:license_statuses',
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

            $LicenseStatus = new LicenseStatus();
            $LicenseStatus->name = $validated['name'];
            $LicenseStatus->code = $validated['code'] ?? null;
            $LicenseStatus->description = $validated['description'] ?? null;
            $LicenseStatus->default_fee = $validated['default_fee'] ?? 0;
            $LicenseStatus->category = $validated['category'] ?? 'Other';
            $LicenseStatus->is_active = $request->has('is_active');
            $LicenseStatus->is_required = $request->has('is_required');

            // If display_order is not provided, set it to the next available order
            if (empty($validated['display_order'])) {
                $maxOrder = LicenseStatus::max('display_order') ?? 0;
                $LicenseStatus->display_order = $maxOrder + 1;
            } else {
                $LicenseStatus->display_order = $validated['display_order'];
            }

            // Set created_by
            $LicenseStatus->created_by = auth()->id();

            $LicenseStatus->save();

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
        $LicenseStatus = LicenseStatus::findOrFail($id);
        return view('license_statuses.edit', compact('LicenseStatus'));
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
        $LicenseStatus = LicenseStatus::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:license_statuses,name,' . $id,
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

            $LicenseStatus->name = $validated['name'];
            $LicenseStatus->code = $validated['code'] ?? $LicenseStatus->code;
            $LicenseStatus->description = $validated['description'] ?? null;
            $LicenseStatus->default_fee = $validated['default_fee'] ?? $LicenseStatus->default_fee;
            $LicenseStatus->category = $validated['category'] ?? $LicenseStatus->category;
            $LicenseStatus->is_active = $request->has('is_active');
            $LicenseStatus->is_required = $request->has('is_required');
            $LicenseStatus->display_order = $validated['display_order'] ?? $LicenseStatus->display_order;
            $LicenseStatus->updated_by = auth()->id();
            $LicenseStatus->save();

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
        try {
            $LicenseStatus = LicenseStatus::findOrFail($id);

            // Check if this status is being used in license_expenses
            $isUsed = \App\Support\CompanyQuery::table('license_expenses')->where('license_status', $LicenseStatus->name)->exists();

            if ($isUsed) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete this License Status as it is being used in License Expenses.',
                    ], 422);
                }
                Flash::error('Cannot delete this License Status as it is being used in License Expenses.');
                return redirect()->back();
            }

            $LicenseStatus->delete();
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
        try {
            $LicenseStatus = LicenseStatus::findOrFail($id);
            $LicenseStatus->is_active = !$LicenseStatus->is_active;
            $LicenseStatus->save();

            $status = $LicenseStatus->is_active ? 'activated' : 'deactivated';
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

    private function redirectAfterAction(Request $request): RedirectResponse
    {
        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            return redirect()->to($returnTo);
        }

        return redirect()->route($this->licenseStatusesIndexRoute());
    }
}
