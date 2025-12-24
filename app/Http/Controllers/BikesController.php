<?php

namespace App\Http\Controllers;

use App\DataTables\BikeHistoryDataTable;
use App\DataTables\BikesDataTable;
use App\DataTables\BikesHistoryDataTable;
use App\Http\Requests\CreateBikesRequest;
use App\Http\Requests\UpdateBikesRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\BikeHistory;
use App\Models\Bikes;
use App\Models\Riders;
use App\Models\VehicleModels;
use App\Repositories\BikesRepository;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Flash;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomizableBikeExport;
use App\Imports\ImportBikes;
use Maatwebsite\Excel\Concerns\FromCollection;

class BikesController extends AppBaseController
{
  use GlobalPagination;
  /** @var BikesRepository $bikesRepository*/
  private $bikesRepository;

  public function __construct(BikesRepository $bikesRepo)
  {
    $this->bikesRepository = $bikesRepo;
  }

  /**
   * Display a listing of the Bikes.
   */
  public function index(Request $request)
  {

    if (!auth()->user()->hasPermissionTo('bike_view')) {
      abort(403, 'Unauthorized action.');
    }
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Bikes::query()
      ->orderBy('bike_code', 'desc');
    if ($request->has('bike_code') && !empty($request->bike_code)) {
      $query->where('bike_code', 'like', '%' . $request->bike_code . '%');
    }
    if ($request->has('plate') && !empty($request->plate)) {
      $query->where('plate', 'like', '%' . $request->plate . '%');
    }
    if ($request->has('rider_id') && !empty($request->rider_id)) {
      $query->where('rider_id', $request->rider_id);
    }
    if ($request->has('rider') && !empty($request->rider)) {
      $query->where('rider_id', $request->rider);
    }
    if ($request->has('company') && !empty($request->company)) {
      $query->where('company', $request->company);
    }
    if ($request->has('emirates') && !empty($request->emirates)) {
      $query->where('emirates', $request->emirates);
    }
    if ($request->filled('expiry_date_from')) {
      $fromDate = \Carbon\Carbon::createFromFormat('Y-d-m', $request->expiry_date_from);
      $query->where('expiry_date', '>=', $fromDate);
    }

    if ($request->filled('expiry_date_to')) {
      $toDate = \Carbon\Carbon::createFromFormat('Y-d-m', $request->expiry_date_to);
      $query->where('expiry_date', '<=', $toDate);
    }

    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
    }

    // Add warehouse filter
    if ($request->has('warehouse') && !empty($request->warehouse)) {
      $query->where('warehouse', $request->warehouse);
    }

    // Add quick search functionality
    if ($request->filled('quick_search')) {
      $search = $request->input('quick_search');

      $query->leftJoin('riders', 'bikes.rider_id', '=', 'riders.id')
        ->leftJoin('leasing_companies', 'bikes.company', '=', 'leasing_companies.id')
        ->leftJoin('customers', 'bikes.customer_id', '=', 'customers.id')
        ->where(function ($q) use ($search) {
          $q->where('bikes.plate', 'like', "%{$search}%")
            ->orWhere('bikes.bike_code', 'like', "%{$search}%")
            ->orWhere('bikes.chassis_number', 'like', "%{$search}%")
            ->orWhere('bikes.color', 'like', "%{$search}%")
            ->orWhere('bikes.model', 'like', "%{$search}%")
            ->orWhere('bikes.emirates', 'like', "%{$search}%")
            ->orWhere('bikes.warehouse', 'like', "%{$search}%")
            ->orWhere('riders.name', 'like', "%{$search}%")
            ->orWhere('riders.rider_id', 'like', "%{$search}%")
            ->orWhere('leasing_companies.name', 'like', "%{$search}%")
            ->orWhere('customers.name', 'like', "%{$search}%");
        });
      $query->select('bikes.*');
    }

    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);
    if ($request->ajax()) {
      $tableData = view('bikes.table', [
        'data' => $data,
      ])->render();
      $paginationLinks = $data->links('components.global-pagination')->render();
      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
      ]);
    }
    // Get table columns configuration
    $tableColumns = $this->getTableColumns();

    return view('bikes.index', [
      'data' => $data,
      'tableColumns' => $tableColumns,
    ]);
  }

  /**
   * Get table columns configuration for bikes
   */
  private function getTableColumns()
  {
    // Get all columns from bikes table
    $filteredColumns = Schema::getColumnListing('bikes');

    // Columns to exclude
    $exclude = ['id', 'vehicle_type', 'created_at', 'updated_at', 'notes', 'traffic_file_number', 'registration_date', 'insurance_expiry', 'insurance_co', 'policy_no', 'contract_number'];

    // Final filtered columns
    $dbColumns = array_diff($filteredColumns, $exclude);
    $preferredOrder = [
      'bike_code',
      'plate',
      'rider_id',
      // Rider name is a computed column and should always be available
      'rider_name', // Ensure rider_name is included and controllable
      'emirates',
      'company',
      'customer_id',
      'warehouse',
      'status',
      'expiry_date',
      'created_by',
      'updated_by',
    ];

    $columns = [];
    $added = [];
    $makeTitle = function ($key) {
      return ucwords(str_replace('_', ' ', $key));
    };

    // Add preferred DB columns first
    foreach ($preferredOrder as $key) {
      // Add rider_name always, even if not in DB
      if ($key === 'rider_name') {
        $columns[] = ['data' => 'rider_name', 'title' => $makeTitle('rider_name')];
        $added['rider_name'] = true;
      } elseif (in_array($key, $dbColumns)) {
        $columns[] = ['data' => $key, 'title' => $makeTitle($key)];
        $added[$key] = true;
      }
    }

    // Add remaining DB columns
    foreach ($dbColumns as $key) {
      if (empty($added[$key])) {
        $columns[] = ['data' => $key, 'title' => $makeTitle($key)];
      }
    }

    // Append special/computed columns used in UI
    $columns = array_merge($columns, [
      ['data' => 'action', 'title' => 'Actions'],
      // Keep last two fixed utility columns for search and control icons
      ['data' => 'search', 'title' => 'Search'],
      ['data' => 'control', 'title' => 'Control'],
    ]);

    return $columns;
  }

  /**
   * Handle AJAX filter requests for bikes listing
   */
  public function filterAjax(Request $request)
  {
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

    $query = Bikes::query()
      ->orderBy('bike_code', 'desc');

    if ($request->has('bike_code') && !empty($request->bike_code)) {
      $query->where('bike_code', 'like', '%' . $request->bike_code . '%');
    }
    if ($request->has('plate') && !empty($request->plate)) {
      $query->where('plate', 'like', '%' . $request->plate . '%');
    }
    if ($request->has('rider_id') && !empty($request->rider_id)) {
      $query->where('rider_id', $request->rider_id);
    }
    if ($request->has('rider') && !empty($request->rider)) {
      $query->where('rider_id', $request->rider);
    }
    if ($request->has('company') && !empty($request->company)) {
      $query->where('company', $request->company);
    }
    if ($request->has('emirates') && !empty($request->emirates)) {
      $query->where('emirates', $request->emirates);
    }
    if ($request->filled('expiry_date_from')) {
      $fromDate = \Carbon\Carbon::createFromFormat('Y-d-m', $request->expiry_date_from);
      $query->where('expiry_date', '>=', $fromDate);
    }

    if ($request->filled('expiry_date_to')) {
      $toDate = \Carbon\Carbon::createFromFormat('Y-d-m', $request->expiry_date_to);
      $query->where('expiry_date', '<=', $toDate);
    }

    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
    }

    // Add warehouse filter
    if ($request->has('warehouse') && !empty($request->warehouse)) {
      $query->where('warehouse', $request->warehouse);
    }

    // Add quick search functionality
    if ($request->filled('quick_search')) {
      $search = $request->input('quick_search');

      $query->leftJoin('riders', 'bikes.rider_id', '=', 'riders.id')
        ->leftJoin('leasing_companies', 'bikes.company', '=', 'leasing_companies.id')
        ->leftJoin('customers', 'bikes.customer_id', '=', 'customers.id')
        ->where(function ($q) use ($search) {
          $q->where('bikes.plate', 'like', "%{$search}%")
            ->orWhere('bikes.bike_code', 'like', "%{$search}%")
            ->orWhere('bikes.chassis_number', 'like', "%{$search}%")
            ->orWhere('bikes.color', 'like', "%{$search}%")
            ->orWhere('bikes.model', 'like', "%{$search}%")
            ->orWhere('bikes.emirates', 'like', "%{$search}%")
            ->orWhere('bikes.warehouse', 'like', "%{$search}%")
            ->orWhere('riders.name', 'like', "%{$search}%")
            ->orWhere('riders.rider_id', 'like', "%{$search}%")
            ->orWhere('leasing_companies.name', 'like', "%{$search}%")
            ->orWhere('customers.name', 'like', "%{$search}%");
        });
      $query->select('bikes.*');
    }

    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);

    // Get table columns configuration
    $tableColumns = $this->getTableColumns();

    $tableData = view('bikes.table', [
      'data' => $data,
      'tableColumns' => $tableColumns,
    ])->render();

    // Use global pagination component
    if (method_exists($data, 'links')) {
      $paginationLinks = $data->links('components.global-pagination')->render();
    } else {
      $paginationLinks = '';
    }

    return response()->json([
      'success' => true,
      'html' => $tableData,
      'pagination' => $paginationLinks,
      'total' => method_exists($data, 'total') ? $data->total() : $data->count(),
      'per_page' => method_exists($data, 'perPage') ? $data->perPage() : $data->count(),
    ]);
  }

  /**
   * Show the form for creating a new Bikes.
   */
  public function create()
  {
    return view('bikes.create');
  }

  /**
   * Store a newly created Bikes in storage.
   */
  public function store(CreateBikesRequest $request)
  {
    $input = $request->all();

    // Check if selected vehicle type is Cyclist
    $vehicleModel = DB::table('vehicle_models')->find($request->vehicle_type);

    if ($vehicleModel && strtolower($vehicleModel->name) === 'cyclist') {
      unset(
        $input['bike_code'],
        $input['chassis_number'],
        $input['engine'],
        $input['model_type'],
        $input['policy_no'],
      );
    }

    $bikes = $this->bikesRepository->create($input);
    $bikes->created_by = Auth::user()->id;
    $bikes->save();

    return response()->json(['message' => 'Bike added successfully.']);
  }


  /**
   * Display the specified Bikes.
   */
  public function show($id)
  {
    $bikes = $this->bikesRepository->find($id);

    if (empty($bikes)) {
      Flash::error('Bikes not found');

      return redirect(route('bikes.index'));
    }

    $mulkiyaFile = DB::table('files')
    ->where('type', 'bike')
    ->where('type_id', $id)
    ->where(function($query) {
        $query->where('name', 'LIKE', '%mulkiya%')
              ->orWhere('name', 'LIKE', '%Mulkiya%')
              ->orWhere('name', 'LIKE', '%MULKIYA%');
    })
    ->first();

    return view('bikes.show2')->with('bikes', $bikes)->with('mulkiyaFile', $mulkiyaFile);
  }

  /**
   * Show the form for editing the specified Bikes.
   */
  public function edit($id)
  {
    $bikes = $this->bikesRepository->find($id);
    if (empty($bikes)) {
      Flash::error('Bikes not found');
      return redirect(route('bikes.index'));
    }
    return view('bikes.edit')->with('bikes', $bikes);
  }

  /**
   * Update the specified Bikes in storage.
   */
  public function update($id, UpdateBikesRequest $request)
  {
    $bikes = $this->bikesRepository->find($id);

    if (empty($bikes)) {
      return response()->json(['errors' => ['error' => 'Bike not found!']], 422);
    }

    $bikes = $this->bikesRepository->update($request->all(), $id);
    $bikes->updated_by = Auth::user()->id;
    $bikes->save();

    // Sync customer_id and designation to rider if changed and rider is assigned
    if ($bikes->rider_id && $request->has('customer_id')) {
      $rider = Riders::find($bikes->rider_id);
      if ($rider) {
        $customer_id = $request->customer_id;
        // Determine new designation (copy from assignrider logic)
        $designation = $rider->designation;
        $emirate_hub = $request->emirate_hub;
        if ($bikes->vehicle_type) {
          $vehicleModel = \DB::table('vehicle_models')->where('id', $bikes->vehicle_type)->first();
          $vehicleTypeName = $vehicleModel ? strtolower($vehicleModel->name) : '';
          if (strpos($vehicleTypeName, 'bike') !== false) {
            $designation = 'Rider';
          } elseif (strpos($vehicleTypeName, 'car') !== false || strpos($vehicleTypeName, 'van') !== false) {
            $designation = 'Driver';
          } elseif (strpos($vehicleTypeName, 'cyclist') !== false) {
            $designation = 'Cyclist';
          }
        }
        $rider->update([
          'customer_id' => $customer_id,
          'designation' => $designation,
          'emirate_hub' => $emirate_hub,
        ]);
      }
    }
    return response()->json(['message' => 'Bike updated successfully.']);
  }

  /**
   * Remove the specified Bikes from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $bikes = $this->bikesRepository->find($id);

    if (empty($bikes)) {
      return response()->json(['errors' => ['error' => 'Bike not found!']], 422);
    }

    $this->bikesRepository->delete($id);

    return response()->json(['message' => 'Bike deleted successfully.']);
  }

  // Return bike
  public function assignrider(Request $request, $id)
  {
    if ($request->isMethod('post')) {
      $rules = [
        'bike_id'   => 'required|exists:bikes,id',
        'warehouse' => 'required|string',
        'return_date' => [
                  'required',
                  'date',
                  function ($attribute, $value, $fail) use ($request) {
                      
                      // Get the last assign date for this bike from bike history
                      $lastAssignDate = DB::table('bike_histories')
                          ->where('bike_id', $request->bike_id)
                          ->where('warehouse', 'Active')
                          ->orderBy('note_date', 'desc')
                          ->value('note_date');
                      
                      // If there's a last assign date, check that return date is after it
                      if ($lastAssignDate && strtotime($value) < strtotime($lastAssignDate)) {
                          $fail('Return date cannot be before the last Assignment date (' . \Carbon\Carbon::parse($lastAssignDate)->format('d-m-Y') . ').');
                          return;
                      }
                      // We can alo check if date is in the future 
                      if(strtotime($value) >= strtotime('tomorrow')) {
                          $fail('Return date cannot be later than today.');
                          return;
                      }
                  }
              ],
      ];
      $messages = [
        'bike_id.required' => 'Bike ID Required',
        'bike_id.exists'   => 'Invalid Bike ID',
      ];

      $this->validate($request, $rules, $messages);

      DB::beginTransaction();
      try {

        $bike = Bikes::findOrFail($request->bike_id);
        $rider = DB::table('riders')->where('id', $bike->rider_id)->first();
        $designation = $request->designation;
        $message = "*Bike* 🏍️\n";
        $message .= "────────────────\n";
        $message .= "*Bike No:* {$bike->plate}\n";
        $message .= "*ID:* {$rider->rider_id}\n";
        $message .= "*Name:* {$rider->name}\n";
        if($request->warehouse == 'Absconded')
          $message .= "*Absconding Date:* {$request->return_date}\n";
        else
          $message .= "*Return Date:* {$request->return_date}\n";
        $message .= "*Time:* " . now()->setTimezone('Asia/Dubai')->format('h:i a') . "\n";
        $project = DB::table('customers')->where('id', $bike->customer_id)->first();
        $message .= "*Project:* {$project->name}\n";
        $message .= "*Emirates:* {$bike->emirates}\n";
        $message .= "*Note:*" . $request->notes??''. "\n";

        // Status handling
        if($request->warehouse == 'Absconded') {
            Riders::where('id', $bike->rider_id)
              ->update(['status' => 5]);
            $bike->update(['warehouse' => 'Absconded']);
            $this->updateBikeHistory2($bike, 'Absconded', $bike->rider_id, $message);
        }elseif ($request->warehouse == 'Vacation') {
            Riders::where('id', $bike->rider_id)
              ->update([
                'status'      => 4,
                'designation' => null,
                'customer_id' => null,
              ]);
            $this->updateBikeHistory($bike, 'Return', $bike->rider_id, $message, $request->return_date);
            $bike->update(['rider_id' => null, 'warehouse' => 'Return', 'customer_id' => null]);
            
        }elseif ($request->warehouse == 'Return') {
            Riders::where('id', $bike->rider_id)
              ->update([
                'status'      => 3,
                'designation' => null,
                'customer_id' => null,
              ]);
            $this->updateBikeHistory($bike, 'Return', $bike->rider_id, $message, $request->return_date);
            $bike->update([
              'rider_id'  => null,
              'warehouse' => 'Return',
              'customer_id' => null,
            ]);
        }else {

            return response()->json([
              'success' => false,
              'errors'  => 'Invalid warehouse status.'
            ], 400);
        }

        DB::commit();
        return response()->json(['message' => 'Rider assignment updated successfully.']);
      } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
          'success' => false,
          'errors'  => $e->getMessage()
        ], 400);
      }
    }

    return view('bikes.assignBike_change', compact('id'));
  }

  private function updateBikeHistory($bike, $status, $rider_id, $notes, $return_date)
  {
    
    $userid = Auth::user()->id;
    
    $lastHistory = BikeHistory::where('bike_id', $bike->id)
      ->where('rider_id', $rider_id)
      ->whereNull('return_date')
      ->latest('note_date')
      ->first();

   if($bike->warehouse == 'Absconded'){
      $notes = $lastHistory->notes . "\n" . $notes;
    } 

    $lastHistory->update([
      'warehouse'   => $status,
      'return_date' => $return_date,
      'notes'       => $notes,
      'updated_by' => $userid
    ]);
    
    
  }

  private function updateBikeHistory2($bike, $status, $rider_id, $notes)
  {
    $userid = Auth::user()->id;
    
    $lastHistory = BikeHistory::where('bike_id', $bike->id)
      ->where('rider_id', $rider_id)
      ->whereNull('return_date')
      ->latest('note_date')
      ->first();

    $lastHistory->update([
      'warehouse'   => $status,
      'notes'       => $notes,
    ]);
    
    
  }

  public function assign_rider(Request $request, $id)
  {
      if ($request->isMethod('post')) {
          $rules = [
              'bike_id'  => 'required',
              'customer_id' => 'required',
              'rider_id' => [
                  'required',
                  function ($attribute, $value, $fail) use ($request) {
                      if ($value) {
                          // Check if bike exists
                          $bike = DB::table('bikes')->where('id', $request->bike_id)->first(); 
                          if (!$bike) {
                              $fail('Bike not found.');
                              return;
                          }
                          // Check if bike is inactive (status = 1 or 2)
                          if ($bike->status == 2 ) {
                              $fail('Cannot assign rider to an inactive bike.');
                              return;
                          }
                          // Check if rider has any bike that's not "Returned" or "Vacation"
                          $assignedBike = DB::table('bikes')
                              ->where('rider_id', $value)
                              ->whereNotIn('warehouse', ['Return', 'Vacation', 'Express Garage'])
                              ->first();
                          
                          if ($assignedBike) {
                              $fail('Rider is already assigned to ' . ($assignedBike->plate ? 'bike #' . $assignedBike->plate : 'a vehicle') . '.');
                          }
                      }
                  }
              ],
              'note_date' => [
                  'required',
                  'date',
                  function ($attribute, $value, $fail) use ($request) {
                      // Check if date is empty
                      if (empty($value)) {
                          $fail('Assignment date is required.');
                          return;
                      }
                      // Get the last return date for this bike from bike history
                      $lastReturnDate = DB::table('bike_histories')
                          ->where('bike_id', $request->bike_id)
                          ->where('warehouse', 'Return')
                          ->where('warehouse', 'Vacation')
                          ->orderBy('return_date', 'desc')
                          ->value('return_date');
                      
                      // If there's a last return date, check that assignment date is after it
                      if ($lastReturnDate && strtotime($value) < strtotime($lastReturnDate)) {
                          $fail('Assignment date cannot be before the last return date (' . \Carbon\Carbon::parse($lastReturnDate)->format('d-m-Y') . ').');
                          return;
                      }
                      // We can alo check if date is in the future 
                      if(strtotime($value) >= strtotime('tomorrow')) {
                          $fail('Assignment date cannot be later than today.');
                          return;
                       }
                  }
              ],
          ];
          
          $message = [
              'bike_id.required' => 'Bike ID is required.',
              'note_date.required' => 'Assignment date is required.',
              'customer_id.required' => 'Project is required.',
          ];
          
          $this->validate($request, $rules, $message);

          $data = $request->all();
          DB::beginTransaction();
          try {
              $bike = Bikes::findOrFail($request->bike_id);
              $rider = DB::table('riders')->where('id', $request->rider_id)->first();
              $designation = $request->designation;
              $customer_id = $request->customer_id;

              $message = "*Bike* 🏍️\n";
              $message .= "────────────────\n";
              $message .= "*Bike No:* {$bike->plate}\n";
              $message .= "*ID:* {$rider->rider_id}\n";
              $message .= "*Name:* {$rider->name}\n";
              $message .= "*Assign Date:* {$request->note_date}\n";
              $message .= "*Time:* " . now()->setTimezone('Asia/Dubai')->format('h:i a') . "\n";
              $project = DB::table('customers')->where('id', $customer_id)->first();
              $message .= "*Project:* {$project->name}\n";
              $message .= "*Emirates:* {$bike->emirates}\n";
              $message .= "*Note:*" . $request->notes??''. "\n";
              
              // Update rider status + designation depending on warehouse
              $rider = Riders::find($request->rider_id);
              $rider->update(['status' => 1, 'designation' => $designation, 'customer_id' => $customer_id, 'emirate_hub' => $bike->emirates]);
              $bike->update(['rider_id' => $request->rider_id, 'warehouse' => $request->warehouse, 'customer_id' => $customer_id]);
              
              
              // Save bike history
              $data['created_by'] = Auth::id();
              $data['notes'] = $message;
              $data['customer_id'] = $customer_id;
              $bikeHistory = BikeHistory::create($data);
              DB::commit();
              return response()->json(['message' => 'Rider assigned successfully.']);
              
          } catch (QueryException $e) {
              DB::rollBack();
              return response()->json([
                  'success' => 'false',
                  'errors'  => $e->getMessage(),
              ], 400);
          }
      }

      return view('bikes.assignBike_active', compact('id'));
  }


  public function assignContract($id)
  {
    $contract = BikeHistory::find($id);


    return view('bikes.assignContract', compact('contract'));
  }

  public function returnContract($id)
  {
    $contract = BikeHistory::find($id);


    return view('bikes.returnContract', compact('contract'));
  }

  public function contract_upload(Request $request)
  {
    $contract = BikeHistory::find($request->id);
    if (isset($request->contract)) {

      $doc = $request->contract;
      $extension = $doc->extension();
      $name = time() . '.' . $extension;
      $doc->storeAs('contract', $name);


      $contract->contract = $name;
      $contract->updated_by = Auth::id();
      $contract->save();

      return response()->json(['message' => $contract->rider->name . '( ' . $contract->rider->rider_id . ' ) Bike Plate # ' . $contract->bike->plate . ' Contract uploaded.']);
      //return redirect(url('bikes'))->with('success', $contract->rider->name . '( ' . $contract->rider->rider_id . ' ) Bike Plate # ' . $contract->bike->plate . ' Contract uploaded.');
    }

    return view('bikes.contract-modal', compact('contract'));
  }


  /**
   * Show export bikes form
   */
  public function exportBikes(Request $request)
  {
    if (!auth()->user()->hasPermissionTo('bike_view')) {
      abort(403, 'Unauthorized action.');
    }

    if ($request->ajax()) {
      return response()->view('bikes.export_modal');
    }

    return redirect()->route('bikes.index');
  }

  /**
   * Export bikes to Excel/CSV/PDF with customizable columns
   */
  public function exportCustomizableBikes(Request $request)
  {
    if (!auth()->user()->hasPermissionTo('bike_view')) {
      abort(403, 'Unauthorized action.');
    }

    // Get column configuration from request or user settings
    $visibleColumns = $request->input('visible_columns');
    $columnOrder = $request->input('column_order');
    $format = $request->input('format', 'excel');
    $applyFilters = $request->input('apply_filters', true);

    // Parse JSON strings if they exist
    if (is_string($visibleColumns)) {
      $visibleColumns = json_decode($visibleColumns, true);
    }
    if (is_string($columnOrder)) {
      $columnOrder = json_decode($columnOrder, true);
    }

    // If no column settings provided in request, get from user's saved settings
    if (empty($visibleColumns) || empty($columnOrder)) {
      $userSettings = \App\Models\UserTableSettings::getSettings(auth()->id(), 'bikes_table');

      if ($userSettings) {
        $visibleColumns = $visibleColumns ?: $userSettings->visible_columns;
        $columnOrder = $columnOrder ?: $userSettings->column_order;
      }
    }

    // Get current filters from session or request if apply_filters is true
    $filters = [];
    if ($applyFilters) {
      $filters = [
        'bike_code' => $request->input('bike_code') ?: session('bikes_filter.bike_code'),
        'plate' => $request->input('plate') ?: session('bikes_filter.plate'),
        'rider_id' => $request->input('rider_id') ?: session('bikes_filter.rider_id'),
        'rider' => $request->input('rider') ?: session('bikes_filter.rider'),
        'company' => $request->input('company') ?: session('bikes_filter.company'),
        'emirates' => $request->input('emirates') ?: session('bikes_filter.emirates'),
        'warehouse' => $request->input('warehouse') ?: session('bikes_filter.warehouse'),
        'status' => $request->input('status') ?: session('bikes_filter.status'),
        'expiry_date_from' => $request->input('expiry_date_from') ?: session('bikes_filter.expiry_date_from'),
        'expiry_date_to' => $request->input('expiry_date_to') ?: session('bikes_filter.expiry_date_to'),
        'quick_search' => $request->input('quick_search') ?: session('bikes_filter.quick_search'),
      ];
    }

    // Create customizable export
    $export = new CustomizableBikeExport($visibleColumns, $columnOrder, $filters);

    // Generate filename with format
    $timestamp = now()->format('Y-m-d_H-i-s');
    $username = auth()->user()->name ?? auth()->user()->email ?? 'user';
    $username = preg_replace('/[^a-zA-Z0-9]/', '_', $username); // Sanitize username for filename
    $filename = "Bikes_export_{$username}_{$timestamp}";

    // Return appropriate format
    switch ($format) {
      case 'csv':
        return Excel::download($export, "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV,);
      case 'pdf':
        return Excel::download($export, "{$filename}.pdf", \Maatwebsite\Excel\Excel::DOMPDF);
      case 'excel':
      default:
        return Excel::download($export, "{$filename}.xlsx");
    }
  }

  /**
   * Show import bikes form
   */
  public function importbikes()
  {
    if (!auth()->user()->hasPermissionTo('bike_view')) 
      abort(403, 'Unauthorized action.');
    
    \Log::info('Stack trace: reached importbikes');
    return view('bikes.import');
  }

  /**
   * Process bike import from Excel file
   */
  public function processImport(Request $request)
  {
    if (!auth()->user()->hasPermissionTo('bike_view')) {
      abort(403, 'Unauthorized action.');
    }

    // Validate the request
    $request->validate([
      'file' => 'required|file|mimes:xlsx,xls,csv|max:51200', // Max 50MB
    ]);

    try {
      // Handle data reset if requested (admin only)
      $reset = false;
      if (auth()->user()->hasRole('admin') && $request->has('reset_data')) {
        DB::beginTransaction();
        try {
          // Delete all bike history first
          BikeHistory::truncate();
          // Then delete all bikes
          Bikes::truncate();
          DB::commit();
          $reset = true;
        } catch (\Exception $e) {
          DB::rollBack();
          return response()->json([
            'success' => false,
            'message' => 'Error resetting data: ' . $e->getMessage()
          ], 500);
        }
      }

      // Process the import
      $import = new ImportBikes();
      Excel::import($import, $request->file('file'));

      // Get import results
      $results = $import->getResults();

      // Prepare response message
      $message = "Successfully imported {$results['success_count']} bikes.";
      if ($results['error_count'] > 0) {
        $message .= " {$results['error_count']} rows had errors.";
      }

      // Check if there were any errors
      if ($import->hasErrors()) {
        return response()->json([
          'success' => false,
          'message' => $message,
          'errors' => $import->getErrors(),
          'success_count' => $results['success_count'],
          'error_count' => $results['error_count'],
          'reset' => $reset
        ]);
      }

      return response()->json([
        'success' => true,
        'message' => $message,
        'success_count' => $results['success_count'],
        'error_count' => $results['error_count'],
        'reset' => $reset
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Import failed: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Download sample template for bike import
   */
  public function downloadSampleTemplate()
  {
    if (!auth()->user()->hasPermissionTo('bike_view')) {
      abort(403, 'Unauthorized action.');
    }

    $headers = [
      'plate',
      'vehicle_type',
      'chassis_number',
      'color',
      'model',
      'model_type',
      'engine',
      'company_name',
      'rider_name',
      'notes',
      'warehouse',
      'traffic_file_number',
      'emirates',
      'bike_code',
      'registration_date',
      'expiry_date',
      'insurance_expiry',
      'insurance_co',
      'policy_no',
      'status',
      'contract_number',
      'customer_name'
    ];

    $sampleData = [
      [
        '1',
        'HONDA UNICORN',
        'ME4KC20F0NA015779',
        'BLACK',
        '2022',
        'UNICORN',
        'KC20EA0035034',
        'Leasing Company Name',
        '',
        'Sample notes for bike 1',
        'Active',
        '50527229',
        'DXB',
        '',
        '2023-01-15',
        '2024-01-15',
        '2024-06-30',
        'Insurance Company',
        'POL001',
        '1',
        'CNT001',
        'Customer Name'
      ],
      [
        '1',
        'HONDA UNICORN',
        'ME4KC20F7NA010241',
        'BLACK',
        '2022',
        'UNICORN',
        'KC20EA0029505',
        'Leasing Company Name',
        '',
        'Sample notes for bike 2',
        'Active',
        '50527229',
        'DXB',
        '',
        '2023-02-20',
        '2024-02-20',
        '2024-07-15',
        'Insurance Company',
        'POL002',
        '1',
        'CNT002',
        'Customer Name'
      ],
      [
        '1',
        'HONDA UNICORN',
        'ME4KC20F9NA015781',
        'BLACK',
        '2022',
        'UNICORN',
        'KC20EA0035037',
        'Leasing Company Name',
        '',
        'Sample notes for bike 3',
        'Active',
        '50527229',
        'DXB',
        '',
        '2023-03-10',
        '2024-03-10',
        '2024-08-10',
        'Insurance Company',
        'POL003',
        '1',
        'CNT003',
        'Customer Name'
      ],
    ];

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $col = 'A';
    foreach ($headers as $header) {
      $sheet->setCellValue($col . '1', $header);
      $sheet->getStyle($col . '1')->getFont()->setBold(true);
      $sheet->getStyle($col . '1')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFD3D3D3');
      $col++;
    }

    // Add sample data
    $row = 2;
    foreach ($sampleData as $data) {
      $col = 'A';
      foreach ($data as $value) {
        $sheet->setCellValue($col . $row, $value);
        $col++;
      }
      $row++;
    }

    // Auto-size columns
    foreach (range('A', 'V') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Create writer and download
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $filename = 'bikes_import_template_' . date('Y-m-d') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer->save('php://output');
    exit;
  }
}
