<?php

namespace App\Http\Controllers;

use App\Models\VisaStatus;
use Illuminate\Http\Request;
use Flash;
use DB;

class VisaStatusController extends Controller
{
    /**
     * Display a listing of the visa statuses.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        // Check permissions
        if (!auth()->user()->hasPermissionTo('visaexpense_view')) {
            abort(403, 'Unauthorized action.');
        }

        $visaStatuses = VisaStatus::orderBy('display_order')->orderBy('name')->get();

        return view('visa_statuses.index', compact('visaStatuses'));
    }

    /**
     * Show the form for creating a new visa status.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Check permissions
        if (!auth()->user()->hasPermissionTo('visaexpense_create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('visa_statuses.create');
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
        if (!auth()->user()->hasPermissionTo('visaexpense_create')) {
            abort(403, 'Unauthorized action.');
        }

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
            return redirect()->route('visa-statuses.index');
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
    public function edit($id)
    {
        // Check permissions
        if (!auth()->user()->hasPermissionTo('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

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
    public function update(Request $request, $id)
    {
        // Check permissions
        if (!auth()->user()->hasPermissionTo('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

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
            return redirect()->route('visa-statuses.index');
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
    public function destroy($id)
    {
        // Check permissions
        if (!auth()->user()->hasPermissionTo('visaexpense_delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $visaStatus = VisaStatus::findOrFail($id);

            // Check if this status is being used in visa_expenses
            $isUsed = DB::table('visa_expenses')->where('visa_status', $visaStatus->name)->exists();

            if ($isUsed) {
                Flash::error('Cannot delete this visa status as it is being used in visa expenses.');
                return redirect()->back();
            }

            $visaStatus->delete();
            Flash::success('Visa Status deleted successfully.');
            return redirect()->route('visa-statuses.index');
        } catch (\Exception $e) {
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
    public function toggleActive($id)
    {
        // Check permissions
        if (!auth()->user()->hasPermissionTo('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $visaStatus = VisaStatus::findOrFail($id);
            $visaStatus->is_active = !$visaStatus->is_active;
            $visaStatus->save();

            $status = $visaStatus->is_active ? 'activated' : 'deactivated';
            Flash::success("Visa Status {$status} successfully.");
            return redirect()->route('visa-statuses.index');
        } catch (\Exception $e) {
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back();
        }
    }
}
