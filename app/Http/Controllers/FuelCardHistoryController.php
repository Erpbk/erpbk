<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FuelCards;
use App\Models\FuelCardHistory;
use Flash;
use Illuminate\Support\Facades\DB;

class FuelCardHistoryController extends Controller
{

    public function assign(Request $request, $id)
    {
        if(!auth()->user()->hasPermissionTo('fuel_assign')) {
            abort(403, 'Unauthorized action.');
        }

        if($request->isMethod('get')){

            $fuelCard = FuelCards::find($id);
            if (!$fuelCard) {
                return response()->json(['message' => 'Fuel Card Not Found'], 404);
            }

            return view('fuel_cards.assign', compact('fuelCard'));
        }

        $fuelCard = FuelCards::find($id);
        if (!$fuelCard) {
            return response()->json(['message' => 'Fuel Card Not Found'], 404);
        }

        $request->validate([
            'assigned_to' => 'required|integer|exists:riders,id',
            'assign_date' => 'required|date',
            'note'=> 'nullable|string',
        ]);
        DB::beginTransaction();
        try {
            FuelCardHistory::create([
                'card_id' => $fuelCard->id,
                'assigned_to' => $request['assigned_to'],
                'assigned_by' => auth()->id(),
                'assign_date' => $request['assign_date'],
                'note'=> $request['note'],
            ]);
            $fuelCard->assigned_to = $request['assigned_to'];
            $fuelCard->status = 'Active';
            $fuelCard->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['message' => 'An Error Occurred: ' . $e->getMessage()], 500);
            }
            Flash::error('Error Occurred: ' . $e->getMessage());
            return redirect()->back();
        }

        if ($request->ajax()) {
            return response()->json(['message' => 'Fuel Card Assigned Successfully']);
        }
        Flash::success('Fuel Card Assigned Successfully');
        return redirect()->back();
    }

    public function return(Request $request, $id)
    {
        if(!auth()->user()->hasPermissionTo('fuel_assign')) {
            abort(403, 'Unauthorized action.');
        }

        if($request->isMethod('get')){

            $fuelCard = FuelCards::find($id);
            if (!$fuelCard) {
                return response()->json(['message' => 'Fuel Card Not Found'], 404);
            }
            return view('fuel_cards.return', compact('fuelCard'));
        }

        $fuelCard = FuelCards::find($id);
        if (!$fuelCard) {
            return response()->json(['message' => 'Fuel Card Not Found'], 404);
        }

        $request->validate([
            'return_date' => 'required|date',
            'note'=> 'nullable|string',
        ]);
        DB::beginTransaction();
        try {

            $history = FuelCardHistory::where('card_id', $fuelCard->id)
                ->whereNull('return_date')
                ->orderByDesc('id')
                ->first();

            if (!$history) {
                throw new \Exception('No active assignment found for this fuel card.');
            }
            $history->return_date = $request['return_date'];
            $history->note = $request['note'];
            $history->returned_by = auth()->id();
            $history->save();

            $fuelCard->assigned_to = null;
            $fuelCard->status = 'Inactive';
            $fuelCard->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['message' => 'An Error Occurred: ' . $e->getMessage()], 500);
            }
            Flash::error('Error Occurred: ' . $e->getMessage());
            return redirect()->back();
        }

        if ($request->ajax()) {
            return response()->json(['message' => 'Fuel Card Returned Successfully']);
        }
        Flash::success('Fuel Card Returned Successfully');
        return redirect()->back();
    }
}
