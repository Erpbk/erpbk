<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\FuelCards;
use App\Models\FuelData;
use App\Models\Bikes;
use Flash;
use Illuminate\Support\Facades\DB;
use App\Traits\GlobalPagination;
use App\Imports\FuelDataImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\FuelMonthlyLedgerService;


class FuelDataController extends Controller
{
    use GlobalPagination;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!user_can('fuel_view')) {
            abort(403, 'Unauthorized action.');
        }
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = FuelData::query()
            ->orderBy('billing_month', 'desc')
            ->with(['card', 'rider', 'bike']);
        $query->whereHas('card');
        if ($request->has('rider_id') && !empty($request->rider_id)) {
            $query->where('rider_id', $request->rider_id);
        }

        if ($request->has('billing_month') && !empty($request->billing_month)) {
            $query->where('billing_month', $request->billing_month);
        }
        if ($request->has('date') && !empty($request->date)) {
            $query->whereDate('trans_date', '=', $request->date);
        }

        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        if ($request->ajax()) {
            $tableData = view('fuel_data.table', [
                'data' => $data,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        return view('fuel_data.index', [
            'data' => $data,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!user_can('fuel_create')) {
            abort(403, 'Unauthorized action.');
        }
        $data = null;
        return view('fuel_data.create', compact('data'));
    }

    /**
     * Store a newly created fuel invoice item.
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'trans_no' => 'required|string|max:255|unique:fuel_data,trans_no',
            'trans_date' => 'required|date',
            'auth_code' => 'required|string|max:255',
            'site' => 'required|string|max:255',
            'billing_month' => 'required|date_format:Y-m',
            'bike_no' => 'nullable|string|exists:bikes,plate',
            'card_no' => 'required|string|exists:fuel_cards,card_number',
            'product' => 'required|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0.01',
            'vat_amount' => 'required|numeric|min:0',
            'subtotal' => 'nullable|numeric',
            'total' => 'nullable|numeric',
            'service_charges' => 'nullable|numeric|min:0.01'
        ], [
            'trans_no.unique' => 'This transaction number already exists.',
            'bike_no.exists' => 'Selected bike number does not exist.',
            'card_no.exists' => 'Selected card number does not exist.',
        ]);

        $card = FuelCards::where('card_number', $request->card_no)->first();
        $bike = Bikes::where('plate', $request->bike_no)->first();
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid card number.'
            ], 400);
        }
        $rider = $card->findRiderForDate(Carbon::parse($request->trans_date)->format('Y-m-d'));
        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'No rider found for the selected card on the transaction date.'
            ], 400);
        }
        try {
            DB::beginTransaction();

            // Calculate values if not provided
            $subtotal = $request->subtotal ?? ($request->qty * $request->price);
            $total = $request->total ?? ($subtotal + $request->vat_amount);
            $serviceCharges = (float) ($request->service_charges ?? FuelMonthlyLedgerService::DEFAULT_SERVICE_CHARGE);
            $billingMonth = $request->billing_month . '-01';

            // Store individual fuel line (shown on monthly invoice)
            $fuelData = FuelData::create([
                'trans_no' => $request->trans_no,
                'trans_date' => $request->trans_date,
                'billing_month' => $billingMonth,
                'rider_id' => $rider->id,
                'bike_no' => $bike->plate ?? null,
                'card_no' => $card->card_number,
                'auth_code' => $request->auth_code,
                'site' => $request->site,
                'product' => $request->product,
                'qty' => $request->qty,
                'price' => $request->price,
                'subtotal' => $subtotal,
                'vat_amount' => $request->vat_amount,
                'total' => $total,
            ]);

            // Ledger: one monthly set of totals for this rider+invoice month
            app(FuelMonthlyLedgerService::class)->sync(
                (int) $rider->id,
                $billingMonth,
                $serviceCharges
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fuel Transaction added successfully.',
                'reload' => true
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add fuel transaction.' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($company_slug, string $id)
    {
        $data = FuelData::find($id);
        $summary = $data->getMonthlySummary();
        return view('fuel_data.show', compact('summary'));
    }

    /**
     * Display the specified resource.
     */
    public function show2($company_slug, $rider_id, $billing_month)
    {
        $data = FuelData::where('rider_id', $rider_id)->where('billing_month', $billing_month)->first();
        $summary = $data->getMonthlySummary();
        return view('fuel_data.show', compact('summary'));
    }

    /**
     * Show the form for editing the specified resource.
     */

    public function monthlySummary(Request $request)
    {
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = FuelData::query()->select(
            'inv_id',
            'rider_id',
            DB::raw('DATE_FORMAT(billing_month, "%Y-%m") as billing_month'),
            DB::raw('COUNT(*) as transaction_count'),
            DB::raw('SUM(qty) as total_qty'),
            DB::raw('SUM(subtotal) as total_subtotal'),
            DB::raw('SUM(vat_amount) as total_vat'),
            DB::raw('SUM(total) as total_amount'),
            DB::raw('MIN(trans_date) as first_transaction'),
            DB::raw('MAX(trans_date) as last_transaction')
        )
            ->with('rider')
            ->groupBy('inv_id', 'rider_id', 'billing_month')
            ->orderBy('billing_month', 'desc');
        if ($request->has('billing_month') && !empty($request->billing_month)) {
            $query->whereDate('billing_month', $request->billing_month . '-01');
        }
        if ($request->filled('rider_id')) {
            $query->where('rider_id', $request->rider_id);
        }
        $summaries = $query->get();

        return view('fuel_data.monthly_summary', compact('summaries'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($company_slug, string $id)
    {
        if (!user_can('fuel_create')) {
            abort(403, 'Unauthorized action.');
        }
        $data = FuelData::find($id);
        return view('fuel_data.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $company_slug, string $id)
    {
        // Validate the request
        $request->validate([
            'trans_no' => 'required|string|max:255|unique:fuel_data,trans_no,' . $id,
            'trans_date' => 'required|date',
            'auth_code' => 'required|string|max:255',
            'site' => 'required|string|max:255',
            'billing_month' => 'required|date_format:Y-m',
            'bike_no' => 'required|string|exists:bikes,plate',
            'card_no' => 'required|string|exists:fuel_cards,card_number',
            'product' => 'required|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0.01',
            'vat_amount' => 'required|numeric|min:0',
            'subtotal' => 'nullable|numeric',
            'total' => 'nullable|numeric',
            'service_charges' => 'nullable|numeric|min:0.01'
        ], [
            'trans_no.unique' => 'This transaction number already exists.',
            'bike_no.exists' => 'Selected bike number does not exist.',
            'card_no.exists' => 'Selected card number does not exist.',
        ]);

        $card = FuelCards::where('card_number', $request->card_no)->first();
        $bike = Bikes::where('plate', $request->bike_no)->first();
        $fuelData = FuelData::find($id);
        if (!$fuelData) {
            return response()->json([
                'success' => false,
                'message' => 'Fuel transaction not found.'
            ], 404);
        }
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid card number.'
            ], 400);
        }
        $rider = $card->findRiderForDate(Carbon::parse($request->trans_date)->format('Y-m-d'));
        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'No rider found for the selected card on the transaction date.'
            ], 400);
        }
        try {
            DB::beginTransaction();

            // Calculate values if not provided
            $subtotal = $request->subtotal ?? ($request->qty * $request->price);
            $total = $request->total ?? ($subtotal + $request->vat_amount);
            $request['billing_month'] = $request->billing_month . '-01';
            $request['rider_id'] = $rider->id;
            $request['subtotal'] = $subtotal;
            $request['total'] = $total;
            $serviceCharges = (float) ($request->service_charges ?? FuelMonthlyLedgerService::DEFAULT_SERVICE_CHARGE);
            $previousRiderId = (int) $fuelData->rider_id;
            $previousBillingMonth = Carbon::parse($fuelData->billing_month)->startOfMonth()->toDateString();

            $fuelData->fill($request->all());
            if ($fuelData->isDirty()) {
                $fuelData->save();
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'No changes detected to update.',
                    'reload' => false
                ], 200);
            }

            $newBillingMonth = Carbon::parse($fuelData->billing_month)->startOfMonth()->toDateString();
            $newRiderId = (int) $fuelData->rider_id;

            // Re-sync monthly ledger totals (old month if rider/month changed, then current)
            $ledger = app(FuelMonthlyLedgerService::class);
            if ($previousRiderId !== $newRiderId || $previousBillingMonth !== $newBillingMonth) {
                $ledger->sync($previousRiderId, $previousBillingMonth, $serviceCharges);
            }
            $ledger->sync($newRiderId, $newBillingMonth, $serviceCharges);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fuel Transaction added successfully.',
                'reload' => true
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add fuel transaction.' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($company_slug, string $id)
    {
        // Check permission
        if (!user_can('fuel_delete')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete fuel transactions.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $fuelData = FuelData::find($id);

            if (!$fuelData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fuel transaction not found.'
                ], 404);
            }
            $riderId = (int) $fuelData->rider_id;
            $billingMonth = Carbon::parse($fuelData->billing_month)->startOfMonth()->toDateString();

            // Delete the fuel line item, then rebuild monthly ledger totals
            $fuelData->delete();
            app(FuelMonthlyLedgerService::class)->sync($riderId, $billingMonth);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fuel transaction deleted successfully.',
                'reload' => true
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete fuel transaction: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show form to delete fuel data for a billing month (optional rider filter).
     */
    public function deleteMonthlyForm()
    {
        if (! user_can('fuel_cards_transactions_delete') && ! user_can('fuel_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $riders = \App\Models\Riders::query()
            ->orderBy('rider_id')
            ->get(['id', 'rider_id', 'name']);

        return view('fuel_data.delete_monthly', compact('riders'));
    }

    /**
     * Delete fuel data for a billing month, optionally limited to one rider.
     * Rebuilds monthly ledger totals for each affected rider.
     */
    public function deleteMonthly(Request $request)
    {
        if (! user_can('fuel_cards_transactions_delete') && ! user_can('fuel_delete')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete fuel transactions.',
            ], 403);
        }

        $request->validate([
            'billing_month' => 'required|date_format:Y-m',
            'rider_id' => 'nullable|exists:riders,id',
        ], [
            'billing_month.required' => 'Billing month is required.',
            'billing_month.date_format' => 'Billing month must be a valid month.',
        ]);

        $billingMonth = Carbon::createFromFormat('Y-m', $request->billing_month)->startOfMonth()->toDateString();
        $riderId = $request->filled('rider_id') ? (int) $request->rider_id : null;

        try {
            DB::beginTransaction();

            $query = FuelData::query()->whereDate('billing_month', $billingMonth);
            if ($riderId) {
                $query->where('rider_id', $riderId);
            }

            $affectedRiderIds = (clone $query)->distinct()->pluck('rider_id')->map(fn ($id) => (int) $id)->all();
            $deletedCount = (clone $query)->count();

            if ($deletedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fuel data found for the selected month'
                        . ($riderId ? ' and rider.' : '.'),
                ], 404);
            }

            $query->delete();

            $ledger = app(FuelMonthlyLedgerService::class);
            foreach ($affectedRiderIds as $affectedRiderId) {
                $ledger->sync($affectedRiderId, $billingMonth);
            }

            DB::commit();

            $monthLabel = Carbon::parse($billingMonth)->format('F Y');
            $scope = $riderId
                ? 'for the selected rider in ' . $monthLabel
                : 'for all riders in ' . $monthLabel;

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deletedCount} fuel transaction(s) {$scope}.",
                'reload' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete monthly fuel data: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('fuel_data.import');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new FuelDataImport();

            Excel::import($import, $request->file('file'));

            $result = [
                'success_count' => $import->getSuccessCount(),
                'failed_count' => count($import->getFailedRows()),
                'total_rows' => $import->getTotalRows(),
                'failed_rows' => $import->getFailedRows()
            ];

            // Return JSON response for AJAX
            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            \Log::error('Import error: ' . $e->getMessage());

            // Return JSON error for AJAX
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $headers = [
            'Transaction No',
            'Transaction Date',
            'Customer Code',
            'customer Name',
            'Group Code',
            'Group Name',
            'Plate No',
            'Chassis',
            'Odometer',
            'Transaction Type',
            'Service Charge Description',
            'Origin',
            'VIP/Card Number',
            'Auth Code',
            'Status',
            'Site',
            'LOB',
            'Product Name',
            'Quantity',
            'Unit Price',
            'Amount Without VAT',
            'VAT Amount',
            'Total Amount',
            'Card Name',
            'Employee',
            'Remarks'
        ];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            // Sample data
            fputcsv($file, [
                '321433001',
                '2024-04-15 14:30:00',
                '3000048785',
                'DELIVERY SERVICE L L C-BRANCH OF ABU DHABI',
                'EX-G',
                'DELIVERY SERVICE L L C-BRANCH OF ABU DHABI Group',
                '1-DXB-13310',
                'XMBJHBBY5JH',
                '15000',
                'Purchase',
                '',
                'Select Prestige Dr',
                '7001048785267928',
                'AUTH123',
                'Completed',
                '1021',
                'FUEL',
                'SPECIAL',
                '10',
                '28.50',
                '285',
                '14.25',
                '299.25',
                'DELIVERY SERVICE L L C-BRANCH OF ABU DHABI',
                'John Doe',
                'Sample remark'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="fuel_data_import_template.csv"'
        ]);
    }
}
