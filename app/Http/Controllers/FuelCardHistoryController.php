<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FuelCards;
use App\Models\FuelCardHistory;
use App\Support\PublicStorageDisk;
use Flash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FuelCardHistoryController extends Controller
{

    public function assign(Request $request, $company_slug, $id)
    {
        if (!user_can('fuel_assign')) {
            abort(403, 'Unauthorized action.');
        }

        $fuelCard = FuelCards::find($id);
        if (!$fuelCard) {
            return response()->json(['message' => 'Fuel Card Not Found'], 404);
        }

        // A card holds at most one rider: it must be returned before reassignment.
        if ($fuelCard->assigned_to) {
            $message = 'This fuel card is already assigned to ' . ($fuelCard->rider?->name ?? 'a rider')
                . '. Return it before assigning it to someone else.';

            if ($request->isMethod('get') || $request->ajax()) {
                return response()->json(['message' => $message], 422);
            }
            Flash::error($message);
            return redirect()->back();
        }

        // Deactivated and lost cards are out of service.
        if ($fuelCard->isDeactivated() || $fuelCard->isLost()) {
            $message = $fuelCard->isLost()
                ? 'This fuel card is marked as lost and cannot be assigned.'
                : 'This fuel card is deactivated and cannot be assigned. Activate it first.';

            if ($request->isMethod('get') || $request->ajax()) {
                return response()->json(['message' => $message], 422);
            }
            Flash::error($message);
            return redirect()->back();
        }

        if ($request->isMethod('get')) {
            $availableRiders = \App\Models\Riders::where('status', 1)
                ->whereNotIn('id', FuelCards::whereNotNull('assigned_to')->pluck('assigned_to'))
                ->with('bikes')
                ->orderBy('name')
                ->get();

            return view('fuel_cards.assign', compact('fuelCard', 'availableRiders'));
        }

        $request->validate([
            // A rider holds at most one card, so no other card may point at them.
            'assigned_to' => [
                'required',
                'integer',
                'exists:riders,id',
                Rule::unique('fuel_cards', 'assigned_to')->ignore($fuelCard->id),
            ],
            'assign_date' => 'required|date',
            'note' => 'nullable|string',
        ], [
            'assigned_to.unique' => 'This rider already holds a fuel card. Return that card before assigning another one.',
        ]);
        DB::beginTransaction();
        try {
            FuelCardHistory::create([
                'card_id' => $fuelCard->id,
                'assigned_to' => $request['assigned_to'],
                'assigned_by' => auth()->id(),
                'assign_date' => $request['assign_date'],
                'note' => $request['note'],
            ]);
            $rider = \App\Models\Riders::find($request['assigned_to']);
            $fuelCard->assigned_to = $request['assigned_to'];
            $fuelCard->branch_id = $rider->branch_id;
            $fuelCard->bike_no = $rider->bikes->plate ?? null;
            $fuelCard->status = FuelCards::STATUS_ACTIVE;
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

    public function return(Request $request, $company_slug, $id)
    {
        if (!user_can('fuel_assign')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->isMethod('get')) {

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
            'note' => 'nullable|string',
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

            // A returned card goes back to the office, ready to assign again.
            $fuelCard->assigned_to = null;
            $fuelCard->status = FuelCards::STATUS_IN_OFFICE;
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

    public function updateAssignment(Request $request, $company_slug, $id)
    {
        if (!user_can('fuel_assign')) {
            abort(403, 'Unauthorized action.');
        }

        $fuelCard = FuelCards::find($id);
        if (!$fuelCard) {
            return response()->json(['message' => 'Card Not Found']);
        }

        if ($request->isMethod('get')) {
            return view('fuel_cards.update_assignment', compact('fuelCard'));
        } else {
            $request->validate([
                'attachment' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);
            $file = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = PublicStorageDisk::storeUploadedFile($file, 'fuelcards', $filename);
            $fuelCard->attachment = $path;
            $fuelCard->bike_no = $fuelCard->rider->bikes->plate;
            $fuelCard->save();
            if ($request->ajax()) {
                return response()->json(['message' => 'Action Performed Successfully', 'reload' => true]);
            }
            Flash::success('Action Performed Successfully');
            return redirect()->back();
        }
    }
}
