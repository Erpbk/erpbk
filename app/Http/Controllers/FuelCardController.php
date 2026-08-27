<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FuelCards;
use App\Models\FuelData;
use App\Imports\FuelCardImport;
use App\Exports\FuelCardExport;
use App\Services\FuelCardLossService;
use App\Support\RoleFieldAccess;
use Flash;
use App\Traits\GlobalPagination;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class FuelCardController extends Controller
{
    use GlobalPagination;
    
     public function index(Request $request)
    {

        if (!user_can('fuel_view')) {
            abort(403, 'Unauthorized action.');
        }
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = FuelCards::query()
            ->orderBy('id', 'asc')
            ->with(['rider.bikes', 'fuelCompany']);
        if ($request->has('card_number') && !empty($request->card_number)) {
            $query->where('card_number', 'like', '%' . $request->card_number . '%');
        }
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status',  $request->status );
        }
        if ($request->has('assigned_to') && !empty($request->assigned_to)) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->filled('quick_search')) {
            $search = trim((string) $request->input('quick_search'));
            $query->where(function ($q) use ($search) {
                $q->where('card_number', 'like', '%' . $search . '%')
                    ->orWhere('bike_no', 'like', '%' . $search . '%')
                    ->orWhereHas('rider', function ($rq) use ($search) {
                        $rq->where('rider_id', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('rider.bikes', function ($bq) use ($search) {
                        $bq->where('plate', 'like', '%' . $search . '%');
                    });
            });
        }

        $stats['total'] = $query->count();
        $stats['active'] = (clone $query)->where('status', FuelCards::STATUS_ACTIVE)->count();
        $stats['deactivated'] = (clone $query)->where('status', FuelCards::STATUS_DEACTIVATED)->count();
        $stats['lost'] = (clone $query)->where('status', FuelCards::STATUS_LOST)->count();
        // Anything not assigned, deactivated or lost is sitting in the office,
        // including legacy "Inactive"/null rows.
        $stats['in_office'] = $stats['total'] - $stats['active'] - $stats['deactivated'] - $stats['lost'];
        $stats['absconded'] = (clone $query)->whereAssigneeAbsconded()->count();
        $stats['no_vehicle'] = (clone $query)->whereNoVehicleAssigned()->count();
        $stats['vehicle_changed'] = (clone $query)->whereVehicleChanged()->count();

        $otherFilter = strtolower(trim((string) $request->input('other', '')));
        if ($otherFilter === 'absconded') {
            $query->whereAssigneeAbsconded();
        } elseif ($otherFilter === 'no_vehicle') {
            $query->whereNoVehicleAssigned();
        } elseif (in_array($otherFilter, ['vehicle_changed', 'vehicle-changed'], true)) {
            $query->whereVehicleChanged();
        }

        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        if ($request->ajax()) {
            $tableData = view('fuel_cards.table', [
                'data' => $data,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'stats' => $stats,
            ]);
        }

        return view('fuel_cards.index', [
            'data' => $data,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('fuel_cards.create');
    }

    /**
     * Card detail rules shared by store/update. Rider assignment is not part of
     * this form; it is handled by FuelCardHistoryController.
     *
     * Rules follow the role's field permissions: a field the role cannot edit is
     * never rendered or submitted, so validating it (above all as required) would
     * reject an otherwise valid form.
     */
    private function cardDetailRules(?string $ignoreId = null): array
    {
        $definitions = [
            'card_number' => ['required', 'string', 'min:16', 'unique:fuel_cards,card_number' . ($ignoreId ? ',' . $ignoreId : '')],
            'fuel_company_id' => ['required', 'exists:fuel_companies,id'],
            'service_charges' => ['nullable', 'numeric', 'min:0'],
            'card_issue_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];

        $rules = [];
        foreach ($definitions as $field => $rule) {
            if (!field_editable('fuel', $field)) {
                continue;
            }
            if (field_required('fuel', $field) && !in_array('required', $rule, true)) {
                $rule = array_merge(['required'], array_values(array_diff($rule, ['nullable'])));
            }
            $rules[$field] = $rule;
        }

        return $rules;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validate($request, $this->cardDetailRules());
        $data = RoleFieldAccess::stripNonEditableInput($data, 'fuel');

        $data['created_by'] = auth()->id();
        $data['status'] = FuelCards::STATUS_IN_OFFICE;

        try{
            FuelCards::create($data);
        }catch(\Exception $e){
            if($request->ajax()){
                return response()->json(['message' => 'An Error Occurred: '. $e->getMessage()],500);
            }
            Flash::error('Error Occurred: '.$e->getMessage());
            return redirect()->back();
        }

        if($request->ajax()) {
            return response()->json(['message' => 'Fuel Card Added Successfully', 'reload' => true]);
        }
        Flash::success('Fuel Card Added Successfully');
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show($company_slug, string $id)
    {
        $card = FuelCards::find($id);
        if (!$card) {
            Flash::error('Fuel Card Not Found');
            return redirect()->route('fuelCards.index');
        }

        $card->load(['branch', 'fuelCompany', 'rider', 'lostRider', 'lostBy']);

        $chargeableRider = $card->chargeableRider();

        $histories = $card->histories()
            ->with(['rider', 'assignedBy.roles', 'returnedBy.roles'])
            ->orderByDesc('assign_date')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString()
            ->appends(['tab' => 'assignments']);

        $transactions = FuelData::query()
            ->with('rider')
            ->where('card_no', $card->card_number)
            ->orderByDesc('trans_date')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'txn_page')
            ->withQueryString()
            ->appends(['tab' => 'transactions']);

        $transactionTotals = FuelData::query()
            ->where('card_no', $card->card_number)
            ->selectRaw('COUNT(*) as trips, COALESCE(SUM(qty), 0) as qty, COALESCE(SUM(total), 0) as total')
            ->first();

        return view('fuel_cards.show', compact(
            'card',
            'chargeableRider',
            'histories',
            'transactions',
            'transactionTotals'
        ));
    }

    /**
     * Charge the holding rider for a card that was lost or never returned. The
     * charge is posted as an Inventory Loss (IL) voucher, not a penalty (PN),
     * because the card itself is company property that is gone.
     */
    public function chargeLost(Request $request, $company_slug, string $id)
    {
        if (!user_can('fuel_cards_card_edit')) {
            if ($request->isMethod('get')) {
                abort(403, 'Unauthorized action.');
            }

            return response()->json(['message' => 'You do not have permission to charge for a lost card.'], 403);
        }

        $card = FuelCards::find($id);
        if (!$card) {
            if ($request->isMethod('get')) {
                abort(404, 'Fuel Card Not Found');
            }

            return response()->json(['message' => 'Fuel Card Not Found'], 404);
        }

        if ($card->isLost()) {
            $message = 'This fuel card is already marked as lost.';
            if ($request->isMethod('get') || $request->ajax()) {
                return response()->json(['message' => $message], 422);
            }
            Flash::error($message);
            return redirect()->back();
        }

        if ($request->isMethod('get')) {
            $card->load('fuelCompany');

            return view('fuel_cards.charge_lost', [
                'card' => $card,
                'rider' => $card->chargeableRider(),
            ]);
        }

        $data = $this->validate($request, [
            'amount' => 'required|numeric|min:0.01',
            'lost_date' => 'required|date',
            'billing_month' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
        ], [
            'amount.required' => 'Please enter the amount to charge the rider.',
            'amount.min' => 'Charge amount must be greater than zero.',
            'lost_date.required' => 'Please provide the date the card was lost.',
            'billing_month.required' => 'Please select the billing month for the voucher.',
        ]);

        $billingMonth = \Carbon\Carbon::parse($data['billing_month'])->startOfMonth()->format('Y-m-d');

        DB::beginTransaction();

        try {
            $result = app(FuelCardLossService::class)->chargeRiderForLostCard(
                $card,
                (float) $data['amount'],
                $data['lost_date'],
                $billingMonth,
                $data['remarks'] ?? null,
                auth()->id()
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            Flash::error($e->getMessage());
            return redirect()->back();
        }

        $message = 'Card marked as lost. ' . number_format($result['amount'], 2)
            . ' charged to ' . ($result['rider']->name ?? 'the rider')
            . ' on Inventory Loss voucher ' . ($result['voucher']->formatted_id ?? 'IL-' . str_pad((string) $result['voucher']->id, 4, '0', STR_PAD_LEFT)) . '.';

        if ($request->ajax()) {
            return response()->json(['message' => $message, 'reload' => true]);
        }

        Flash::success($message);
        return redirect()->back();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($company_slug, string $id)
    {
        $fuelCard = FuelCards::find($id);
        return view('fuel_cards.edit')->with('fuelCard', $fuelCard);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $company_slug, string $id)
    {
        $card = FuelCards::find($id);
        if(!$card){
            return response()->json(['message' => 'Card Not Found']);
        }

        $data = $this->validate($request, $this->cardDetailRules($id));
        $data = RoleFieldAccess::stripNonEditableInput($data, 'fuel');

        $card->fill($data);
        if($card->isClean()){
            if($request->ajax()){
                return response()->json(['message' => 'No Changes Detected To update'],200);
            }
            Flash::info('No Changes Detected');
            return redirect()->back();
        }
        $data['updated_by'] = auth()->id();
        try{
            $card->update($data);
        }catch(\Exception $e){
            if($request->ajax()){
                return response()->json(['message' => 'An Error Occurred: '. $e->getMessage()],500);
            }
            Flash::error('Error Occurred: '.$e->getMessage());
            return redirect()->back();
        }
        
        if($request->ajax()) {
            return response()->json(['message' => 'Fuel Card Updated Succesfully', 'reload' => true]);
        }
        Flash::success('Fuel Card Updated Successfully');
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($company_slug, string $id)
    {
        $fuelCard = FuelCards::find($id);
        if(!$fuelCard){
            return response()->json(['message'=> 'Fuel Card Not Found'],404);
        }
        if($fuelCard->histories()->count() > 0){
            return response()->json(['message'=> 'Cannot delete Fuel Card with assignment history.'],400);
        }
        $fuelCard->delete();
        return response()->json(['message'=> 'Fuel Card Deleted successfully'],200);
    }

    /**
     * Bulk activate / deactivate cards. Deactivating takes an in-office card out
     * of service so it cannot be assigned; activating returns it to the office.
     */
    public function activateDeactivate(Request $request)
    {
        if (!user_can('fuel_cards_card_edit')) {
            if ($request->isMethod('get') || !$request->ajax()) {
                abort(403, 'Unauthorized action.');
            }

            return response()->json([
                'message' => 'You do not have permission to change fuel card status.',
            ], 403);
        }

        if ($request->isMethod('get')) {
            return view('fuel_cards.activate_deactivate', [
                'inOfficeCards' => $this->statusPickList(FuelCards::STATUS_IN_OFFICE),
                'deactivatedCards' => $this->statusPickList(FuelCards::STATUS_DEACTIVATED),
            ]);
        }

        $data = $this->validate($request, [
            'mode' => 'required|in:activate,deactivate',
            'card_ids' => 'required|array|min:1',
            'card_ids.*' => 'integer|exists:fuel_cards,id',
        ], [
            'mode.required' => 'Please choose whether to activate or deactivate.',
            'card_ids.required' => 'Please select at least one card.',
            'card_ids.min' => 'Please select at least one card.',
        ]);

        $deactivating = $data['mode'] === 'deactivate';
        $from = $deactivating ? FuelCards::STATUS_IN_OFFICE : FuelCards::STATUS_DEACTIVATED;
        $to = $deactivating ? FuelCards::STATUS_DEACTIVATED : FuelCards::STATUS_IN_OFFICE;

        // whereNull(assigned_to) keeps an assigned card from being swept up by a
        // stale form; assignment status is owned by FuelCardHistoryController.
        $updated = FuelCards::query()
            ->whereIn('id', array_map('intval', $data['card_ids']))
            ->where('status', $from)
            ->whereNull('assigned_to')
            ->update([
                'status' => $to,
                'updated_by' => auth()->id(),
            ]);

        if ($updated === 0) {
            $error = $deactivating
                ? 'No in-office cards were updated. Select cards that are currently in office.'
                : 'No deactivated cards were updated. Select cards that are currently deactivated.';

            if ($request->ajax()) {
                return response()->json(['message' => $error], 422);
            }

            Flash::error($error);
            return redirect()->back();
        }

        $noun = $updated === 1 ? 'card' : 'cards';
        $message = $deactivating
            ? "{$updated} {$noun} deactivated."
            : "{$updated} {$noun} activated and returned to office.";

        // The card page posts this form directly, so support non-AJAX submits too.
        if ($request->ajax()) {
            return response()->json(['message' => $message, 'reload' => true]);
        }

        Flash::success($message);
        return redirect()->back();
    }

    /**
     * Unassigned cards in a given status, shaped for the shared picker partial.
     *
     * @return list<array{id: int, primary: string, secondary: string}>
     */
    private function statusPickList(string $status): array
    {
        return FuelCards::query()
            ->with('fuelCompany')
            ->where('status', $status)
            ->whereNull('assigned_to')
            ->orderBy('card_number')
            ->get()
            ->map(fn (FuelCards $card) => [
                'id' => (int) $card->id,
                'primary' => (string) $card->card_number,
                'secondary' => (string) ($card->fuelCompany?->name ?? ''),
            ])
            ->all();
    }

    public function import(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('fuel_cards.import');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'Excel file is required',
        ]);

        try {
            $import = new FuelCardImport();
            Excel::import($import, $request->file('file'));
            $results = $import->getResults();
            $stats = $results['stats'] ?? [];
            $failed = $results['failed'] ?? [];

            $message = 'Imported ' . ($stats['imported'] ?? 0) . ' of ' . ($stats['total'] ?? 0) . ' fuel cards.';
            if (($stats['failed'] ?? 0) > 0) {
                $message .= ' ' . $stats['failed'] . ' row(s) failed.';
                $reasons = collect($failed)->take(5)->map(function ($row) {
                    return 'Row ' . ($row['row_number'] ?? '?') . ': ' . ($row['reason'] ?? 'Unknown');
                })->implode(' | ');
                if ($reasons !== '') {
                    $message .= ' ' . $reasons;
                }
            }

            if ($request->ajax()) {
                return response()->json([
                    'message' => $message,
                    'reload' => ($stats['imported'] ?? 0) > 0,
                ]);
            }

            Flash::success($message);
            return redirect()->route('fuelCards.index');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
            }
            Flash::error('Import failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function downloadTemplate()
    {
        $headers = ['Card Number', 'Fuel Company', 'Service Charges', 'Card Issue Date', 'Remarks'];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['1234567890123456', 'Company Name', '25.00', now()->format('Y-m-d'), 'Optional note']);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="fuel_card_import_template.csv"',
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'fuel_cards_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new FuelCardExport, $filename);
    }
}
