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

class BikeMaintenanceController extends Controller
{
    use GlobalPagination;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
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
        $stats = $stats = Bikes::where('status',1)->withMaintenanceStats()->first();
        return view('bike-maintenance.overdue-table', compact('data', 'stats'));
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
        $validated = $request->validate([
            'bike_id' => 'required|exists:bikes,id',
            'rider_id' => 'nullable|exists:riders,id',
            'maintenance_date' => 'required|date|before:tomorrow',
            'previous_km' => 'required|numeric|min:1',
            'current_km' => 'nullable|numeric',
            'maintenance_at' => 'required|numeric|min:0|gt:previous_km',
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
            'total_cost' => 'nullable|numeric|min:0',
        ],[
            'bike_id.required' => 'Please Select A Bike',
            'item_id.*.required' => 'Item Field Cannot Be Empty',
            'quantity.*.required' => 'Quantity Field Cannot be Empty',
            'rate.*.required' => 'Rate Field Cannot Be Empty',
        ]);

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
            if($validated['maintenance_at'] > ($validated['current_km']??0))
                $bike->update([
                    'previous_km' => $validated['maintenance_at'],
                    'current_km' => $validated['maintenance_at'],
                    'maintenance_km' => $validated['maintenance_km']
                    ]);
            else
                $bike->update([
                    'previous_km' => $validated['maintenance_at'],
                    'current_km' => $validated['current_km'],
                    'maintenance_km' => $validated['maintenance_km']
                    ]);
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
    public function edit(Bikes $bike)
    {
        return view('bike-maintenance.missingData_form', compact('bike'));
    }

    /**
     * Update the MAintenance Fields in Bikes Table.
     */
    public function update(Request $request, Bikes $bike)
    {
        $this->validate($request,[
            'previous_km'    => 'required|numeric|min:1',
            'current_km'     => 'required|numeric|min:1',
            'maintenance_km' => 'required|numeric|min:100'
        ]);

        $request['updated_by'] = Auth()->id();
        $bike->fill($request->all());
        if($bike->isClean()){
            return  response()->json(['message' => 'No new data entered to update.'],200);
        }
        DB::beginTransaction();
        try{
            $bike->update($request->all());
            DB::commit();
            return response()->json(['message' => 'Data Updated Successfully.', 'reload' => true], 200);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => 'An Error Occurred'. $e->getMessage()],500);
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
            if($maintenance){
                $bike->update([
                'previous_km' => $maintenance->maintenance_at,
                'current_km' => $maintenance->current_km,
                'maintenance_km' => $maintenance->manitenance_km
                ]);
            }else{
                $bike->update([
                'previous_km' => $prev,
                'current_km' => $prev,
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
            return response()->json(['message' => 'Error: '.$e->getMessage()]);
        }

    }
}
