<?php

namespace App\Http\Controllers;

use App\DataTables\FilesDataTable;
use App\DataTables\LedgerDataTable;
use App\DataTables\RiderActivitiesDataTable;
use App\DataTables\RiderAttendanceDataTable;
use App\DataTables\RiderEmailsDataTable;
use App\DataTables\RiderInvoicesDataTable;
use App\DataTables\RidersDataTable;
use App\Exports\MonthlyActivityExport;
use App\Exports\RiderExport;
use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\General;
use App\Helpers\HeadAccount;
use App\Http\Requests\CreateAccountsRequest;
use App\Http\Requests\CreateRidersRequest;
use App\Http\Requests\UpdateRidersRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Accounts;
use App\Models\RiderEmails;
use App\Models\RiderItemPrice;
use App\Models\JobStatus;
use App\Models\Riders;
use App\Models\Files;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Models\Bikes;
use App\Models\BikeHistory;
use App\Models\RiderInvoices;
use App\Models\RiderActivities;
use App\Models\RiderAttendance;
use App\Models\cod;
use App\Models\penalties;
use App\Models\RtaFines;
use App\Models\salik;
use App\Models\visa_expenses;
use App\Models\visa_installment_plan;
use Illuminate\Support\Facades\Auth;
use App\Repositories\RidersRepository;
use App\Traits\GlobalPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Flash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RidersController extends AppBaseController
{
  use GlobalPagination;
  /** @var RidersRepository $ridersRepository*/
  private $ridersRepository;

  /**
   * Normalize billing month input to first day of month (Y-m-01).
   */
  private function normalizeBillingMonth($input)
  {
    if (empty($input)) {
      return date('Y-m-01');
    }
    // Accept formats like YYYY-MM or YYYY-MM-DD
    // If only year-month provided, append -01
    if (preg_match('/^\d{4}-\d{2}$/', $input)) {
      return $input . '-01';
    }
    // Try to parse any date string and return first day of that month
    $ts = strtotime($input);
    if ($ts !== false) {
      return date('Y-m-01', $ts);
    }
    // Fallback to current month start
    return date('Y-m-01');
  }

  public function __construct(RidersRepository $ridersRepo)
  {
    $this->ridersRepository = $ridersRepo;
  }

  /**
   * Display a listing of the Riders.
   */
  public function index(Request $request)
  {
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

    $currentMonthStart = Carbon::now()->startOfMonth()->toDateString();
    $currentMonthEnd = Carbon::now()->endOfMonth()->toDateString();

    $query = Riders::query()
      ->leftJoin(
        \DB::raw("(SELECT rider_id, COUNT(date) as days_count 
                   FROM rider_activities 
                   WHERE date BETWEEN '{$currentMonthStart}' AND '{$currentMonthEnd}' 
                   GROUP BY rider_id) as ra"),
        'riders.id',
        '=',
        'ra.rider_id'
      )
      ->select('riders.*', \DB::raw('COALESCE(ra.days_count, 0) as days_count'))
      ->orderBy('days_count', 'asc');
    if ($request->has('rider_id') && !empty($request->rider_id)) {
      $query->where('riders.rider_id', 'like', '%' . $request->rider_id . '%');
    }
    if ($request->has('courier_id') && !empty($request->courier_id)) {
      $courierIdInput = $request->courier_id;
      // Remove 'CI-' prefix if present (case-insensitive)
      if (stripos($courierIdInput, 'CI-') === 0) {
        $courierIdInput = substr($courierIdInput, 3);
      }
      $query->where('riders.courier_id', 'like', '%' . $courierIdInput . '%');
    }
    if ($request->has('name') && !empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('fleet_supervisor') && !empty($request->fleet_supervisor)) {
      $query->where('fleet_supervisor', $request->fleet_supervisor);
    }
    if ($request->has('hub') && !empty($request->hub)) {
      $query->where('hub', $request->hub);
    }
    if ($request->has('customer_id') && !empty($request->customer_id)) {
      $query->where('customer_id', $request->customer_id);
    }
    if ($request->has('branded_plate_no') && !empty($request->branded_plate_no)) {
      $query->where('branded_plate_no', $request->branded_plate_no);
    }
    if ($request->has('designation') && !empty($request->designation)) {
      $query->where('designation', $request->designation);
    }
    if ($request->has('attendance') && !empty($request->attendance)) {
      $query->where('attendance', $request->attendance);
    }
    // Explicit tag filters coming from slider cards
    $absconderParam = (array) $request->input('absconder', []);
    $followupParam = (array) $request->input('followup', []);
    $llicenseParam = (array) $request->input('llicense', []);

    // Absconder (expects absconder[]=1)
    if (!empty($absconderParam) && in_array('1', $absconderParam, true)) {
      $query->where('absconder', 1);
    }
    // Follow Up (expects followup[]=1)
    if (!empty($followupParam) && in_array('1', $followupParam, true)) {
      $query->where('flowup', 1);
    }
    // Learning License (expects llicense[]=1)
    if (!empty($llicenseParam) && in_array('1', $llicenseParam, true)) {
      $query->where('l_license', 1);
    }

    // Filter by rider status (followup, llicense, active, inactive)
    if ($request->has('rider_status') && !empty($request->rider_status)) {
      $statusFilters = $request->rider_status;

      // If explicit tag params are present, drop corresponding tokens from rider_status[]
      if (!empty($absconderParam)) {
        $statusFilters = array_values(array_filter($statusFilters, function ($s) {
          return $s !== 'absconder';
        }));
      }
      if (!empty($followupParam)) {
        $statusFilters = array_values(array_filter($statusFilters, function ($s) {
          return $s !== 'followup';
        }));
      }
      if (!empty($llicenseParam)) {
        $statusFilters = array_values(array_filter($statusFilters, function ($s) {
          return $s !== 'llicense';
        }));
      }

      if (is_array($statusFilters)) {
        $query->where(function ($q) use ($statusFilters) {
          foreach ($statusFilters as $status) {
            if ($status === 'followup') {
              $q->orWhere('flowup', 1);
            } elseif ($status === 'llicense') {
              $q->orWhere('l_license', 1);
            } elseif ($status === 'active') {
              // Active riders: status = 1 (regardless of bike assignment)
              $q->orWhere('riders.status', 1);
            } elseif ($status === 'inactive') {
              // Inactive riders: status = 3 OR no active bike assigned
              $q->orWhere(function ($subQuery) {
                $subQuery->where('riders.status', 3);
              })->orWhere(function ($subQuery) {
                $subQuery->whereDoesntHave('bikes', function ($bikeQuery) {
                  $bikeQuery->where('warehouse', 'Active');
                });
              });
            }
          }
        });
      } else {
        // Handle single selection for backward compatibility
        if ($statusFilters === 'followup') {
          $query->where('flowup', 1);
        } elseif ($statusFilters === 'llicense') {
          $query->where('l_license', 1);
        } elseif ($statusFilters === 'active') {
          // Active riders: status = 1 AND have active bike assigned
          $query->where('riders.status', 1);
        } elseif ($statusFilters === 'inactive') {
          // Inactive riders: status = 3 OR no active bike assigned
          $query->where(function ($q) {
            $q->where('riders.status', 3)->orWhereDoesntHave('bikes', function ($bikeQuery) {
              $bikeQuery->where('warehouse', 'Active');
            });
          });
        }
      }
    }
    // if ($request->has('status') && !empty($request->status)) {
    //   $query->where('status', $request->status);
    // }
    // Filter by bike assignment status (Active/Inactive based on bike assignment)
    if ($request->has('bike_assignment_status') && !empty($request->bike_assignment_status)) {
      if ($request->bike_assignment_status === 'Active') {
        // Riders who have an active bike assigned
        $query->whereHas('bikes', function ($q) {
          $q->where('warehouse', 'Active');
        });
      } elseif ($request->bike_assignment_status === 'Inactive') {
        // Riders who don't have an active bike assigned
        $query->whereDoesntHave('bikes', function ($q) {
          $q->where('warehouse', 'Active');
        });
      }
    }
    // Filter by balance
    if ($request->has('balance_filter') && !empty($request->balance_filter)) {
      if ($request->balance_filter === 'greater_than_zero') {
        // Riders with balance greater than 0
        $query->whereHas('account', function ($q) {
          $q->whereRaw('(SELECT COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) FROM transactions WHERE account_id = accounts.id) > 0');
        });
      }
    }
    if ($request->filled('quick_search')) {
      $search = $request->input('quick_search');

      $query->leftJoin('customers', 'riders.customer_id', '=', 'customers.id')
        ->leftJoin('bikes', 'riders.id', '=', 'bikes.rider_id')
        ->where(function ($q) use ($search) {
          $q->where('riders.name', 'like', "%{$search}%")
            ->orWhere('riders.rider_id', 'like', "%{$search}%")
            ->orWhere('riders.courier_id', 'like', "%{$search}%")
            ->orWhere('riders.branded_plate_no', 'like', "%{$search}%")
            ->orWhere('riders.fleet_supervisor', 'like', "%{$search}%")
            ->orWhere('riders.emirate_hub', 'like', "%{$search}%")
            ->orWhere('riders.customer_id', 'like', "%{$search}%")
            ->orWhere('riders.designation', 'like', "%{$search}%")
            ->orWhere('customers.name', 'like', "%{$search}%");
          if (stripos($search, 'active') !== false) {
            $q->orWhereExists(function ($subQuery) {
              $subQuery->select(\DB::raw(1))
                ->from('bikes')
                ->whereRaw('bikes.rider_id = riders.id')
                ->where('bikes.warehouse', '=', 'Active');
            });
          }
          if (stripos($search, 'inactive') !== false) {
            $q->orWhere(function ($subQ) {
              $subQ->whereNotExists(function ($subQuery) {
                $subQuery->select(\DB::raw(1))
                  ->from('bikes')
                  ->whereRaw('bikes.rider_id = riders.id')
                  ->where('bikes.warehouse', '=', 'Active');
              });
            });
          }
        });
      $query->select('riders.*');
    }

    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);

    return view('riders.index', [
      'data' => $data,
    ]);
  }

  /**
   * Handle AJAX filter requests for riders listing
   */
  public function filterAjax(Request $request)
  {
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

    $currentMonthStart = Carbon::now()->startOfMonth()->toDateString();
    $currentMonthEnd = Carbon::now()->endOfMonth()->toDateString();

    $query = Riders::query()
      ->leftJoin(
        \DB::raw("(SELECT rider_id, COUNT(date) as days_count 
                   FROM rider_activities 
                   WHERE date BETWEEN '{$currentMonthStart}' AND '{$currentMonthEnd}' 
                   GROUP BY rider_id) as ra"),
        'riders.id',
        '=',
        'ra.rider_id'
      )
      ->select('riders.*', \DB::raw('COALESCE(ra.days_count, 0) as days_count'))
      ->orderBy('days_count', 'desc')
      ->orderBy('riders.id', 'desc');

    if ($request->has('rider_id') && !empty($request->rider_id)) {
      $query->where('riders.rider_id', 'like', '%' . $request->rider_id . '%');
    }
    if ($request->has('courier_id') && !empty($request->courier_id)) {
      $courierIdInput = $request->courier_id;
      // Remove 'CI-' prefix if present (case-insensitive)
      if (stripos($courierIdInput, 'CI-') === 0) {
        $courierIdInput = substr($courierIdInput, 3);
      }
      $query->where('riders.courier_id', 'like', '%' . $courierIdInput . '%');
    }
    if ($request->has('name') && !empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('fleet_supervisor') && !empty($request->fleet_supervisor)) {
      $query->where('fleet_supervisor', $request->fleet_supervisor);
    }
    if ($request->has('hub') && !empty($request->hub)) {
      $query->where('hub', $request->hub);
    }
    if ($request->has('customer_id') && !empty($request->customer_id)) {
      $query->where('customer_id', $request->customer_id);
    }
    if ($request->has('branded_plate_no') && !empty($request->branded_plate_no)) {
      $query->where('branded_plate_no', $request->branded_plate_no);
    }
    if ($request->has('designation') && !empty($request->designation)) {
      $query->where('designation', $request->designation);
    }
    if ($request->has('attendance') && !empty($request->attendance)) {
      $query->where('attendance', $request->attendance);
    }

    // Explicit tag filters coming from slider cards (AJAX)
    $absconderParam = (array) $request->input('absconder', []);
    $followupParam = (array) $request->input('followup', []);
    $llicenseParam = (array) $request->input('llicense', []);

    if (!empty($absconderParam) && in_array('1', $absconderParam, true)) {
      $query->where('absconder', 1);
    }
    if (!empty($followupParam) && in_array('1', $followupParam, true)) {
      $query->where('flowup', 1);
    }
    if (!empty($llicenseParam) && in_array('1', $llicenseParam, true)) {
      $query->where('l_license', 1);
    }

    // Filter by rider status (followup, llicense, active, inactive)
    if ($request->has('rider_status') && !empty($request->rider_status)) {
      $statusFilters = $request->rider_status;

      // If explicit tag params are present, drop corresponding tokens from rider_status[]
      if (!empty($absconderParam)) {
        $statusFilters = array_values(array_filter($statusFilters, function ($s) {
          return $s !== 'absconder';
        }));
      }
      if (!empty($followupParam)) {
        $statusFilters = array_values(array_filter($statusFilters, function ($s) {
          return $s !== 'followup';
        }));
      }
      if (!empty($llicenseParam)) {
        $statusFilters = array_values(array_filter($statusFilters, function ($s) {
          return $s !== 'llicense';
        }));
      }

      if (is_array($statusFilters)) {
        $query->where(function ($q) use ($statusFilters) {
          foreach ($statusFilters as $status) {
            if ($status === 'followup') {
              $q->orWhere('flowup', 1);
            } elseif ($status === 'llicense') {
              $q->orWhere('l_license', 1);
            } elseif ($status === 'active') {
              // Active riders: status = 1 (regardless of bike assignment)
              $q->orWhere('status', 1);
            } elseif ($status === 'inactive') {
              // Inactive riders: status = 3 OR no active bike assigned
              $q->orWhere(function ($subQuery) {
                $subQuery->where('status', 3);
              })->orWhere(function ($subQuery) {
                $subQuery->whereDoesntHave('bikes', function ($bikeQuery) {
                  $bikeQuery->where('warehouse', 'Active');
                });
              });
            }
          }
        });
      } else {
        // Handle single selection for backward compatibility
        if ($statusFilters === 'absconder') {
          $query->where('absconder', 1);
        } elseif ($statusFilters === 'followup') {
          $query->where('flowup', 1);
        } elseif ($statusFilters === 'llicense') {
          $query->where('l_license', 1);
        } elseif ($statusFilters === 'active') {
          // Active riders: status = 1 AND have active bike assigned
          $query->where('status', 1);
        } elseif ($statusFilters === 'inactive') {
          // Inactive riders: status = 3 OR no active bike assigned
          $query->where(function ($q) {
            $q->where('status', 3)->orWhereDoesntHave('bikes', function ($bikeQuery) {
              $bikeQuery->where('warehouse', 'Active');
            });
          });
        }
      }
    }

    // Filter by balance
    if ($request->has('balance_filter') && !empty($request->balance_filter)) {
      if ($request->balance_filter === 'greater_than_zero') {
        // Riders with balance greater than 0
        $query->whereHas('account', function ($q) {
          $q->whereRaw('(SELECT COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) FROM transactions WHERE account_id = accounts.id) > 0');
        });
      }
    }

    if ($request->filled('quick_search')) {
      $search = $request->input('quick_search');

      $query->leftJoin('customers', 'riders.customer_id', '=', 'customers.id')
        ->leftJoin('bikes', 'riders.id', '=', 'bikes.rider_id')
        ->where(function ($q) use ($search) {
          $q->where('riders.name', 'like', "%{$search}%")
            ->orWhere('riders.rider_id', 'like', "%{$search}%")
            ->orWhere('riders.courier_id', 'like', "%{$search}%")
            ->orWhere('riders.branded_plate_no', 'like', "%{$search}%")
            ->orWhere('riders.fleet_supervisor', 'like', "%{$search}%")
            ->orWhere('riders.emirate_hub', 'like', "%{$search}%")
            ->orWhere('riders.customer_id', 'like', "%{$search}%")
            ->orWhere('riders.designation', 'like', "%{$search}%")
            ->orWhere('customers.name', 'like', "%{$search}%");
          if (stripos($search, 'active') !== false) {
            $q->orWhereExists(function ($subQuery) {
              $subQuery->select(\DB::raw(1))
                ->from('bikes')
                ->whereRaw('bikes.rider_id = riders.id')
                ->where('bikes.warehouse', '=', 'Active');
            });
          }
          if (stripos($search, 'inactive') !== false) {
            $q->orWhere(function ($subQ) {
              $subQ->whereNotExists(function ($subQuery) {
                $subQuery->select(\DB::raw(1))
                  ->from('bikes')
                  ->whereRaw('bikes.rider_id = riders.id')
                  ->where('bikes.warehouse', '=', 'Active');
              });
            });
          }
        });
      $query->select('riders.*');
    }

    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);

    $tableData = view('riders.table', [
      'data' => $data,
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
   * Show the form for creating a new Riders.
   */
  public function create()
  {
    return view('riders.create');
  }
  /**
   * Store a newly created Riders in storage.
   */
  public function store(CreateRidersRequest $request)
  {
    try {
      DB::beginTransaction();

      $input = $request->all();
      $items = $request->get('items');

      // Check if rider with this rider_id already exists
      $existingRider = Riders::where('rider_id', $input['rider_id'])->first();
      if ($existingRider) {
        DB::rollback();

        if (request()->ajax()) {
          return response()->json([
            'success' => false,
            'message' => 'A rider with ID ' . $input['rider_id'] . ' already exists. Please use a different Rider ID.',
            'errors' => [
              'rider_id' => ['A rider with this ID already exists.']
            ]
          ], 422);
        }

        Flash::error('A rider with ID ' . $input['rider_id'] . ' already exists.');
        return redirect()->back()->withInput();
      }

      $riders = $this->ridersRepository->create($input);
      if ($riders) {

        /* $parentAccount = Accounts::firstOrCreate(
          ['name' => 'Riders', 'account_type' => 'Liability', 'parent_id' => null],
          ['name' => 'Riders', 'account_type' => 'Liability', 'account_code' => Account::code()]
        ); */

        $account = new Accounts();
        $account->account_code = 'RD' . str_pad($riders->rider_id, 4, "0", STR_PAD_LEFT);
        $account->name = $riders->name;
        $account->account_type = 'Liability';
        $account->ref_name = 'Rider';
        $account->parent_id = HeadAccount::RIDER;
        $account->ref_id = $riders->id;
        $account->save();

        if ($items) {
          foreach ($items['id'] as $key => $val) {
            if ($items['id'][$key] != 0) {
              $riderItemPrice = new RiderItemPrice();
              $riderItemPrice->item_id = $items['id'][$key];
              $riderItemPrice->price = isset($item['price'][$key]) ? $items['price'][$key] : 0;
              $riderItemPrice->RID = $riders->id;
              $riderItemPrice->save();
            }
          }
        }

        $riders->account_id = $account->id;
        $riders->status = 3;
        $riders->save();
      }

      DB::commit();

      // Check if request is AJAX
      if (request()->ajax()) {
        return response()->json([
          'success' => true,
          'message' => 'Rider created successfully!',
          'redirect_url' => route('riders.index')
        ]);
      }

      Flash::success('Rider created successfully.');
      return redirect(route('riders.index'));
    } catch (\Illuminate\Database\QueryException $e) {
      DB::rollback();

      // Handle duplicate entry error
      if ($e->getCode() == 23000) {
        $errorMessage = 'A rider with this ID already exists. Please use a different Rider ID.';

        if (request()->ajax()) {
          return response()->json([
            'success' => false,
            'message' => $errorMessage,
            'errors' => [
              'rider_id' => ['A rider with this ID already exists.']
            ]
          ], 422);
        }

        Flash::error($errorMessage);
        return redirect()->back()->withInput();
      }

      // Handle other database errors
      Log::error('Rider creation error: ' . $e->getMessage());

      if (request()->ajax()) {
        return response()->json([
          'success' => false,
          'message' => 'An error occurred while creating the rider. Please try again.'
        ], 500);
      }

      Flash::error('An error occurred while creating the rider. Please try again.');
      return redirect()->back()->withInput();
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Rider creation error: ' . $e->getMessage());

      if (request()->ajax()) {
        return response()->json([
          'success' => false,
          'message' => 'An unexpected error occurred. Please try again.'
        ], 500);
      }

      Flash::error('An unexpected error occurred. Please try again.');
      return redirect()->back()->withInput();
    }
  }

  /**
   * Display the specified Riders.
   */
  public function show($id)
  {
    $rider = $this->ridersRepository->find($id);
    // $rider_items = $rider->items;
    $result = $rider->toArray();
    $job_status = JobStatus::where('RID', $id)->orderByDesc('id')->get();

    // Get dropdown data for edit forms
    $countries = \App\Models\Countries::pluck('name', 'id')->prepend('Select', '');
    $vendors = \App\Models\Vendors::pluck('name', 'id')->prepend('Select', '');
    $customers = \App\Models\Customers::pluck('name', 'id')->prepend('Select', '');

    return view('riders.show_fields', compact('result', 'rider', 'job_status', 'countries', 'vendors', 'customers'));
  }

  /**
   * Show the form for editing the specified Riders.
   */
  public function edit($id)
  {
    // $riders = $this->ridersRepository->find($id);
    $riders = $this->ridersRepository->getRiderWithItemsRelations($id);

    if (empty($riders)) {
      Flash::error('Riders not found');

      return redirect(route('riders.index'));
    }

    return view('riders.edit')->with('riders', $riders);
  }

  /**
   * Update the specified Riders in storage.
   */
  public function update($id, UpdateRidersRequest $request)
  {
    $riders = $this->ridersRepository->getRiderWithItemsRelations($id);
    // $items = $riders->items;
    $items = $request->get('items');
    if (empty($riders)) {
      Flash::error('Riders not found');

      return redirect(route('riders.index'));
    }

    $riders = $this->ridersRepository->update($request->all(), $id);
    if ($riders) {

      $riders->account->name = $riders->name;
      $riders->account->account_code = 'RD' . str_pad($riders->rider_id, 4, "0", STR_PAD_LEFT);
      $riders->account->save();

      if ($request->items) {
        RiderItemPrice::where('RID', $id)->delete();
        $items = $request->items;
        foreach ($items['id'] as $key => $val) {

          $riderItemPrice = new RiderItemPrice();
          $riderItemPrice->item_id = $items['id'][$key];
          $riderItemPrice->price = $items['price'][$key] ?? 0;
          $riderItemPrice->RID = $riders->id;
          $riderItemPrice->save();
        }
      }
    }
    // Check if request is AJAX
    if (request()->ajax()) {
      return response()->json([
        'success' => true,
        'message' => 'Rider information updated successfully!',
        'redirect_url' => route('riders.index')
      ]);
    }

    /*     Flash::success('Riders updated successfully.');
     */
    return redirect(route('riders.index'));
  }

  /**
   * Remove the specified Riders from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $riders = $this->ridersRepository->find($id);

    if (empty($riders)) {
      Flash::error('Riders not found');

      return redirect(route('riders.index'));
    }

    // Check if rider account has any transactions
    if ($riders->account_id) {
      $accountTransactions = Transactions::where('account_id', $riders->account_id)->count();
      if ($accountTransactions > 0) {
        Flash::error('Cannot delete rider. The rider account has ' . $accountTransactions . ' transaction(s). Please remove all transactions first.');
        return redirect(route('riders.index'));
      }
    }

    // Check if rider has any vouchers (by ref_id or rider_id)
    $vouchersCount = Vouchers::where(function ($query) use ($id) {
      $query->where('ref_id', $id)
        ->orWhere('rider_id', $id);
    })->count();

    if ($vouchersCount > 0) {
      Flash::error('Cannot delete rider. The rider has ' . $vouchersCount . ' voucher(s). Please remove all vouchers first.');
      return redirect(route('riders.index'));
    }

    // Check for other related records
    $relatedRecords = [];

    // Check rider invoices
    $riderInvoicesCount = RiderInvoices::where('rider_id', $id)->count();
    if ($riderInvoicesCount > 0) {
      $relatedRecords[] = $riderInvoicesCount . ' invoice(s)';
    }

    // Check rider activities
    $riderActivitiesCount = RiderActivities::where('rider_id', $id)->count();
    if ($riderActivitiesCount > 0) {
      $relatedRecords[] = $riderActivitiesCount . ' activity record(s)';
    }

    // Check rider attendance
    $riderAttendanceCount = RiderAttendance::where('rider_id', $id)->count();
    if ($riderAttendanceCount > 0) {
      $relatedRecords[] = $riderAttendanceCount . ' attendance record(s)';
    }

    // Check rider emails
    $riderEmailsCount = RiderEmails::where('rider_id', $id)->count();
    if ($riderEmailsCount > 0) {
      $relatedRecords[] = $riderEmailsCount . ' email record(s)';
    }

    // Check rider item prices
    $riderItemPricesCount = RiderItemPrice::where('RID', $id)->count();
    if ($riderItemPricesCount > 0) {
      $relatedRecords[] = $riderItemPricesCount . ' item price record(s)';
    }

    // Check bikes
    $bikesCount = Bikes::where('rider_id', $id)->count();
    if ($bikesCount > 0) {
      $relatedRecords[] = $bikesCount . ' bike assignment(s)';
    }

    // Check bike history
    $bikeHistoryCount = BikeHistory::where('rider_id', $id)->count();
    if ($bikeHistoryCount > 0) {
      $relatedRecords[] = $bikeHistoryCount . ' bike history record(s)';
    }

    // Check RTA fines
    $rtaFinesCount = RtaFines::where('rider_id', $id)->count();
    if ($rtaFinesCount > 0) {
      $relatedRecords[] = $rtaFinesCount . ' RTA fine record(s)';
    }

    // Check Salik records
    $salikCount = salik::where('rider_id', $id)->count();
    if ($salikCount > 0) {
      $relatedRecords[] = $salikCount . ' Salik record(s)';
    }

    // Check visa expenses
    $visaExpensesCount = visa_expenses::where('rider_id', $id)->count();
    if ($visaExpensesCount > 0) {
      $relatedRecords[] = $visaExpensesCount . ' visa expense record(s)';
    }

    // Check visa installment plans
    $visaInstallmentCount = visa_installment_plan::where('rider_id', $id)->count();
    if ($visaInstallmentCount > 0) {
      $relatedRecords[] = $visaInstallmentCount . ' visa installment plan record(s)';
    }

    // Check job status
    $jobStatusCount = JobStatus::where('RID', $id)->count();
    if ($jobStatusCount > 0) {
      $relatedRecords[] = $jobStatusCount . ' job status record(s)';
    }

    // Check files
    $filesCount = Files::where('type_id', $id)->where('type', 'rider')->count();
    if ($filesCount > 0) {
      $relatedRecords[] = $filesCount . ' file(s)';
    }

    // If there are any related records, prevent deletion
    if (!empty($relatedRecords)) {
      $message = 'Cannot delete rider. The rider has the following related records: ' . implode(', ', $relatedRecords) . '. Please remove all related records first.';
      Flash::error($message);
      return redirect(route('riders.index'));
    }

    $this->ridersRepository->delete($id);

    Flash::success('Riders deleted successfully.');

    return redirect(route('riders.index'));
  }

  public function getItems(Request $request)
  {
    /* $random = rand(0,999);
    $row = '<td>';
    $row .= '<select name="items['.$random.'][item_id]" class="form-control form-control-sm""><option value="0">Select Item</option>';
        $items = Item::all();
        foreach($items as $item){
            $row .='<option value="'.$item->id.'">'.$item->item_name.' - '.$item->pirce.'</option>';
        }
    $row .='</select></td>';
    $row .='<td><label>Price: &nbsp;</label>';
    $row .='<input type="number" step="any" name="items['.$random.'][price]" /></td>';

    $row .='<td><input type="button" class="ibtnDel btn btn-md btn-xs btn-danger "  value="Delete"></td>'; */

    $item = Item::find($request->item_id);
    $row = '<td width="250"><label>' . $item->item_name . '(Price: ' . $item->pirce . ')</label></td>
      <td width="130"><input type="number" name="items[' . $item->id . ']" id="item-' . $item->id . '" value="' . $request->item_price . '" step="any" class="form-control form-control-sm" /></td>';

    $row .= '<td width="300"><input type="button" class="ibtnDel btn btn-md btn-xs btn-danger "  value="Delete"></td>';
    return $row;
  }
  /*
   *
   */

  public function document($rider_id)
  {
    if (request()->post()) {

      foreach (request('documents') as $document) {

        if ($document['expiry_date']) {
          $data = [];
          if (isset($document['file_name'])) {

            $extension = $document['file_name']->extension();
            $name = $document['type'] . '-' . $rider_id . '-' . time() . '.' . $extension;
            $document['file_name']->storeAs('rider', $name);

            $data['file_name'] = $name;
            $data['file_type'] = $extension;
          }

          $data['type_id'] = $rider_id;
          $data['type'] = $document['type'];
          $data['expiry_date'] = $document['expiry_date'];

          $condition = [
            'type' => $document['type'],
            'type_id' => $rider_id
          ];

          Files::updateOrCreate($condition, $data);
        } else {
          if (isset($document['file_name'])) {
            return response()->json(['errors' => ['error' => General::file_types($document['type']) . ' expiry date must be selected.']], 422);
          }
        }
      }
      return 1;
    }

    $files = Files::where('type_id', $rider_id)->get();
    $rider = Riders::find($rider_id);

    return view('riders.document', compact('files', 'rider'));
  }
  public function timeline($id)
  {
    $riders = Riders::find($id);
    $job_status = JobStatus::where('RID', $id)->orderByDesc('id')->get();
    return view('riders.timeline', compact('riders', 'job_status'));
  }

  public function contract($id)
  {
    $rider = Riders::find($id);

    return view('riders.contract', compact('rider'));
  }
  public function contract_upload(Request $request, $id)
  {
    if (isset($request->contract)) {

      $doc = $request->contract;
      $extension = $doc->extension();
      $name = time() . '.' . $extension;
      $doc->storeAs('contract', $name);

      $rider = Riders::find($request->id);
      $rider->contract = $name;
      $rider->save();

      return redirect(route('riders.index'))->with('success', $rider->name . '( ' . $rider->rider_id . ' ) Contract uploaded.');
    } else {
      $rider = Riders::find($id);
      return view('riders.contract-modal', compact('rider'));
    }
  }

  public function picture_upload(Request $request, $id)
  {
    if (isset($request->image_name)) {

      $image_name = $request->image_name;
      $extension = $image_name->extension();
      $name = time() . '.' . $extension;
      $image_name->storeAs('profile', $name);

      $rider = Riders::find($request->id);
      $rider->image_name = $name;
      $rider->save();

      Flash::success('Profile picture uploaded successfully.');
      return redirect()->back();
      // redirect(url('rider'))->with('success', $rider->name . '( ' . $rider->rider_id . ' ) Profile Picture uploaded.');
    }
  }

  public function job_status($id, Request $request)
  {
    $rider = Riders::find($id);

    if ($request->isMethod('post')) {
      $input = $request->all();
      $input['RID'] = $id;
      $input['status_by'] = auth()->user()->id;
      JobStatus::create($input);
      /*  $rider = Riders::find($id);
       $rider->job_status = $input['job_status'];
       $rider->save(); */
      return "Timeline added successfully";
    }
    return view('riders.job_status-modal', compact('rider'));
  }

  public function updateRider()
  {
    $riders = Riders::all();

    $parentAccount = Accounts::firstOrCreate(
      ['name' => 'Riders', 'account_type' => 'Liability', 'parent_id' => null],
      ['name' => 'Riders', 'account_type' => 'Liability', 'account_code' => Account::code()]
    );

    foreach ($riders as $rider) {

      $account = new Accounts();
      $account->account_code = 'RD' . str_pad($rider->rider_id, 4, "0", STR_PAD_LEFT);
      $account->name = $rider->name;
      $account->account_type = 'Liability';
      $account->ref_name = 'Rider';
      $account->parent_id = $parentAccount->id;
      $account->ref_id = $rider->id;
      $account->save();

      $rider->account_id = $account->id;
      $rider->save();
    }
  }
  public function ledger($rider_id, LedgerDataTable $ledgerDataTable)
  {
    $rider = Riders::find($rider_id);
    $files = Transactions::where('account_id', $rider->account_id)->get();
    $account_id = $rider->account_id;
    return $ledgerDataTable->with(['account_id' => $account_id])->render('riders.ledger', compact('files', 'rider'));
  }
  public function items($rider_id)
  {
    $rider = $this->ridersRepository->find($rider_id);
    return view('riders.items', compact('rider'));
  }
  public function additems($rider_id)
  {
    $rider = $this->ridersRepository->find($rider_id);
    return view('riders.additems', compact('rider'));
  }

  public function storeitems(Request $request, $rider_id)
  {
    $rider = $this->ridersRepository->find($rider_id);

    if (empty($rider)) {
      return response()->json([
        'success' => false,
        'message' => 'Rider not found'
      ], 404);
    }

    try {
      if ($request->items) {
        $items = $request->items;
        $duplicates = [];
        $usedItems = [];

        // Check for duplicates in the submitted items
        foreach ($items['id'] as $key => $val) {
          if ($val != 0) {
            if (in_array($val, $usedItems)) {
              $duplicates[] = $val;
            }
            $usedItems[] = $val;
          }
        }

        // Check for existing items
        foreach ($usedItems as $itemId) {
          $existingItem = RiderItemPrice::where('RID', $rider_id)
            ->where('item_id', $itemId)
            ->first();
          if ($existingItem) {
            $duplicates[] = $itemId;
          }
        }

        if (!empty($duplicates)) {
          $duplicateItems = \App\Models\Items::whereIn('id', array_unique($duplicates))->pluck('name');
          return response()->json([
            'success' => false,
            'message' => 'The following items are duplicates or already assigned: ' . implode(', ', $duplicateItems->toArray())
          ], 422);
        }

        // If no duplicates, proceed with saving
        foreach ($items['id'] as $key => $val) {
          if ($val != 0) {
            $riderItemPrice = new RiderItemPrice();
            $riderItemPrice->item_id = $val;
            $riderItemPrice->price = $items['price'][$key] ?? 0;
            $riderItemPrice->RID = $rider_id;
            $riderItemPrice->save();
          }
        }

        return response()->json([
          'success' => true,
          'message' => 'Items added successfully'
        ]);
      }
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error adding items: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Add a single item for a rider (inline add)
   */
  public function additem(Request $request, $rider_id)
  {
    try {
      $request->validate([
        'item_id' => 'required|integer|min:1',
        'price' => 'required|numeric|min:0',
      ]);

      $rider = $this->ridersRepository->find($rider_id);
      if (empty($rider)) {
        return response()->json([
          'success' => false,
          'message' => 'Rider not found'
        ], 404);
      }

      // Check duplicate in DB
      $exists = RiderItemPrice::where('RID', $rider_id)
        ->where('item_id', $request->item_id)
        ->exists();

      if ($exists) {
        return response()->json([
          'success' => false,
          'message' => 'This item is already assigned to the rider'
        ], 422);
      }

      $rip = new RiderItemPrice();
      $rip->RID = $rider_id;
      $rip->item_id = (int) $request->item_id;
      $rip->price = (float) $request->price;
      $rip->save();

      $item = \App\Models\Items::find($rip->item_id);

      return response()->json([
        'success' => true,
        'message' => 'Item added successfully',
        'data' => [
          'id' => $rip->id,
          'item_id' => $rip->item_id,
          'item_name' => $item->name ?? 'Item',
          'price' => $rip->price,
        ]
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error adding item: ' . $e->getMessage()
      ], 500);
    }
  }

  public function edititem($rider_id, $item_id)
  {
    try {
      $riderItem = RiderItemPrice::where('RID', $rider_id)
        ->where('id', $item_id)
        ->firstOrFail();

      return response()->json([
        'success' => true,
        'data' => $riderItem
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Item not found'
      ], 404);
    }
  }

  public function updateitem(Request $request, $rider_id, $item_id)
  {
    try {
      // Check if item already exists for this rider
      $existingItem = RiderItemPrice::where('RID', $rider_id)
        ->where('item_id', $request->item_id)
        ->where('id', '!=', $item_id)
        ->first();

      if ($existingItem) {
        return response()->json([
          'success' => false,
          'message' => 'This item is already assigned to the rider'
        ], 422);
      }

      $riderItem = RiderItemPrice::where('RID', $rider_id)
        ->where('id', $item_id)
        ->firstOrFail();

      $riderItem->item_id = $request->item_id;
      $riderItem->price = $request->price;
      $riderItem->save();

      return response()->json([
        'success' => true,
        'message' => 'Item updated successfully'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating item: ' . $e->getMessage()
      ], 500);
    }
  }

  public function deleteitem($rider_id, $item_id)
  {
    try {
      $riderItem = RiderItemPrice::where('RID', $rider_id)
        ->where('id', $item_id)
        ->firstOrFail();

      $riderItem->delete();
      Flash::success('Rider item deleted successfully.');
      return redirect()->back();
    } catch (\Exception $e) {
      Flash::error('Error deleting rider item: ' . $e->getMessage());
      return redirect()->back();
    }
  }
  public function attendance($rider_id, RiderAttendanceDataTable $riderAttendanceDataTable)
  {
    return $riderAttendanceDataTable->with(['rider_id' => $rider_id])->render('riders.attendance');
  }
  public function activities($rider_id)
  {
    $month = request('month') ?? date('Y-m');
    $filters = [
      'rider_id' => $rider_id,
      'month' => $month,
    ];

    // Parse month to get year and month
    $year = date('Y', strtotime($month . '-01'));
    $monthNum = date('m', strtotime($month . '-01'));

    $query = RiderActivities::where('rider_id', $rider_id)
      ->whereYear('date', $year)
      ->whereMonth('date', $monthNum);

    $data = $query->orderBy('date', 'desc')->get();

    // Calculate totals from database
    $totals = [
      'working_days' => $data->count(),
      'valid_days' => $data->where('delivery_rating', 'Yes')->count(),
      'invalid_days' => $data->where('delivery_rating', 'No')->count(),
      'off_days' => $data->filter(function ($item) {
        return $item->delivery_rating != 'Yes' && $item->delivery_rating != 'No';
      })->count(),
      'total_orders' => $data->sum('delivered_orders'),
      'total_rejected' => $data->sum('rejected_orders'),
      'total_hours' => $data->sum('login_hr'),
      'avg_ontime' => $data->where('ontime_orders_percentage', '>', 0)->avg('ontime_orders_percentage') ?? 0,
    ];

    // Convert average ontime to percentage
    $totals['avg_ontime'] = $totals['avg_ontime'] * 100;

    return view('riders.activities', compact('data', 'filters', 'totals'));
  }

  public function activitiesPdf($rider_id)
  {
    $month = request('month') ?? date('Y-m');
    $filters = [
      'rider_id' => $rider_id,
      'month' => $month,
    ];

    // Parse month to get year and month
    $year = date('Y', strtotime($month . '-01'));
    $monthNum = date('m', strtotime($month . '-01'));

    $query = RiderActivities::where('rider_id', $rider_id)
      ->whereYear('date', $year)
      ->whereMonth('date', $monthNum);

    // Get all data for totals calculation
    $allData = $query->orderBy('date', 'desc')->get();

    // Limit to 30 rows for display
    $data = $allData->take(30);

    // Calculate totals from all data (not just displayed rows)
    $totals = [
      'working_days' => $allData->count(),
      'valid_days' => $allData->where('delivery_rating', 'Yes')->count(),
      'invalid_days' => $allData->where('delivery_rating', 'No')->count(),
      'off_days' => $allData->filter(function ($item) {
        return $item->delivery_rating != 'Yes' && $item->delivery_rating != 'No';
      })->count(),
      'total_orders' => $allData->sum('delivered_orders'),
      'total_rejected' => $allData->sum('rejected_orders'),
      'total_hours' => $allData->sum('login_hr'),
      'avg_ontime' => $allData->where('ontime_orders_percentage', '>', 0)->avg('ontime_orders_percentage') ?? 0,
    ];

    // Convert average ontime to percentage
    $totals['avg_ontime'] = $totals['avg_ontime'] * 100;

    // Get rider info
    $rider = Riders::find($rider_id);

    // Use dompdf directly (dompdf/dompdf package is installed)
    $dompdf = new \Dompdf\Dompdf();
    $html = view('riders.activities_pdf', compact('data', 'filters', 'totals', 'rider', 'month'))->render();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');

    // Set options to fit exactly 30 rows on one page with absolute minimal margins
    $options = $dompdf->getOptions();
    $options->set([
      'defaultFont' => 'Arial',
      'isHtml5ParserEnabled' => true,
      'isRemoteEnabled' => false,
      'marginTop' => 2,
      'marginBottom' => 2,
      'marginLeft' => 2,
      'marginRight' => 2,
      'enableCssFloat' => false,
    ]);
    $dompdf->setOptions($options);

    $dompdf->render();

    $filename = 'Rider_Activities_' . ($rider->name ?? $rider_id) . '_' . $month . '.pdf';

    return response()->streamDownload(function () use ($dompdf) {
      echo $dompdf->output();
    }, $filename, [
      'Content-Type' => 'application/pdf',
    ]);
  }

  public function activitiesPrint($rider_id)
  {
    $month = request('month') ?? date('Y-m');
    $filters = [
      'rider_id' => $rider_id,
      'month' => $month,
    ];

    // Parse month to get year and month
    $year = date('Y', strtotime($month . '-01'));
    $monthNum = date('m', strtotime($month . '-01'));

    $query = RiderActivities::where('rider_id', $rider_id)
      ->whereYear('date', $year)
      ->whereMonth('date', $monthNum);

    // Get all data for totals calculation
    $allData = $query->orderBy('date', 'desc')->get();

    // Limit to 30 rows for display
    $data = $allData->take(30);

    // Calculate totals from all data (not just displayed rows)
    $totals = [
      'working_days' => $allData->count(),
      'valid_days' => $allData->where('delivery_rating', 'Yes')->count(),
      'invalid_days' => $allData->where('delivery_rating', 'No')->count(),
      'off_days' => $allData->filter(function ($item) {
        return $item->delivery_rating != 'Yes' && $item->delivery_rating != 'No';
      })->count(),
      'total_orders' => $allData->sum('delivered_orders'),
      'total_rejected' => $allData->sum('rejected_orders'),
      'total_hours' => $allData->sum('login_hr'),
      'avg_ontime' => $allData->where('ontime_orders_percentage', '>', 0)->avg('ontime_orders_percentage') ?? 0,
    ];

    // Convert average ontime to percentage
    $totals['avg_ontime'] = $totals['avg_ontime'] * 100;

    // Get rider info
    $rider = Riders::find($rider_id);

    return view('riders.activities_print', compact('data', 'filters', 'totals', 'rider', 'month'));
  }

  public function invoices($rider_id, RiderInvoicesDataTable $riderInvoicesDataTable)
  {
    return $riderInvoicesDataTable->with(['rider_id' => $rider_id])->render('riders.invoices');
  }
  public function emails($rider_id, RiderEmailsDataTable $riderEmailsDataTable)
  {
    return $riderEmailsDataTable->with(['rider_id' => $rider_id])->render('riders.emails');
  }

  /**
   * Import rider vouchers from Excel
   * Expected columns: Rider ID, Billing Month, Date, Amount, Voucher Type, Account_id
   */
  public function importVouchers(Request $request)
  {
    if ($request->isMethod('post')) {
      $request->validate([
        'file' => 'required|mimes:xlsx|max:50000',
        'payment_from' => 'required|integer',
      ], [
        'file.required' => 'Excel file is required',
        'payment_from.required' => 'Select the account to credit',
      ]);

      try {
        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\ImportVoucher(), $request->file('file'));
        Flash::success('Vouchers imported successfully.');
      } catch (\Throwable $e) {
        Flash::error('Error importing vouchers: ' . $e->getMessage());
      }
    }

    return view('riders.import_vouchers', compact('bank_accounts'));
  }

  /**
   * Standalone page to import rider vouchers (save only into vouchers table).
   * Expected headers: Rider ID, Billing Month, Date, Amount, Voucher Type, Account_id
   */
  public function importRiderVouchers(Request $request)
  {
    if ($request->isMethod('post')) {
      $request->validate([
        'file' => 'required|mimes:xlsx|max:50000',
      ], [
        'file.required' => 'Excel file is required',
      ]);

      try {
        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\ImportRiderVoucherOnly(), $request->file('file'));
        Flash::success('Rider vouchers imported successfully.');
      } catch (\Throwable $e) {
        Flash::error('Error importing rider vouchers: ' . $e->getMessage());
      }

      return redirect()->back();
    }

    // On GET: return modal or full page depending on query
    if ($request->query('modal')) {
      return view('riders.import_rider_voucher_modal');
    }
    return view('riders.import_rider_voucher');
  }

  public function visaloan($rider_id)
  {
    $rider = Riders::find($rider_id);
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();
    return view('riders.visaloan-modal', compact('rider', 'account', 'accounts', 'bank_accounts'));
  }

  public function advanceloan($rider_id)
  {
    $rider = Riders::find($rider_id);
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();
    return view('riders.advanceloan-modal', compact('rider', 'account', 'accounts', 'bank_accounts'));
  }

  public function files($rider_id, FilesDataTable $filesDataTable)
  {
    $expectedFiles = [
        'profile' => 'Profile Photo',
        'passport' => ['Passport 1st Page', 'Passport 2nd Page'],
        'nic' => ['NIC/National ID Front', 'NIC/National ID Back'],
        'emirates' => ['Emirates ID Front', 'Emirates ID Back'],
        'labor' => 'Labor Card',
        'residency' => 'Residency',
        'mol' => 'MOL/Job Offer Letter',
        'license' => 'Driving License',
        'health' => 'Health Insurance',
        'workers' => 'Workers Insurance',
        'road' => 'Road Permit',
        'contract' => 'Rider Contract'
    ];

    $riderFiles = DB::table('files')
                  ->where('type', 'rider')
                  ->where('type_id', $rider_id)
                  ->get()
                  ->pluck('name');

    $riderFiles = $riderFiles->all();
    $missingFiles = [];

    foreach($expectedFiles as $key => $desc){
        $found = false;
        foreach($riderFiles as $riderFile){
            if(str_contains($riderFile, $key)){
              $found = true;
              break;
            }
        }
        if(!$found) $missingFiles[$key] = $desc;
    }
    return $filesDataTable->with(['type_id' => $rider_id, 'type' => 'rider', 'missingFiles' => $missingFiles])->render('riders.document', compact('missingFiles'));
  }

  public function sendEmail($id, Request $request)
  {
    if ($request->isMethod('post')) {
      $data = [
        'html' => $request->email_message
      ];
      /* $res = RiderInvoices::with(['riderInv_item'])->where('id', $id)->get();
      $pdf = \PDF::loadView('invoices.rider_invoices.show', ['res' => $res]); */
      $fileName = $id . "_monthly_activity_{$request->month}.xlsx";
      $filePath = storage_path("app/public/{$fileName}");
      Excel::store(new MonthlyActivityExport($id, $request->month), "public/{$fileName}");
      Mail::send('emails.general', $data, function ($message) use ($request, $filePath) {
        $message->to([$request->email_to]);
        $message->cc(env('ADMIN_CC_EMAIL'));
        $message->bcc(["haseeb@efdservice.com", "adnan@efdservice.com", "sumayya@efdservice.com"]);
        $message->replyTo([env('ADMIN_CC_EMAIL')]);
        $message->subject($request->email_subject);
        //$message->attachData($pdf->output(), $request->email_subject . '.pdf');
        $message->attach($filePath);
        $message->priority(3);
      });
      $email_data = [
        'rider_id' => $id,
        'mail_to' => $request->email_to,
        'subject' => $request->email_subject,
        'message' => $request->email_message,
      ];
      RiderEmails::create($email_data);
    }
    $rider = Riders::find($id);
    return view('riders.send_email', compact('rider'));
  }

  public function exportRiders()
  {
    return Excel::download(new RiderExport(), 'Riders_export_' . now() . '.xlsx');
  }

  public function exportCustomizableRiders(Request $request)
  {
    // Get column configuration from request or user settings
    $visibleColumns = $request->input('visible_columns');
    $columnOrder = $request->input('column_order');
    $format = $request->input('format', 'excel');

    // Parse JSON strings if they exist
    if (is_string($visibleColumns)) {
      $visibleColumns = json_decode($visibleColumns, true);
    }
    if (is_string($columnOrder)) {
      $columnOrder = json_decode($columnOrder, true);
    }

    // If no column settings provided in request, get from user's saved settings
    if (empty($visibleColumns) || empty($columnOrder)) {
      $userSettings = \App\Models\UserTableSettings::getSettings(auth()->id(), 'riders_table');

      if ($userSettings) {
        $visibleColumns = $visibleColumns ?: $userSettings->visible_columns;
        $columnOrder = $columnOrder ?: $userSettings->column_order;
      }
    }

    // Get current filters from session or request
    $filters = [
      'rider_id' => $request->input('rider_id') ?: session('riders_filter.rider_id'),
      'name' => $request->input('name') ?: session('riders_filter.name'),
      'fleet_supervisor' => $request->input('fleet_supervisor') ?: session('riders_filter.fleet_supervisor'),
      'status' => $request->input('status') ?: session('riders_filter.status'),
      'emirate_hub' => $request->input('emirate_hub') ?: session('riders_filter.emirate_hub'),
      'quick_search' => $request->input('quick_search') ?: session('riders_filter.quick_search'),
    ];

    // Create customizable export
    $export = new \App\Exports\CustomizableRiderExport($visibleColumns, $columnOrder, $filters);

    // Generate filename with format
    $timestamp = now()->format('Y-m-d_H-i-s');
    $username = auth()->user()->name ?? auth()->user()->email ?? 'user';
    $username = preg_replace('/[^a-zA-Z0-9]/', '_', $username); // Sanitize username for filename
    $filename = "Riders_export_{$username}_{$timestamp}";

    // Return appropriate format
    switch ($format) {
      case 'csv':
        return Excel::download($export, "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
      case 'pdf':
        return Excel::download($export, "{$filename}.pdf", \Maatwebsite\Excel\Excel::DOMPDF);
      case 'excel':
      default:
        return Excel::download($export, "{$filename}.xlsx");
    }
  }

  public function updateSection(Request $request, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    $section = $request->input('section');
    $data = $request->except(['_token', 'section']);

    try {
      // Update only the fields for the specific section
      $rider->update($data);

      return response()->json([
        'success' => true,
        'message' => ucfirst($section) . ' information updated successfully'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating ' . $section . ' information'
      ], 500);
    }
  }

  public function toggleAbsconder(Request $request, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      // Toggle the absconder status
      $rider->absconder = $rider->absconder ? 0 : 1;
      $rider->save();

      return response()->json([
        'success' => true,
        'message' => 'Absconder status updated successfully',
        'absconder' => $rider->absconder
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating absconder status'
      ], 500);
    }
  }

  public function toggleFlowup(Request $request, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      // Toggle the flowup status
      $rider->flowup = $rider->flowup ? 0 : 1;
      $rider->save();

      return response()->json([
        'success' => true,
        'message' => 'Flowup status updated successfully',
        'flowup' => $rider->flowup
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating flowup status'
      ], 500);
    }
  }

  public function toggleLlicense(Request $request, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      // Toggle the l_license status
      $rider->l_license = $rider->l_license ? 0 : 1;
      $rider->save();

      return response()->json([
        'success' => true,
        'message' => 'Learning license status updated successfully',
        'l_license' => $rider->l_license
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating learning license status'
      ], 500);
    }
  }

  public function toggleWalker(Request $request, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      // Toggle designation Walker: if currently Walker -> clear, else set to Walker
      $isSettingWalker = $rider->designation !== 'Walker';
      $rider->designation = $isSettingWalker ? 'Walker' : null;

      // If setting to Walker, set status to 1 and assign fleet supervisor and customer
      if ($isSettingWalker) {
        $rider->status = 1;
        $rider->fleet_supervisor = 'Waqas';
        $rider->customer_id = 1;
      } else {
        $rider->status = 3;
      }

      $rider->save();

      // If setting to Walker and a bike is currently assigned, return it today
      if ($isSettingWalker) {
        $bike = Bikes::where('rider_id', $rider->id)->first();
        if ($bike) {
          $today = Carbon::now()->toDateString();

          // Close last open bike history for this rider and bike
          $lastHistory = BikeHistory::where('bike_id', $bike->id)
            ->where('rider_id', $rider->id)
            ->whereNull('return_date')
            ->latest('note_date')
            ->first();
          if ($lastHistory) {
            $lastHistory->update([
              'warehouse'   => 'Return',
              'return_date' => $today,
              'updated_by'  => Auth::id(),
            ]);
          }

          // Update bike to returned
          $bike->update([
            'rider_id'  => null,
            'warehouse' => 'Return',
          ]);

          // Note: customer_id is already set to 1 when Walker is activated
          // No need to detach from customer for Walkers
        }
      }

      return response()->json([
        'success' => true,
        'message' => 'Designation updated successfully',
        'designation' => $rider->designation,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating designation'
      ], 500);
    }
  }

  public function toggleMol(Request $request, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      // Toggle the MOL status
      $rider->mol = $rider->mol ? 0 : 1;
      $rider->save();

      return response()->json([
        'success' => true,
        'message' => 'MOL status updated successfully',
        'mol' => $rider->mol
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating MOL status'
      ], 500);
    }
  }
  public function togglePro(Request $request, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      // Toggle the PRO status
      $rider->pro = $rider->pro ? 0 : 1;
      $rider->save();

      return response()->json([
        'success' => true,
        'message' => 'PRO status updated successfully',
        'pro' => $rider->pro
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating PRO status'
      ], 500);
    }
  }

  public function returnBike(Request $request, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      $bike = Bikes::where('rider_id', $rider->id)->first();
      if (!$bike) {
        return response()->json([
          'success' => false,
          'message' => 'No bike currently assigned to this rider'
        ], 400);
      }

      $returnDate = $request->input('return_date');
      $returnDate = $returnDate ? Carbon::parse($returnDate)->toDateString() : Carbon::now()->toDateString();
      $notes = $request->input('notes');

      // Close last open bike history for this rider and bike
      $lastHistory = BikeHistory::where('bike_id', $bike->id)
        ->where('rider_id', $rider->id)
        ->whereNull('return_date')
        ->latest('note_date')
        ->first();
      if ($lastHistory) {
        $lastHistory->update([
          'warehouse'   => 'Return',
          'return_date' => $returnDate,
          'notes'       => $notes,
        ]);
      }

      // Update bike to returned
      $bike->update([
        'rider_id'  => null,
        'warehouse' => 'Return',
      ]);

      // Update rider state
      $rider->status = 3; // Return
      $rider->designation = null;
      $rider->customer_id = null;
      $rider->save();

      return response()->json([
        'success' => true,
        'message' => 'Bike returned successfully',
        'return_date' => $returnDate,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error returning bike'
      ], 500);
    }
  }

  public function storeadvanceloan(Request $request)
  {
    try {
      \DB::beginTransaction();

      // Validate the request
      $request->validate([
        'account_id' => 'required|array|min:2',
        'account_id.*' => 'required|integer',
        'dr_amount' => 'required|array',
        'dr_amount.*' => 'required|numeric|min:0',
        'narration' => 'required|array|min:2',
        'narration.*' => 'required|string',
      ]);

      // Get rider account (first entry should be the rider's liability account)
      $riderAccountId = $request->account_id[0];

      if (empty($riderAccountId)) {
        throw new \Exception('Rider account ID is required');
      }

      $riderAccount = Accounts::find($riderAccountId);

      if (!$riderAccount) {
        throw new \Exception('Rider account not found with ID: ' . $riderAccountId);
      }

      // Get the second account (credit account - should be Advance Loan account)
      $creditAccountId = $request->account_id[1] ?? HeadAccount::ADVANCE_LOAN;

      // Get amounts
      $riderAmount = $request->dr_amount[0] ?? 0;
      $creditAmount = $request->dr_amount[1] ?? 0;

      // Use the first amount for both entries if only one amount is provided
      if ($creditAmount == 0) {
        $creditAmount = $riderAmount;
      }

      // Generate transaction code
      $transCode = \App\Helpers\Account::trans_code();

      // Create voucher entry
      $voucherData = [
        'trans_date' => $request->trans_date ?? date('Y-m-d'),
        'voucher_type' => 'AL', // Advance Loan
        'payment_type' => $request->payment_type ?? 1, // Default to Cash
        'payment_from' => HeadAccount::ADVANCE_LOAN,
        'billing_month' => $this->normalizeBillingMonth($request->billing_month ?? null),
        'amount' => $riderAmount,
        'remarks' => 'Advance Loan to Rider',
        'ref_id' => $riderAccount->ref_id, // Rider ID
        'trans_code' => $transCode,
        'Created_By' => auth()->id(),
        'status' => 1
      ];

      $voucher = Vouchers::create($voucherData);

      // Create debit transaction for rider account (first entry)
      $debitTransaction = [
        'account_id' => $riderAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'AL',
        'trans_code' => $transCode,
        'trans_date' => $voucherData['trans_date'],
        'narration' => $request->narration[0] ?? 'Advance Loan Received',
        'debit' => $riderAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id()
      ];

      Transactions::create($debitTransaction);

      // Create credit transaction for advance loan account (second entry)
      $creditTransaction = [
        'account_id' => $creditAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'AL',
        'trans_code' => $transCode,
        'trans_date' => $voucherData['trans_date'],
        'narration' => $request->narration[1] ?? 'Advance Loan Given to ' . $riderAccount->name,
        'credit' => $creditAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id()
      ];

      Transactions::create($creditTransaction);

      \DB::commit();

      // Return success response
      return response()->json([
        'success' => true,
        'message' => 'Advance loan recorded successfully',
        'voucher_id' => $voucher->id,
        'trans_code' => $transCode
      ]);
    } catch (\Exception $e) {
      \DB::rollback();

      // Log the request data for debugging
      \Log::error('Advance loan error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error recording advance loan: ' . $e->getMessage(),
        'debug' => [
          'account_ids' => $request->account_id ?? 'not provided',
          'dr_amounts' => $request->dr_amount ?? 'not provided',
          'narrations' => $request->narration ?? 'not provided'
        ]
      ], 500);
    }
  }

  public function cod($rider_id)
  {
    $rider = Riders::find($rider_id);
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();
    return view('riders.cod-modal', compact('rider', 'account', 'accounts', 'bank_accounts'));
  }

  public function penalty($rider_id)
  {
    $rider = Riders::find($rider_id);
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();
    return view('riders.penalty-modal', compact('rider', 'account', 'accounts', 'bank_accounts'));
  }

  public function storecod(Request $request)
  {
    try {
      \DB::beginTransaction();

      // Validate the request
      $request->validate([
        'account_id' => 'required|array|min:2',
        'account_id.*' => 'required|integer',
        'dr_amount' => 'required|array',
        'dr_amount.*' => 'required|numeric|min:0',
        'narration' => 'required|array|min:2',
        'narration.*' => 'required|string',
      ]);

      // Get rider account (first entry should be the rider's liability account)
      $riderAccountId = $request->account_id[0];

      if (empty($riderAccountId)) {
        throw new \Exception('Rider account ID is required');
      }

      $riderAccount = Accounts::find($riderAccountId);

      if (!$riderAccount) {
        throw new \Exception('Rider account not found with ID: ' . $riderAccountId);
      }

      // Get the second account (credit account - should be COD account)
      $creditAccountId = $request->account_id[1];

      // Get amounts
      $riderAmount = $request->dr_amount[0] ?? 0;
      $creditAmount = $request->dr_amount[1] ?? 0;

      // Use the first amount for both entries if only one amount is provided
      if ($creditAmount == 0) {
        $creditAmount = $riderAmount;
      }

      // Generate transaction code
      $transCode = \App\Helpers\Account::trans_code();

      // Create voucher entry
      $voucherData = [
        'trans_date' => $request->trans_date ?? date('Y-m-d'),
        'voucher_type' => 'COD', // COD
        'payment_type' => $request->payment_type ?? 1, // Default to Cash
        'payment_from' => HeadAccount::COD_ACCOUNT,
        'billing_month' => $this->normalizeBillingMonth($request->billing_month ?? null),
        'amount' => $riderAmount,
        'remarks' => 'COD Amount to Rider',
        'ref_id' => $riderAccount->ref_id, // Rider ID
        'trans_code' => $transCode,
        'Created_By' => auth()->id(),
        'status' => 1
      ];

      $voucher = Vouchers::create($voucherData);

      // Create debit transaction for rider account (first entry)
      $debitTransaction = [
        'account_id' => $riderAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'COD',
        'trans_code' => $transCode,
        'trans_date' => $voucherData['trans_date'],
        'narration' => $request->narration[0] ?? 'COD Amount Received',
        'debit' => $riderAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id()
      ];

      Transactions::create($debitTransaction);

      // Create credit transaction for COD account (second entry)
      $creditTransaction = [
        'account_id' => $creditAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'COD',
        'trans_code' => $transCode,
        'trans_date' => $voucherData['trans_date'],
        'narration' => $request->narration[1] ?? 'COD Amount Given to ' . $riderAccount->name,
        'credit' => $creditAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id()
      ];

      Transactions::create($creditTransaction);

      \DB::commit();

      // Return success response
      return response()->json([
        'success' => true,
        'message' => 'COD amount recorded successfully',
        'voucher_id' => $voucher->id,
        'trans_code' => $transCode
      ]);
    } catch (\Exception $e) {
      \DB::rollback();

      // Log the request data for debugging
      \Log::error('COD error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error recording COD amount: ' . $e->getMessage(),
        'debug' => [
          'account_ids' => $request->account_id ?? 'not provided',
          'dr_amounts' => $request->dr_amount ?? 'not provided',
          'narrations' => $request->narration ?? 'not provided'
        ]
      ], 500);
    }
  }

  public function storepenalty(Request $request)
  {
    try {
      \DB::beginTransaction();

      // Validate the request
      $request->validate([
        'account_id' => 'required|array|min:2',
        'account_id.*' => 'required|integer',
        'dr_amount' => 'required|array',
        'dr_amount.*' => 'required|numeric|min:0',
        'narration' => 'required|array|min:2',
        'narration.*' => 'required|string',
      ]);

      // Get rider account (first entry should be the rider's liability account)
      $riderAccountId = $request->account_id[0];

      if (empty($riderAccountId)) {
        throw new \Exception('Rider account ID is required');
      }

      $riderAccount = Accounts::find($riderAccountId);

      if (!$riderAccount) {
        throw new \Exception('Rider account not found with ID: ' . $riderAccountId);
      }

      // Get the second account (credit account - should be Penalty account)
      $creditAccountId = $request->account_id[1];

      // Get amounts
      $riderAmount = $request->dr_amount[0] ?? 0;
      $creditAmount = $request->dr_amount[1] ?? 0;

      // Use the first amount for both entries if only one amount is provided
      if ($creditAmount == 0) {
        $creditAmount = $riderAmount;
      }

      // Generate transaction code
      $transCode = \App\Helpers\Account::trans_code();

      // Create voucher entry
      $voucherData = [
        'trans_date' => $request->trans_date ?? date('Y-m-d'),
        'voucher_type' => 'PN', // Penalty
        'payment_type' => $request->payment_type ?? 1, // Default to Cash
        'payment_from' => HeadAccount::PENALTY_ACCOUNT,
        'billing_month' => $this->normalizeBillingMonth($request->billing_month ?? null),
        'amount' => $riderAmount,
        'remarks' => 'Penalty Amount to Rider',
        'ref_id' => $riderAccount->ref_id, // Rider ID
        'trans_code' => $transCode,
        'Created_By' => auth()->id(),
        'status' => 1
      ];

      $voucher = Vouchers::create($voucherData);

      // Create debit transaction for rider account (first entry)
      $debitTransaction = [
        'account_id' => $riderAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'PN',
        'trans_code' => $transCode,
        'trans_date' => $voucherData['trans_date'],
        'narration' => $request->narration[0] ?? 'Penalty Amount Received',
        'debit' => $riderAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id()
      ];

      Transactions::create($debitTransaction);

      // Create credit transaction for penalty account (second entry)
      $creditTransaction = [
        'account_id' => $creditAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'PN',
        'trans_code' => $transCode,
        'trans_date' => $voucherData['trans_date'],
        'narration' => $request->narration[1] ?? 'Penalty Amount Given to ' . $riderAccount->name,
        'credit' => $creditAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id()
      ];

      Transactions::create($creditTransaction);

      \DB::commit();

      // Return success response
      return response()->json([
        'success' => true,
        'message' => 'Penalty amount recorded successfully',
        'voucher_id' => $voucher->id,
        'trans_code' => $transCode
      ]);
    } catch (\Exception $e) {
      \DB::rollback();

      // Log the request data for debugging
      \Log::error('Penalty error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error recording penalty amount: ' . $e->getMessage(),
        'debug' => [
          'account_ids' => $request->account_id ?? 'not provided',
          'dr_amounts' => $request->dr_amount ?? 'not provided',
          'narrations' => $request->narration ?? 'not provided'
        ]
      ], 500);
    }
  }

  public function incentive($rider_id)
  {
    $rider = Riders::find($rider_id);
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();
    return view('riders.incentive-modal', compact('rider', 'account', 'accounts', 'bank_accounts'));
  }

  public function payment($rider_id)
  {
    $rider = Riders::find($rider_id);
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();
    return view('riders.payment-modal', compact('rider', 'account', 'accounts', 'bank_accounts'));
  }

  public function storepayment(Request $request)
  {
    try {
      \DB::beginTransaction();

      // Validate the request
      $request->validate([
        'account_id' => 'required|array|min:2',
        'account_id.*' => 'required|integer',
        'dr_amount' => 'required|array',
        'dr_amount.*' => 'required|numeric|min:0',
        'narration' => 'required|array|min:2',
        'narration.*' => 'required|string',
      ]);

      // Get rider account (first entry should be the rider's liability account)
      $riderAccountId = $request->account_id[0];

      if (empty($riderAccountId)) {
        throw new \Exception('Rider account ID is required');
      }

      $riderAccount = Accounts::find($riderAccountId);

      if (!$riderAccount) {
        throw new \Exception('Rider account not found with ID: ' . $riderAccountId);
      }

      // Get the second account (credit account - should be Payment account)
      $creditAccountId = $request->account_id[1];

      // Get amounts
      $riderAmount = $request->dr_amount[0] ?? 0;
      $creditAmount = $request->dr_amount[1] ?? 0;

      // Use the first amount for both entries if only one amount is provided
      if ($creditAmount == 0) {
        $creditAmount = $riderAmount;
      }

      // Generate transaction code
      $transCode = \App\Helpers\Account::trans_code();

      // Create voucher entry
      $voucherData = [
        'trans_date' => $request->trans_date ?? date('Y-m-d'),
        'voucher_type' => 'PAY', // Payment
        'payment_type' => $request->payment_type ?? 1, // Default to Cash
        'payment_from' => HeadAccount::PAYMENT_ACCOUNT,
        'billing_month' => $this->normalizeBillingMonth($request->billing_month ?? null),
        'amount' => $riderAmount,
        'remarks' => 'Payment Amount to Rider',
        'ref_id' => $riderAccount->ref_id, // Rider ID
        'trans_code' => $transCode,
        'Created_By' => auth()->id(),
        'status' => 1
      ];

      $voucher = Vouchers::create($voucherData);

      // Create debit transaction for rider account (first entry)
      $debitTransaction = [
        'account_id' => $riderAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'PAY',
        'trans_code' => $transCode,
        'trans_date' => $voucherData['trans_date'],
        'narration' => $request->narration[0] ?? 'Payment Amount Received',
        'debit' => $riderAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id()
      ];

      Transactions::create($debitTransaction);

      // Create credit transaction for payment account (second entry)
      $creditTransaction = [
        'account_id' => $creditAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'PAY',
        'trans_code' => $transCode,
        'trans_date' => $voucherData['trans_date'],
        'narration' => $request->narration[1] ?? 'Payment Amount Given to ' . $riderAccount->name,
        'credit' => $creditAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id()
      ];

      Transactions::create($creditTransaction);

      \DB::commit();

      // Return success response
      return response()->json([
        'success' => true,
        'message' => 'Payment amount recorded successfully',
        'voucher_id' => $voucher->id,
        'trans_code' => $transCode
      ]);
    } catch (\Exception $e) {
      \DB::rollback();

      // Log the request data for debugging
      \Log::error('Payment error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error recording payment amount: ' . $e->getMessage(),
        'debug' => [
          'account_ids' => $request->account_id ?? 'not provided',
          'dr_amounts' => $request->dr_amount ?? 'not provided',
          'narrations' => $request->narration ?? 'not provided'
        ]
      ], 500);
    }
  }

  public function storeincentive(Request $request)
  {
    try {
      \DB::beginTransaction();

      // Validate the request
      $request->validate([
        'account_id' => 'required|array|min:2',
        'account_id.*' => 'required|integer',
        'dr_amount' => 'required|array',
        'dr_amount.*' => 'required|numeric|min:0',
        'narration' => 'required|array|min:2',
        'narration.*' => 'required|string',
      ]);

      // Get rider account (first entry should be the rider's liability account)
      $riderAccountId = $request->account_id[0];

      if (empty($riderAccountId)) {
        throw new \Exception('Rider account ID is required');
      }

      $riderAccount = Accounts::find($riderAccountId);

      if (!$riderAccount) {
        throw new \Exception('Rider account not found with ID: ' . $riderAccountId);
      }

      // Get the second account (credit account - should be Incentive account)
      $creditAccountId = $request->account_id[1];

      // Get amounts
      $riderAmount = $request->dr_amount[0] ?? 0;
      $creditAmount = $request->dr_amount[1] ?? 0;

      // Use the first amount for both entries if only one amount is provided
      if ($creditAmount == 0) {
        $creditAmount = $riderAmount;
      }

      // Generate transaction code
      $transCode = \App\Helpers\Account::trans_code();

      // Create voucher entry
      $voucherData = [
        'trans_date' => $request->trans_date ?? date('Y-m-d'),
        'voucher_type' => 'INC', // Incentive
        'payment_type' => $request->payment_type ?? 1, // Default to Cash
        'payment_from' => HeadAccount::INCENTIVE_ACCOUNT,
        'billing_month' => $this->normalizeBillingMonth($request->billing_month ?? null),
        'amount' => $riderAmount,
        'remarks' => 'Incentive Amount to Rider',
        'ref_id' => $riderAccount->ref_id, // Rider ID
        'trans_code' => $transCode,
        'Created_By' => auth()->id(),
        'status' => 1
      ];

      $voucher = Vouchers::create($voucherData);

      // Create debit transaction for rider account (first entry)
      $debitTransaction = [
        'account_id' => $creditAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'INC',
        'trans_code' => $transCode,
        'trans_date' => $voucherData['trans_date'],
        'narration' => $request->narration[0] ?? 'Incentive Amount Received',
        'debit' => $riderAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id()
      ];

      Transactions::create($debitTransaction);

      // Create credit transaction for incentive account (second entry)
      $creditTransaction = [
        'account_id' => $riderAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'INC',
        'trans_code' => $transCode,
        'trans_date' => $voucherData['trans_date'],
        'narration' => $request->narration[1] ?? 'Incentive Amount Given to ' . $riderAccount->name,
        'credit' => $creditAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id()
      ];

      Transactions::create($creditTransaction);

      \DB::commit();

      // Return success response
      return response()->json([
        'success' => true,
        'message' => 'Incentive amount recorded successfully',
        'voucher_id' => $voucher->id,
        'trans_code' => $transCode
      ]);
    } catch (\Exception $e) {
      \DB::rollback();

      // Log the request data for debugging
      \Log::error('Incentive error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error recording incentive amount: ' . $e->getMessage(),
        'debug' => [
          'account_ids' => $request->account_id ?? 'not provided',
          'dr_amounts' => $request->dr_amount ?? 'not provided',
          'narrations' => $request->narration ?? 'not provided'
        ]
      ], 500);
    }
  }

  public function vendorcharges($rider_id)
  {
    $rider = Riders::find($rider_id);
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();
    return view('riders.vendorcharges-modal', compact('rider', 'account', 'accounts', 'bank_accounts'));
  }

  /**
   * Unified voucher modal for rider: supports types AL, COD, PN, PAY, VC
   * Incentive remains separate as requested.
   */
  public function voucher($rider_id)
  {
    $rider = Riders::find($rider_id);
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();
    // Provide available voucher types (exclude Incentive)
    $voucherTypes = [
      'AL'  => 'Advance Loan',
      'COD' => 'COD',
      'PN'  => 'Penalty',
      'PAY' => 'Payment',
      'VC'  => 'Vendor Charges',
    ];
    return view('riders.voucher-modal', compact('rider', 'account', 'accounts', 'bank_accounts', 'voucherTypes'));
  }

  public function storevendorcharges(Request $request)
  {
    try {
      \DB::beginTransaction();

      // Validate the request
      $request->validate([
        'account_id' => 'required|array|min:2',
        'account_id.*' => 'required|integer',
        'dr_amount' => 'required|array',
        'dr_amount.*' => 'required|numeric|min:0',
        'narration' => 'required|array|min:2',
        'narration.*' => 'required|string',
      ]);

      // Get rider account (first entry should be the rider's liability account)
      $riderAccountId = $request->account_id[0];

      if (empty($riderAccountId)) {
        throw new \Exception('Rider account ID is required');
      }

      $riderAccount = Accounts::find($riderAccountId);

      if (!$riderAccount) {
        throw new \Exception('Rider account not found with ID: ' . $riderAccountId);
      }

      // Get the second account (credit account - should be Vendor Charges account)
      $creditAccountId = $request->account_id[1];

      // Get amounts
      $riderAmount = $request->dr_amount[0] ?? 0;
      $creditAmount = $request->dr_amount[1] ?? 0;

      // Use the first amount for both entries if only one amount is provided
      if ($creditAmount == 0) {
        $creditAmount = $riderAmount;
      }

      // Generate transaction code
      $transCode = \App\Helpers\Account::trans_code();

      // Create voucher entry
      $voucherData = [
        'trans_date' => $request->trans_date ?? date('Y-m-d'),
        'voucher_type' => 'VC', // Vendor Charges
        'payment_type' => $request->payment_type ?? 1, // Default to Cash
        'payment_from' => HeadAccount::VENDOR_CHARGES_ACCOUNT,
        'billing_month' => $this->normalizeBillingMonth($request->billing_month ?? null),
        'amount' => $riderAmount,
        'remarks' => 'Vendor Charges to Rider ' . $riderAccount->name,
        'ref_id' => $riderAccount->ref_id, // Rider ID
        'trans_code' => $transCode,
        'Created_By' => auth()->id(),
        'status' => 1
      ];

      $voucher = Vouchers::create($voucherData);

      // Create debit transaction for rider account (first entry)
      $debitTransaction = [
        'account_id' => $riderAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'VC',
        'trans_code' => $transCode,
        'trans_date' => $voucherData['trans_date'],
        'narration' => $request->narration[0] ?? 'Vendor Charges ' . $riderAccount->name,
        'debit' => $riderAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id()
      ];

      Transactions::create($debitTransaction);

      // Create credit transaction for vendor charges account (second entry)
      $creditTransaction = [
        'account_id' => $creditAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'VC',
        'trans_code' => $transCode,
        'trans_date' => $voucherData['trans_date'],
        'narration' => $request->narration[1] ?? 'Vendor Charges from ' . $riderAccount->name,
        'credit' => $creditAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id()
      ];

      Transactions::create($creditTransaction);

      \DB::commit();

      // Return success response
      return response()->json([
        'success' => true,
        'message' => 'Vendor charges recorded successfully',
        'voucher_id' => $voucher->id,
        'trans_code' => $transCode
      ]);
    } catch (\Exception $e) {
      \DB::rollback();

      // Log the request data for debugging
      \Log::error('Vendor charges error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error recording vendor charges: ' . $e->getMessage(),
        'debug' => [
          'account_ids' => $request->account_id ?? 'not provided',
          'dr_amounts' => $request->dr_amount ?? 'not provided',
          'narrations' => $request->narration ?? 'not provided'
        ]
      ], 500);
    }
  }

  /**
   * Add new recruiter to dropdown options
   */
  public function addRecruiter(Request $request)
  {
    try {
      $request->validate([
        'recruiter_name' => 'required|string|max:255'
      ]);

      $recruiterName = trim($request->recruiter_name);

      // Get the recruiter dropdown
      $dropdown = \App\Models\Dropdowns::where('key', 'recuriter')->first();

      if (!$dropdown) {
        // Create new dropdown if it doesn't exist
        $dropdown = \App\Models\Dropdowns::create([
          'name' => 'Recruiter',
          'label' => 'Recruiter',
          'key' => 'recuriter',
          'values' => json_encode([$recruiterName]),
          'status' => true
        ]);
      } else {
        // Get existing values
        $existingValues = json_decode($dropdown->values, true) ?: [];

        // Check if recruiter already exists (case insensitive)
        $exists = false;
        foreach ($existingValues as $value) {
          if (strtolower(trim($value)) === strtolower($recruiterName)) {
            $exists = true;
            break;
          }
        }

        if (!$exists) {
          // Add new recruiter to the list
          $existingValues[] = $recruiterName;
          $dropdown->values = json_encode($existingValues);
          $dropdown->save();
        }
      }

      return response()->json([
        'success' => true,
        'message' => 'Recruiter added successfully',
        'recruiter_name' => $recruiterName
      ]);
    } catch (\Exception $e) {
      \Log::error('Add recruiter error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error adding recruiter: ' . $e->getMessage()
      ], 500);
    }
  }
}
