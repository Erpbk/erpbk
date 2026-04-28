<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FuelCards;
use App\Models\FuelCardHistory;
use Flash;
use Illuminate\Support\Facades\DB;
use App\Traits\GlobalPagination;

class FuelCardController extends Controller
{
    use GlobalPagination;
    
     public function index(Request $request)
    {

        if (!auth()->user()->hasPermissionTo('fuel_view')) {
            abort(403, 'Unauthorized action.');
        }
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = FuelCards::query()
            ->orderBy('id', 'asc')
            ->with('rider');
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

        $stats['total'] = $query->count();
        $stats['active'] = (clone $query)->where('status', 'Active')->count();
        $stats['inactive'] = (clone $query)->where('status', 'Inactive')->count();
        $stats['inactive'] += (clone $query)->where('status', null)->count();

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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'card_number' => 'required|string|min:16|unique:fuel_cards,card_number',
            'card_type'=> 'nullable|string|max:255',
            'assigned_to'=> 'nullable|integer|exists:riders,id',
            'status' =>'nullable|string',
            'assign_date' => 'nullable|date',
            ]);

        $request['created_by'] = auth()->id();
        if(($request['assigned_to'])){
            $request['status'] = 'Active';
            $rider = \App\Models\Riders::find($request->assigned_to);
            $request['branch_id'] = $rider->branch_id;
            $request['bike_no'] = $rider->bikes->id;
        }
        else
            $request['status'] = 'Inactive';
        DB::beginTransaction();
        try{
            $card = FuelCards::create($request->all());
            if($request['assigned_to']){
                FuelCardHistory::create([
                    'card_id' => $card->id,
                    'assigned_to' => $card->assigned_to,
                    'assigned_by' => auth()->id(),
                    'assign_date' => $request['assign_date'] ?? now(),
                    'note'=> 'Initial Assignment',
                ]);
            }
            DB::commit();
        }catch(\Exception $e){
            DB::rollBack();
            if($request->ajax()){
                return response()->json(['message' => 'An Error Occurred'. $e->getMessage() . $e->getTraceAsString()],500);
            }
            Flash::error('Error Occurred: '.$e->getMessage());
            return redirect()->back();
        }
        
        if($request->ajax()) {
            return response()->json(['message' => 'Fuel Card Added Succesfully', 'reload' => true]);
        }
        Flash::success('Fuel Card Added Successfully');
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show($company_slug, string $id)
    {
        \Log::info("Showing fuel card with ID: $id");
        $card = FuelCards::find($id);
        $histories = $card->histories()->orderByDesc('id')->get();
        $card->load('branch');
        return view('fuel_cards.show', compact('card', 'histories'));
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
        $this->validate($request, [
            'card_number' => 'required|string|min:16|unique:fuel_cards,card_number,'.$id,
            'card_type'=> 'nullable|string|max:255',
            ]);

        $card = FuelCards::find($id);
        if(!$card){
            return response()->json(['message' => 'Card Not Found']);
        }
        $card->fill($request->all());
        if($card->isClean()){
            if($request->ajax()){
                return response()->json(['message' => 'No Changes Detected To update'],200);
            }
            Flash::info('No Changes Detected');
            return redirect()->back();
        }
        $request['updated_by'] = auth()->id();
        try{
            $card->update($request->all());
        }catch(\Exception $e){
            if($request->ajax()){
                return response()->json(['messahe' => 'An Error Occurred'. $e->getMessage()],500);
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

    public function import(Request $request){
        return redirect()->back();
    }

    public function export(Request $request){
        return redirect()->back();
    }
}
