<?php

namespace App\Http\Controllers;

use App\Models\RiderInventoryAssignment;
use App\Models\RiderInventoryContract;
use App\Models\RiderInventoryItem;
use App\Models\Riders;
use App\Models\Transactions;
use App\Models\User;
use App\Models\Vouchers;
use App\Services\Agreements\AgreementPdfBranding;
use App\Services\RiderInventoryLossService;
use App\Support\CompanyAuthRedirect;
use App\Support\CompanyContext;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RiderInventoryController extends AppBaseController
{
    use GlobalPagination, TracksCascadingDeletions;

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
        $riderIds = RiderInventoryAssignment::pluck('rider_id')->unique();
        $query = Riders::query()->whereIn('id', $riderIds)->orderBy('name');

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

        $availableItems = RiderInventoryItem::availableForAssignment();

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
            'availableItems' => RiderInventoryItem::availableForAssignment(),
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

        DB::beginTransaction();

        try {
            $contractDate = Carbon::parse($validated['return_date'])->format('Y-m-d');
            $contractNumber = RiderInventoryContract::nextContractNumber(RiderInventoryContract::TYPE_RETURN);

            $assignment->status = RiderInventoryAssignment::STATUS_RETURNED;
            $assignment->return_date = $contractDate;
            $assignment->returned_by = auth()->id();
            $assignment->return_contract_number = $contractNumber;
            $assignment->remarks = $validated['remarks'] ?? null;
            $assignment->updated_by = auth()->id();
            $assignment->save();

            RiderInventoryContract::create([
                'rider_id' => $assignment->rider_id,
                'contract_type' => RiderInventoryContract::TYPE_RETURN,
                'contract_number' => $contractNumber,
                'contract_date' => $contractDate,
                'total_items' => 1,
                'total_returned' => 1,
                'total_lost' => 0,
                'total_chargeable_amount' => 0,
                'remarks' => $validated['remarks'] ?? null,
                'generated_by' => auth()->id(),
            ]);

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory item marked as returned.',
                ]);
            }

            Flash::success('Inventory item marked as returned.');

            return redirect()->route('RiderInventory.show', $assignment->rider_id);
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            Flash::error($e->getMessage());

            return redirect()->back();
        }
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

    public function changeStatusForm(string $company_slug, int $assignmentId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $assignment = $this->findChangeableRecord($assignmentId);
        $availableStatuses = $this->availableStatusTransitions($assignment->status);

        return view('rider_inventory.change_status_modal', compact('assignment', 'availableStatuses'));
    }

    public function changeStatusStore(Request $request, string $company_slug, int $assignmentId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $assignment = $this->findChangeableRecord($assignmentId);

        $validated = $request->validate([
            'target_status' => [
                'required',
                Rule::in([
                    RiderInventoryAssignment::STATUS_ASSIGNED,
                    RiderInventoryAssignment::STATUS_RETURNED,
                    RiderInventoryAssignment::STATUS_LOST,
                ]),
            ],
            'event_date' => 'nullable|date|required_if:target_status,' . RiderInventoryAssignment::STATUS_RETURNED . ',' . RiderInventoryAssignment::STATUS_LOST,
            'remarks' => 'nullable|string|max:1000',
        ]);

        if ($validated['target_status'] === $assignment->status) {
            return response()->json(['message' => 'Please select a different status.'], 422);
        }

        if (!array_key_exists($validated['target_status'], $this->availableStatusTransitions($assignment->status))) {
            return response()->json(['message' => 'Invalid status transition.'], 422);
        }

        DB::beginTransaction();

        try {
            $message = match ($validated['target_status']) {
                RiderInventoryAssignment::STATUS_ASSIGNED => $this->convertAssignmentToAssigned(
                    $assignment,
                    $validated['remarks'] ?? null
                ),
                RiderInventoryAssignment::STATUS_RETURNED => $this->convertAssignmentToReturned(
                    $assignment,
                    $validated['event_date'],
                    $validated['remarks'] ?? null
                ),
                RiderInventoryAssignment::STATUS_LOST => $this->convertAssignmentToLost(
                    $assignment,
                    $validated['event_date'],
                    $validated['remarks'] ?? null
                ),
            };

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            Flash::success($message);

            return redirect()->back();
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
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
            'dispositions' => 'nullable|array',
            'dispositions.*' => 'nullable|in:returned,lost,skip',
            'amounts' => 'nullable|array',
            'amounts.*' => 'nullable|numeric|min:0.01',
        ]);

        $assignmentIds = $assignments->pluck('id')->map(fn ($id) => (int) $id)->all();
        $processedCount = 0;

        foreach ($assignmentIds as $id) {
            $disposition = $validated['dispositions'][$id] ?? 'skip';
            if ($disposition === 'skip') {
                continue;
            }

            $processedCount++;

            if (!isset($validated['amounts'][$id])) {
                return back()->withInput()->withErrors([
                    'amounts' => 'Please enter an amount for each returned or lost item.',
                ]);
            }
        }

        if ($processedCount === 0) {
            return back()->withInput()->withErrors([
                'dispositions' => 'Please mark at least one item as Returned or Lost.',
            ]);
        }

        DB::beginTransaction();

        try {
            $contractNumber = RiderInventoryContract::nextContractNumber(RiderInventoryContract::TYPE_RETURN);
            $contractDate = Carbon::parse($validated['return_date'])->format('Y-m-d');
            $returnedItems = collect();
            $lostItems = collect();
            $lostAssignments = collect();
            $totalChargeable = 0.0;
            $lossService = app(RiderInventoryLossService::class);

            foreach ($assignments as $assignment) {
                $disposition = $validated['dispositions'][$assignment->id] ?? 'skip';
                if ($disposition === 'skip') {
                    continue;
                }

                $assignment->amount = (float) $validated['amounts'][$assignment->id];
                $assignment->updated_by = auth()->id();
                $assignment->save();

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
                    $lostAssignments->push($assignment);
                }
            }

            if ($lostAssignments->isNotEmpty()) {
                $lossResult = $lossService->chargeRiderForLostItems(
                    $lostAssignments,
                    $contractDate,
                    $validated['remarks'] ?? null,
                    auth()->id(),
                    $contractNumber
                );
                $totalChargeable = $lossResult['total_amount'];

                foreach ($lostAssignments as $assignment) {
                    $lostItems->push($assignment->fresh(['inventoryItem', 'assignedByUser', 'lostByUser', 'voucher']));
                }
            }

            $processedItems = $returnedItems->merge($lostItems);

            $contract = RiderInventoryContract::create([
                'rider_id' => $rider->id,
                'contract_type' => RiderInventoryContract::TYPE_RETURN,
                'contract_number' => $contractNumber,
                'contract_date' => $contractDate,
                'total_items' => $processedItems->count(),
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
                'allItems' => $processedItems,
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

    public function destroyAssignment(Request $request, string $company_slug, int $assignmentId)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $assignment = RiderInventoryAssignment::with(['rider', 'inventoryItem'])->findOrFail($assignmentId);

        if (!$assignment->isAssigned()) {
            $message = 'Only assigned inventory items can be deleted.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $user = auth()->user();
            $userName = $user ? ($user->name ?? 'User #' . $user->id) : 'System';
            $itemName = $assignment->inventoryItem->name ?? 'Inventory Item';
            $rider = $assignment->rider;
            $riderLabel = ($rider->name ?? 'Rider') . ' (' . ($rider->rider_id ?? $assignment->rider_id) . ')';
            $assignmentName = "{$itemName} — {$riderLabel}";

            $assignment->deleted_by = auth()->id();
            $assignment->save();

            $this->trackCascadeDeletion(
                User::class,
                auth()->id(),
                $userName,
                RiderInventoryAssignment::class,
                $assignment->id,
                $assignmentName,
                'hasMany',
                'inventoryAssignments',
                'soft',
                'User-initiated inventory assignment deletion'
            );

            $assignment->delete();

            DB::commit();

            $message = 'Inventory assignment moved to Recycle Bin. <a href="' . route('settings-panel.trash.index') . '?module=rider_inventory_assignments" class="alert-link">View Recycle Bin</a> to restore if needed.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'id' => (int) $assignmentId,
                ]);
            }

            Flash::success($message);

            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            }

            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back();
        }
    }

    private function findAssignedRecord(int $assignmentId): RiderInventoryAssignment
    {
        $assignment = RiderInventoryAssignment::with(['rider', 'inventoryItem'])->findOrFail($assignmentId);
        if (!$assignment->isAssigned()) {
            abort(422, 'Only assigned inventory items can be returned or marked as lost.');
        }

        return $assignment;
    }

    private function findChangeableRecord(int $assignmentId): RiderInventoryAssignment
    {
        $assignment = RiderInventoryAssignment::with(['rider', 'inventoryItem'])->findOrFail($assignmentId);

        if (!in_array($assignment->status, [
            RiderInventoryAssignment::STATUS_RETURNED,
            RiderInventoryAssignment::STATUS_LOST,
        ], true)) {
            abort(422, 'Only returned or lost inventory items can have their status changed.');
        }

        return $assignment;
    }

    private function availableStatusTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            RiderInventoryAssignment::STATUS_LOST => [
                RiderInventoryAssignment::STATUS_ASSIGNED => 'Assigned',
                RiderInventoryAssignment::STATUS_RETURNED => 'Returned',
            ],
            RiderInventoryAssignment::STATUS_RETURNED => [
                RiderInventoryAssignment::STATUS_ASSIGNED => 'Assigned',
                RiderInventoryAssignment::STATUS_LOST => 'Lost (charge rider)',
            ],
            default => [],
        };
    }

    private function convertAssignmentToAssigned(RiderInventoryAssignment $assignment, ?string $remarks): string
    {
        if ($assignment->status === RiderInventoryAssignment::STATUS_LOST) {
            $this->removeLossVoucher($assignment);
        }

        if ($assignment->status === RiderInventoryAssignment::STATUS_RETURNED) {
            $this->removeReturnContractLink($assignment);
        }

        $assignment->status = RiderInventoryAssignment::STATUS_ASSIGNED;
        $assignment->return_date = null;
        $assignment->returned_by = null;
        $assignment->loss_date = null;
        $assignment->lost_by = null;
        $assignment->trans_code = null;
        $assignment->il_voucher_number = null;
        $assignment->voucher_id = null;
        $assignment->remarks = $remarks;
        $assignment->updated_by = auth()->id();
        $assignment->save();

        return 'Inventory item reverted to assigned status.';
    }

    private function convertAssignmentToReturned(RiderInventoryAssignment $assignment, string $eventDate, ?string $remarks): string
    {
        if ($assignment->status === RiderInventoryAssignment::STATUS_LOST) {
            $this->removeLossVoucher($assignment);
        }

        if ($assignment->status === RiderInventoryAssignment::STATUS_RETURNED) {
            $this->removeReturnContractLink($assignment);
        }

        $contractDate = Carbon::parse($eventDate)->format('Y-m-d');
        $contractNumber = RiderInventoryContract::nextContractNumber(RiderInventoryContract::TYPE_RETURN);

        $assignment->status = RiderInventoryAssignment::STATUS_RETURNED;
        $assignment->return_date = $contractDate;
        $assignment->returned_by = auth()->id();
        $assignment->loss_date = null;
        $assignment->lost_by = null;
        $assignment->trans_code = null;
        $assignment->il_voucher_number = null;
        $assignment->voucher_id = null;
        $assignment->return_contract_number = $contractNumber;
        $assignment->remarks = $remarks;
        $assignment->updated_by = auth()->id();
        $assignment->save();

        RiderInventoryContract::create([
            'rider_id' => $assignment->rider_id,
            'contract_type' => RiderInventoryContract::TYPE_RETURN,
            'contract_number' => $contractNumber,
            'contract_date' => $contractDate,
            'total_items' => 1,
            'total_returned' => 1,
            'total_lost' => 0,
            'total_chargeable_amount' => 0,
            'remarks' => $remarks,
            'generated_by' => auth()->id(),
        ]);

        return 'Inventory item marked as returned.';
    }

    private function convertAssignmentToLost(RiderInventoryAssignment $assignment, string $eventDate, ?string $remarks): string
    {
        if ($assignment->status === RiderInventoryAssignment::STATUS_RETURNED) {
            $this->removeReturnContractLink($assignment);
            $assignment->return_date = null;
            $assignment->returned_by = null;
            $assignment->status = RiderInventoryAssignment::STATUS_ASSIGNED;
            $assignment->save();
            $assignment->refresh();
        }

        if ($assignment->status === RiderInventoryAssignment::STATUS_LOST) {
            $this->removeLossVoucher($assignment);
            $assignment->status = RiderInventoryAssignment::STATUS_ASSIGNED;
            $assignment->save();
            $assignment->refresh();
        }

        app(RiderInventoryLossService::class)->chargeRiderForLostItem(
            $assignment,
            $eventDate,
            $remarks,
            auth()->id()
        );

        return 'Inventory item marked as lost and rider charged successfully.';
    }

    private function removeLossVoucher(RiderInventoryAssignment $assignment): void
    {
        if (!empty($assignment->voucher_id) || !empty($assignment->trans_code)) {
            app(RiderInventoryLossService::class)->reverseLossChargeForAssignment($assignment);
        }

        if ($assignment->return_contract_number) {
            $this->removeReturnContractLink($assignment);
        }
    }

    private function removeReturnContractLink(RiderInventoryAssignment $assignment): void
    {
        $contractNumber = $assignment->return_contract_number;
        $assignment->return_contract_number = null;

        if (!$contractNumber) {
            return;
        }

        $remaining = RiderInventoryAssignment::query()
            ->where('return_contract_number', $contractNumber)
            ->where('id', '!=', $assignment->id)
            ->count();

        if ($remaining === 0) {
            RiderInventoryContract::where('contract_number', $contractNumber)->delete();
        }
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
