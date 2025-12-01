<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AppBaseController;
use App\Repositories\PenaltiesRepository;
use App\Models\penalties;
use App\Models\Riders;
use App\Models\Accounts;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Flash;
use DB;

class PenaltiesController extends AppBaseController
{
    use GlobalPagination;
    /** @var PenaltiesRepository $penaltiesRepository*/
    private $penaltiesRepository;

    public function __construct(PenaltiesRepository $penaltiesRepo)
    {
        $this->penaltiesRepository = $penaltiesRepo;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $riderId = null)
    {
        if (!auth()->user()->hasPermissionTo('penality_view')) {
            abort(403, 'Unauthorized action.');
        }

        // If rider_id is passed in URL, redirect to rider-specific page
        if ($riderId) {
            return redirect()->route('penalties.rider', $riderId);
        }

        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = penalties::query()->orderBy('id', 'desc');

        // Apply filters
        if ($request->filled('rider_id')) {
            $query->where('rider_id', $request->rider_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('transaction_date')) {
            $query->whereDate('transaction_date', $request->transaction_date);
        }

        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);

        // Calculate totals (filter by rider if specified)
        $filterRiderId = $request->filled('rider_id') ? $request->rider_id : null;
        $statistics = $this->penaltiesRepository->getStatistics($filterRiderId);

        // Get selected rider info if filtering by rider
        $selectedRider = null;
        if ($request->filled('rider_id')) {
            $selectedRider = \App\Models\Riders::find($request->rider_id);
        }

        if ($request->ajax()) {
            $tableData = view('penalties.table', compact('data', 'selectedRider'))->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'statistics' => $statistics
            ]);
        }

        return view('penalties.index', compact('data', 'statistics', 'selectedRider'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->hasPermissionTo('penality_view')) {
            abort(403, 'Unauthorized action.');
        }

        $riders = Riders::select('id', 'name', 'rider_id')->get();
        return view('penalties.create', compact('riders'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('penality_view')) {
            abort(403, 'Unauthorized action.');
        }

        $rules = penalties::$rules;
        $request->validate($rules);

        try {
            $input = $request->all();
            $penalty = $this->penaltiesRepository->createWithAccounting($input);

            Flash::success('Penalty entry created successfully with voucher.');
            return redirect(route('penalties.index'));
        } catch (\Exception $e) {
            Flash::error('Error creating penalty entry: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $penalty = $this->penaltiesRepository->find($id);

        if (empty($penalty)) {
            Flash::error('Penalty entry not found');
            return redirect(route('penalties.index'));
        }

        return view('penalties.show')->with('penalty', $penalty);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        if (!auth()->user()->hasPermissionTo('penalty_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $penalty = $this->penaltiesRepository->find($id);

        if (empty($penalty)) {
            Flash::error('Penalty entry not found');
            return redirect(route('penalties.index'));
        }

        $riders = Riders::select('id', 'name', 'rider_id')->get();
        return view('penalties.edit', compact('penalty', 'riders'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('penalty_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $penalty = $this->penaltiesRepository->find($id);

        if (empty($penalty)) {
            Flash::error('Penalty entry not found');
            return redirect(route('penalties.index'));
        }

        $rules = penalties::$rules;
        $request->validate($rules);

        try {
            $input = $request->all();
            $penalty = $this->penaltiesRepository->updateWithAccounting($id, $input);

            Flash::success('Penalty entry updated successfully.');
            return redirect(route('penalties.index'));
        } catch (\Exception $e) {
            Flash::error('Error updating penalty entry: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->hasPermissionTo('penalty_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $penalty = $this->penaltiesRepository->find($id);

        if (empty($penalty)) {
            Flash::error('Penalty entry not found');
            return redirect(route('penalties.index'));
        }

        try {
            $this->penaltiesRepository->deleteWithAccounting($id);
            Flash::success('Penalty entry deleted successfully.');
        } catch (\Exception $e) {
            Flash::error('Error deleting penalty entry: ' . $e->getMessage());
        }

        return redirect(route('penalties.index'));
    }

    /**
     * Mark penalty as paid
     */
    public function markAsPaid(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('penalty_edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $paymentData = [
                'payment_account_id' => $request->payment_account_id ?? 1001,
                'payment_date' => $request->payment_date ?? now(),
            ];

            $penalty = $this->penaltiesRepository->markAsPaid($id, $paymentData);

            Flash::success('Penalty marked as paid successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            Flash::error('Error marking penalty as paid: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Get penalty statistics
     */
    public function getStatistics(Request $request)
    {
        $riderId = $request->input('rider_id');
        $statistics = $this->penaltiesRepository->getStatistics($riderId);

        return response()->json($statistics);
    }

    /**
     * Show Penalty entries for a specific rider
     */
    public function riderPenalties(Request $request, $riderId)
    {
        if (!auth()->user()->hasPermissionTo('penality_view')) {
            abort(403, 'Unauthorized action.');
        }

        $rider = \App\Models\Riders::findOrFail($riderId);

        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = penalties::query()
            ->where('rider_id', $riderId)
            ->orderBy('id', 'desc');

        // Apply additional filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('transaction_date')) {
            $query->whereDate('transaction_date', $request->transaction_date);
        }
        if ($request->filled('billing_month')) {
            $billingMonth = \Carbon\Carbon::parse($request->billing_month);
            $query->whereYear('billing_month', $billingMonth->year)
                ->whereMonth('billing_month', $billingMonth->month);
        }

        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);

        // Calculate totals for this rider
        $filteredData = $query->get();
        $paidAmount = $filteredData->where('status', 'paid')->sum('amount');
        $unpaidAmount = $filteredData->where('status', 'unpaid')->sum('amount');
        $pendingAmount = $filteredData->where('status', 'pending')->sum('amount');
        $paidCount = $filteredData->where('status', 'paid')->count();
        $unpaidCount = $filteredData->where('status', 'unpaid')->count();
        $pendingCount = $filteredData->where('status', 'pending')->count();

        if ($request->ajax()) {
            $tableData = view('penalties.rider_table', [
                'data' => $data,
                'rider' => $rider,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'totals' => [
                    'paidAmount' => number_format($paidAmount, 2),
                    'unpaidAmount' => number_format($unpaidAmount, 2),
                    'pendingAmount' => number_format($pendingAmount, 2),
                    'paidCount' => $paidCount,
                    'unpaidCount' => $unpaidCount,
                    'pendingCount' => $pendingCount,
                ]
            ]);
        }

        return view('penalties.rider_index', [
            'data' => $data,
            'rider' => $rider,
            'paidAmount' => $paidAmount,
            'unpaidAmount' => $unpaidAmount,
            'pendingAmount' => $pendingAmount,
            'paidCount' => $paidCount,
            'unpaidCount' => $unpaidCount,
            'pendingCount' => $pendingCount,
        ]);
    }

    /**
     * View voucher for penalty
     */
    public function viewVoucher($id)
    {
        $penalty = $this->penaltiesRepository->find($id);

        if (empty($penalty)) {
            Flash::error('Penalty entry not found');
            return redirect(route('penalties.index'));
        }

        return view('penalties.viewvoucher', compact('penalty'));
    }

    /**
     * Get penalties by rider (for AJAX)
     */
    public function getByRider(Request $request)
    {
        $riderId = $request->input('rider_id');
        $perPage = $request->input('per_page', 10);

        $penalties = $this->penaltiesRepository->getByRider($riderId, $perPage);

        return response()->json([
            'data' => $penalties->items(),
            'pagination' => [
                'current_page' => $penalties->currentPage(),
                'last_page' => $penalties->lastPage(),
                'total' => $penalties->total(),
            ]
        ]);
    }

    /**
     * Import penalties from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        try {
            // You can implement Excel import similar to Salik
            // For now, returning a placeholder
            Flash::success('Penalties import functionality to be implemented.');
            return redirect()->back();
        } catch (\Exception $e) {
            Flash::error('Import failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }
}
