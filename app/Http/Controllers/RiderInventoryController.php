<?php

namespace App\Http\Controllers;

use App\Models\Customers;
use App\Models\Items;
use App\Models\RiderInventoryAssignment;
use App\Models\RiderInventoryContract;
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

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:riders_inventory_view')->only('index', 'show', 'returnContractForm', 'returnContractDocument');
        $this->middleware('permission:riders_inventory_create')->only('assignForm', 'assignStore', 'assignmentContract', 'returnToCustomerAssignments', 'returnToCustomerStore');
        $this->middleware('permission:riders_inventory_edit')->only('returnForm', 'returnStore', 'lostForm', 'markLost', 'changeStatusForm', 'changeStatusStore', 'assignmentContract', 'returnToCustomerAssignments', 'returnToCustomerStore');
        $this->middleware('permission:riders_inventory_delete')->only('destroyAssignment');
        $this->middleware('permission:customers_inventory_edit')->only('returnToCustomerAssignments', 'returnToCustomerStore');
    }

    public function index(Request $request)
    {

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $statusFilter = $request->input('status_filter', '');

        $assignmentQuery = RiderInventoryAssignment::query()
            ->with(['rider', 'customer', 'inventoryItem'])
            ->orderByDesc('assigned_date')
            ->orderByDesc('id');

        if ($request->filled('quick_search')) {
            $term = trim((string) $request->quick_search);
            $assignmentQuery->where(function ($q) use ($term) {
                $q->whereHas('rider', function ($riderQuery) use ($term) {
                    $riderQuery->where('name', 'like', '%' . $term . '%')
                        ->orWhere('rider_id', 'like', '%' . $term . '%')
                        ->orWhere('person_code', 'like', '%' . $term . '%');
                })->orWhereHas('customer', function ($customerQuery) use ($term) {
                    $customerQuery->where('name', 'like', '%' . $term . '%')
                        ->orWhere('company_name', 'like', '%' . $term . '%');
                })->orWhereHas('inventoryItem', function ($itemQuery) use ($term) {
                    $itemQuery->where('name', 'like', '%' . $term . '%');
                });
            });
        }

        if ($statusFilter === 'assigned') {
            $assignmentQuery->where('status', RiderInventoryAssignment::STATUS_ASSIGNED);
        } elseif ($statusFilter === 'returned') {
            $assignmentQuery->where('status', RiderInventoryAssignment::STATUS_RETURNED);
        } elseif ($statusFilter === 'lost') {
            $assignmentQuery->where('status', RiderInventoryAssignment::STATUS_LOST);
        }

        $assignedCount = RiderInventoryAssignment::where('status', RiderInventoryAssignment::STATUS_ASSIGNED)->count();
        $returnedCount = RiderInventoryAssignment::where('status', RiderInventoryAssignment::STATUS_RETURNED)->count();
        $lostCount = RiderInventoryAssignment::where('status', RiderInventoryAssignment::STATUS_LOST)->count();
        $returnedToCustomerCount = RiderInventoryAssignment::where('status', RiderInventoryAssignment::STATUS_RETURNED_TO_CUSTOMER)->count();

        $assignments = $this->applyPagination($assignmentQuery, $paginationParams);

        if ($request->ajax()) {
            return response()->json([
                'tableData' => view('rider_inventory.assignment_index_table', [
                    'assignments' => $assignments,
                ])->render(),
                'paginationLinks' => $assignments->links('components.global-pagination')->render(),
                'stats' => [
                    'assigned' => $assignedCount,
                    'returned' => $returnedCount,
                    'lost' => $lostCount,
                    'returnedToCustomer' => $returnedToCustomerCount,
                ],
            ]);
        }

        return view('rider_inventory.index', [
            'assignments' => $assignments,
            'assignedCount' => $assignedCount,
            'returnedCount' => $returnedCount,
            'lostCount' => $lostCount,
            'returnedToCustomerCount' => $returnedToCustomerCount,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function show(Request $request, string $company_slug, int $riderId)
    {

        $rider = Riders::findOrFail($riderId);
        $assignments = RiderInventoryAssignment::query()
            ->with(['inventoryItem', 'customer', 'assignedByUser', 'returnedByUser', 'lostByUser', 'voucher'])
            ->where('rider_id', $riderId)
            ->orderByDesc('assigned_date')
            ->orderByDesc('id')
            ->get();

        $availableItems = Items::availableForAssignment();

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

    public function assignForm(string $company_slug, ?int $riderId = null)
    {

        $rider = $riderId ? Riders::findOrFail($riderId) : null;
        $showRiderSelect = $rider === null;

        return view('rider_inventory.assign_form', [
            'rider' => $rider,
            'showRiderSelect' => $showRiderSelect,
            'allRiders' => $showRiderSelect
                ? Riders::orderBy('name')->get(['id', 'name', 'rider_id'])
                : collect(),
            'availableItems' => Items::availableForAssignment(),
            'customers' => $this->customersForInventory(activeOnly: true),
        ]);
    }

    public function assignStore(Request $request, string $company_slug)
    {

        $validated = $request->validate([
            'rider_id' => 'required|integer|exists:riders,id',
            'assigned_date' => 'required|date',
            'customer_id' => 'required|integer|exists:customers,id',
            'item_id' => 'required|array|min:1',
            'item_id.*' => 'nullable|integer',
            'qty' => 'required|array',
            'qty.*' => 'nullable|integer|min:1',
            'rate' => 'required|array',
            'rate.*' => 'nullable|numeric|min:0',
        ]);

        $lines = collect($validated['item_id'])
            ->map(function ($itemId, $index) use ($validated) {
                return [
                    'inventory_item_id' => $itemId,
                    'qty' => $validated['qty'][$index] ?? 1,
                    'amount' => $validated['rate'][$index] ?? null,
                ];
            })
            ->filter(fn ($line) => !empty($line['inventory_item_id']))
            ->values();

        if ($lines->isEmpty()) {
            $message = 'Please add at least one inventory item.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message, 'errors' => ['item_id' => [$message]]], 422);
            }
            Flash::error($message);

            return redirect()->back()->withInput();
        }

        $itemExistsRule = Rule::exists('items', 'id')->where(function ($query) {
            $query->where('status', 1)
                ->whereJsonContains('owner', 'riderInventory');
        });

        foreach ($lines as $line) {
            validator($line, [
                'inventory_item_id' => ['required', 'integer', $itemExistsRule],
                'qty' => 'required|integer|min:1',
                'amount' => 'required|numeric|min:0.01',
            ], [], [
                'inventory_item_id' => 'item',
                'qty' => 'quantity',
                'amount' => 'price',
            ])->validate();
        }

        $rider = Riders::findOrFail($validated['rider_id']);

        $assignments = DB::transaction(function () use ($rider, $validated, $lines) {
            $created = collect();

            foreach ($lines as $line) {
                $created->push(RiderInventoryAssignment::create([
                    'rider_id' => $rider->id,
                    'inventory_item_id' => $line['inventory_item_id'],
                    'customer_id' => $validated['customer_id'],
                    'assigned_date' => $validated['assigned_date'],
                    'assigned_by' => auth()->id(),
                    'status' => RiderInventoryAssignment::STATUS_ASSIGNED,
                    'qty' => (int) $line['qty'],
                    'amount' => $line['amount'],
                    'created_by' => auth()->id(),
                ]));
            }

            return $created;
        });

        $count = $assignments->count();
        $message = $count === 1
            ? 'Inventory item assigned successfully.'
            : $count . ' inventory items assigned successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'reload' => true,
                'assignment_ids' => $assignments->pluck('id')->values(),
            ]);
        }

        Flash::success($message);

        return redirect()->route('RiderInventory.show', $rider->id);
    }

    public function returnForm(string $company_slug, int $assignmentId)
    {

        $assignment = $this->findAssignedRecord($assignmentId);

        return view('rider_inventory.return_modal', compact('assignment'));
    }

    public function returnStore(Request $request, string $company_slug, int $assignmentId)
    {

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

        $assignment = $this->findAssignedRecord($assignmentId);
        $assignment->load(['rider', 'inventoryItem']);

        return view('rider_inventory.lost_modal', compact('assignment'));
    }

    public function markLost(Request $request, string $company_slug, int $assignmentId)
    {

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

        $assignment = $this->findChangeableRecord($assignmentId);
        $availableStatuses = $this->availableStatusTransitions($assignment->status);

        return view('rider_inventory.change_status_modal', compact('assignment', 'availableStatuses'));
    }

    public function changeStatusStore(Request $request, string $company_slug, int $assignmentId)
    {

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

                $formTotal = (float) $validated['amounts'][$assignment->id];
                $qty = max(1, (int) ($assignment->qty ?? 1));
                $assignment->amount = round($formTotal / $qty, 2);
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

        $contract = RiderInventoryContract::with(['rider', 'generatedByUser'])->findOrFail($contractId);

        if ($contract->contract_type !== RiderInventoryContract::TYPE_RETURN) {
            abort(404);
        }

        $allItems = RiderInventoryAssignment::query()
            ->with(['inventoryItem', 'customer', 'assignedByUser', 'returnedByUser', 'lostByUser', 'voucher'])
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

    public function returnToCustomerForm(string $company_slug)
    {

        return view('rider_inventory.return_to_customer_form', [
            'customers' => $this->customersForInventory(activeOnly: false),
        ]);
    }

    public function returnToCustomerAssignments(Request $request, string $company_slug)
    {

        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
        ]);

        $assignments = RiderInventoryAssignment::query()
            ->with(['rider', 'inventoryItem', 'returnedByUser'])
            ->where('customer_id', $validated['customer_id'])
            ->where('status', RiderInventoryAssignment::STATUS_RETURNED)
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'tableHtml' => view('rider_inventory.return_to_customer_table', [
                    'assignments' => $assignments,
                ])->render(),
                'count' => $assignments->count(),
            ]);
        }

        return view('rider_inventory.return_to_customer_table', compact('assignments'));
    }

    public function returnToCustomerStore(Request $request, string $company_slug)
    {

        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'return_to_customer_date' => 'required|date',
            'assignment_ids' => 'required|array|min:1',
            'assignment_ids.*' => 'integer',
        ]);

        $returnToCustomerDate = Carbon::parse($validated['return_to_customer_date'])->format('Y-m-d');

        $assignments = RiderInventoryAssignment::query()
            ->where('customer_id', $validated['customer_id'])
            ->where('status', RiderInventoryAssignment::STATUS_RETURNED)
            ->whereIn('id', $validated['assignment_ids'])
            ->get();

        if ($assignments->isEmpty()) {
            $message = 'No eligible inventory items were found for return to customer.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->route('RiderInventory.returnToCustomerForm')->withInput();
        }

        if ($assignments->count() !== count($validated['assignment_ids'])) {
            $message = 'One or more selected items are not eligible for return to customer.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->route('RiderInventory.returnToCustomerForm')->withInput();
        }

        RiderInventoryAssignment::query()
            ->whereIn('id', $assignments->pluck('id'))
            ->update([
                'status' => RiderInventoryAssignment::STATUS_RETURNED_TO_CUSTOMER,
                'returned_to_customer' => $returnToCustomerDate,
                'updated_by' => auth()->id(),
            ]);

        $message = $assignments->count() . ' item(s) marked as returned to customer.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        Flash::success($message);

        return redirect()->route('RiderInventory.returnToCustomerForm');
    }

    public function destroyAssignment(Request $request, string $company_slug, int $assignmentId)
    {

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
            RiderInventoryAssignment::STATUS_RETURNED_TO_CUSTOMER,
        ], true)) {
            abort(422, 'Only returned, lost, or returned-to-customer inventory items can have their status changed.');
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
            RiderInventoryAssignment::STATUS_RETURNED_TO_CUSTOMER => [
                RiderInventoryAssignment::STATUS_ASSIGNED => 'Assigned',
                RiderInventoryAssignment::STATUS_RETURNED => 'Returned (from rider)',
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

        if ($assignment->status === RiderInventoryAssignment::STATUS_RETURNED_TO_CUSTOMER) {
            $assignment->returned_to_customer = null;
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
        if ($assignment->status === RiderInventoryAssignment::STATUS_RETURNED_TO_CUSTOMER) {
            $assignment->status = RiderInventoryAssignment::STATUS_RETURNED;
            $assignment->returned_to_customer = null;
            $assignment->remarks = $remarks;
            $assignment->updated_by = auth()->id();
            $assignment->save();

            return 'Inventory item reverted to returned from rider (not yet returned to customer).';
        }

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

    private function customersForInventory(bool $activeOnly = true)
    {
        $query = Customers::query()->orderBy('name');

        if ($activeOnly) {
            $query->active();
        }

        return $query->get(['id', 'name', 'company_name', 'status']);
    }

    private function contractBranding(): array
    {
        return app(AgreementPdfBranding::class)->forCompany(CompanyContext::id());
    }
}
