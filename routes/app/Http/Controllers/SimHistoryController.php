<?php

namespace App\Http\Controllers;

use App\Models\SimHistory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SimHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function index(Request $request, $sim_id = null)
    {
        // Create dummy SIM histories for testing
        $simHistories = SimHistory::where('sim_id', $sim_id)->orderByDesc('id')->get();

        if($simHistories->isEmpty()) {
            // If no histories found, create some dummy data
            for ($i = 1; $i <= 5; $i++) {
                SimHistory::create([
                    'sim_id' => 23,
                    'change_date' => Carbon::now()->subDays($i),
                    'details' => "Dummy history entry $i for SIM ID 23",
                ]);
            }
            // Retrieve the newly created histories
            $simHistories = SimHistory::where('sim_id', 23)->orderByDesc('id')->get();
        }

        return view('sim_histories.index', compact('simHistories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
    public function show(SimHistory $simHistory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SimHistory $simHistory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SimHistory $simHistory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SimHistory $simHistory)
    {
        //
    }
}
