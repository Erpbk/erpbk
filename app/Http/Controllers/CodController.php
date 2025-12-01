<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AppBaseController;
use App\Repositories\CodRepository;
use App\Models\cod;
use App\Models\Riders;
use App\Models\Accounts;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Flash;
use DB;

class CodController extends AppBaseController
{
    use GlobalPagination;
    /** @var CodRepository $codRepository*/
    private $codRepository;

    public function __construct(CodRepository $codRepo)
    {
        $this->codRepository = $codRepo;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $riderId = null)
    {
        if (!auth()->user()->hasPermissionTo('cod_view')) {
            abort(403, 'Unauthorized action.');
        }

        // If rider_id is passed in URL, redirect to rider-specific page
        if ($riderId) {
            return redirect()->route('cod.rider', $riderId);
        }

        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = cod::query()->orderBy('id', 'desc');

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
        $statistics = $this->codRepository->getStatistics($filterRiderId);

        // Get selected rider info if filtering by rider
        $selectedRider = null;
        if ($request->filled('rider_id')) {
            $selectedRider = \App\Models\Riders::find($request->rider_id);
        }

        if ($request->ajax()) {
            $tableData = view('cod.table', compact('data', 'selectedRider'))->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'statistics' => $statistics
            ]);
        }

        return view('cod.index', compact('data', 'statistics', 'selectedRider'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->hasPermissionTo('cod_create')) {
            abort(403, 'Unauthorized action.');
        }

        $riders = Riders::select('id', 'name', 'rider_id')->get();
        return view('cod.create', compact('riders'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('cod_create')) {
            abort(403, 'Unauthorized action.');
        }

        $rules = cod::$rules;
        $request->validate($rules);

        try {
            $input = $request->all();
            $cod = $this->codRepository->createWithAccounting($input);

            Flash::success('COD entry created successfully with voucher.');
            return redirect(route('cod.index'));
        } catch (\Exception $e) {
            Flash::error('Error creating COD entry: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $cod = $this->codRepository->find($id);

        if (empty($cod)) {
            Flash::error('COD entry not found');
            return redirect(route('cod.index'));
        }

        return view('cod.show')->with('cod', $cod);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        if (!auth()->user()->hasPermissionTo('cod_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $cod = $this->codRepository->find($id);

        if (empty($cod)) {
            Flash::error('COD entry not found');
            return redirect(route('cod.index'));
        }

        $riders = Riders::select('id', 'name', 'rider_id')->get();
        return view('cod.edit', compact('cod', 'riders'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('cod_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $cod = $this->codRepository->find($id);

        if (empty($cod)) {
            Flash::error('COD entry not found');
            return redirect(route('cod.index'));
        }

        $rules = cod::$rules;
        $request->validate($rules);

        try {
            $input = $request->all();
            $cod = $this->codRepository->updateWithAccounting($id, $input);

            Flash::success('COD entry updated successfully.');
            return redirect(route('cod.index'));
        } catch (\Exception $e) {
            Flash::error('Error updating COD entry: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->hasPermissionTo('cod_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $cod = $this->codRepository->find($id);

        if (empty($cod)) {
            Flash::error('COD entry not found');
            return redirect(route('cod.index'));
        }

        try {
            $this->codRepository->deleteWithAccounting($id);
            Flash::success('COD entry deleted successfully.');
        } catch (\Exception $e) {
            Flash::error('Error deleting COD entry: ' . $e->getMessage());
        }

        return redirect(route('cod.index'));
    }

    /**
     * Mark COD as paid
     */
    public function markAsPaid(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('cod_edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $paymentData = [
                'payment_account_id' => $request->payment_account_id ?? 1001,
                'payment_date' => $request->payment_date ?? now(),
            ];

            $cod = $this->codRepository->markAsPaid($id, $paymentData);

            Flash::success('COD marked as paid successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            Flash::error('Error marking COD as paid: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Get COD statistics
     */
    public function getStatistics(Request $request)
    {
        $riderId = $request->input('rider_id');
        $statistics = $this->codRepository->getStatistics($riderId);

        return response()->json($statistics);
    }

    /**
     * Show COD entries for a specific rider
     */
    public function riderCod(Request $request, $riderId)
    {
        if (!auth()->user()->hasPermissionTo('cod_view')) {
            abort(403, 'Unauthorized action.');
        }

        $rider = \App\Models\Riders::findOrFail($riderId);

        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = cod::query()
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
            $tableData = view('cod.rider_table', [
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

        return view('cod.rider_index', [
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
     * View voucher for COD
     */
    public function viewVoucher($id)
    {
        $cod = $this->codRepository->find($id);

        if (empty($cod)) {
            Flash::error('COD entry not found');
            return redirect(route('cod.index'));
        }

        return view('cod.viewvoucher', compact('cod'));
    }
}
