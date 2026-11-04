<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Support\GlobalAccounts;
use App\Models\Accounts;
use App\Models\BikeHistory;
use App\Models\BikeMaintenance;
use App\Models\BikeMaintenanceItem;
use App\Models\BikeRentCompany;
use App\Models\Bikes;
use App\Models\Garages;
use App\Models\InventoryPurchase;
use App\Models\Items;
use App\Models\LeasingCompanies;
use App\Models\Riders;
use App\Models\Transactions;
use App\Traits\GlobalPagination;
use App\Support\PublicStorageDisk;
use App\Support\InvoicePdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BikeMaintenanceController extends Controller
{
    use GlobalPagination;

    /** @var array<string, mixed>|null */
    private ?array $maintenanceGlobalAccounts = null;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $maintenances = BikeMaintenance::with(['bike.rider'])
            ->orderBy('maintenance_date', 'desc')
            ->whereHas('bike')
            ->get();
        $stats = $this->stats($maintenances);

        return view('bike-maintenance.index', compact('maintenances', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $items = Items::dropdown('garage');
        $bikee = null;
        if ($request->filled('id')) {
            $bikee = Bikes::find($request->input('id'));
        }
        $bikes = Bikes::where('status', 1)->get();
        [$garages, $leasingCompanies] = $this->performerDropdownCollections();

        return view('bike-maintenance.create_general', compact('items', 'bikee', 'bikes', 'garages', 'leasingCompanies'));
    }

    /**
     * Assignees (riders + rental companies) for a bike from history and current assignment.
     */
    public function getAssignees(Request $request)
    {
        $request->validate([
            'bike_id' => 'required|exists:bikes,id',
            'maintenance_date' => 'nullable|date',
            'selected_rider_id' => 'nullable|integer',
            'selected_rental_company_id' => 'nullable|integer',
        ]);

        $bike = Bikes::findOrFail($request->input('bike_id'));
        $payload = $this->buildBikeAssigneesPayload(
            $bike,
            $request->input('maintenance_date'),
            $request->filled('selected_rider_id') ? (int) $request->input('selected_rider_id') : null,
            $request->filled('selected_rental_company_id') ? (int) $request->input('selected_rental_company_id') : null
        );

        return response()->json($payload);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $bike = Bikes::find($validated['bike_id']);
        $validated['created_by'] = auth()->id();
        $validated['billing_month'] = $validated['billing_month'].'-01';
        $validated['status'] = 1;
        $path = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = 'maintenance_'.time().'.'.$file->extension();
            $path = PublicStorageDisk::storeUploadedFile($file, 'bike/'.$bike->id, $filename);
            $validated['attachment'] = $path;
        }
        DB::beginTransaction();
        try {
            [$garage, , $validated] = $this->resolvePerformer($validated);
            $isInternal = $garage && $garage->garage_type === 'internal';
            if ($bike->rentalCompany?->customer_type == 'garage' && ! $isInternal) {
                throw new \Exception('Cannot perform maintenance for a garage customer in an external garage or leasing company. Please select an internal garage.');
            }
            $this->validateFinancials($request, $isInternal);
            [$validated, $maintenanceKm] = $this->applyMaintenanceTypeRules($validated, $bike);
            $maintenance = BikeMaintenance::create($validated);

            $rows = $this->buildMaintenanceItemRows($request, $maintenance, $bike, $isInternal);
            if (! empty($rows)) {
                BikeMaintenanceItem::insert($rows);
            }

            $maintenance->update(['total_cost' => $maintenance->cost]);
            BikeMaintenance::syncBikeMeterFieldsForBike($bike, $maintenanceKm);
            $data = $this->billData($maintenance);
            if (! empty($data['missing'])) {
                throw new \Exception(implode('. ', $data['missing']));
            }
            $this->chargeInvoice($maintenance, $data);
            DB::commit();

            return response()->json(['message' => 'Maintenance Record Created Successfully.', 'reload' => true], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($path) {
                Storage::delete($path);
            }
            \Log::error(
                'An error occured while creating bike maintenance record',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json(['message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    public function invoice($company_slug, BikeMaintenance $maintenance)
    {
        $maintenance->load([
            'bike.rider',
            'garage',
            'leasingCompany',
            'maintenanceItems',
            'createdBy',
            'UpdatedBy',
            'rentalCompany',
        ]);

        return view('bike-maintenance.invoice', compact('maintenance'));
    }

    public function downloadInvoice($company_slug, BikeMaintenance $maintenance)
    {
        $maintenance->load([
            'bike.rider',
            'garage',
            'leasingCompany',
            'maintenanceItems',
            'createdBy',
            'UpdatedBy',
            'rentalCompany',
        ]);

        return InvoicePdf::download(
            'bike-maintenance.invoice',
            ['maintenance' => $maintenance],
            'Maintenance-Invoice-MA-' . $maintenance->id . '.pdf'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show($company_slug, BikeMaintenance $bikeMaintenance)
    {
        //
    }

    /**
     * Show the form for editing the specified Bike's Maintenance Details.
     */
    public function edit($company_slug, BikeMaintenance $bikeMaintenance)
    {

        $maintenance = $bikeMaintenance->load(['garage', 'leasingCompany']);
        $bike = $bikeMaintenance->bike;
        $items = $bikeMaintenance->maintenanceItems;
        $catalogItems = Items::dropdown('garage');
        [$garages, $leasingCompanies] = $this->performerDropdownCollections();

        return view('bike-maintenance.edit', compact(
            'bike',
            'items',
            'maintenance',
            'garages',
            'leasingCompanies',
            'catalogItems'
        ));
    }

    /**
     * Update the MAintenance Fields in Bikes Table.
     */
    public function update(Request $request, $company_slug, BikeMaintenance $bikeMaintenance)
    {
        $validated = $this->validateRequest($request);
        $maintenance = $bikeMaintenance->load('bike');
        $bike = $maintenance->bike;
        $validated['updated_by'] = auth()->id();
        $oldpath = $maintenance->attachment;
        $path = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = 'maintenance_'.time().'.'.$file->extension();
            $path = PublicStorageDisk::storeUploadedFile($file, 'bike/'.$bike->id, $filename);
            $validated['attachment'] = $path;
        }
        DB::beginTransaction();
        try {
            [$garage, , $validated] = $this->resolvePerformer($validated);
            $isInternal = $garage && $garage->garage_type === 'internal';
            if ($bike->rentalCompany?->customer_type == 'garage' && ! $isInternal) {
                throw new \Exception('Cannot perform maintenance for a garage customer in an external garage or leasing company. Please select an internal garage.');
            }
            $this->validateFinancials($request, $isInternal);
            [$validated, $maintenanceKm] = $this->applyMaintenanceTypeRules($validated, $bike);
            $maintenance->restoreInventoryQuantities();
            $maintenance->update($validated);
            // Hard-delete: line items are rebuilt from the form and are not meant to be restored.
            BikeMaintenanceItem::withTrashed()
                ->where('bike_maintenance_id', $maintenance->id)
                ->forceDelete();

            $rows = $this->buildMaintenanceItemRows($request, $maintenance, $bike, $isInternal);
            if (! empty($rows)) {
                BikeMaintenanceItem::insert($rows);
            }

            $maintenance->update(['total_cost' => $maintenance->cost]);
            BikeMaintenance::syncBikeMeterFieldsForBike($bike, $maintenanceKm);
            $data = $this->billData($maintenance);
            if (! empty($data['missing'])) {
                throw new \Exception(implode('. ', $data['missing']));
            }
            $transcode = Transactions::withTrashed()
                ->where('reference_type', 'Bike Maintenance')
                ->where('reference_id', $maintenance->id)
                ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                ->value('trans_code');
            // Hard-delete: ledger lines are rebuilt on edit and are not meant to be restored.
            Transactions::withTrashed()
                ->where('reference_type', 'Bike Maintenance')
                ->where('reference_id', $maintenance->id)
                ->forceDelete();
            $this->chargeInvoice($maintenance, $data, $transcode);
            DB::commit();
            if ($oldpath) {
                if ($path) {
                    Storage::delete($oldpath);
                }
            }

            return response()->json(['message' => 'Maintenance Record Updated Successfully.', 'reload' => true], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($path) {
                Storage::delete($path);
            }
            \Log::error(
                'An error occured while update bike maintenance record',
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTrace(),
                ]
            );

            return response()->json(['message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    /**
     * Build bike_maintenance_items rows from the request.
     * Internal + Company: backend forces rate = cost and VAT = 0 (no self-profit).
     * Maintained items use unit_cost from the same FIFO purchase batches that are consumed.
     */
    private function buildMaintenanceItemRows(
        Request $request,
        BikeMaintenance $maintenance,
        Bikes $bike,
        bool $isInternal
    ): array {
        if (! $request->filled('item_id')) {
            return [];
        }

        $rows = [];
        $now = now();
        $itemIds = array_values(array_filter($request->item_id ?? []));
        $itemsById = Items::whereIn('id', $itemIds)->get()->keyBy('id');

        if ($isInternal) {
            foreach ($request->item_id as $index => $itemId) {
                $item = $itemsById->get($itemId);
                if (! $item) {
                    throw new \Exception('Item not found at row '.($index + 1));
                }

                $qty = (float) ($request->quantity[$index] ?? 0);
                $discount = (float) ($request->discount[$index] ?? 0);
                $chargeTo = $request->charge_to[$index];
                $rate = (float) ($request->rate[$index] ?? 0);
                $vat = (float) ($request->vat[$index] ?? 0);
                $vatAmount = (float) ($request->vat_amount[$index] ?? 0);
                $itemTotal = (float) ($request->item_total[$index] ?? 0);

                if ($item->is_maintained) {
                    // Company: rate/VAT/total are derived inside FIFO from purchase.unit_cost.
                    // User: keep submitted selling rate / VAT.
                    if ($chargeTo === 'Company') {
                        $rate = 0.0;
                        $vat = 0.0;
                        $vatAmount = 0.0;
                        $itemTotal = 0.0;
                    }

                    $rows = array_merge(
                        $rows,
                        $this->buildMaintainedItemRows(
                            $maintenance,
                            $bike,
                            $item,
                            $qty,
                            $rate,
                            $discount,
                            $vat,
                            $vatAmount,
                            $itemTotal,
                            $chargeTo,
                            $now
                        )
                    );
                } else {
                    $itemCost = (float) $item->cost;
                    if ($chargeTo === 'Company') {
                        // Non-maintained: catalog cost is the only cost source.
                        $rate = $itemCost;
                        $vat = 0.0;
                        $vatAmount = 0.0;
                        $itemTotal = round(($qty * $rate) - $discount, 2);
                    }

                    $lineCost = $itemCost * $qty;
                    $profit = $chargeTo === 'Company'
                        ? 0.0
                        : (($rate * $qty - $discount) - $lineCost);

                    $rows[] = [
                        'bike_maintenance_id' => $maintenance->id,
                        'item_id' => $item->id,
                        'item_name' => $item->name ?? 'unknown',
                        'quantity' => $qty,
                        'rate' => $rate,
                        'discount' => $discount,
                        'vat' => $vat,
                        'vat_amount' => $vatAmount,
                        'total_amount' => $itemTotal,
                        'cost' => $itemCost,
                        'total_cost' => $lineCost,
                        'profit' => $profit,
                        'charge_to' => $chargeTo,
                        'branch_id' => $bike->branch_id ?? null,
                        'company_id' => $bike->company_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        } else {
            foreach ($request->item_id as $index => $itemId) {
                $item = $itemsById->get($itemId);
                $rows[] = [
                    'bike_maintenance_id' => $maintenance->id,
                    'item_id' => $itemId,
                    'item_name' => $item->name ?? 'unknown',
                    'quantity' => $request->quantity[$index] ?? 1,
                    'rate' => $request->rate[$index] ?? 0,
                    'discount' => $request->discount[$index] ?? 0,
                    'vat' => $request->vat[$index] ?? 0,
                    'vat_amount' => $request->vat_amount[$index] ?? 0,
                    'total_amount' => $request->item_total[$index] ?? 0,
                    'charge_to' => $request->charge_to[$index],
                    'branch_id' => $bike->branch_id ?? null,
                    'company_id' => $bike->company_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $rows;
    }

    /**
     * Consume FIFO inventory for a maintained item and build line rows.
     *
     * Cost, total_cost, and profit always use each consumed InventoryPurchase.unit_cost —
     * never Items.cost. Company charge also locks rate to that batch unit_cost with VAT = 0.
     */
    private function buildMaintainedItemRows(
        BikeMaintenance $maintenance,
        Bikes $bike,
        Items $item,
        float $qty,
        float $rate,
        float $discount,
        float $vat,
        float $vatAmount,
        float $itemTotal,
        string $chargeTo,
        $now
    ): array {
        $availableStock = $item->available;
        if ($availableStock <= 0) {
            throw new \Exception('The item: '.$item->name.' is out of stock.');
        }
        if ($availableStock < $qty) {
            throw new \Exception('Only '.$availableStock.' units of '.$item->name.' are available.');
        }

        $chargeToCompany = $chargeTo === 'Company';
        // Authoritative for company: never trust client rate/VAT.
        if ($chargeToCompany) {
            $vat = 0.0;
            $vatAmount = 0.0;
        }

        $remaining = $qty;
        // Same FIFO batches drive stock consumption, stored cost, and profit.
        $inventory = $item->getAvailableInventory();
        $slices = [];

        foreach ($inventory as $purchase) {
            if ($remaining <= 0) {
                break;
            }

            $qtyFromPurchase = min($remaining, (float) $purchase->remaining_quantity);
            // Batch cost only — do not use $item->cost for maintained inventory.
            $batchUnitCost = (float) $purchase->unit_cost;
            $sliceRate = $chargeToCompany ? $batchUnitCost : $rate;

            if (! $chargeToCompany && $sliceRate < $batchUnitCost) {
                throw new \Exception(
                    'Cannot charge less than item cost. Item: '.$item->name.' Cost: '.$batchUnitCost
                );
            }

            $slices[] = [
                'purchase' => $purchase,
                'qty' => $qtyFromPurchase,
                'batch_unit_cost' => $batchUnitCost,
                'rate' => $sliceRate,
            ];
            $remaining -= $qtyFromPurchase;
        }

        if ($remaining > 0) {
            throw new \Exception('Only '.($qty - $remaining).' units of '.$item->name.' could be allocated from inventory.');
        }

        if ($chargeToCompany) {
            $itemTotal = 0.0;
            foreach ($slices as $slice) {
                $itemTotal += ($slice['batch_unit_cost'] * $slice['qty']);
            }
            $itemTotal = round($itemTotal - $discount, 2);
        }

        $rows = [];
        $sliceCount = count($slices);
        $allocatedDiscount = 0.0;
        $allocatedVatAmount = 0.0;
        $allocatedTotal = 0.0;

        foreach ($slices as $i => $slice) {
            $purchase = $slice['purchase'];
            // Re-read from the purchase row being consumed (same object decremented below).
            $batchUnitCost = (float) $purchase->unit_cost;
            $isLast = ($i === $sliceCount - 1);
            $share = $qty > 0 ? ($slice['qty'] / $qty) : 0;
            $sliceRate = $chargeToCompany ? $batchUnitCost : $slice['rate'];

            if ($isLast) {
                $sliceDiscount = round($discount - $allocatedDiscount, 2);
                $sliceVatAmount = round($vatAmount - $allocatedVatAmount, 2);
                $sliceTotal = round($itemTotal - $allocatedTotal, 2);
            } else {
                $sliceDiscount = round($discount * $share, 2);
                $sliceVatAmount = round($vatAmount * $share, 2);
                $sliceTotal = round($itemTotal * $share, 2);
                $allocatedDiscount += $sliceDiscount;
                $allocatedVatAmount += $sliceVatAmount;
                $allocatedTotal += $sliceTotal;
            }

            if ($chargeToCompany) {
                $sliceVatAmount = 0.0;
                $vat = 0.0;
            }

            $lineCost = $batchUnitCost * $slice['qty'];
            $sliceRevenue = ($sliceRate * $slice['qty']) - $sliceDiscount;
            // Profit = ex-VAT revenue − consumed purchase batch cost (not Items.cost).
            $profit = $chargeToCompany ? 0.0 : ($sliceRevenue - $lineCost);

            $rows[] = [
                'bike_maintenance_id' => $maintenance->id,
                'item_id' => $item->id,
                'inventory_purchase_id' => $purchase->id,
                'item_name' => $item->name ?? 'unknown',
                'quantity' => $slice['qty'],
                'rate' => $sliceRate,
                'discount' => $sliceDiscount,
                'vat' => $vat,
                'vat_amount' => $sliceVatAmount,
                'total_amount' => $sliceTotal,
                'cost' => $batchUnitCost,
                'total_cost' => $lineCost,
                'profit' => $profit,
                'charge_to' => $chargeTo,
                'branch_id' => $bike->branch_id ?? null,
                'company_id' => $bike->company_id,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $purchase->decrement('remaining_quantity', $slice['qty']);
        }

        return $rows;
    }

    private function billData(BikeMaintenance $maintenance)
    {
        $maintenance->load([
            'bike',
            'rider.account',
            'garage.account',
            'leasingCompany.account',
            'maintenanceItems',
            'rentalCompany.account',
        ]);

        $missing = [];
        $userTotal = 0.0;
        $userAccount = null;
        $companyTotal = 0.0;
        $companyAccount = null;
        $payeeAccount = null;
        $vatSalesAccount = null;
        $vatPurchaseAccount = null;
        $incomeAccount = null;
        $vatSalesAmount = 0.0;
        $vatPurchaseAmount = 0.0;

        $items = $maintenance->maintenanceItems;
        $userItems = $items->where('charge_to', 'User');
        $companyItems = $items->where('charge_to', 'Company');
        $isInternal = $maintenance->isInternalGaragePerformer();

        $globals = $this->resolveMaintenanceGlobalAccounts();

        if ($items->isEmpty()) {
            $missing[] = 'No items Added in the Bill';
        }

        $profit = (float) $items->sum('profit');
        $cogs = (float) $items->sum('total_cost');
        $gross = (float) $items->sum('total_amount');

        if ($isInternal) {
            // User: gross debit; company: cost (VAT forced 0). Payee credit = COGS (incl company cost lines).
            $payeeCredit = $cogs;
            $userTotal = (float) $userItems->sum('total_amount');
            $companyTotal = (float) $companyItems->sum('total_amount');
            $vatSalesAmount = (float) $userItems->sum('vat_amount');
            $profit = (float) $userItems->sum('profit');
        } else {
            // User recovers gross incl VAT; company: Dr maint (ex-VAT) + Dr VAT purchase; Cr payee gross.
            $payeeCredit = $gross;
            $profit = 0.0;
            $userTotal = (float) $userItems->sum('total_amount');
            $companyTotal = (float) ($companyItems->sum('total_amount') - $companyItems->sum('vat_amount'));
            $vatPurchaseAmount = (float) $companyItems->sum('vat_amount');
        }

        $overdueAmount = round(
            (float) ($maintenance->overdue_km ?? 0) * (float) ($maintenance->overdue_cost_per_km ?? 0),
            2
        );
        $chargeOverdueToRider = $overdueAmount > 0 && $maintenance->overdue_paidby === 'Rider';
        if (! $chargeOverdueToRider) {
            $overdueAmount = 0;
        }
        $needsUserAccount = $userItems->isNotEmpty() || $overdueAmount > 0;

        if ($needsUserAccount) {
            if (! $maintenance->rider_id && ! $maintenance->rental_company_id) {
                $missing[] = 'No User found for this maintenance but amounts are charged to User/Rider';
            }

            if ($maintenance->rider && $maintenance->rider->account) {
                $userAccount = $maintenance->rider->account;
            } elseif ($maintenance->rentalCompany && $maintenance->rentalCompany->account) {
                $userAccount = $maintenance->rentalCompany->account;
            } else {
                $missing[] = 'No account found for the assigned rider or rental company';
            }
        }

        foreach ($items as $item) {
            // Split inventory rows use proportional totals; skip strict per-slice recalc.
            if ($item->inventory_purchase_id) {
                continue;
            }
            $line = (float) $item->quantity * (float) $item->rate;
            $line -= (float) ($item->discount ?? 0);
            $vatAmount = $line * ((float) $item->vat / 100);
            $line += $vatAmount;
            if (round((float) $item->total_amount, 2) != round($line, 2)) {
                $missing[] = 'Item total amount mismatch for item: '.$item->item_name;
            }
        }

        if ($companyItems->isNotEmpty()) {
            if (! $globals['bike_maintenance_id']) {
                $missing[] = 'Company Bike Maintenance Account not configured (BIKE_MAINTENANCE_ACCOUNT)';
            } else {
                $companyAccount = $globals['bike_maintenance'];
                if (! $companyAccount) {
                    $missing[] = 'Company Bike Maintenance Account not found';
                }
            }
        }

        if ($vatSalesAmount > 0) {
            if (! $globals['vat_on_sales_id']) {
                $missing[] = 'VAT Account not Found (VAT_ON_SALES)';
            } else {
                $vatSalesAccount = $globals['vat_on_sales'];
                if (! $vatSalesAccount) {
                    $missing[] = 'VAT Account not Found (VAT_ON_SALES)';
                }
            }
        }

        if ($vatPurchaseAmount > 0) {
            if (! $globals['vat_purchase_id']) {
                $missing[] = 'VAT Account not Found (VAT_PURCHASE_ACCOUNT)';
            } else {
                $vatPurchaseAccount = $globals['vat_purchase'];
                if (! $vatPurchaseAccount) {
                    $missing[] = 'VAT Account not Found (VAT_PURCHASE_ACCOUNT)';
                }
            }
        }

        if ($maintenance->leasing_company_id) {
            if (! $maintenance->leasingCompany || ! $maintenance->leasingCompany->account) {
                $missing[] = 'No Associated Leasing Company or Leasing Company Account found';
            } else {
                $payeeAccount = $maintenance->leasingCompany->account;
            }
        } elseif ($maintenance->garage_id) {
            if (! $maintenance->garage || ! $maintenance->garage->account) {
                $missing[] = 'No Associated Garage or Garage Account found';
            } else {
                $payeeAccount = $maintenance->garage->account;
            }
        } else {
            $missing[] = 'No performer (garage or leasing company) selected';
        }

        if ($profit > 0 || $overdueAmount > 0) {
            if (! $globals['garage_income_id']) {
                $missing[] = 'Garage Income Account not configured (GARAGE_INCOME_ACCOUNT)';
            } else {
                $incomeAccount = $globals['garage_income'];
            }
        }

        return [
            'total' => $payeeCredit,
            'profit' => $profit,
            'user_amount' => $userTotal,
            'user_account' => $userAccount,
            'company_amount' => $companyTotal,
            'company_account' => $companyAccount,
            'vat_sales_amount' => $vatSalesAmount,
            'vat_purchase_amount' => $vatPurchaseAmount,
            'vat_sales_account' => $vatSalesAccount,
            'vat_purchase_account' => $vatPurchaseAccount,
            'payee_account' => $payeeAccount,
            'garage_account' => $payeeAccount,
            'income_account' => $incomeAccount,
            'overdue_amount' => $overdueAmount,
            'is_internal' => $isInternal,
            'description' => "Maintenance Performed on bike: {$maintenance->bike->emirates}-{$maintenance->bike->plate}, Notes: {$maintenance->description}",
            'missing' => $missing,
        ];
    }

    private function chargeInvoice(BikeMaintenance $maintenance, $data, $transcode = null)
    {
        $transCode = $transcode ?? Account::trans_code();
        $base = [
            'trans_code' => $transCode,
            'trans_date' => $maintenance->maintenance_date,
            'reference_id' => $maintenance->id,
            'reference_type' => 'Bike Maintenance',
            'billing_month' => $maintenance->billing_month,
            'narration' => $data['description'],
        ];

        // Keep model creates so Transactions::creating (voucher approval deferral) still runs.
        if ($data['user_amount'] && $data['user_amount'] > 0) {
            Transactions::create(array_merge($base, [
                'account_id' => $data['user_account']->id,
                'credit' => 0,
                'debit' => $data['user_amount'],
            ]));
        }

        if ($data['company_amount'] && $data['company_amount'] > 0) {
            Transactions::create(array_merge($base, [
                'account_id' => $data['company_account']->id,
                'credit' => 0,
                'debit' => $data['company_amount'],
            ]));
        }

        Transactions::create(array_merge($base, [
            'account_id' => $data['payee_account']->id,
            'credit' => $data['total'],
            'debit' => 0,
        ]));

        if ($data['profit'] > 0) {
            Transactions::create(array_merge($base, [
                'account_id' => $data['income_account']->id,
                'credit' => $data['profit'],
                'debit' => 0,
            ]));
        }

        if (($data['vat_sales_amount'] ?? 0) > 0) {
            Transactions::create(array_merge($base, [
                'account_id' => $data['vat_sales_account']->id,
                'credit' => $data['vat_sales_amount'],
                'debit' => 0,
            ]));
        }

        if (($data['vat_purchase_amount'] ?? 0) > 0) {
            Transactions::create(array_merge($base, [
                'account_id' => $data['vat_purchase_account']->id,
                'credit' => 0,
                'debit' => $data['vat_purchase_amount'],
            ]));
        }

        if (($data['overdue_amount'] ?? 0) > 0) {
            $overdueNarration = $data['description'].' (Overdue KM charge)';
            Transactions::create(array_merge($base, [
                'account_id' => $data['user_account']->id,
                'credit' => 0,
                'debit' => $data['overdue_amount'],
                'narration' => $overdueNarration,
            ]));

            Transactions::create(array_merge($base, [
                'account_id' => $data['income_account']->id,
                'credit' => $data['overdue_amount'],
                'debit' => 0,
                'narration' => $overdueNarration,
            ]));
        }
    }

    /**
     * Rebuild MA- ledger (and normalize internal+company lines) for an existing maintenance.
     * Used by data migrations / repair jobs. Preserves prior trans_code when present.
     *
     * @return array{ok: bool, missing: array<int, string>}
     */
    public function rebuildLedgerForMaintenance(BikeMaintenance $maintenance): array
    {
        $maintenance->loadMissing([
            'bike',
            'garage',
            'leasingCompany',
            'maintenanceItems',
            'rider.account',
            'rentalCompany.account',
        ]);

        $this->normalizeInternalCompanyLines($maintenance);

        $data = $this->billData($maintenance->fresh([
            'bike',
            'rider.account',
            'garage.account',
            'leasingCompany.account',
            'maintenanceItems',
            'rentalCompany.account',
        ]));

        if (! empty($data['missing'])) {
            return ['ok' => false, 'missing' => $data['missing']];
        }

        $transcode = Transactions::withTrashed()
            ->withoutGlobalScope('branch')
            ->where('reference_type', 'Bike Maintenance')
            ->where('reference_id', $maintenance->id)
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->value('trans_code');

        Transactions::withTrashed()
            ->withoutGlobalScope('branch')
            ->where('reference_type', 'Bike Maintenance')
            ->where('reference_id', $maintenance->id)
            ->forceDelete();

        $this->chargeInvoice($maintenance, $data, $transcode);

        $maintenance->update(['total_cost' => $maintenance->cost]);

        return ['ok' => true, 'missing' => []];
    }

    /**
     * Align stored line items with internal+company rules before ledger rebuild:
     * rate = line cost (batch/catalog already on the row), VAT = 0, profit = 0.
     */
    private function normalizeInternalCompanyLines(BikeMaintenance $maintenance): void
    {
        if (! $maintenance->isInternalGaragePerformer()) {
            return;
        }

        foreach ($maintenance->maintenanceItems as $item) {
            if ($item->charge_to !== 'Company') {
                continue;
            }

            $unitCost = (float) ($item->cost ?? 0);
            $qty = (float) $item->quantity;
            $discount = (float) ($item->discount ?? 0);
            $lineCost = $unitCost * $qty;

            $item->rate = $unitCost;
            $item->vat = 0;
            $item->vat_amount = 0;
            $item->total_amount = round($lineCost - $discount, 2);
            $item->total_cost = $lineCost;
            $item->profit = 0;
            $item->save();
        }
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    private function performerDropdownCollections(): array
    {
        $garages = Garages::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'garage_type']);

        $leasingCompanies = LeasingCompanies::query()
            ->where('status', 1)
            ->whereNotNull('account_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        return [$garages, $leasingCompanies];
    }

    /**
     * @return array{0: ?Garages, 1: ?LeasingCompanies, 2: array}
     */
    private function resolvePerformer(array $validated): array
    {
        $performer = (string) ($validated['performer'] ?? '');
        unset($validated['performer']);

        if (preg_match('/^garage:(\d+)$/', $performer, $m)) {
            $garage = Garages::query()
                ->where('status', 1)
                ->where('id', (int) $m[1])
                ->first();
            if (! $garage) {
                throw new \InvalidArgumentException('Selected garage is invalid or inactive.');
            }
            $validated['garage_id'] = $garage->id;
            $validated['leasing_company_id'] = null;

            return [$garage, null, $validated];
        }

        if (preg_match('/^leasing:(\d+)$/', $performer, $m)) {
            $leasing = LeasingCompanies::query()
                ->where('status', 1)
                ->whereNotNull('account_id')
                ->where('id', (int) $m[1])
                ->first();
            if (! $leasing) {
                throw new \InvalidArgumentException('Selected leasing company is invalid, inactive, or has no account.');
            }
            $validated['garage_id'] = null;
            $validated['leasing_company_id'] = $leasing->id;

            return [null, $leasing, $validated];
        }

        throw new \InvalidArgumentException('Please select a garage or leasing company.');
    }

    /**
     * Resolve global COA accounts once per billing pass.
     *
     * @return array<string, mixed>
     */
    private function resolveMaintenanceGlobalAccounts(): array
    {
        if ($this->maintenanceGlobalAccounts !== null) {
            return $this->maintenanceGlobalAccounts;
        }

        $bikeMaintId = GlobalAccounts::idOrNull('BIKE_MAINTENANCE_ACCOUNT');
        $vatSalesId = GlobalAccounts::idOrNull('VAT_ON_SALES');
        $vatPurchaseId = GlobalAccounts::idOrNull('VAT_PURCHASE_ACCOUNT');
        $incomeId = GlobalAccounts::idOrNull('GARAGE_INCOME_ACCOUNT');

        $ids = array_values(array_filter([$bikeMaintId, $vatSalesId, $vatPurchaseId, $incomeId]));
        $accounts = $ids
            ? Accounts::withoutGlobalScopes(['company', 'branch'])->whereIn('id', $ids)->get()->keyBy('id')
            : collect();

        $this->maintenanceGlobalAccounts = [
            'bike_maintenance_id' => $bikeMaintId,
            'bike_maintenance' => $bikeMaintId ? $accounts->get($bikeMaintId) : null,
            'vat_on_sales_id' => $vatSalesId,
            'vat_on_sales' => $vatSalesId ? $accounts->get($vatSalesId) : null,
            'vat_purchase_id' => $vatPurchaseId,
            'vat_purchase' => $vatPurchaseId ? $accounts->get($vatPurchaseId) : null,
            'garage_income_id' => $incomeId,
            'garage_income' => $incomeId ? $accounts->get($incomeId) : null,
        ];

        return $this->maintenanceGlobalAccounts;
    }

    /**
     * Charge overdue to rider only when the form toggle is set and overdue cost > 0.
     */
    private function resolveOverduePaidBy(array $validated): ?string
    {
        $overdueCost = round(
            (float) ($validated['overdue_km'] ?? 0) * (float) ($validated['overdue_cost_per_km'] ?? 0),
            2
        );

        if ($overdueCost > 0 && ($validated['overdue_paidby'] ?? null) === 'Rider') {
            return 'Rider';
        }

        return null;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($company_slug, BikeMaintenance $bikeMaintenance)
    {
        $trashUrl = route('settings-panel.trash.index').'?module=bike_maintenance';

        try {
            // Delete first: when delete-approval is enabled this only queues a request
            // and the record stays intact, so related items/ledger must not be touched yet.
            $bikeMaintenance->delete();

            if (request()->attributes->get('delete_approval_created')) {
                return delete_json_response('Bike maintenance', $trashUrl);
            }

            DB::beginTransaction();
            $bikeMaintenance->finalizeSoftDeletion(auth()->id());
            DB::commit();

            return delete_json_response('Bike maintenance', $trashUrl);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            if (method_exists($bikeMaintenance, 'trashed') && $bikeMaintenance->trashed()) {
                $bikeMaintenance->restore();
            }
            \Log::error('error occured while deleteing maintenance record', [
                'error' => $e->getMessage(),
                'trace: ' => $e->getTrace(),
            ]);

            return delete_error_response('Error: '.$e->getMessage(), null, 500);
        }
    }

    /**
     * Build selectable assignee list for maintenance forms.
     *
     * @return array{
     *   success: bool,
     *   assignees: list<array{key: string, type: string, id: int, label: string, customer_type: ?string}>,
     *   selected: ?string,
     *   previous_km: mixed,
     *   maintenance_km: mixed
     * }
     */
    private function buildBikeAssigneesPayload(
        Bikes $bike,
        ?string $maintenanceDate = null,
        ?int $selectedRiderId = null,
        ?int $selectedRentalCompanyId = null
    ): array {
        $histories = BikeHistory::where('bike_id', $bike->id)
            ->where(function ($q) {
                $q->whereNotNull('rider_id')->orWhereNotNull('rental_company_id');
            })
            ->orderByDesc('note_date')
            ->orderByDesc('id')
            ->get(['rider_id', 'rental_company_id', 'note_date', 'return_date']);

        $riderIds = $histories->pluck('rider_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $companyIds = $histories->pluck('rental_company_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($bike->rider_id) {
            $riderIds = $riderIds->push((int) $bike->rider_id)->unique()->values();
        }
        if ($bike->rental_company_id) {
            $companyIds = $companyIds->push((int) $bike->rental_company_id)->unique()->values();
        }
        if ($selectedRiderId) {
            $riderIds = $riderIds->push($selectedRiderId)->unique()->values();
        }
        if ($selectedRentalCompanyId) {
            $companyIds = $companyIds->push($selectedRentalCompanyId)->unique()->values();
        }

        if (! $selectedRiderId && ! $selectedRentalCompanyId) {
            $party = $this->resolveAssigneeForDate($bike, $histories, $maintenanceDate);
            $selectedRiderId = $party['rider_id'] ?? null;
            $selectedRentalCompanyId = $party['rental_company_id'] ?? null;
        }

        // Prefer one party only when both are sticky
        if ($selectedRiderId && $selectedRentalCompanyId) {
            $selectedRentalCompanyId = null;
        }

        $assignees = [];

        if ($riderIds->isNotEmpty()) {
            $riders = Riders::whereIn('id', $riderIds)->orderBy('name')->get(['id', 'rider_id', 'name']);
            foreach ($riders as $rider) {
                $assignees[] = [
                    'key' => 'rider:'.$rider->id,
                    'type' => 'rider',
                    'id' => (int) $rider->id,
                    'label' => trim(($rider->rider_id ?? '').' - '.($rider->name ?? ''), ' -'),
                    'customer_type' => null,
                ];
            }
        }

        if ($companyIds->isNotEmpty()) {
            $companies = BikeRentCompany::whereIn('id', $companyIds)->orderBy('name')->get(['id', 'name', 'customer_type']);
            foreach ($companies as $company) {
                $assignees[] = [
                    'key' => 'company:'.$company->id,
                    'type' => 'company',
                    'id' => (int) $company->id,
                    'label' => (string) $company->name,
                    'customer_type' => $company->customer_type,
                ];
            }
        }

        $selected = null;
        if ($selectedRiderId) {
            $selected = 'rider:'.$selectedRiderId;
        } elseif ($selectedRentalCompanyId) {
            $selected = 'company:'.$selectedRentalCompanyId;
        }

        $selectedKeys = collect($assignees)->pluck('key');
        if ($selected && ! $selectedKeys->contains($selected)) {
            $selected = null;
        }

        return [
            'success' => true,
            'assignees' => $assignees,
            'selected' => $selected,
            'previous_km' => $bike->current_km,
            'maintenance_km' => $bike->maintenance_km,
        ];
    }

    /**
     * Resolve default rider/company for a bike on a given date (or current assignment).
     *
     * @param  \Illuminate\Support\Collection<int, BikeHistory>  $histories
     * @return array{rider_id: ?int, rental_company_id: ?int}
     */
    private function resolveAssigneeForDate(Bikes $bike, $histories, ?string $date = null): array
    {
        if ($date) {
            try {
                $day = Carbon::parse($date)->startOfDay();
            } catch (\Exception $e) {
                $day = null;
            }

            if ($day) {
                $active = $histories->filter(function ($h) use ($day) {
                    $start = $h->note_date ? Carbon::parse($h->note_date)->startOfDay() : null;
                    if ($start && $start->gt($day)) {
                        return false;
                    }
                    if ($h->return_date === null) {
                        return true;
                    }

                    return Carbon::parse($h->return_date)->startOfDay()->gte($day);
                });

                $riderHistory = $active->first(fn ($h) => ! empty($h->rider_id));
                if ($riderHistory) {
                    return [
                        'rider_id' => (int) $riderHistory->rider_id,
                        'rental_company_id' => null,
                    ];
                }

                $companyHistory = $active->first(fn ($h) => ! empty($h->rental_company_id));
                if ($companyHistory) {
                    return [
                        'rider_id' => null,
                        'rental_company_id' => (int) $companyHistory->rental_company_id,
                    ];
                }
            }
        }

        if ($bike->rider_id) {
            return [
                'rider_id' => (int) $bike->rider_id,
                'rental_company_id' => null,
            ];
        }

        if ($bike->rental_company_id) {
            return [
                'rider_id' => null,
                'rental_company_id' => (int) $bike->rental_company_id,
            ];
        }

        return [
            'rider_id' => null,
            'rental_company_id' => null,
        ];
    }

    private function validateRequest(Request $request)
    {

        return $request->validate([
            'bike_id' => 'required|exists:bikes,id',
            'rider_id' => 'nullable|exists:riders,id',
            'rental_company_id' => 'nullable|exists:bike_rent_companies,id',
            'performer' => ['required', 'regex:/^(garage|leasing):\d+$/'],
            'maintenance_type' => 'required|in:Scheduled,Repairs',
            'maintenance_date' => 'required|date|before:tomorrow',
            'previous_km' => 'nullable|numeric|min:0',
            'current_km' => 'nullable|numeric|min:0|required_if:maintenance_type,Scheduled',
            'maintenance_km' => 'nullable|numeric|min:100|required_if:maintenance_type,Scheduled',
            'overdue_cost_per_km' => 'nullable|numeric|min:0',
            'overdue_km' => 'nullable|numeric|min:0',
            'overdue_cost' => 'nullable|numeric|min:0',
            'overdue_paidby' => 'nullable|in:Rider',
            'description' => 'nullable|string',
            'billing_month' => 'required|date_format:Y-m',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:items,id',
            'quantity' => 'nullable|array',
            'quantity.*' => 'required|numeric|min:0',
            'rate' => 'nullable|array',
            'rate.*' => 'required|numeric|min:0',
            'discount' => 'nullable|array',
            'discount.*' => 'nullable|numeric|min:0',
            'vat' => 'nullable|array',
            'vat.*' => 'nullable|numeric|min:0',
            'item_total' => 'nullable|array',
            'item_total.*' => 'required|numeric|min:0',
            'charge_to' => 'nullable|array',
            'charge_to.*' => 'required|in:Company,User',
            'total_cost' => 'nullable|numeric|min:0',
        ], [
            'bike_id.required' => 'Please Select A Bike',
            'performer.required' => 'Please select who performed the maintenance',
            'performer.regex' => 'Please select a garage or leasing company',
            'maintenance_type.required' => 'Please Select Maintenance Type',
            'current_km.required_if' => 'Current Reading is required for Scheduled maintenance',
            'maintenance_km.required_if' => 'Maintenance Interval is required for Scheduled maintenance',
            'item_id.*.required' => 'Item Field Cannot Be Empty',
            'quantity.*.required' => 'Quantity Field Cannot be Empty',
            'rate.*.required' => 'Rate Field Cannot Be Empty',
            'charge_to.*.required' => 'You Must Choose Who will be Charged for every Item',
        ]);
    }

    /**
     * Apply Scheduled vs Repairs rules for odometer / overdue fields.
     * maintenance_type is form-only and is not persisted.
     * Bike meter fields are synced separately via BikeMaintenance::syncBikeMeterFieldsForBike().
     *
     * @return array{0: array, 1: float|int|string|null} [validated, maintenanceKmForBikeSync]
     */
    private function applyMaintenanceTypeRules(array $validated, Bikes $bike): array
    {
        $type = $validated['maintenance_type'] ?? '';
        $maintenanceKm = $validated['maintenance_km'] ?? null;
        unset($validated['maintenance_type'], $validated['maintenance_km'], $validated['overdue_cost']);

        if ($type === 'Repairs') {
            $validated['previous_km'] = 0;
            $validated['current_km'] = 0;
            $validated['overdue_km'] = 0;
            $validated['overdue_cost_per_km'] = 0;
            $validated['overdue_paidby'] = null;

            return [$validated, null];
        }

        // Scheduled: previous reading defaults to bike's last scheduled reading (current_km)
        if (($validated['previous_km'] ?? null) === null || $validated['previous_km'] === '') {
            $validated['previous_km'] = $bike->current_km ?? 0;
        }
        $validated['overdue_paidby'] = $this->resolveOverduePaidBy($validated);

        return [$validated, $maintenanceKm];
    }

    private function stats($maintenances)
    {

        return [
            'active' => Bikes::where('status', 1)->count(),
            'total' => $maintenances->count(),
            'current' => $maintenances->whereBetween('maintenance_date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])->count(),
            'total_overdue' => $maintenances->where('overdue_km', '>', 0)->count(),
            'current_overdue' => $maintenances->whereBetween('maintenance_date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
                ->where('overdue_km', '>', 0)
                ->count(),
            'avg' => $maintenances
                ->where('overdue_km', '>', 0)
                ->groupBy(fn ($item) => $item->maintenance_date->format('Y-m'))
                ->map(fn ($group) => $group->count())
                ->avg() ?? 0,
            'overdue_cost' => $maintenances->where('overdue_km', '>', 0)->sum(fn ($m) => $m->overdue_km * $m->overdue_cost_per_km),
            'overdue_charged' => $maintenances->where('overdue_km', '>', 0)->where('overdue_paidby', 'Rider')->sum(fn ($m) => $m->overdue_km * $m->overdue_cost_per_km),
            'maint_cost' => $maintenances->sum('total_cost'),

        ];
    }

    private function validateFinancials(Request $request, bool $isInternal): void
    {
        $itemsTotal = 0.0;
        $itemIds = array_values(array_filter($request->item_id ?? []));
        $itemsById = $isInternal
            ? Items::whereIn('id', $itemIds)->get(['id', 'cost', 'is_maintained', 'name'])->keyBy('id')
            : collect();

        foreach ($request->item_id ?? [] as $index => $itemId) {

            $qty = (float) ($request->quantity[$index] ?? 0);
            $rate = (float) ($request->rate[$index] ?? 0);
            $discount = (float) ($request->discount[$index] ?? 0);
            $vat = (float) ($request->vat[$index] ?? 0);
            $frontendTotal = (float) ($request->item_total[$index] ?? 0);
            $chargeTo = $request->charge_to[$index] ?? null;

            // Internal + Company: VAT must be 0. Rate/cost are forced again when building rows.
            if ($isInternal && $chargeTo === 'Company') {
                if (round($vat, 4) !== 0.0) {
                    throw new \Exception('Cannot charge VAT to yourself (row '.($index + 1).').');
                }

                $item = $itemsById->get($itemId);
                if (! $item) {
                    throw new \Exception('Item not found at row '.($index + 1));
                }

                if ($item->is_maintained) {
                    // Authoritative total from the same FIFO batches that will be consumed.
                    $authoritative = $this->estimateMaintainedCompanyLineTotal($item, $qty, $discount);
                    $itemsTotal += $authoritative;
                    continue;
                }

                // Non-maintained: catalog cost is authoritative.
                $rate = (float) $item->cost;
                $frontendTotal = round(($qty * $rate) - $discount, 2);
            }

            $line = $qty * $rate;
            $line -= $discount;

            $vatAmount = $line * ($vat / 100);
            $calculated = round($line + $vatAmount, 2);

            if (round($frontendTotal, 2) !== $calculated) {
                throw new \Exception('Invalid calculation for item at row '.($index + 1));
            }

            $itemsTotal += $calculated;
        }

        // When internal+company includes maintained items, form totals may still use catalog
        // cost while $itemsTotal is FIFO-batch based — allow that gap; rows are rewritten on save.
        $hasMaintainedCompany = false;
        if ($isInternal) {
            foreach ($request->item_id ?? [] as $index => $itemId) {
                if (($request->charge_to[$index] ?? null) !== 'Company') {
                    continue;
                }
                $item = $itemsById->get($itemId);
                if ($item && $item->is_maintained) {
                    $hasMaintainedCompany = true;
                    break;
                }
            }
        }

        if (! $hasMaintainedCompany && round($itemsTotal, 2) !== round((float) $request->total_cost, 2)) {
            throw new \Exception('Maintenance total mismatch.');
        }

        if ($request->maintenance_type === 'Repairs') {
            return;
        }

        $overdue = (float) $request->overdue_km;
        $perKm = (float) $request->overdue_cost_per_km;
        $overdueCalculated = round($overdue * $perKm, 2);

        if (round((float) $request->overdue_cost, 2) !== $overdueCalculated) {
            throw new \Exception('Overdue cost calculation mismatch.');
        }
    }

    /**
     * Estimate company-charged maintained line total from the same FIFO purchase batches
     * that buildMaintainedItemRows will consume (read-only; does not decrement stock).
     */
    private function estimateMaintainedCompanyLineTotal(Items $item, float $qty, float $discount): float
    {
        $availableStock = (float) $item->available;
        if ($availableStock <= 0) {
            throw new \Exception('The item: '.$item->name.' is out of stock.');
        }
        if ($availableStock < $qty) {
            throw new \Exception('Only '.$availableStock.' units of '.$item->name.' are available.');
        }

        $remaining = $qty;
        $gross = 0.0;
        foreach ($item->getAvailableInventory() as $purchase) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, (float) $purchase->remaining_quantity);
            $gross += $take * (float) $purchase->unit_cost;
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \Exception('Only '.($qty - $remaining).' units of '.$item->name.' could be allocated from inventory.');
        }

        return round($gross - $discount, 2);
    }

    public function sticker($company_slug, BikeMaintenance $maintenance)
    {

        $maintenance->load('bike');

        $sticker = [
            'date' => $maintenance->maintenance_date,
            'bike' => $maintenance->bike->emirates.'-'.$maintenance->bike->plate,
            'current_reading' => $maintenance->current_km,
            'next_reading' => $maintenance->current_km + $maintenance->bike->maintenance_km,
        ];

        return view('bike-maintenance.sticker', compact('sticker'));
    }

}
