<?php

namespace App\Http\Controllers;

use App\Models\LegalCaseStatus;
use App\Support\CompanyAuthRedirect;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Flash;
use DB;

class LegalCaseStatusController extends Controller
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
        if (!user_can('legalcase_view')) {
            abort(403, 'Unauthorized action.');
        }

        $query = LegalCaseStatus::query();

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

        $legalCaseStatuses = $query->orderBy('display_order')->orderBy('name')->get();

        $legalCaseRoute = str_replace('.index', '', $request->route()->getName());

        if ($request->ajax()) {
            $tableData = view('legal_case_statuses.table', [
                'legalCaseStatuses' => $legalCaseStatuses,
                'legalCaseRoute' => $legalCaseRoute,
            ])->render();
            return response()->json([
                'tableData' => $tableData,
            ]);
        }

        return view('legal_case_statuses.index', compact('legalCaseStatuses', 'legalCaseRoute'));
    }

    /**
     * Show the form for creating a new visa status.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Check permissions
        if (!user_can('legalcase_create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('legal_case_statuses.create');
    }

    /**
     * Display a single visa status.
     * Kept for resource-route compatibility so accidental GETs to /legal-case-statuses/{id}
     * do not crash with "show does not exist".
     */
    public function show($company_slug, $id)
    {
        if (!auth()->check()) {
            return redirect()->route($this->legalCaseStatusesIndexRoute());
        }

        if (!user_can('legalcase_view')) {
            abort(403, 'Unauthorized action.');
        }

        // If user can edit, send them to edit page; otherwise back to index.
        if (user_can('legalcase_edit')) {
            return redirect()->route($this->legalCaseStatusesRouteBase() . '.edit', ['case_status' => $id]);
        }

        return redirect()->route($this->legalCaseStatusesIndexRoute());
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
        if (!user_can('legalcase_create')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:legal_case_statuses',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'category' => 'nullable|string|in:Document,Permit,License,Insurance,Other',
            'is_active' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $visaStatus = new LegalCaseStatus();
            $visaStatus->name = $validated['name'];
            $visaStatus->code = $validated['code'] ?? null;
            $visaStatus->description = $validated['description'] ?? null;
            $visaStatus->category = $validated['category'] ?? 'Other';
            $visaStatus->is_active = $request->has('is_active');
            $visaStatus->is_required = $request->has('is_required');

            // If display_order is not provided, set it to the next available order
            if (empty($validated['display_order'])) {
                $maxOrder = LegalCaseStatus::max('display_order') ?? 0;
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
        if (!user_can('legalcase_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $visaStatus = LegalCaseStatus::findOrFail($id);
        return view('legal_case_statuses.edit', compact('visaStatus'));
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
        if (!user_can('legalcase_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $visaStatus = LegalCaseStatus::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:legal_case_statuses,name,' . $id,
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
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
        if (!user_can('legalcase_delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $visaStatus = LegalCaseStatus::findOrFail($id);

            // Active references block permanent deletion, so soft-delete the status instead.
            $hasActiveReferences = \App\Support\CompanyQuery::table('legal_cases')
                ->where('case_status', $visaStatus->name)
                ->whereNull('deleted_at')
                ->exists();

            if ($hasActiveReferences) {
                $visaStatus->delete();
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Legal Case Status is still referenced by active legal cases and was soft deleted instead.',
                        'id' => (int) $id,
                        'soft_deleted' => true,
                    ]);
                }
                Flash::success('Legal Case Status is still referenced by active legal cases and was soft deleted instead.');
                return $this->redirectAfterAction($request);
            }

            $visaStatus->forceDelete();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Legal Case Status deleted successfully.',
                    'id' => (int) $id,
                ]);
            }
            Flash::success('Legal Case Status deleted successfully.');
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
        if (!user_can('legalcase_edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $visaStatus = LegalCaseStatus::findOrFail($id);
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
        if (!user_can('legalcase_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $order = $request->input('order', []);
        if (!is_array($order) || empty($order)) {
            return response()->json(['success' => false, 'message' => 'Invalid order.'], 422);
        }

        foreach ($order as $position => $id) {
            LegalCaseStatus::where('id', (int) $id)->update(['display_order' => $position + 1]);
        }

        return response()->json(['success' => true]);
    }

    private function legalCaseStatusesIndexRoute(): string
    {
        $name = request()->route()?->getName() ?? '';
        return str_starts_with($name, 'settings-panel.') ? 'settings-panel.legal-case-statuses.index' : 'legal-case-statuses.index';
    }

    private function legalCaseStatusesRouteBase(): string
    {
        $name = request()->route()?->getName() ?? '';
        return str_starts_with($name, 'settings-panel.') ? 'settings-panel.legal-case-statuses' : 'legal-case-statuses';
    }

    private function redirectAfterAction(Request $request): RedirectResponse
    {
        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            return redirect()->to($returnTo);
        }

        return redirect()->route($this->legalCaseStatusesIndexRoute());
    }
}
