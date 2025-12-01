<?php

namespace App\Http\Controllers;
use App\Helpers\IConstants;
use App\Helpers\Common;
use App\Models\Calculations;
use App\Models\Services;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;

class HomeController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('auth');
  }

  /**
   * Show the application dashboard.
   *
   * @return \Illuminate\Contracts\Support\Renderable
   */
  public function index()
  {
    $pieData = [
        'labels' => ["Vendors", "Customers", "Riders", "Bikes", "Sims"],
        'data' => [
            \App\Models\Vendors::count(),
            \App\Models\Customers::count(),
            \App\Models\Riders::count(),
            \App\Models\Bikes::count(),
            \App\Models\Sims::count()
        ],
        'colors' => ["#706c7e", "#5c98e5", "#0760d3", "#211c1d", "#94baec"]
    ];

    // LINE CHART: x from 0 to 10, y = sin(x)
    $lineData = ['x' => [], 'y' => []];
    for ($x = 0; $x <= 10; $x += 0.5) {
        $lineData['x'][] = $x;
        $lineData['y'][] = sin($x);
    }

    return view('content.dashboard', compact('pieData', 'lineData'));

  }

  public function settings(Request $request)
  {

    /*   if (!auth()->user()->hasPermissionTo('setting_view')) {
        abort(403, 'Unauthorized action.');
      } */
    /*    if (\Gate::check("isUser", \Auth::user())) {
         abort(404);
       } */

    if ($request->post('settings')) {

      foreach ($request->post('settings') as $key => $value) {
        //echo $key.'-'.$value;
        Settings::updateOrCreate(['name' => $key], ['name' => $key, 'value' => $value]);
        session()->flash('success', 'Settings updated successfully.');

      }
    }
    $settings = Settings::pluck('value', 'name');
    return view('content.settings', compact('settings'));
  }




}
