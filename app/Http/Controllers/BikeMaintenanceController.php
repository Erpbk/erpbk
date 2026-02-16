<?php

namespace App\Http\Controllers;

use App\Models\BikeMaintenance;
use App\Models\BikeMaintenanceItem;
use Illuminate\Http\Request;
use App\Models\Bikes;
use App\Models\Items;
use App\Models\Accounts;
use App\Models\Transactions;
use App\Traits\GlobalPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

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
                        ->get();
        $stats = $this->stats($maintenances);

        return view('bike-maintenance.index',compact('maintenances','stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if(request()->id){
            $bike = Bikes::find(request()->id);
            return view('bike-maintenance.create', compact('bike'));
        }
        else
            $bike = null;

        return view('bike-maintenance.create_general');

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $bike = Bikes::find($validated['bike_id']);
        $validated['created_by'] = auth()->id();
        $validated['billing_month'] = $validated['billing_month'] . '-01';
        $validated['status'] = 1;
        $path = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = 'maintenance_'.time().'.'.$file->extension();
            $path = $file->storeAs('bike/'.$bike->id.'/', $filename);
            $validated['attachment'] = $path;
        }
        DB::beginTransaction();
        try{
            $this->validateFinancials($request);
            $bike->update([
                'previous_km' => $validated['current_km'],
                'maintenance_km' => $validated['maintenance_km']
                ]);
            if($validated['previous_km'] === null)
                $validated['previous_km'] = $validated['current_km'];
            $maintenance = BikeMaintenance::create($validated);
            // Create maintenance items if present
            if ($request->filled('item_id')) {

                $itemIds = array_filter($request->item_id ?? []);
                $itemsMap = Items::whereIn('id', $itemIds)
                    ->pluck('name', 'id'); // [id => name]
                $rows = [];
                foreach ($request->item_id as $index => $itemId) {
                    $rows[] = [
                        'bike_maintenance_id' => $maintenance->id,
                        'item_id'            => $itemId,
                        'item_name'          => $itemsMap[$itemId] ?? 'unknown',
                        'quantity'           => $request->quantity[$index] ?? 1,
                        'rate'               => $request->rate[$index] ?? 0,
                        'discount'           => $request->discount[$index] ?? 0,
                        'vat'                => $request->vat[$index] ?? 0,
                        'vat_amount'         => $request->vat_amount[$index] ?? 0,
                        'total_amount'       => $request->item_total[$index] ?? 0,
                        'charge_to'          => $request->charge_to[$index],
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];
                }
                BikeMaintenanceItem::insert($rows);
            }
            $data = $this->billData($maintenance);
            if(!empty($data['missing'])){
                throw new \Exception(implode('. ', $data['missing']));
            }
            $this->chargeInvoice($maintenance, $data);
            DB::commit();
            return response()->json(['message' => 'Maintenance Record Created Successfully.'],200);
        }catch(\Exception $e){
            DB::rollBack();
            if($path)
                Storage::delete($path);
            \Log::error(
                'An error occured while creating bike maintenance record',
                [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTrace(),
                ]
            );
            return response()->json(['message' => 'Error: '. $e->getMessage()],500);
        }
        
    }

    public function invoice(BikeMaintenance $maintenance)
    {
        $maintenance->load([
            'bike.rider',
            'garage',
            'maintenanceItems',
            'createdBy',
            'UpdatedBy'
        ]);

        return view('bike-maintenance.invoice', compact('maintenance'));
    }

    /**
     * Display the specified resource.
     */
    public function show(BikeMaintenance $bikeMaintenance)
    {
        //
    }

    /**
     * Show the form for editing the specified Bike's Maintenance Details.
     */

    public function edit(BikeMaintenance $bikeMaintenance){
        $maintenance = $bikeMaintenance;
        $bike = $bikeMaintenance->bike;
        $items = $bikeMaintenance->maintenanceItems;
        return view('bike-maintenance.edit', compact('bike','items','maintenance'));
    }

    

    /**
     * Update the MAintenance Fields in Bikes Table.
     */
    public function update(Request $request, BikeMaintenance $bikeMaintenance)
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
            $path = $file->storeAs('bike/'.$bike->id.'/', $filename);
            $validated['attachment'] = $path;
        }
        DB::beginTransaction();
        try{
            $this->validateFinancials($request);
            $bike->update([
                'previous_km' => $validated['current_km'],
                'current_km' => null,
                'maintenance_km' => $validated['maintenance_km']
                ]);
            $maintenance->update($validated);
            BikeMaintenanceItem::where('bike_maintenance_id', $maintenance->id)->delete();
            // Create maintenance items if present
            if ($request->filled('item_id')) {

                $itemIds = array_filter($request->item_id ?? []);
                $itemsMap = Items::whereIn('id', $itemIds)
                    ->pluck('name', 'id'); // [id => name]
                $rows = [];
                foreach ($request->item_id as $index => $itemId) {
                    $rows[] = [
                        'bike_maintenance_id' => $maintenance->id,
                        'item_id'            => $itemId,
                        'item_name'          => $itemsMap[$itemId] ?? 'unknown',
                        'quantity'           => $request->quantity[$index] ?? 1,
                        'rate'               => $request->rate[$index] ?? 0,
                        'discount'           => $request->discount[$index] ?? 0,
                        'vat'                => $request->vat[$index] ?? 0,
                        'vat_amount'         => $request->vat_amount[$index] ?? 0,
                        'total_amount'       => $request->item_total[$index] ?? 0,
                        'charge_to'          => $request->charge_to[$index],
                        'updated_at'         => now(),
                    ];
                }
                BikeMaintenanceItem::insert($rows);
            }
            $data = $this->billData($maintenance);
            if(!empty($data['missing'])){
                throw new \Exception(implode('. ', $data['missing']));
            }
            $transcode = Transactions::where('reference_type','Bike Maintenance')
                        ->where('reference_id',$maintenance->id)
                        ->value('trans_code');
            Transactions::where('reference_type','Bike Maintenance')
                        ->where('reference_id',$maintenance->id)
                        ->delete();
            $this->chargeInvoice($maintenance, $data, $transcode);
            DB::commit();
            if($oldpath)
                if($path)
                    Storage::delete($oldpath);
            return response()->json(['message' => 'Maintenance Record Updated Successfully.'],200);
        }catch(\Exception $e){
            DB::rollBack();
            if($path)
                Storage::delete($path);
            \Log::error(
                'An error occured while update bike maintenance record',
                [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTrace(),
                ]
            );
            return response()->json(['message' => 'Error: '. $e->getMessage()],500);
        }

    }

    private function billData(BikeMaintenance $maintenance){
        // Eager load everything including nested accounts
        $maintenance->load([
            'bike',
            'rider.account',
            'garage.account',
            'maintenanceItems',
        ]);

        $missing = [];

        $items = $maintenance->maintenanceItems;
        $riderItems   = $items->where('charge_to', 'Rider');
        $companyItems = $items->where('charge_to', 'Company');
        
        if($items->isEmpty())
            $missing[] = 'No items Added in the Bill';
        // Overdue calculation
        $overdueCost = ($maintenance->overdue_paidby === 'Rider')
            ? $maintenance->overdue_cost_per_km * $maintenance->overdue_km
            : 0;

        $total = $maintenance->total_cost + $overdueCost;

        $riderTotal   = null;
        $riderAccount = null;

        if ($riderItems->isNotEmpty() || $overdueCost > 0) {

            if (!$maintenance->rider_id) {
                $missing[] = 'No rider found for this maintenance but maintenance items charged to rider';
            }

            $riderTotal = $riderItems->sum('total_amount') + $overdueCost;

            if ($maintenance->rider && $maintenance->rider->account) {
                $account = $maintenance->rider->account;
                $riderAccount = "{$account->account_code}-{$account->name}";
            } else {
                $riderAccount = 'No Rider Assigned to bike';
            }
        }

        foreach($items as $item){
            $itemTotal = $item->quantity * $item->rate;
            $discount = $item->discount ?? 0;
            $itemTotal -= $discount;
            $vatAmount = $itemTotal * ($item->vat/100);
            $itemTotal += $vatAmount;
            if(round($item->total_amount,2) != round($itemTotal,2))
                $missing[] = 'Item total amount mismatch for item: ' . $item->item_name;
        }

        $companyTotal   = null;
        $companyAccount = null;

        if ($companyItems->isNotEmpty()) {

            $companyTotal = $companyItems->sum('total_amount');
            $companyAcc = Accounts::select('account_code', 'name')
                ->find(1213); // bike maintenance account

            $companyAccount = $companyAcc
                ? "{$companyAcc->account_code}-{$companyAcc->name}"
                : 'Company Account Not Found';
        }

        $vat = $items->sum('vat_amount');
        $vatAccount = null;

        if ($vat > 0) {
            $vatAcc = Accounts::select('account_code', 'name')
                ->find(1023); // VAT on purchase

            $vatAccount = $vatAcc
                ? "{$vatAcc->account_code}-{$vatAcc->name}"
                : 'VAT Account Not Found';
        }

        if (!$maintenance->garage || !$maintenance->garage->account) {
            $missing[] = 'No Associated Garage or Garage Account found';
            $garageAccount = 'Garage Not Found';
        } else {
            $garageAcc = $maintenance->garage->account;
            $garageAccount = "{$garageAcc->account_code}-{$garageAcc->name}";
        }

        $data = [
            'total'            => $total,
            'rider_amount'     => $riderTotal,
            'rider_account'    => $riderAccount,
            'company_amount'   => $companyTotal,
            'company_account'  => $companyAccount,
            'vat_amount'       => $vat,
            'vat_account'      => $vatAccount,
            'garage_account'   => $garageAccount,
            'description'      => "Maintenance Performed on bike: {$maintenance->bike->emirates}-{$maintenance->bike->plate}",
            'missing'          => $missing,
        ];

        return $data;
    }

    private function chargeInvoice(BikeMaintenance $maintenance, $data, $transcode = null){

        $transCode = $transcode ?? \App\Helpers\Account::trans_code();
        if( $data['rider_amount'] && $data['rider_amount'] > 0){
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $maintenance->maintenance_date,
                'reference_id' => $maintenance->id,
                'reference_type' => 'Bike Maintenance',
                'account_id' => $maintenance->rider->account_id,
                'credit' => 0,
                'debit' => $data['rider_amount'],
                'billing_month' => $maintenance->billing_month,
                'narration' => $data['description'],
            ]);
        }
        if($data['company_amount'] && $data['company_amount'] > 0){
            $companyAcc = Accounts::select('id')
                ->find(1213); // bike maintenance account
            if (!$companyAcc) {
                throw new \Exception('Bike Maintenance Company Account Not Found');
            }

            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $maintenance->maintenance_date,
                'reference_id' => $maintenance->id,
                'reference_type' => 'Bike Maintenance',
                'account_id' => $companyAcc->id,
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
            'account_id' => $maintenance->garage->account_id,
            'credit' => $data['total'],
            'debit' => 0,
            'billing_month' => $maintenance->billing_month,
            'narration' => $data['description'],
        ]);
        if($data['vat_amount'] > 0){
            $vatAcc = Accounts::select('id') ->find(1023); // VAT on purchase
            if (!$companyAcc) {
                throw new \Exception('VAT on purchase Account Not Found');
            }

            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $maintenance->maintenance_date,
                'reference_id' => $maintenance->id,
                'reference_type' => 'Bike Maintenance',
                'account_id' => $vatAcc->id,
                'credit' => 0,
                'debit' => $data['vat_amount'],
                'billing_month' => $maintenance->billing_month,
                'narration' => $data['description'],
            ]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BikeMaintenance $bikeMaintenance)
    {
        DB::beginTransaction();
        try{
            BikeMaintenanceItem::where('bike_maintenance_id', $bikeMaintenance->id)->delete();
            Transactions::where('reference_type', 'Bike Maintenance')
                        ->where('reference_id', $bikeMaintenance->id)
                        ->delete();

            $path = $bikeMaintenance->attachment ;
            $bike = $bikeMaintenance->bike;
            $prev = $bikeMaintenance->previous_km;
            $maintenance = BikeMaintenance::where('bike_id',$bike->id)
                        ->where('id', '!=', $bikeMaintenance->id)
                        ->orderby('maintenance_date','desc')
                        ->first();
            if($maintenance){
                $bike->update([
                'previous_km' => $maintenance->current_km,
                'maintenance_km' => $maintenance->maintenance_km
                ]);
            }else{
                $bike->update([
                'previous_km' => null,
                ]);
            }
            $bikeMaintenance->delete();
            if($path)
                Storage::delete($path);
            
            DB::commit();
            return response()->json(['message' => 'Maintenance Record Deleted Successfully'],200);
        }catch(\Exception $e){
            DB::rollBack();
            \Log::error('error occured while deleteing maintenance record',['error' => $e->getMessage() , 'trace: ' => $e->getTrace()]);
            return response()->json(['message' => 'Error: '.$e->getMessage()],500);
        }

    }

    private function validateRequest(Request $request){

        return $request->validate([
            'bike_id' => 'required|exists:bikes,id',
            'rider_id' => 'nullable|exists:riders,id',
            'garage_id' => 'required|exists:garages,id',
            'maintenance_date' => 'required|date|before:tomorrow',
            'previous_km' => 'nullable|numeric|min:1',
            'current_km' => 'required|numeric|min:0',
            'maintenance_km' => 'required|numeric|min:100',
            'overdue_cost_per_km' => 'nullable|numeric|min:0',
            'overdue_km' => 'nullable|numeric|min:0',
            'overdue_cost' => 'nullable|numeric|min:0',
            'overdue_paidby' => 'nullable|in:Company,Rider',
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
            'charge_to.*' => 'required|in:Company,Rider',
            'total_cost' => 'nullable|numeric|min:0',
        ],[
            'bike_id.required' => 'Please Select A Bike',
            'item_id.*.required' => 'Item Field Cannot Be Empty',
            'quantity.*.required' => 'Quantity Field Cannot be Empty',
            'rate.*.required' => 'Rate Field Cannot Be Empty',
            'charge_to.*.required' => 'You Must Choose Who will be Charged for every Item'
        ]);
    }

    private function stats($maintenances){

        return [
            'active' => Bikes::where('status',1)->count(),
            'total'  => $maintenances->count(),
            'current' => $maintenances->whereBetween('maintenance_date', [
                                Carbon::now()->startOfMonth(),
                                Carbon::now()->endOfMonth()
                            ])->count(),
            'total_overdue' => $maintenances->where('overdue_km', '>', 0)->count(),
            'current_overdue' => $maintenances->whereBetween('maintenance_date', [
                                Carbon::now()->startOfMonth(),
                                Carbon::now()->endOfMonth()
                            ])
                            ->where('overdue_km', '>', 0)
                            ->count(),
            'avg' => $maintenances
                    ->where('overdue_km', '>', 0)
                    ->groupBy(fn($item) => $item->maintenance_date->format('Y-m'))
                    ->map(fn($group) => $group->count())
                    ->avg() ?? 0,
            'overdue_cost' => $maintenances->where('overdue_km', '>', 0)->sum(fn($m)=>  $m->overdue_km*$m->overdue_cost_per_km),
            'overdue_charged' => $maintenances->where('overdue_km', '>', 0)->where('overdue_paidby','Rider')->sum(fn($m)=>  $m->overdue_km*$m->overdue_cost_per_km),
            'maint_cost' => $maintenances->sum('total_cost'),

        ];
    }

    private function validateFinancials(Request $request): void
    {
        $itemsTotal = 0;

        foreach ($request->item_id ?? [] as $index => $itemId) {

            $qty      = (float) ($request->quantity[$index] ?? 0);
            $rate     = (float) ($request->rate[$index] ?? 0);
            $discount = (float) ($request->discount[$index] ?? 0);
            $vat      = (float) ($request->vat[$index] ?? 0);
            $frontendTotal = (float) ($request->item_total[$index] ?? 0);

            $line = $qty * $rate;
            $line -= $discount;

            $vatAmount = $line * ($vat / 100);
            $calculated = round($line + $vatAmount, 2);

            if (round($frontendTotal, 2) !== $calculated) {
                throw new \Exception("Invalid calculation for item at row " . ($index + 1));
            }

            $itemsTotal += $calculated;
        }

        if (round($itemsTotal, 2) !== round((float) $request->total_cost, 2)) {
            throw new \Exception("Maintenance total mismatch.");
        }

        // Validate overdue
        $overdue = (float) $request->overdue_km;
        $perKm   = (float) $request->overdue_cost_per_km;
        $overdueCalculated = round($overdue * $perKm, 2);

        if (round((float)$request->overdue_cost, 2) !== $overdueCalculated) {
            throw new \Exception("Overdue cost calculation mismatch.");
        }

    }

}
