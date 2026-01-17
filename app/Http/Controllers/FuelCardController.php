<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FuelCards;
use App\Traits\GlobalPagination;

class FuelCardController extends Controller
{
    use GlobalPagination;
    
     public function index(Request $request)
    {

        if (!auth()->user()->hasPermissionTo('sim_view')) {
            abort(403, 'Unauthorized action.');
        }
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = FuelCards::query()
            ->orderBy('id', 'asc');
        if ($request->has('card_number') && !empty($request->card_number)) {
            $query->where('card_number', 'like', '%' . $request->card_numbernumber . '%');
        }
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status',  $request->status );
        }
        if ($request->has('assigned_to') && !empty($request->assigned_to)) {
            $query->where('assigned_to', $request->assigned_to);
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
            ]);
        }

        return view('fuel_cards.index', [
            'data' => $data,
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
            'card_number' => 'required|string|min:19|unique:fuel_cards,card_number',
            'card_type'=> 'nullable|string|max:255',
            'assigned_to'=> 'nullable|integer',
            'status' =>'nullable|string',
            ]);

        $request['created_by'] = auth()->id();

        try{
            FuelCards::create($request->all());
        }catch(\Exception $e){
            if($request->ajax()){
                return response()->json(['messahe' => 'An Error Occurred'. $e->getMessage() . $e->getTraceAsString()],500);
            }
            Flash::error('Error Occurred: '.$e->getMessage());
            return redirect()->back();
        }
        
        if($request->ajax()) {
            return response()->json(['message' => 'Fuel Card Added Succesfully']);
        }
        Flash::success('Fuel Card Added Successfully');
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $fuelCard = FuelCards::find($id);
        return view('fuel_cards.show')->with('fuelCard', $fuelCard);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $fuelCard = FuelCards::find($id);
        return view('fuel_cards.edit')->with('fuelCard', $fuelCard);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->validate($request, [
            'card_number' => 'required|string|min:19|unique:fuel_cards,card_number'.$id,
            'card_type'=> 'nullable|string|max:255',
            'assigned_to'=> 'nullable|integer',
            'status' =>'nullable|string',
            ]);

        $request['updated_by'] = auth()->id();

        try{
            FuelCards::create($request->all());
        }catch(\Exception $e){
            if($request->ajax()){
                return response()->json(['messahe' => 'An Error Occurred'. $e->getMessage()],500);
            }
            Flash::error('Error Occurred: '.$e->getMessage());
            return redirect()->back();
        }
        
        if($request->ajax()) {
            return response()->json(['message' => 'Fuel Card Updated Succesfully']);
        }
        Flash::success('Fuel Card Updated Successfully');
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $fuelCard = FuelCards::find($id);
        if(!$fuelCard){
            return response()->json(['message'=> 'Fuel Card Not Found'],404);
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
