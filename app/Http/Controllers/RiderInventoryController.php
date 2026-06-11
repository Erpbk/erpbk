<?php

namespace App\Http\Controllers;

use App\Models\RiderInventoryAssignment;
use App\Models\RiderInventoryContract;
use App\Models\RiderInventoryItem;
use App\Models\Riders;
use App\Services\Agreements\AgreementPdfBranding;
use App\Services\RiderInventoryLossService;
use App\Support\CompanyAuthRedirect;
use App\Support\CompanyContext;
use App\Traits\GlobalPagination;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RiderInventoryController extends AppBaseController
{
    use GlobalPagination;

    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!auth()->user()->hasPermissionTo('riderinventory_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $statusFilter = $request->input('status_filter', '');
        $userBranches = app('user_branches');

        $query = Riders::query()->orderBy('name');

        if (!auth()->user()->isAdmin()) {
            if (!empty($userBranches)) {
                $query->where(function ($q) use ($userBranches) {
                    $q->whereIn('branch_id', $userBranches)->orWhereNull('branch_id');
                });
            } else {
                $query->whereNull('branch_id');
            }
        }

        if ($request->filled('quick_search')) {
            $term = trim((string) $request->quick_search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('rider_id', 'like', '%' . $term . '%')
                    ->orWhere('person_code', 'like', '%' . $term . '%');
            });
        }

        if ($statusFilter === 'assigned') {
            $query->whereHas('inventoryAssignments', fn($q) => $q->where('status', RiderInventoryAssignment::STATUS_ASSIGNED));
        } elseif ($statusFilter === 'returned') {
            $query->whereHas('inventoryAssignments', fn($q) => $q->where('status', RiderInventoryAssignment::STATUS_RETURNED));
        } elseif ($statusFilter === 'lost') {
            $query->whereHas('inventoryAssignments', fn($q) => $q->where('status', RiderInventoryAssignment::STATUS_LOST));
        }

        $assignedCount = RiderInventoryAssignment::where('status', RiderInventoryAssignment::STATUS_ASSIGNED)->count();
        $returnedCount = RiderInventoryAssignment::where('status', RiderInventoryAssignment::STATUS_RETURNED)->count();
        $lostCount = RiderInventoryAssignment::where('status', RiderInventoryAssignment::STATUS_LOST)->count();

        $data = $this->applyPagination($query, $paginationParams);
        $assignmentCounts = RiderInventoryAssignment::query()
            ->selectRaw('rider_id, status, COUNT(*) as total')
            ->whereIn('rider_id', collect($data->items())->pluck('id'))
            ->groupBy('rider_id', 'status')
            ->get()
            ->groupBy('rider_id');

        if ($request->ajax()) {
            return response()->json([
                'tableData' => view('rider_inventory.rider_table', [
                    'riders' => $data,
                    'assignmentCounts' => $assignmentCounts,
                ])->render(),
                'paginationLinks' => $data->links('components.global-pagination')->render(),
                'stats' => [
                    'assigned' => $assignedCount,
                    'returned' => $returnedCount,
                    'lost' => $lostCount,
                ],
            ]);
        }

        return view('rider_inventory.index', [
            'riders' => $data,
            'assignmentCounts' => $assignmentCounts,
            'assignedCount' => $assignedCount,
            'returnedCount' => $returnedCount,
            'lostCount' => $lostCount,
            'statusFilter' => $statusFilter,
            'allRiders' => Riders::orderBy('name')->get(['id', 'name', 'rider_id']),
        ]);
    }

    public function show(Request $request, string $company_slug, int $riderId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_view')) {
            abort(403, 'Unauthorized action.');
        }

        $rider = Riders::findOrFail($riderId);
        $assignments = RiderInventoryAssignment::query()
            ->with(['inventoryItem', 'assignedByUser', 'returnedByUser', 'lostByUser', 'voucher'])
            ->where('rider_id', $riderId)
            ->orderByDesc('assigned_date')
            ->orderByDesc('id')
            ->get();

        $availableItems = $this->availableItemsForAssignment();

        if ($request->ajax()) {
            return response()->json([
                'tableData' => view('rider_inventory.assignment_table', [
                    'assignments' => $assignments,
                    'rider' => $rider,
                ])->render(),
            ]);
        }

        return view('rider_inventory.show', compact('rider', 'assignments', 'availableItems'));
    }

    public function assignForm(string $company_slug, int $riderId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_create')) {
            abort(403, 'Unauthorized action.');
        }

        $rider = Riders::findOrFail($riderId);

        return view('rider_inventory.assign_modal', [
            'rider' => $rider,
            'availableItems' => $this->availableItemsForAssignment(),
        ]);
    }

    public function assignStore(Request $request, string $company_slug, int $riderId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_create')) {
            abort(403, 'Unauthorized action.');
        }

        $rider = Riders::findOrFail($riderId);

        $validated = $request->validate([
            'inventory_item_id' => [
                'required',
                'integer',
                Rule::exists('rider_inventory_items', 'id')->where(fn($q) => $q->where('is_active', true)),
            ],
            'assigned_date' => 'required|date',
        ]);

        $item = RiderInventoryItem::findOrFail($validated['inventory_item_id']);
        if ($item->hasOpenAssignment()) {
            return response()->json([
                'success' => false,
                'message' => 'This inventory item is already assigned and has not been returned.',
            ], 422);
        }

        $assignment = RiderInventoryAssignment::create([
            'rider_id' => $rider->id,
            'inventory_item_id' => $item->id,
            'assigned_date' => $validated['assigned_date'],
            'assigned_by' => auth()->id(),
            'status' => RiderInventoryAssignment::STATUS_ASSIGNED,
            'amount' => $item->item_price,
            'created_by' => auth()->id(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Inventory item assigned successfully.',
                'assignment_id' => $assignment->id,
            ]);
        }

        Flash::success('Inventory item assigned successfully.');

        return redirect()->route('RiderInventory.show', $riderId);
    }

    public function returnForm(string $company_slug, int $assignmentId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $assignment = $this->findAssignedRecord($assignmentId);

        return view('rider_inventory.return_modal', compact('assignment'));
    }

    public function returnStore(Request $request, string $company_slug, int $assignmentId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $assignment = $this->findAssignedRecord($assignmentId);

        $validated = $request->validate([
            'return_date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $assignment->status = RiderInventoryAssignment::STATUS_RETURNED;
        $assignment->return_date = $validated['return_date'];
        $assignment->returned_by = auth()->id();
        $assignment->remarks = $validated['remarks'] ?? null;
        $assignment->updated_by = auth()->id();
        $assignment->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Inventory item marked as returned.',
            ]);
        }

        Flash::success('Inventory item marked as returned.');

        return redirect()->route('RiderInventory.show', $assignment->rider_id);
    }

    public function lostForm(string $company_slug, int $assignmentId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $assignment = $this->findAssignedRecord($assignmentId);
        $assignment->load(['rider', 'inventoryItem']);

        return view('rider_inventory.lost_modal', compact('assignment'));
    }

    public function markLost(Request $request, string $company_slug, int $assignmentId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $assignment = $this->findAssignedRecord($assignmentId);

        $validated = $request->validate([
            'return_date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $result = app(RiderInventoryLossService::class)->chargeRiderForLostItem(
                $assignment,
                $validated['return_date'],
                $validated['remarks'] ?? null,
                auth()->id()
            );

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory item marked as lost and rider charged successfully.',
                    'voucher_id' => $result['voucher']->id,
                ]);
            }

            Flash::success('Inventory item marked as lost and rider charged successfully.');

            return redirect()->route('RiderInventory.show', $assignment->rider_id);
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            Flash::error($e->getMessage());

            return redirect()->back();
        }
    }

    public function assignmentContract(string $company_slug, int $riderId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_contract_print')) {
            abort(403, 'Unauthorized action.');
        }

        $rider = Riders::findOrFail($riderId);
        $assignments = $this->assignedItemsForRider($riderId);

        if ($assignments->isEmpty()) {
            Flash::error('No assigned inventory items found for this rider.');

            return redirect()->route('RiderInventory.show', $riderId);
        }

        DB::beginTransaction();

        try {
            $contractNumber = RiderInventoryContract::nextContractNumber(RiderInventoryContract::TYPE_ASSIGNMENT);
            $contractDate = now()->toDateString();

            $contract = RiderInventoryContract::create([
                'rider_id' => $rider->id,
                'contract_type' => RiderInventoryContract::TYPE_ASSIGNMENT,
                'contract_number' => $contractNumber,
                'contract_date' => $contractDate,
                'total_items' => $assignments->count(),
                'total_returned' => 0,
                'total_lost' => 0,
                'total_chargeable_amount' => 0,
                'generated_by' => auth()->id(),
            ]);

            foreach ($assignments as $assignment) {
                $assignment->assignment_contract_number = $contractNumber;
                $assignment->updated_by = auth()->id();
                $assignment->save();
            }

            DB::commit();

            return view('rider_inventory.assignment_contract', [
                'rider' => $rider,
                'assignments' => $assignments->fresh(['inventoryItem', 'assignedByUser']),
                'contract' => $contract,
                'branding' => $this->contractBranding(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Flash::error('Error generating assignment contract: ' . $e->getMessage());

            return redirect()->route('RiderInventory.show', $riderId);
        }
    }

    public function returnContractForm(string $company_slug, int $riderId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_contract_print')) {
            abort(403, 'Unauthorized action.');
        }

        $rider = Riders::findOrFail($riderId);
        $assignments = $this->assignedItemsForRider($riderId);

        if ($assignments->isEmpty()) {
            Flash::error('No assigned inventory items found for return contract.');

            return redirect()->route('RiderInventory.show', $riderId);
        }

        return view('rider_inventory.return_contract_form', [
            'rider' => $rider,
            'assignments' => $assignments,
        ]);
    }

    public function returnContractProcess(Request $request, string $company_slug, int $riderId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_contract_print')) {
            abort(403, 'Unauthorized action.');
        }

        $rider = Riders::findOrFail($riderId);
        $assignments = $this->assignedItemsForRider($riderId);

        if ($assignments->isEmpty()) {
            Flash::error('No assigned inventory items found.');

            return redirect()->route('RiderInventory.show', $riderId);
        }

        $validated = $request->validate([
            'return_date' => 'required|date',
            'remarks' => 'nullable|string|max:2000',
            'dispositions' => 'required|array',
            'dispositions.*' => 'required|in:returned,lost',
        ]);

        $assignmentIds = $assignments->pluck('id')->map(fn ($id) => (int) $id)->all();
        foreach ($assignmentIds as $id) {
            if (!isset($validated['dispositions'][$id])) {
                return back()->withInput()->withErrors([
                    'dispositions' => 'Please select Returned or Lost for every inventory item.',
                ]);
            }
        }

        DB::beginTransaction();

        try {
            $contractNumber = RiderInventoryContract::nextContractNumber(RiderInventoryContract::TYPE_RETURN);
            $contractDate = Carbon::parse($validated['return_date'])->format('Y-m-d');
            $returnedItems = collect();
            $lostItems = collect();
            $totalChargeable = 0.0;
            $lossService = app(RiderInventoryLossService::class);

            foreach ($assignments as $assignment) {
                $disposition = $validated['dispositions'][$assignment->id];

                if ($disposition === 'returned') {
                    $assignment->status = RiderInventoryAssignment::STATUS_RETURNED;
                    $assignment->return_date = $contractDate;
                    $assignment->returned_by = auth()->id();
                    $assignment->return_contract_number = $contractNumber;
                    $assignment->remarks = $validated['remarks'] ?? $assignment->remarks;
                    $assignment->updated_by = auth()->id();
                    $assignment->save();
                    $returnedItems->push($assignment->fresh(['inventoryItem', 'assignedByUser', 'returnedByUser']));
                } else {
                    $result = $lossService->chargeRiderForLostItem(
                        $assignment,
                        $contractDate,
                        $validated['remarks'] ?? null,
                        auth()->id(),
                        $contractNumber
                    );
                    $totalChargeable += $result['amount'];
                    $lostItems->push($assignment->fresh(['inventoryItem', 'assignedByUser', 'lostByUser', 'voucher']));
                }
            }

            $contract = RiderInventoryContract::create([
                'rider_id' => $rider->id,
                'contract_type' => RiderInventoryContract::TYPE_RETURN,
                'contract_number' => $contractNumber,
                'contract_date' => $contractDate,
                'total_items' => $assignments->count(),
                'total_returned' => $returnedItems->count(),
                'total_lost' => $lostItems->count(),
                'total_chargeable_amount' => $totalChargeable,
                'remarks' => $validated['remarks'] ?? null,
                'generated_by' => auth()->id(),
            ]);

            DB::commit();

            $contract->load('generatedByUser');

            return view('rider_inventory.return_contract', [
                'rider' => $rider,
                'contract' => $contract,
                'returnedItems' => $returnedItems,
                'lostItems' => $lostItems,
                'allItems' => $assignments,
                'branding' => $this->contractBranding(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Flash::error('Error processing return contract: ' . $e->getMessage());

            return redirect()->route('RiderInventory.returnContractForm', $riderId)->withInput();
        }
    }

    public function returnContractDocument(string $company_slug, int $contractId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_contract_print')) {
            abort(403, 'Unauthorized action.');
        }

        $contract = RiderInventoryContract::with(['rider', 'generatedByUser'])->findOrFail($contractId);

        if ($contract->contract_type !== RiderInventoryContract::TYPE_RETURN) {
            abort(404);
        }

        $allItems = RiderInventoryAssignment::query()
            ->with(['inventoryItem', 'assignedByUser', 'returnedByUser', 'lostByUser', 'voucher'])
            ->where('return_contract_number', $contract->contract_number)
            ->get();

        $returnedItems = $allItems->where('status', RiderInventoryAssignment::STATUS_RETURNED);
        $lostItems = $allItems->where('status', RiderInventoryAssignment::STATUS_LOST);

        return view('rider_inventory.return_contract', [
            'rider' => $contract->rider,
            'contract' => $contract,
            'returnedItems' => $returnedItems,
            'lostItems' => $lostItems,
            'allItems' => $allItems,
            'branding' => $this->contractBranding(),
        ]);
    }

    private function findAssignedRecord(int $assignmentId): RiderInventoryAssignment
    {
        $assignment = RiderInventoryAssignment::with(['rider', 'inventoryItem'])->findOrFail($assignmentId);
        if (!$assignment->isAssigned()) {
            abort(422, 'Only assigned inventory items can be returned or marked as lost.');
        }

        return $assignment;
    }

    private function availableItemsForAssignment()
    {
        $assignedItemIds = RiderInventoryAssignment::query()
            ->where('status', RiderInventoryAssignment::STATUS_ASSIGNED)
            ->pluck('inventory_item_id');

        return RiderInventoryItem::query()
            ->where('is_active', true)
            ->whereNotIn('id', $assignedItemIds)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    private function assignedItemsForRider(int $riderId)
    {
        return RiderInventoryAssignment::query()
            ->with(['inventoryItem', 'assignedByUser'])
            ->where('rider_id', $riderId)
            ->where('status', RiderInventoryAssignment::STATUS_ASSIGNED)
            ->orderBy('assigned_date')
            ->orderBy('id')
            ->get();
    }

    private function contractBranding(): array
    {
        return app(AgreementPdfBranding::class)->forCompany(CompanyContext::id());
    }
}
