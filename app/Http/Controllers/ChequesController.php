<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cheques;
use App\Models\Banks;

class ChequesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accountId = request()->input('id') ?? null;
        if($accountId){
            $bank = Banks::find($accountId);
            return view('cheques.create',compact('bank'));
        }
        else
            return view('cheques.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cheque = Cheques::findOrFail($id);
        return view('cheques.show', compact('cheque'));
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function updateStatus(Request $request, Cheques $cheque)
    {
        $request->validate([
            'status' => 'required|in:Issued,Cleared,Returned,Stop Payment,Lost',
            'cleared_date' => 'nullable|date',
            'returned_date' => 'nullable|date',
            'stop_payment_date' => 'nullable|date',
            'return_reason' => 'nullable|string|max:255'
        ]);
        
        try {
            $cheque->status = $request->status;
            
            // Update dates based on status
            if ($request->status === 'Cleared' && $request->filled('cleared_date')) {
                $cheque->cleared_date = $request->cleared_date;
            } elseif ($request->status === 'Returned' && $request->filled('returned_date')) {
                $cheque->returned_date = $request->returned_date;
                $cheque->return_reason = $request->return_reason;
            } elseif ($request->status === 'Stop Payment' && $request->filled('stop_payment_date')) {
                $cheque->stop_payment_date = $request->stop_payment_date;
            }
            
            $cheque->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Cheque status updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cheque status: ' . $e->getMessage()
            ], 500);
        }
    }
}
