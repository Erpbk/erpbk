<?php

namespace App\Http\Controllers;

use App\Models\BikeRegistrationStatus;
use App\Support\CompanyAuthRedirect;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Flash;
use DB;

class BikeRegistrationStatusController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!auth()->user()->hasPermissionTo('bike_registration_view')) {
            abort(403, 'Unauthorized action.');
        }

        $query = BikeRegistrationStatus::query();

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

        $bikeRegistrationStatuses = $query->orderBy('display_order')->orderBy('name')->get();

        $bikeRegistrationRoute = str_replace('.index', '', $request->route()->getName());

        if ($request->ajax()) {
            $tableData = view('bike_registration_statuses.table', [
                'bikeRegistrationStatuses' => $bikeRegistrationStatuses,
                'bikeRegistrationRoute' => $bikeRegistrationRoute,
            ])->render();

            return response()->json([
                'tableData' => $tableData,
            ]);
        }

        return view('bike_registration_statuses.index', compact('bikeRegistrationStatuses', 'bikeRegistrationRoute'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('bike_registration_create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('bike_registration_statuses.create');
    }

    public function show($company_slug, $id)
    {
        if (!auth()->check()) {
            return redirect()->route($this->statusesIndexRoute());
        }

        if (!auth()->user()->hasPermissionTo('bike_registration_view')) {
            abort(403, 'Unauthorized action.');
        }

        if (auth()->user()->hasPermissionTo('bike_registration_edit')) {
            return redirect()->route($this->statusesRouteBase() . '.edit', ['bike_registration_status' => $id]);
        }

        return redirect()->route($this->statusesIndexRoute());
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('bike_registration_create')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:bike_registration_statuses',
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

            $row = new BikeRegistrationStatus();
            $row->name = $validated['name'];
            $row->code = $validated['code'] ?? null;
            $row->description = $validated['description'] ?? null;
            $row->default_fee = $validated['default_fee'] ?? 0;
            $row->category = $validated['category'] ?? 'Other';
            $row->is_active = $request->has('is_active');
            $row->is_required = $request->has('is_required');

            if (empty($validated['display_order'])) {
                $maxOrder = BikeRegistrationStatus::max('display_order') ?? 0;
                $row->display_order = $maxOrder + 1;
            } else {
                $row->display_order = $validated['display_order'];
            }

            $row->created_by = auth()->id();
            $row->save();

            DB::commit();

            Flash::success('Registration status added successfully.');

            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function edit($company_slug, $id)
    {
        if (!auth()->user()->hasPermissionTo('bike_registration_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $bikeRegistrationStatus = BikeRegistrationStatus::findOrFail($id);

        return view('bike_registration_statuses.edit', compact('bikeRegistrationStatus'));
    }

    public function update(Request $request, $company_slug, $id)
    {
        if (!auth()->user()->hasPermissionTo('bike_registration_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $bikeRegistrationStatus = BikeRegistrationStatus::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:bike_registration_statuses,name,' . $id,
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

            $bikeRegistrationStatus->name = $validated['name'];
            $bikeRegistrationStatus->code = $validated['code'] ?? $bikeRegistrationStatus->code;
            $bikeRegistrationStatus->description = $validated['description'] ?? null;
            $bikeRegistrationStatus->default_fee = $validated['default_fee'] ?? $bikeRegistrationStatus->default_fee;
            $bikeRegistrationStatus->category = $validated['category'] ?? $bikeRegistrationStatus->category;
            $bikeRegistrationStatus->is_active = $request->has('is_active');
            $bikeRegistrationStatus->is_required = $request->has('is_required');
            $bikeRegistrationStatus->display_order = $validated['display_order'] ?? $bikeRegistrationStatus->display_order;
            $bikeRegistrationStatus->updated_by = auth()->id();
            $bikeRegistrationStatus->save();

            DB::commit();

            Flash::success('Registration status updated successfully.');

            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function destroy(Request $request, $company_slug, $id)
    {
        if (!auth()->user()->hasPermissionTo('bike_registration_delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $bikeRegistrationStatus = BikeRegistrationStatus::findOrFail($id);

            $isUsed = \App\Support\CompanyQuery::table('bike_registrations')
                ->where('registration_status', $bikeRegistrationStatus->name)
                ->exists();

            if ($isUsed) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete this status as it is used in bike registrations.',
                    ], 422);
                }
                Flash::error('Cannot delete this status as it is used in bike registrations.');

                return redirect()->back();
            }

            $bikeRegistrationStatus->delete();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Registration status deleted successfully.',
                    'id' => (int) $id,
                ]);
            }
            Flash::success('Registration status deleted successfully.');

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

    public function toggleActive($company_slug, $id)
    {
        if (!auth()->user()->hasPermissionTo('bike_registration_edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $bikeRegistrationStatus = BikeRegistrationStatus::findOrFail($id);
            $bikeRegistrationStatus->is_active = !$bikeRegistrationStatus->is_active;
            $bikeRegistrationStatus->save();

            $status = $bikeRegistrationStatus->is_active ? 'activated' : 'deactivated';
            Flash::success("Registration status {$status} successfully.");

            return $this->redirectAfterAction(request());
        } catch (\Exception $e) {
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back();
        }
    }

    public function reorder(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('bike_registration_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $order = $request->input('order', []);
        if (!is_array($order) || empty($order)) {
            return response()->json(['success' => false, 'message' => 'Invalid order.'], 422);
        }

        foreach ($order as $position => $id) {
            BikeRegistrationStatus::where('id', (int) $id)->update(['display_order' => $position + 1]);
        }

        return response()->json(['success' => true]);
    }

    private function statusesIndexRoute(): string
    {
        $name = request()->route()?->getName() ?? '';

        return str_starts_with($name, 'settings-panel.') ? 'settings-panel.bike-registration-statuses.index' : 'bike-registration-statuses.index';
    }

    private function statusesRouteBase(): string
    {
        $name = request()->route()?->getName() ?? '';

        return str_starts_with($name, 'settings-panel.') ? 'settings-panel.bike-registration-statuses' : 'bike-registration-statuses';
    }

    private function redirectAfterAction(Request $request): RedirectResponse
    {
        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            return redirect()->to($returnTo);
        }

        return redirect()->route($this->statusesIndexRoute());
    }
}
