<?php

namespace App\Http\Controllers;

use App\Models\rider_hiring;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Illuminate\Support\Facades\Auth;
use Flash;
use DB;

class riderhiringController extends Controller
{
    use GlobalPagination;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = rider_hiring::query()->orderBy('id', 'desc');
        if ($request->filled('rider_id')) {
            $query->where('rider_id', 'like', '%' . $request->rider_id . '%');
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('fleet_sup')) {
            $query->where('fleet_sup', $request->fleet_sup);
        }
        if ($request->filled('nationality')) {
            $query->where('nationality', $request->nationality);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        if ($request->ajax()) {
            try {
                $tableData = view('riders.hiring_table', compact('data'))->render();
                $paginationLinks = view('pagination', ['paginator' => $data])->render();
                return response()->json([
                    'tableData' => $tableData,
                    'paginationLinks' => $paginationLinks,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'error' => 'View render failed',
                    'message' => $e->getMessage()
                ], 500);
            }
        }
        return view('riders.hiring_index', compact('data'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userid = Auth::user()->id;
        // Check for duplicates
        $exists = rider_hiring::where('contact', $request->contact)
            ->orWhere('whatsapp_contact', $request->whatsapp_contact)
            ->exists();

        if ($exists) {
            Flash::error('Entry with this phone or WhatsApp number already exists.');
            return redirect()->back();
        }

        // Create the record
        rider_hiring::create([
            'name' => $request->name,
            'contact' => $request->contact,
            'whatsapp_contact' => $request->whatsapp_contact,
            'fleet_sup' => $request->fleet_sup,
            'stay' => $request->stay,
            'nationality' => $request->nationality,
            'detail' => $request->detail,
            'created_by' => $userid,
        ]);

        Flash::success('Rider Lead submitted successfully.');
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $userid = Auth::user()->id;
        // Check for duplicates
        $exists = rider_hiring::where('contact', $request->contact)
            ->orWhere('whatsapp_contact', $request->whatsapp_contact)
            ->exists();

        if ($exists) {
            Flash::error('Entry with this phone or WhatsApp number already exists.');
            return redirect()->back();
        }

        $rider = rider_hiring::findOrFail($id);

        $rider->update([
            'name' => $request->name,
            'contact' => $request->updatecontact,
            'whatsapp_contact' => $request->updatewhatsapp_contact,
            'fleet_sup' => $request->fleet_sup,
            'stay' => $request->stay,
            'nationality' => $request->nationality,
            'detail' => $request->detail,
            'updated_by' => $userid,
        ]);

        Flash::success('Rider Lead Updated Successfully.');
        return redirect()->back();
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $lead = rider_hiring::find($id);
        if (!$lead) {
            return redirect()->back()->with('error', 'Record not found.');
        }
        $lead->delete();
        Flash::success('Rider lead deleted successfully.');
        return redirect()->route('riderleads.index');
    }
}
