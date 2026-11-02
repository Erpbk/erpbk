<?php

namespace App\Http\Controllers;

use App\Models\RiderInventoryAssignment;
use App\Models\RiderInventoryItem;
use App\Models\Riders;
use App\Models\Vouchers;
use App\Support\CompanyAuthRedirect;
use App\Traits\GlobalPagination;
use Illuminate\Http\Request;

class RiderInventoryReportController extends AppBaseController
{
    use GlobalPagination;

    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!user_can('riderinventory_view')) {
            abort(403, 'Unauthorized action.');
        }

        return view('rider_inventory_reports.index', [
            'reportType' => $request->input('type', 'assigned'),
        ]);
    }

    public function data(Request $request)
    {
        if (!user_can('riderinventory_view')) {
            abort(403, 'Unauthorized action.');
        }

        $type = $request->input('type', 'assigned');
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        if ($type === 'loss_vouchers') {
            return $this->lossVoucherReport($request, $paginationParams);
        }

        if ($type === 'rider_history') {
            return $this->riderHistoryReport($request, $paginationParams);
        }

        $statusMap = [
            'assigned' => RiderInventoryAssignment::STATUS_ASSIGNED,
            'returned' => RiderInventoryAssignment::STATUS_RETURNED,
            'lost' => RiderInventoryAssignment::STATUS_LOST,
        ];

        if (!isset($statusMap[$type])) {
            abort(404, 'Invalid report type.');
        }

        $query = RiderInventoryAssignment::query()
            ->with(['rider', 'inventoryItem', 'assignedByUser', 'returnedByUser'])
            ->where('status', $statusMap[$type])
            ->orderByDesc('assigned_date')
            ->orderByDesc('id');

        if ($request->filled('rider_id')) {
            $query->where('rider_id', (int) $request->rider_id);
        }
        if ($request->filled('inventory_item_id')) {
            $query->where('inventory_item_id', (int) $request->inventory_item_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('assigned_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('assigned_date', '<=', $request->date_to);
        }

        $data = $this->applyPagination($query, $paginationParams);

        return response()->json([
            'tableData' => view('rider_inventory_reports.assignment_report_table', [
                'rows' => $data,
                'reportType' => $type,
            ])->render(),
            'paginationLinks' => $data->links('components.global-pagination')->render(),
        ]);
    }

    private function riderHistoryReport(Request $request, array $paginationParams)
    {
        $request->validate([
            'rider_id' => 'required|integer|exists:riders,id',
        ]);

        $query = RiderInventoryAssignment::query()
            ->with(['inventoryItem', 'assignedByUser', 'returnedByUser', 'voucher'])
            ->where('rider_id', (int) $request->rider_id)
            ->orderByDesc('assigned_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $this->applyPagination($query, $paginationParams);
        $rider = Riders::find($request->rider_id);

        return response()->json([
            'tableData' => view('rider_inventory_reports.rider_history_table', [
                'rows' => $data,
                'rider' => $rider,
            ])->render(),
            'paginationLinks' => $data->links('components.global-pagination')->render(),
        ]);
    }

    private function lossVoucherReport(Request $request, array $paginationParams)
    {
        $query = Vouchers::query()
            ->where('voucher_type', 'IL')
            ->orderByDesc('trans_date')
            ->orderByDesc('id');

        if ($request->filled('date_from')) {
            $query->whereDate('trans_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('trans_date', '<=', $request->date_to);
        }

        $data = $this->applyPagination($query, $paginationParams);
        $assignmentIds = collect($data->items())->pluck('ref_id')->filter()->unique()->values();
        $assignments = RiderInventoryAssignment::with(['rider', 'inventoryItem'])
            ->whereIn('id', $assignmentIds)
            ->get()
            ->keyBy('id');

        return response()->json([
            'tableData' => view('rider_inventory_reports.loss_voucher_table', [
                'rows' => $data,
                'assignments' => $assignments,
            ])->render(),
            'paginationLinks' => $data->links('components.global-pagination')->render(),
        ]);
    }
}
