<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Support\GlobalAccounts;
use App\Models\Accounts;
use App\Models\BikeMaintenance;
use App\Models\BikeMaintenanceItem;
use App\Models\Bikes;
use App\Models\Garages;
use App\Models\Items;
use App\Models\Transactions;
use App\Traits\GlobalPagination;
use App\Support\PublicStorageDisk;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BikeMaintenanceController extends Controller
{
    use GlobalPagination;

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
        $bikes = Bikes::where('status', 1)->get();
        $garages = Garages::where('status', 1)->get();

        return view('bike-maintenance.create_general', compact('items', 'bikee', 'bikes', 'garages'));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $bike = Bikes::find($validated['bike_id']);
        $garage = Garages::find($validated['garage_id']);
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
            if ($bike->rentalCompany?->customer_type == 'garage' && $garage->garage_type == 'external') {
                throw new \Exception('Cannot perform maintenance for a garage customer in an external garage. Please select an internal garage.');
            }
            $this->validateFinancials($request);
            $bike->update([
                'previous_km' => $validated['current_km'],
                'maintenance_km' => $validated['maintenance_km'],
            ]);
            if ($validated['previous_km'] == null) {
                $validated['previous_km'] = $validated['current_km'];
            }
            $maintenance = BikeMaintenance::create($validated);
            // Create maintenance items if present
            if ($request->filled('item_id')) {
                $rows = [];
                if ($garage->garage_type == 'internal') {
                    foreach ($request->item_id as $index => $itemId) {
                        $item = Items::findOrFail($itemId);
                        if ($item->is_maintained) {
                            $available_stock = $item->available;
                            if ($available_stock <= 0) {
                                throw new \Exception('The item: '.$item->name.' is out of stock.');
                            }
                            if ($available_stock < $request->quantity[$index]) {
                                throw new \Exception('Only '.$available_stock.' units of '.$item->name.' are available.');
                            }
                            $remaining = $request->quantity[$index];
                            $inventory = $item->getAvailableInventory();
                            foreach ($inventory as $purchase) {
                                if ($remaining <= 0) {
                                    break;
                                }
                                $qty_from_this_purchase = min($remaining, $purchase->remaining_quantity);
                                if ($request->charge_to[$index] == 'User') {
                                    if ($request->charge_to[$index] < $purchase->unit_cost) {
                                        throw new \Exception('Cannot charge less then item cost. Item: '.$item->name.' Cost: '.$item->unit_cost);
                                    }
                                    $rows[] = [
                                        'bike_maintenance_id' => $maintenance->id,
                                        'item_id' => $item->id,
                                        'inventory_purchase_id' => $purchase->id,
                                        'item_name' => $item->name ?? 'unknown',
                                        'quantity' => $qty_from_this_purchase,
                                        'rate' => $request->rate[$index] ?? 0,
                                        'discount' => $request->discount[$index] ?? 0,
                                        'vat' => $request->vat[$index] ?? 0,
                                        'vat_amount' => $request->vat_amount[$index] ?? 0,
                                        'total_amount' => $request->item_total[$index] ?? 0,
                                        'cost' => $purchase->unit_cost,
                                        'total_cost' => $purchase->unit_cost * $qty_from_this_purchase,
                                        'profit' => ($request->rate[$index] * $qty_from_this_purchase) - ($purchase->unit_cost * $qty_from_this_purchase),
                                        'charge_to' => $request->charge_to[$index],
                                        'branch_id' => $bike->branch_id,
                                        'company_id' => $bike->company_id,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                } else {
                                    $rows[] = [
                                        'bike_maintenance_id' => $maintenance->id,
                                        'item_id' => $item->id,
                                        'inventory_purchase_id' => $purchase->id,
                                        'item_name' => $item->name ?? 'unknown',
                                        'quantity' => $qty_from_this_purchase,
                                        'rate' => $purchase->unit_cost,
                                        'discount' => 0,
                                        'vat' => 0,
                                        'vat_amount' => 0,
                                        'total_amount' => $qty_from_this_purchase * $purchase->unit_cost,
                                        'cost' => $purchase->unit_cost,
                                        'total_cost' => $purchase->unit_cost * $qty_from_this_purchase,
                                        'profit' => 0,
                                        'charge_to' => $request->charge_to[$index],
                                        'branch_id' => $bike->branch_id,
                                        'company_id' => $bike->company_id,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                }
                                $purchase->decrement('remaining_quantity', $qty_from_this_purchase);
                                $remaining -= $qty_from_this_purchase;
                            }
                        } else {
                            $rows[] = [
                                'bike_maintenance_id' => $maintenance->id,
                                'item_id' => $item->id,
                                'item_name' => $item->name ?? 'unknown',
                                'quantity' => $request->quantity[$index],
                                'rate' => $request->rate[$index] ?? 0,
                                'discount' => $request->discount[$index] ?? 0,
                                'vat' => $request->vat[$index] ?? 0,
                                'vat_amount' => $request->vat_amount[$index] ?? 0,
                                'total_amount' => $request->item_total[$index] ?? 0,
                                'cost' => $item->cost,
                                'total_cost' => $item->cost * $request->rate[$index],
                                'profit' => ($request->rate[$index] * $request->quantity[$index]) - ($item->cost * $request->rate[$index]),
                                'charge_to' => $request->charge_to[$index],
                                'branch_id' => $bike->branch_id,
                                'company_id' => $bike->company_id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                } else {
                    $itemIds = array_filter($request->item_id ?? []);
                    $itemsMap = Items::whereIn('id', $itemIds)
                        ->pluck('name', 'id'); // [id => name]
                    foreach ($request->item_id as $index => $itemId) {
                        $rows[] = [
                            'bike_maintenance_id' => $maintenance->id,
                            'item_id' => $itemId,
                            'item_name' => $itemsMap[$itemId] ?? 'unknown',
                            'quantity' => $request->quantity[$index] ?? 1,
                            'rate' => $request->rate[$index] ?? 0,
                            'discount' => $request->discount[$index] ?? 0,
                            'vat' => $request->vat[$index] ?? 0,
                            'vat_amount' => $request->vat_amount[$index] ?? 0,
                            'total_amount' => $request->item_total[$index] ?? 0,
                            'charge_to' => $request->charge_to[$index],
                            'branch_id' => $bike->branch_id,
                            'company_id' => $bike->company_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                BikeMaintenanceItem::insert($rows);
                \Log::info('rows: ', $rows);
                \Log::info('items: ', BikeMaintenanceItem::latest()->take(2)->get()->toArray());
            }
            $maintenance->update(['total_cost' => $maintenance->cost]);
            \Log::info('Maintenance created with data', BikeMaintenance::latest()->first()->toArray());
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
            'maintenanceItems',
            'createdBy',
            'UpdatedBy',
            'rentalCompany',
        ]);

        return view('bike-maintenance.invoice', compact('maintenance'));
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

        $maintenance = $bikeMaintenance;
        $bike = $bikeMaintenance->bike;
        $items = $bikeMaintenance->maintenanceItems;
        $garages = Garages::where('status', 1)->get();

        return view('bike-maintenance.edit', compact('bike', 'items', 'maintenance', 'garages'));
    }

    /**
     * Update the MAintenance Fields in Bikes Table.
     */
    public function update(Request $request, $company_slug, BikeMaintenance $bikeMaintenance)
    {
        $validated = $this->validateRequest($request);
        $maintenance = $bikeMaintenance->load('bike');
        $bike = $maintenance->bike;
        $garage = Garages::find($request['garage_id']);
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
            $this->validateFinancials($request);
            $bike->update([
                'previous_km' => $validated['current_km'],
                'current_km' => null,
                'maintenance_km' => $validated['maintenance_km'],
            ]);
            $this->restoreInventoryQuantities($maintenance->id);
            $maintenance->update($validated);
            BikeMaintenanceItem::where('bike_maintenance_id', $maintenance->id)->delete();
            // Create maintenance items if present
            if ($request->filled('item_id')) {

                $rows = [];
                if ($garage->garage_type == 'internal') {
                    foreach ($request->item_id as $index => $itemId) {
                        $item = Items::findOrFail($itemId);
                        $available_stock = $item->available;
                        if ($item->is_maintained) {
                            if ($available_stock <= 0) {
                                throw new \Exception('The item: '.$item->name.' is out of stock.');
                            }
                            if ($available_stock < $request->quantity[$index]) {
                                throw new \Exception('Only '.$available_stock.' units of '.$item->name.' are available.');
                            }
                            $remaining = $request->quantity[$index];
                            $inventory = $item->getAvailableInventory();
                            foreach ($inventory as $purchase) {
                                if ($remaining <= 0) {
                                    break;
                                }
                                $qty_from_this_purchase = min($remaining, $purchase->remaining_quantity);
                                if ($request->charge_to[$index] == 'User') {
                                    if ($request->charge_to[$index] < $purchase->unit_cost) {
                                        throw new \Exception('Cannot charge less then item cost. Item: '.$item->name.' Cost: '.$item->unit_cost);
                                    }
                                    $rows[] = [
                                        'bike_maintenance_id' => $maintenance->id,
                                        'item_id' => $item->id,
                                        'inventory_purchase_id' => $purchase->id,
                                        'item_name' => $item->name ?? 'unknown',
                                        'quantity' => $qty_from_this_purchase,
                                        'rate' => $request->rate[$index] ?? 0,
                                        'discount' => $request->discount[$index] ?? 0,
                                        'vat' => $request->vat[$index] ?? 0,
                                        'vat_amount' => $request->vat_amount[$index] ?? 0,
                                        'total_amount' => $request->item_total[$index] ?? 0,
                                        'cost' => $purchase->unit_cost,
                                        'total_cost' => $purchase->unit_cost * $qty_from_this_purchase,
                                        'profit' => ($request->rate[$index] * $qty_from_this_purchase) - ($purchase->unit_cost * $qty_from_this_purchase),
                                        'charge_to' => $request->charge_to[$index],
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                } else {
                                    $rows[] = [
                                        'bike_maintenance_id' => $maintenance->id,
                                        'item_id' => $item->id,
                                        'inventory_purchase_id' => $purchase->id,
                                        'item_name' => $item->name ?? 'unknown',
                                        'quantity' => $qty_from_this_purchase,
                                        'rate' => $purchase->unit_cost,
                                        'discount' => 0,
                                        'vat' => 0,
                                        'vat_amount' => 0,
                                        'total_amount' => $qty_from_this_purchase * $purchase->unit_cost,
                                        'cost' => $purchase->unit_cost,
                                        'total_cost' => $purchase->unit_cost * $qty_from_this_purchase,
                                        'profit' => 0,
                                        'charge_to' => $request->charge_to[$index],
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                }
                                $purchase->update(['remaining_quantity' => ($purchase->remaining_quantity - $qty_from_this_purchase)]);
                                $remaining -= $qty_from_this_purchase;
                            }
                        } else {
                            $rows[] = [
                                'bike_maintenance_id' => $maintenance->id,
                                'item_id' => $item->id,
                                'item_name' => $item->name ?? 'unknown',
                                'quantity' => $request->quantity[$index],
                                'rate' => $request->rate[$index] ?? 0,
                                'discount' => $request->discount[$index] ?? 0,
                                'vat' => $request->vat[$index] ?? 0,
                                'vat_amount' => $request->vat_amount[$index] ?? 0,
                                'total_amount' => $request->item_total[$index] ?? 0,
                                'cost' => $item->cost,
                                'total_cost' => $item->cost * $request->rate[$index],
                                'profit' => ($request->rate[$index] * $request->quantity[$index]) - ($item->cost * $request->rate[$index]),
                                'charge_to' => $request->charge_to[$index],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                } else {
                    $itemIds = array_filter($request->item_id ?? []);
                    $itemsMap = Items::whereIn('id', $itemIds)
                        ->pluck('name', 'id'); // [id => name]
                    foreach ($request->item_id as $index => $itemId) {
                        $rows[] = [
                            'bike_maintenance_id' => $maintenance->id,
                            'item_id' => $itemId,
                            'item_name' => $itemsMap[$itemId] ?? 'unknown',
                            'quantity' => $request->quantity[$index] ?? 1,
                            'rate' => $request->rate[$index] ?? 0,
                            'discount' => $request->discount[$index] ?? 0,
                            'vat' => $request->vat[$index] ?? 0,
                            'vat_amount' => $request->vat_amount[$index] ?? 0,
                            'total_amount' => $request->item_total[$index] ?? 0,
                            'charge_to' => $request->charge_to[$index],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                BikeMaintenanceItem::insert($rows);
            }
            $maintenance->update(['total_cost' => $maintenance->cost]);
            $data = $this->billData($maintenance);
            if (! empty($data['missing'])) {
                throw new \Exception(implode('. ', $data['missing']));
            }
            $transcode = Transactions::where('reference_type', 'Bike Maintenance')
                ->where('reference_id', $maintenance->id)
                ->value('trans_code');
            Transactions::where('reference_type', 'Bike Maintenance')
                ->where('reference_id', $maintenance->id)
                ->delete();
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

    private function billData(BikeMaintenance $maintenance)
    {
        // Eager load everything including nested accounts
        $maintenance->load([
            'bike',
            'rider.account',
            'garage.account',
            'maintenanceItems',
            'rentalCompany.account',
        ]);

        $missing = [];
        $userTotal = 0;
        $userAccount = null;
        $companyTotal = 0;
        $companyAccount = null;
        $garageAccount = null;
        $vatAccount = null;

        $items = $maintenance->maintenanceItems;
        $userItems = $items->where('charge_to', 'User');
        $companyItems = $items->where('charge_to', 'Company');

        if ($items->isEmpty()) {
            $missing[] = 'No items Added in the Bill';
        }

        $profit = $maintenance->maintenanceItems->sum('profit');
        $total = $maintenance->total_cost - $maintenance->maintenanceItems->sum('profit');

        if ($userItems->isNotEmpty()) {

            if (! $maintenance->rider_id && ! $maintenance->rental_company_id) {
                \Log::info('maintenance data', $maintenance->toArray());
                $missing[] = 'No User found for this maintenance but maintenance items charged to User';
            }

            $userTotal = $userItems->sum('total_amount');

            if ($maintenance->rider && $maintenance->rider->account) {
                $userAccount = $maintenance->rider->account;
            } elseif ($maintenance->rentalCompany && $maintenance->rentalCompany->account) {
                $userAccount = $maintenance->rentalCompany->account;
            } else {
            }
        }

        foreach ($items as $item) {
            $itemTotal = $item->quantity * $item->rate;
            $discount = $item->discount ?? 0;
            $itemTotal -= $discount;
            $vatAmount = $itemTotal * ($item->vat / 100);
            $itemTotal += $vatAmount;
            if (round($item->total_amount, 2) != round($itemTotal, 2)) {
                $missing[] = 'Item total amount mismatch for item: '.$item->item_name;
            }
        }

        if ($companyItems->isNotEmpty()) {

            $companyTotal = $companyItems->sum('total_amount');
            $acc = GlobalAccounts::account('BIKE_MAINTENANCE_ACCOUNT');
            if (! $acc) {
                $missing[] = 'Company Bike Maintennace Account not found ID:'.GlobalAccounts::id('BIKE_MAINTENANCE_ACCOUNT');
            } else {
                $companyAccount = $acc;
            }
        }

        $vat = $items->sum('vat_amount');

        if ($vat > 0) {
            if ($maintenance->garage->garage_type == 'internal') {
                $vatAccount = Accounts::find(GlobalAccounts::id('VAT_ON_SALES'));
            } // VAT on Sales
            else {
                $vatAccount = Accounts::find(GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'));
            } // VAT on Purchase
            if (! $vatAccount) {
                $missing[] = 'VAT Account not Found. ID(sale):'.GlobalAccounts::id('VAT_ON_SALES').' ID(purchase):'.GlobalAccounts::id('VAT_PURCHASE_ACCOUNT');
            }
        }

        if (! $maintenance->garage || ! $maintenance->garage->account) {
            $missing[] = 'No Associated Garage or Garage Account found';
        } else {
            $garageAccount = $maintenance->garage->account;
        }

        $data = [
            'total' => $total,
            'profit' => $profit,
            'user_amount' => $userTotal,
            'user_account' => $userAccount,
            'company_amount' => $companyTotal,
            'company_account' => $companyAccount,
            'vat_amount' => $vat,
            'vat_account' => $vatAccount,
            'garage_account' => $garageAccount,
            'description' => "Maintenance Performed on bike: {$maintenance->bike->emirates}-{$maintenance->bike->plate}",
            'missing' => $missing,
        ];

        return $data;
    }

    private function chargeInvoice(BikeMaintenance $maintenance, $data, $transcode = null)
    {

        $transCode = $transcode ?? Account::trans_code();
        if ($data['user_amount'] && $data['user_amount'] > 0) {
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $maintenance->maintenance_date,
                'reference_id' => $maintenance->id,
                'reference_type' => 'Bike Maintenance',
                'account_id' => $data['user_account']->id,
                'credit' => 0,
                'debit' => $data['user_amount'],
                'billing_month' => $maintenance->billing_month,
                'narration' => $data['description'],
            ]);
        }
        if ($data['company_amount'] && $data['company_amount'] > 0) {

            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $maintenance->maintenance_date,
                'reference_id' => $maintenance->id,
                'reference_type' => 'Bike Maintenance',
                'account_id' => $data['company_account']->id,
                'credit' => 0,
                'debit' => $data['company_amount'],
                'billing_month' => $maintenance->billing_month,
                'narration' => $data['description'],
            ]);
        }
        Transactions::create([
            'trans_code' => $transCode,
            'trans_date' => $maintenance->maintenance_date,
            'reference_id' => $maintenance->id,
            'reference_type' => 'Bike Maintenance',
            'account_id' => $data['garage_account']->id,
            'credit' => $data['total'],
            'debit' => 0,
            'billing_month' => $maintenance->billing_month,
            'narration' => $data['description'],
        ]);
        if ($data['profit'] > 0) {
            $profitAcc = GlobalAccounts::account('GARAGE_INCOME_ACCOUNT');
            if (! $profitAcc) {
                throw new \Exception('Garage Income Account not find');
            }
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $maintenance->maintenance_date,
                'reference_id' => $maintenance->id,
                'reference_type' => 'Bike Maintenance',
                'account_id' => $profitAcc->id,
                'credit' => $data['profit'],
                'debit' => 0,
                'billing_month' => $maintenance->billing_month,
                'narration' => $data['description'],
            ]);
        }
        if ($data['vat_amount'] > 0) {
            if ($maintenance->garage->garage_type == 'internal') {
                $vatAcc = GlobalAccounts::id('VAT_ON_SALES'); // VAT on Sale
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $maintenance->maintenance_date,
                    'reference_id' => $maintenance->id,
                    'reference_type' => 'Bike Maintenance',
                    'account_id' => $vatAcc,
                    'credit' => $data['vat_amount'],
                    'debit' => 0,
                    'billing_month' => $maintenance->billing_month,
                    'narration' => $data['description'],
                ]);
            } else {
                $vatAcc = GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'); // VAT on Purchase
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $maintenance->maintenance_date,
                    'reference_id' => $maintenance->id,
                    'reference_type' => 'Bike Maintenance',
                    'account_id' => $vatAcc,
                    'credit' => 0,
                    'debit' => $data['vat_amount'],
                    'billing_month' => $maintenance->billing_month,
                    'narration' => $data['description'],
                ]);
            }

        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($company_slug, BikeMaintenance $bikeMaintenance)
    {
        DB::beginTransaction();
        try {

            $this->restoreInventoryQuantities($bikeMaintenance->id);
            BikeMaintenanceItem::where('bike_maintenance_id', $bikeMaintenance->id)->delete();
            Transactions::where('reference_type', 'Bike Maintenance')
                ->where('reference_id', $bikeMaintenance->id)
                ->delete();

            $path = $bikeMaintenance->attachment;
            $bike = $bikeMaintenance->bike;
            $prev = $bikeMaintenance->previous_km;
            $maintenance = BikeMaintenance::where('bike_id', $bike->id)
                ->where('id', '!=', $bikeMaintenance->id)
                ->orderby('maintenance_date', 'desc')
                ->first();
            if ($maintenance) {
                $bike->update([
                    'previous_km' => $maintenance->current_km,
                    'maintenance_km' => $maintenance->maintenance_km,
                ]);
            } else {
                $bike->update([
                    'previous_km' => null,
                ]);
            }
            $bikeMaintenance->delete();
            if ($path) {
                Storage::delete($path);
            }

            DB::commit();

            return response()->json(['message' => 'Maintenance Record Deleted Successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('error occured while deleteing maintenance record', ['error' => $e->getMessage(), 'trace: ' => $e->getTrace()]);

            return response()->json(['message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    private function validateRequest(Request $request)
    {

        return $request->validate([
            'bike_id' => 'required|exists:bikes,id',
            'rider_id' => 'nullable|exists:riders,id',
            'rental_company_id' => 'nullable|exists:bike_rent_companies,id',
            'garage_id' => 'required|exists:garages,id',
            'maintenance_date' => 'required|date|before:tomorrow',
            'previous_km' => 'nullable|numeric|min:1',
            'current_km' => 'required|numeric|min:0',
            'maintenance_km' => 'required|numeric|min:100',
            'overdue_cost_per_km' => 'nullable|numeric|min:0',
            'overdue_km' => 'nullable|numeric|min:0',
            'overdue_cost' => 'nullable|numeric|min:0',
            'overdue_paidby' => 'nullable|in:Company,User',
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
            'item_id.*.required' => 'Item Field Cannot Be Empty',
            'quantity.*.required' => 'Quantity Field Cannot be Empty',
            'rate.*.required' => 'Rate Field Cannot Be Empty',
            'charge_to.*.required' => 'You Must Choose Who will be Charged for every Item',
        ]);
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

    private function validateFinancials(Request $request): void
    {
        $itemsTotal = 0;

        foreach ($request->item_id ?? [] as $index => $itemId) {

            $qty = (float) ($request->quantity[$index] ?? 0);
            $rate = (float) ($request->rate[$index] ?? 0);
            $discount = (float) ($request->discount[$index] ?? 0);
            $vat = (float) ($request->vat[$index] ?? 0);
            $frontendTotal = (float) ($request->item_total[$index] ?? 0);

            $line = $qty * $rate;
            $line -= $discount;

            $vatAmount = $line * ($vat / 100);
            $calculated = round($line + $vatAmount, 2);

            if (round($frontendTotal, 2) !== $calculated) {
                throw new \Exception('Invalid calculation for item at row '.($index + 1));
            }

            $itemsTotal += $calculated;
        }
        \Log::info('itemsTotal: '.$itemsTotal.' ,cost: '.$request->total_cost);
        if (round($itemsTotal, 2) !== round((float) $request->total_cost, 2)) {
            throw new \Exception('Maintenance total mismatch.');
        }

        // Validate overdue
        $overdue = (float) $request->overdue_km;
        $perKm = (float) $request->overdue_cost_per_km;
        $overdueCalculated = round($overdue * $perKm, 2);

        if (round((float) $request->overdue_cost, 2) !== $overdueCalculated) {
            throw new \Exception('Overdue cost calculation mismatch.');
        }
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

    private function restoreInventoryQuantities($maintenanceId)
    {
        $oldItems = BikeMaintenanceItem::where('bike_maintenance_id', $maintenanceId)
            ->whereNotNull('inventory_purchase_id')
            ->get();

        foreach ($oldItems as $oldItem) {
            $purchase = InventoryPurchase::find($oldItem->inventory_purchase_id);
            if ($purchase) {
                $purchase->increment('remaining_quantity', $oldItem->quantity);
            }
        }
    }
}
