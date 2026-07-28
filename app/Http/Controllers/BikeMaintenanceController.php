<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Support\GlobalAccounts;
use App\Models\Accounts;
use App\Models\BikeMaintenance;
use App\Models\BikeMaintenanceItem;
use App\Models\Bikes;
use App\Models\Garages;
use App\Models\InventoryPurchase;
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
            [$validated, $maintenanceKm] = $this->applyMaintenanceTypeRules($validated, $bike);
            $maintenance = BikeMaintenance::create($validated);

            $rows = $this->buildMaintenanceItemRows($request, $maintenance, $bike, $garage);
            if (! empty($rows)) {
                BikeMaintenanceItem::insert($rows);
            }

            $maintenance->update(['total_cost' => $maintenance->cost]);
            $this->syncBikeMeterFields($bike, $maintenanceKm);
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
            [$validated, $maintenanceKm] = $this->applyMaintenanceTypeRules($validated, $bike);
            $this->restoreInventoryQuantities($maintenance->id);
            $maintenance->update($validated);
            BikeMaintenanceItem::where('bike_maintenance_id', $maintenance->id)->delete();

            $rows = $this->buildMaintenanceItemRows($request, $maintenance, $bike, $garage);
            if (! empty($rows)) {
                BikeMaintenanceItem::insert($rows);
            }

            $maintenance->update(['total_cost' => $maintenance->cost]);
            $this->syncBikeMeterFields($bike, $maintenanceKm);
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

    /**
     * Build bike_maintenance_items rows from the request.
     * Always stores the selling rate entered on the form.
     */
    private function buildMaintenanceItemRows(Request $request, BikeMaintenance $maintenance, Bikes $bike, Garages $garage): array
    {
        if (! $request->filled('item_id')) {
            return [];
        }

        $rows = [];
        $now = now();

        if ($garage->garage_type == 'internal') {
            foreach ($request->item_id as $index => $itemId) {
                $item = Items::findOrFail($itemId);
                $qty = (float) ($request->quantity[$index] ?? 0);
                $rate = (float) ($request->rate[$index] ?? 0);
                $discount = (float) ($request->discount[$index] ?? 0);
                $vat = (float) ($request->vat[$index] ?? 0);
                $vatAmount = (float) ($request->vat_amount[$index] ?? 0);
                $itemTotal = (float) ($request->item_total[$index] ?? 0);
                $chargeTo = $request->charge_to[$index];

                if ($item->is_maintained) {
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
                    if ($chargeTo === 'Company' && $rate > $itemCost) {
                        throw new \Exception(
                            'Cannot charge company more than item cost. Item: '.$item->name.' Cost: '.$itemCost
                        );
                    }

                    $lineCost = $itemCost * $qty;
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
                        'cost' => $item->cost,
                        'total_cost' => $lineCost,
                        'profit' => ($rate * $qty - $discount) - $lineCost,
                        'charge_to' => $chargeTo,
                        'branch_id' => $bike->branch_id ?? null,
                        'company_id' => $bike->company_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        } else {
            $itemIds = array_filter($request->item_id ?? []);
            $itemsMap = Items::whereIn('id', $itemIds)->pluck('name', 'id');
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

        $remaining = $qty;
        $inventory = $item->getAvailableInventory();
        $slices = [];

        foreach ($inventory as $purchase) {
            if ($remaining <= 0) {
                break;
            }

            $qtyFromPurchase = min($remaining, (float) $purchase->remaining_quantity);
            $unitCost = (float) $purchase->unit_cost;

            if ($rate < $unitCost) {
                throw new \Exception(
                    'Cannot charge less than item cost. Item: '.$item->name.' Cost: '.$unitCost
                );
            }

            if ($chargeTo === 'Company' && $rate > $unitCost) {
                throw new \Exception(
                    'Cannot charge company more than item cost. Item: '.$item->name.' Cost: '.$unitCost
                );
            }

            $slices[] = [
                'purchase' => $purchase,
                'qty' => $qtyFromPurchase,
                'unit_cost' => $unitCost,
            ];
            $remaining -= $qtyFromPurchase;
        }

        if ($remaining > 0) {
            throw new \Exception('Only '.($qty - $remaining).' units of '.$item->name.' could be allocated from inventory.');
        }

        $rows = [];
        $sliceCount = count($slices);
        $allocatedDiscount = 0.0;
        $allocatedVatAmount = 0.0;
        $allocatedTotal = 0.0;

        foreach ($slices as $i => $slice) {
            $isLast = ($i === $sliceCount - 1);
            $share = $qty > 0 ? ($slice['qty'] / $qty) : 0;

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

            $lineCost = $slice['unit_cost'] * $slice['qty'];
            $sliceRevenue = ($rate * $slice['qty']) - $sliceDiscount;

            $rows[] = [
                'bike_maintenance_id' => $maintenance->id,
                'item_id' => $item->id,
                'inventory_purchase_id' => $slice['purchase']->id,
                'item_name' => $item->name ?? 'unknown',
                'quantity' => $slice['qty'],
                'rate' => $rate,
                'discount' => $sliceDiscount,
                'vat' => $vat,
                'vat_amount' => $sliceVatAmount,
                'total_amount' => $sliceTotal,
                'cost' => $slice['unit_cost'],
                'total_cost' => $lineCost,
                'profit' => $sliceRevenue - $lineCost,
                'charge_to' => $chargeTo,
                'branch_id' => $bike->branch_id ?? null,
                'company_id' => $bike->company_id,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $slice['purchase']->decrement('remaining_quantity', $slice['qty']);
        }

        return $rows;
    }

    private function billData(BikeMaintenance $maintenance)
    {
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
        $isInternal = $maintenance->garage && $maintenance->garage->garage_type === 'internal';

        if ($items->isEmpty()) {
            $missing[] = 'No items Added in the Bill';
        }

        $profit = (float) $items->sum('profit');
        $cogs = (float) $items->sum('total_cost');
        $vat = (float) $items->sum('vat_amount');
        $gross = (float) $items->sum('total_amount');

        // Internal: Cr garage at COGS, Cr profit, Cr VAT; Dr payers at gross (incl. VAT).
        // External: Dr payers at ex-VAT, Dr VAT purchase, Cr garage at gross.
        if ($isInternal) {
            $garageCredit = $cogs;
            $userTotal = (float) $userItems->sum('total_amount');
            $companyTotal = (float) $companyItems->sum('total_amount');
        } else {
            $garageCredit = $gross;
            $profit = 0;
            $userTotal = (float) ($userItems->sum('total_amount') - $userItems->sum('vat_amount'));
            $companyTotal = (float) ($companyItems->sum('total_amount') - $companyItems->sum('vat_amount'));
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

        $needsCompanyAccount = $companyItems->isNotEmpty();

        if ($needsCompanyAccount) {
            $companyAccountId = GlobalAccounts::idOrNull('BIKE_MAINTENANCE_ACCOUNT');
            if (! $companyAccountId) {
                $missing[] = 'Company Bike Maintenance Account not configured (BIKE_MAINTENANCE_ACCOUNT)';
            } else {
                $companyAccount = Accounts::withoutGlobalScopes(['company', 'branch'])->find($companyAccountId);
                if (! $companyAccount) {
                    $missing[] = 'Company Bike Maintenance Account not found';
                }
            }
            if ($companyItems->isEmpty()) {
                $companyTotal = 0;
            }
        }

        if ($vat > 0) {
            $vatCode = $isInternal ? 'VAT_ON_SALES' : 'VAT_PURCHASE_ACCOUNT';
            $vatAccountId = GlobalAccounts::idOrNull($vatCode);
            if (! $vatAccountId) {
                $missing[] = 'VAT Account not Found ('.$vatCode.')';
            } else {
                $vatAccount = Accounts::withoutGlobalScopes(['company', 'branch'])->find($vatAccountId);
                if (! $vatAccount) {
                    $missing[] = 'VAT Account not Found ('.$vatCode.')';
                }
            }
        }

        if (! $maintenance->garage || ! $maintenance->garage->account) {
            $missing[] = 'No Associated Garage or Garage Account found';
        } else {
            $garageAccount = $maintenance->garage->account;
        }

        if ($profit > 0 || $overdueAmount > 0) {
            if (! GlobalAccounts::idOrNull('GARAGE_INCOME_ACCOUNT')) {
                $missing[] = 'Garage Income Account not configured (GARAGE_INCOME_ACCOUNT)';
            }
        }

        return [
            'total' => $garageCredit,
            'profit' => $profit,
            'user_amount' => $userTotal,
            'user_account' => $userAccount,
            'company_amount' => $companyTotal,
            'company_account' => $companyAccount,
            'vat_amount' => $vat,
            'vat_account' => $vatAccount,
            'garage_account' => $garageAccount,
            'overdue_amount' => $overdueAmount,
            'description' => "Maintenance Performed on bike: {$maintenance->bike->emirates}-{$maintenance->bike->plate}",
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
            'account_id' => $data['garage_account']->id,
            'credit' => $data['total'],
            'debit' => 0,
        ]));

        if ($data['profit'] > 0) {
            $profitAcc = GlobalAccounts::account('GARAGE_INCOME_ACCOUNT');
            Transactions::create(array_merge($base, [
                'account_id' => $profitAcc->id,
                'credit' => $data['profit'],
                'debit' => 0,
            ]));
        }

        if ($data['vat_amount'] > 0) {
            if ($maintenance->garage->garage_type == 'internal') {
                Transactions::create(array_merge($base, [
                    'account_id' => GlobalAccounts::id('VAT_ON_SALES'),
                    'credit' => $data['vat_amount'],
                    'debit' => 0,
                ]));
            } else {
                Transactions::create(array_merge($base, [
                    'account_id' => GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'),
                    'credit' => 0,
                    'debit' => $data['vat_amount'],
                ]));
            }
        }

        if (($data['overdue_amount'] ?? 0) > 0) {
            $overdueNarration = $data['description'].' (Overdue KM charge)';
            Transactions::create(array_merge($base, [
                'account_id' => $data['user_account']->id,
                'credit' => 0,
                'debit' => $data['overdue_amount'],
                'narration' => $overdueNarration,
            ]));

            $incomeAcc = GlobalAccounts::account('GARAGE_INCOME_ACCOUNT');
            Transactions::create(array_merge($base, [
                'account_id' => $incomeAcc->id,
                'credit' => $data['overdue_amount'],
                'debit' => 0,
                'narration' => $overdueNarration,
            ]));
        }
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
        DB::beginTransaction();
        try {

            $this->restoreInventoryQuantities($bikeMaintenance->id);
            BikeMaintenanceItem::where('bike_maintenance_id', $bikeMaintenance->id)->delete();
            Transactions::where('reference_type', 'Bike Maintenance')
                ->where('reference_id', $bikeMaintenance->id)
                ->delete();

            $path = $bikeMaintenance->attachment;
            $bike = $bikeMaintenance->bike;
            $bikeMaintenance->delete();
            $this->syncBikeMeterFields($bike);
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
     * Bike meter fields are synced separately via syncBikeMeterFields().
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

    /**
     * Rebuild bike meter fields from scheduled maintenance history.
     * bikes.current_km  = latest scheduled reading
     * bikes.previous_km = scheduled reading before that
     * Repairs (current_km = 0) are ignored.
     */
    private function syncBikeMeterFields(Bikes $bike, $maintenanceKm = null): void
    {
        $scheduled = BikeMaintenance::where('bike_id', $bike->id)
            ->where('current_km', '>', 0)
            ->orderByDesc('maintenance_date')
            ->orderByDesc('id')
            ->limit(2)
            ->get();

        $latest = $scheduled->get(0);
        $prior = $scheduled->get(1);

        $data = [
            'current_km' => $latest?->current_km,
            'previous_km' => $prior?->current_km ?? ($latest?->previous_km),
        ];

        if ($maintenanceKm !== null) {
            $data['maintenance_km'] = $maintenanceKm;
        }

        $bike->update($data);
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
            $chargeTo = $request->charge_to[$index] ?? null;

            if ($chargeTo === 'Company' && $vat > 0) {
                throw new \Exception('Cannot charge VAT to yourself (row '.($index + 1).').');
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

        if (round($itemsTotal, 2) !== round((float) $request->total_cost, 2)) {
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
