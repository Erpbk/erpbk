<?php

namespace App\Http\Controllers;

use App\Models\BikeMaintenance;
use App\Models\BikeMaintenanceItem;
use Illuminate\Http\Request;
use App\Models\Bikes;
use App\Models\Items;
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
        $stats = [
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
        return view('bike-maintenance.index',compact('maintenances','stats'));
    }

    public function missingMaintenanceData(Request $request){
        $query = Bikes::query()->where('status','1');
        $query->where(function($q) {
            $q->whereNull('current_km')
              ->orWhereNull('previous_km')
              ->orWhereNull('maintenance_km');
        });
        $query->orderBy('id','asc');
        $data = $query->get();
        $stats = $stats = Bikes::where('status',1)->withMaintenanceStats()->first();
        return view('bike-maintenance.missing-table', compact('data', 'stats'));
    }

    public function OverdueForMaintenance(Request $request){
        $query = Bikes::query()->where('status','1');
        $query->where(function($q) {
            $q->whereNotNull('current_km')
              ->whereNotNull('previous_km')
              ->whereNotNull('maintenance_km')
              ->whereRaw('(current_km - previous_km) > maintenance_km');
        });
        $data = $query->orderBy('id','asc')->get();
        $maintenances = BikeMaintenance::with(['bike', 'rider', 'maintenanceItems'])
                        ->orderBy('maintenance_date', 'desc')
                        ->get();
        $stats = $stats = Bikes::where('status',1)->withMaintenanceStats()->first();
        return view('bike-maintenance.overdue-table', compact('maintenances', 'stats'));
    }

    public function dueForMaintenance(Request $request){
        $query = Bikes::query()->where('status','1');
        $query->where(function($q) {
            $q->whereNotNull('current_km')
              ->whereNotNull('previous_km')
              ->whereNotNull('maintenance_km')
              ->whereRaw('(current_km - previous_km) >= (maintenance_km * 0.8)')
              ->whereRaw('(current_km - previous_km) <= maintenance_km');
        });
        $data = $query->orderBy('id','asc')->get();
        $stats = $stats = Bikes::where('status',1)->withMaintenanceStats()->first();
        return view('bike-maintenance.due-table', compact('data', 'stats'));
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
        $path = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = 'maintenance_'.time().'.'.$file->extension();
            $path = $file->storeAs('bike/'.$bike->id.'/', $filename);
            $validated['attachment'] = $path;
        }
        DB::beginTransaction();
        try{
            $bike->update([
                'previous_km' => $validated['current_km'],
                'current_km' => null,
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
                        'total_amount'       => $request->item_total[$index] ?? 0,
                        'charge_to'          => $request->charge_to[$index],
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];
                }
                BikeMaintenanceItem::insert($rows);
            }
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
            'maintenanceItems',
            'createdBy'
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
                        'total_amount'       => $request->item_total[$index] ?? 0,
                        'charge_to'          => $request->charge_to[$index],
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];
                }
                BikeMaintenanceItem::insert($rows);
            }
            DB::commit();
            if($oldpath)
                if($path)
                    Storage::delete($oldpath);
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BikeMaintenance $bikeMaintenance)
    {
        DB::beginTransaction();
        try{
            BikeMaintenanceItem::where('bike_maintenance_id', $bikeMaintenance->id)->delete();
            $path = $bikeMaintenance->attachment ;
            $bike = $bikeMaintenance->bike;
            $prev = $bikeMaintenance->previous_km;
            $maintenance = BikeMaintenance::where('bike_id',$bike->id)->orderby('maintenance_date','desc')->get();
            if(!$maintenance === null){
                $bike->update([
                'previous_km' => $maintenance->current_km,
                'current_km' => 0,
                'maintenance_km' => $maintenance->manitenance_km
                ]);
            }else{
                $bike->update([
                'previous_km' => 0,
                'current_km' => 0,
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
            'maintenance_date' => 'required|date|before:tomorrow',
            'previous_km' => 'nullable|numeric|min:1',
            'current_km' => 'required|numeric|min:0',
            'maintenance_km' => 'required|numeric|min:100',
            'overdue_cost_per_km' => 'nullable|numeric|min:0',
            'overdue_km' => 'nullable|numeric|min:0',
            'overdue_cost' => 'nullable|numeric|min:0',
            'overdue_paidby' => 'nullable|in:Company,Rider',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
            'item_id' => 'nullable|array',
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
}
