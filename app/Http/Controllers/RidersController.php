<?php

namespace App\Http\Controllers;

use App\DataTables\LedgerDataTable;
use App\DataTables\RiderAttendanceDataTable;
use App\DataTables\RiderEmailsDataTable;
use App\DataTables\RiderInvoicesDataTable;
use App\Exports\CustomizableRiderExport;
use App\Exports\MonthlyActivityExport;
use App\Exports\RiderExport;
use App\Helpers\Account;
use App\Helpers\General;
use App\Helpers\HeadAccount;
use App\Http\Controllers\Concerns\AppliesModuleTopBarFilters;
use App\Imports\ImportRiderVoucherOnly;
use App\Imports\ImportVoucher;
use App\Models\Accounts;
use App\Models\BikeHistory;
use App\Models\Bikes;
use App\Models\Vendors;
use App\Models\Transactions;
use App\Models\RiderHistory;
use App\Models\SimHistory;
use App\Models\RiderDocumentType;
use App\Models\cod;
use App\Models\Countries;
use App\Models\Customers;
use App\Models\Dropdowns;
use App\Models\Files;
use App\Models\Items;
use App\Models\JobStatus;
use App\Models\Payment;
use App\Models\RiderActivities;
use App\Models\RiderAttendance;
use App\Models\RiderFieldCategoryAssignment;
use App\Models\RiderCustomField;
use App\Models\Riders;
use App\Models\AgreementCategory;
use App\Models\RtaFines;
use App\Models\salik;
use App\Models\visa_expenses;
use App\Models\visa_installment_plan;
use App\Models\Vouchers;
use App\Models\VoucherType;
use App\Repositories\RidersRepository;
use App\Services\BikeHistoryLogger;
use App\Services\Email\CompanyEmailBrandingService;
use App\Services\Email\UserEmailService;
use App\Services\RiderHistoryLogger;
use App\Support\CompanyContext;
use App\Support\CompanyQuery;
use App\Support\CompanyScope;
use App\Support\SimAssigneeContactSync;
use App\Support\TopBarNumericStatus;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class RidersController extends AppBaseController
{
  use AppliesModuleTopBarFilters, GlobalPagination, TracksCascadingDeletions;

  /** @var RidersRepository */
  private $ridersRepository;

  private function applyCompanyScope($query)
  {
    return CompanyScope::apply($query, 'riders.company_id');
  }

  /**
   * Column list for riders index table, column control, and AJAX filter refreshes.
   * Always includes riders.status when the column exists (employment / bike lifecycle),
   * even if not toggled in Rider Field assignments.
   */
  private function buildRidersIndexTableColumns(): array
  {
    $riderColumns = Schema::getColumnListing('riders');
    $riderColumnsSet = array_flip($riderColumns);
    $exclude = ['id', 'email', 'created_at', 'updated_at', 'company_id', 'account_id'];
    $excludedSet = array_flip($exclude);

    $assignedFixedColumns = RiderFieldCategoryAssignment::query()
      ->orderBy('display_order')
      ->orderBy('id')
      ->pluck('field_key')
      ->filter(function ($key) use ($riderColumnsSet, $excludedSet) {
        return isset($riderColumnsSet[$key]) && ! isset($excludedSet[$key]);
      })
      ->values()
      ->all();

    $dbColumns = array_values(array_unique(array_merge(
      $assignedFixedColumns,
      Schema::hasColumn('riders', 'status') ? ['status'] : []
    )));

    $assignedCustomFields = RiderCustomField::query()
      ->whereNotNull('category_id')
      ->orderBy('display_order')
      ->orderBy('id')
      ->get(['id', 'label']);

    $preferredOrder = [
      'rider_id',
      'name',
      'fleet_supervisor',
      'customer_id',
      'attendance',
      'status',
    ];

    $columns = [];
    $added = [];
    $makeTitle = function ($key) {
      $customTitles = [
        'doj' => 'Date of Joining',
        'recruiter_id' => 'Recruiter',
      ];

      return $customTitles[$key] ?? ucwords(str_replace('_', ' ', $key));
    };

    foreach ($preferredOrder as $key) {
      if (in_array($key, $dbColumns, true)) {
        $columns[] = ['data' => $key, 'title' => $makeTitle($key)];
        $added[$key] = true;
      }
    }

    foreach ($dbColumns as $key) {
      if (empty($added[$key])) {
        $columns[] = ['data' => $key, 'title' => $makeTitle($key)];
        $added[$key] = true;
      }
    }

    foreach ($assignedCustomFields as $cf) {
      $columns[] = [
        'data' => 'custom_field_values.' . $cf->id,
        'title' => trim((string) $cf->label) !== '' ? $cf->label : ('Custom Field #' . $cf->id),
      ];
    }

    return array_merge($columns, [
      ['data' => 'bike', 'title' => 'Bike'],
      ['data' => 'orders_sum', 'title' => 'Orders'],
      ['data' => 'days', 'title' => 'Days'],
      ['data' => 'balance', 'title' => 'Balance'],
      ['data' => 'action', 'title' => 'Actions'],
      ['data' => 'search', 'title' => 'Search'],
      ['data' => 'control', 'title' => 'Control'],
    ]);
  }

  private function findAccessibleRider(int $id): ?Riders
  {
    $query = Riders::query()->where('id', $id);
    $this->applyCompanyScope($query);

    return $query->first();
  }

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

  /**
   * Build validation rules for dynamic rider fields based on settings.
   */
  private function dynamicFieldRules(): array
  {
    $rules = [];
    $riderColumns = array_flip(Schema::getColumnListing('riders'));
    $assignmentTable = (new RiderFieldCategoryAssignment)->getTable();
    $hasRequiredColumn = Schema::hasTable($assignmentTable) && Schema::hasColumn($assignmentTable, 'is_required');
    $hasVisibleColumn = Schema::hasTable($assignmentTable) && Schema::hasColumn($assignmentTable, 'is_visible');

    if ($hasRequiredColumn) {
      $query = RiderFieldCategoryAssignment::query()->where('is_required', 1);
      if ($hasVisibleColumn) {
        $query->where(function ($q) {
          $q->where('is_visible', 1)->orWhereNull('is_visible');
        });
      }
      $query->get(['field_key'])->each(function ($assignment) use (&$rules, $riderColumns) {
        $fieldKey = (string) $assignment->field_key;
        if (! isset($riderColumns[$fieldKey])) {
          return;
        }
        // Honor settings-required fields while allowing existing base rules to remain.
        $rules[$fieldKey] = 'required';
      });
    }

    RiderCustomField::query()
      ->where('is_mandatory', 1)
      ->whereNotNull('category_id')
      ->where(function ($q) {
        $q->where('is_visible', 1)->orWhereNull('is_visible');
      })
      ->get(['id'])
      ->each(function ($field) use (&$rules) {
        $rules['custom_field_values.' . $field->id] = 'required';
      });

    return $rules;
  }

  /**
   * Build rider create/update validation rules from settings + rider table columns.
   */
  private function riderValidationRules(?int $ignoreRiderId = null): array
  {
    $rules = Riders::$rules;
    $riderColumns = array_flip(Schema::getColumnListing('riders'));
    $assignmentTable = (new RiderFieldCategoryAssignment)->getTable();
    $hasRequiredColumn = Schema::hasTable($assignmentTable) && Schema::hasColumn($assignmentTable, 'is_required');
    $hasVisibleColumn = Schema::hasTable($assignmentTable) && Schema::hasColumn($assignmentTable, 'is_visible');

    $normalizePresenceRule = function ($rule, bool $required) {
      if (is_array($rule)) {
        $tokens = array_values(array_filter($rule, function ($item) {
          return ! is_string($item) || ($item !== 'required' && $item !== 'nullable');
        }));
        array_unshift($tokens, $required ? 'required' : 'nullable');

        return $tokens;
      }

      $tokens = array_values(array_filter(explode('|', (string) $rule), function ($item) {
        return $item !== '' && $item !== 'required' && $item !== 'nullable';
      }));
      array_unshift($tokens, $required ? 'required' : 'nullable');

      return implode('|', $tokens);
    };

    $assignmentColumns = ['field_key'];
    if ($hasRequiredColumn) {
      $assignmentColumns[] = 'is_required';
    }
    if ($hasVisibleColumn) {
      $assignmentColumns[] = 'is_visible';
    }
    $assignments = RiderFieldCategoryAssignment::query()
      ->get($assignmentColumns)
      ->keyBy('field_key');
    $fixedKeys = RiderCustomField::allFixedFieldKeys();

    foreach ($fixedKeys as $fieldKey) {
      if (! isset($riderColumns[$fieldKey])) {
        continue;
      }
      $assignment = $assignments->get($fieldKey);
      // If there is no assignment row yet, default to visible + optional.
      $isVisible = ! $hasVisibleColumn || ! $assignment || $assignment->is_visible === null ? true : (bool) $assignment->is_visible;
      $isRequired = ($assignment && $hasRequiredColumn) ? (bool) $assignment->is_required : false;
      $baseRule = $rules[$fieldKey] ?? 'nullable';
      $rules[$fieldKey] = $normalizePresenceRule($baseRule, $isVisible && $isRequired);
    }

    if ($ignoreRiderId !== null) {
      $rules['rider_id'] = ['required', Rule::unique('riders', 'rider_id')->ignore($ignoreRiderId)];
      $rules['name'] = ['required', 'string', 'max:191', Rule::unique('riders', 'name')->ignore($ignoreRiderId)];
      $passportRule = $rules['passport'] ?? 'nullable|string|max:191';
      $passportTokens = is_array($passportRule) ? $passportRule : explode('|', (string) $passportRule);
      $passportTokens = array_values(array_filter($passportTokens, function ($token) {
        return ! (is_string($token) && str_starts_with($token, 'unique:'));
      }));
      $passportTokens[] = Rule::unique('riders', 'passport')->ignore($ignoreRiderId);
      $rules['passport'] = $passportTokens;
    }

    return array_merge($rules, $this->dynamicFieldRules());
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
      ->orderBy('days_count', 'asc')
      ->with('branch');
    $this->applyCompanyScope($query);
    if ($request->has('rider_id') && ! empty($request->rider_id)) {
      $query->where('riders.rider_id', 'like', '%' . $request->rider_id . '%');
    }
    if ($request->has('branch_id') && ! empty($request->branch_id)) {
      $query->where('riders.branch_id', $request->branch_id);
    }
    if ($request->has('name') && ! empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('fleet_supervisor') && ! empty($request->fleet_supervisor)) {
      $query->where('fleet_supervisor', $request->fleet_supervisor);
    }
    if ($request->has('hub') && ! empty($request->hub)) {
      $query->where('hub', $request->hub);
    }
    if ($request->has('customer_id') && ! empty($request->customer_id)) {
      $query->where('customer_id', $request->customer_id);
    }
    $this->applyModuleTopBarFilters($query, $request, 'riders');
    if ($request->has('attendance') && ! empty($request->attendance)) {
      $query->where('attendance', $request->attendance);
    }
    // Filter by rider status (active = 1, inactive = 0 or 2)
    $riderStatusKeys = TopBarNumericStatus::normalizeStatusKeys($request->input('rider_status'));
    if ($riderStatusKeys !== []) {
      TopBarNumericStatus::applyActiveInactiveOrGroup($query, 'riders.status', $riderStatusKeys);
    }
    // if ($request->has('status') && !empty($request->status)) {
    //   $query->where('status', $request->status);
    // }
    // Filter by bike assignment status (Active/Inactive based on bike assignment)
    if ($request->has('bike_assignment_status') && ! empty($request->bike_assignment_status)) {
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
    if ($request->filled('quick_search')) {
      $search = $request->input('quick_search');

      $query->leftJoin('customers', 'riders.customer_id', '=', 'customers.id')
        ->leftJoin('bikes', 'riders.id', '=', 'bikes.rider_id')
        ->where(function ($q) use ($search) {
          $q->where('riders.name', 'like', "%{$search}%")
            ->orWhere('riders.rider_id', 'like', "%{$search}%")
            ->orWhere('riders.fleet_supervisor', 'like', "%{$search}%")
            ->orWhere('riders.customer_id', 'like', "%{$search}%")
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

    return view('riders.index', array_merge([
      'data' => $data,
      'tableColumns' => $this->buildRidersIndexTableColumns(),
    ], $this->moduleTopBarListingData($request, 'riders')));
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
    $this->applyCompanyScope($query);

    if ($request->has('rider_id') && ! empty($request->rider_id)) {
      $query->where('riders.rider_id', 'like', '%' . $request->rider_id . '%');
    }
    if ($request->has('name') && ! empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('fleet_supervisor') && ! empty($request->fleet_supervisor)) {
      $query->where('fleet_supervisor', $request->fleet_supervisor);
    }
    if ($request->has('hub') && ! empty($request->hub)) {
      $query->where('hub', $request->hub);
    }
    if ($request->has('customer_id') && ! empty($request->customer_id)) {
      $query->where('customer_id', $request->customer_id);
    }
    $this->applyModuleTopBarFilters($query, $request, 'riders');
    if ($request->has('attendance') && ! empty($request->attendance)) {
      $query->where('attendance', $request->attendance);
    }

    // Filter by rider status (active = 1, inactive = 0 or 2)
    $riderStatusKeys = TopBarNumericStatus::normalizeStatusKeys($request->input('rider_status'));
    if ($riderStatusKeys !== []) {
      TopBarNumericStatus::applyActiveInactiveOrGroup($query, 'status', $riderStatusKeys);
    }

    if ($request->filled('quick_search')) {
      $search = $request->input('quick_search');

      $query->leftJoin('customers', 'riders.customer_id', '=', 'customers.id')
        ->leftJoin('bikes', 'riders.id', '=', 'bikes.rider_id')
        ->where(function ($q) use ($search) {
          $q->where('riders.name', 'like', "%{$search}%")
            ->orWhere('riders.rider_id', 'like', "%{$search}%")
            ->orWhere('riders.fleet_supervisor', 'like', "%{$search}%")
            ->orWhere('riders.customer_id', 'like', "%{$search}%")
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
      'tableColumns' => $this->buildRidersIndexTableColumns(),
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
    $riderCategories = RiderCategory::orderBy('display_order')->orderBy('id')->get();
    $fieldsByCategory = RiderCustomField::fieldsByCategoryForForm();

    return view('riders.create', compact('riderCategories', 'fieldsByCategory'));
  }

  /**
   * Store a newly created Riders in storage.
   */
  public function store(Request $request)
  {

    DB::beginTransaction();
    $request->validate($this->riderValidationRules());

    $input = $request->all();
    if (Schema::hasColumn('riders', 'company_id')) {
      $input['company_id'] = auth()->user()->company_id;
    }
    $input = SimAssigneeContactSync::stripManagedContactFromRequestData($input, null, 'rider');
    $items = $request->get('items');

    $riders = $this->ridersRepository->create($input);
    if ($riders) {

      /* $parentAccount = Accounts::firstOrCreate(
                ['name' => 'Riders', 'account_type' => 'Liability', 'parent_id' => null],
                ['name' => 'Riders', 'account_type' => 'Liability', 'account_code' => Account::code()]
              ); */
      $parentAccount = Accounts::where('name', 'Riders')->where('account_type', 'Liability')->first();
      if (! $parentAccount) {
        return response()->json([
          'success' => false,
          'message' => 'Parent account "Riders" not found.',
        ], 422);
      }
      $account = new Accounts;
      $account->account_code = 'RD' . str_pad($riders->rider_id, 4, '0', STR_PAD_LEFT);
      $account->name = $riders->name;
      $account->account_type = 'Liability';
      $account->ref_name = 'Rider';
      $account->company_id = auth()->user()->company_id;
      $account->parent_id = $parentAccount->id;
      $account->ref_id = $riders->id;
      $account->branch_id = $riders->branch_id;
      $account->save();

      if ($items) {
        foreach ($items['id'] as $key => $val) {
          if ($items['id'][$key] != 0) {
            $riderItemPrice = new RiderItemPrice;
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
        'redirect_url' => route('riders.index'),
      ]);
    }

    Flash::success('Rider created successfully.');

    return redirect(route('riders.index'));

    DB::rollback();

    // Handle duplicate entry error
    if ($e->getCode() == 23000) {
      $errorMessage = 'A rider with this ID already exists. Please use a different Rider ID.';

      if (request()->ajax()) {
        return response()->json([
          'success' => false,
          'message' => $errorMessage,
          'errors' => [
            'rider_id' => ['A rider with this ID already exists.'],
          ],
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
        'message' => 'An error occurred while creating the rider. Please try again.',
      ], 500);
    }

    Flash::error('An error occurred while creating the rider. Please try again.');

    return redirect()->back()->withInput();
  }

  /**
   * Display the specified Riders.
   */
  public function show($company_slug, $id)
  {
    $rider = $this->findAccessibleRider((int) $id);
    if (empty($rider)) {

      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }
    if (! empty($rider->branch_id) && ! in_array($rider->branch_id, app('user_branches'))) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }
    // $rider_items = $rider->items;
    $result = $rider->toArray();
    $job_status = JobStatus::where('RID', $id)->orderByDesc('id')->get();
    $fieldsByCategory = RiderCustomField::fieldsByCategoryForForm();

    // Get dropdown data for edit forms
    $countries = Countries::pluck('name', 'id')->prepend('Select', '');
    $vendors = Vendors::pluck('name', 'id')->prepend('Select', '');
    $customers = Customers::pluck('name', 'id')->prepend('Select', '');

    return view('riders.show_fields', compact('result', 'rider', 'job_status', 'countries', 'vendors', 'customers', 'fieldsByCategory'));
  }

  /**
   * Show the form for editing the specified Riders.
   */
  public function edit($company_slug, $id)
  {
    $riders = $this->findAccessibleRider((int) $id);
    if ($riders) {
      $riders->load('items');
    }

    if (empty($riders)) {
      Flash::error('Riders not found');

      return redirect(route('riders.index'));
    }

    $riderCategories = RiderCategory::orderBy('display_order')->orderBy('id')->get();
    $fieldsByCategory = RiderCustomField::fieldsByCategoryForForm();

    return view('riders.edit', compact('riders', 'riderCategories', 'fieldsByCategory'));
  }

  /**
   * Update the specified Riders in storage.
   */
  public function update($company_slug, $id, Request $request)
  {
    $request->validate($this->riderValidationRules((int) $id));
    $riders = $this->findAccessibleRider((int) $id);
    // $items = $riders->items;
    $items = $request->get('items');
    if (empty($riders)) {
      Flash::error('Riders not found');

      return redirect(route('riders.index'));
    }
    $data = $request->except(['_token', 'items']);
    if (Schema::hasColumn('riders', 'company_id')) {
      $data['company_id'] = auth()->user()->company_id;
    }
    $data = SimAssigneeContactSync::stripManagedContactFromRequestData($data, $riders, 'rider');

    $prevCustomerId = $riders->customer_id;
    $prevFleetSupervisor = $riders->fleet_supervisor;

    $riders->update($data);
    if (array_key_exists('customer_id', $data) && (string) $prevCustomerId !== (string) ($riders->customer_id ?? '')) {
      RiderHistoryLogger::projectChange(
        (int) $riders->id,
        $prevCustomerId !== null && $prevCustomerId !== '' ? (string) $prevCustomerId : null,
        (string) ($riders->customer_id ?? ''),
        $prevCustomerId ? optional(Customers::find($prevCustomerId))->name : null,
        optional(Customers::find($riders->customer_id))->name,
        now()->toDateString(),
        'rider_profile',
        RiderHistoryLogger::resolveBranchId($riders),
        $riders->fresh()
      );
    }
    if (array_key_exists('fleet_supervisor', $data)) {
      RiderHistoryLogger::fleetSupervisorChange(
        $riders->fresh(),
        $prevFleetSupervisor,
        $riders->fleet_supervisor,
        now()->toDateString(),
        Bikes::where('rider_id', $riders->id)->first()
      );
    } elseif (array_key_exists('status', $data) || array_key_exists('rider_status', $data)) {
      Riders::syncDisplayStatus($riders->fresh());
    }
    if ($riders) {

      $riders->account->name = $riders->name;
      $riders->account->account_code = 'RD' . str_pad($riders->rider_id, 4, '0', STR_PAD_LEFT);
      $riders->account->save();

      if ($request->items) {
        RiderItemPrice::where('RID', $id)->delete();
        $items = $request->items;
        foreach ($items['id'] as $key => $val) {

          $riderItemPrice = new RiderItemPrice;
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
        'redirect_url' => route('riders.index'),
      ]);
    }

    /*     Flash::success('Riders updated successfully.');
         */
    return redirect(route('riders.index'));
  }

  /**
   * Remove the specified Riders from storage (soft delete).
   *
   * @throws \Exception
   */
  public function destroy($company_slug, $id)
  {
    $riders = $this->findAccessibleRider((int) $id);

    if (empty($riders)) {
      Flash::error('Riders not found');

      return redirect(route('riders.index'));
    }

    // Check if rider account has any transactions (like Banks does)
    if ($riders->account_id) {
      $accountTransactions = Transactions::where('account_id', $riders->account_id)->count();
      if ($accountTransactions > 0) {
        Flash::error('Cannot delete rider. The rider account has ' . $accountTransactions . ' transaction(s). Please remove all transactions first.');

        return redirect(route('riders.index'));
      }
    }

    // Check if rider has any vouchers (by ref_id or rider_id)
    $vouchersCount = Vouchers::where(function ($query) use ($id) {
      $query->where('ref_id', $id);
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
    $visaExpensesCount = visa_expenses::where('rider_id', $riders->account_id)->count();
    if ($visaExpensesCount > 0) {
      $relatedRecords[] = $visaExpensesCount . ' visa expense record(s)';
    }

    // Check visa installment plans
    $visaInstallmentCount = visa_installment_plan::where('rider_id', $riders->account_id)->count();
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
    if (! empty($relatedRecords)) {
      $message = 'Cannot delete rider. The rider has the following related records: ' . implode(', ', $relatedRecords) . '. Please remove all related records first.';
      Flash::error($message);

      return redirect(route('riders.index'));
    }

    // Track cascaded deletions
    $cascadedItems = [];

    // Store rider data BEFORE deleting (important!)
    $riderId = $riders->id;
    $riderName = $riders->name . ' (' . $riders->rider_id . ')';
    $relatedAccount = $riders->account;

    // Set deleted_by if column exists
    if (Schema::hasColumn('riders', 'deleted_by')) {
      $riders->deleted_by = Auth::id();
      $riders->save();
    }

    // Soft delete the rider
    $riders->delete();

    // Also soft delete the related account if exists and track it
    if ($relatedAccount) {
      $cascadedItems[] = [
        'model' => 'Accounts',
        'id' => $relatedAccount->id,
        'name' => $relatedAccount->name,
      ];

      $relatedAccount->delete();

      // Log the cascade
      $this->trackCascadeDeletion(
        'App\Models\Riders',
        $riderId,
        $riderName,
        'App\Models\Accounts',
        $relatedAccount->id,
        $relatedAccount->name,
        'hasOne',
        'account',
        'soft'
      );
    }

    // Build cascade message
    $cascadeMessage = '';
    if (! empty($cascadedItems)) {
      $cascadeMessage = ' (Also deleted: ';
      $parts = [];
      foreach ($cascadedItems as $item) {
        $parts[] = "{$item['model']}: {$item['name']}";
      }
      $cascadeMessage .= implode(', ', $parts) . ')';
    }

    Flash::success('Rider moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('settings-panel.trash.index') . '?module=riders" class="alert-link">View Recycle Bin</a> to restore if needed.')->important();

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

  public function document($company_slug, $rider_id)
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
            'type_id' => $rider_id,
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

    $riders = Riders::find($rider_id);
    if (empty($rider) || (! in_array($rider->branch_id, app('user_branches')) && ! $rider->branch_id)) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }
    $files = Files::where('type_id', $rider_id)->get();

    return view('riders.document', compact('files', 'riders'));
  }

  public function timeline($company_slug, $id)
  {
    $riders = $this->findAccessibleRider((int) $id);
    if (empty($riders) || (! empty($riders->branch_id) && ! in_array($riders->branch_id, app('user_branches')))) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }
    $job_status = JobStatus::where('RID', $id)->orderByDesc('id')->get();

    return view('riders.timeline', compact('riders', 'job_status'));
  }

  public function history($company_slug, $id)
  {
    $riders = $this->findAccessibleRider((int) $id);
    if (empty($riders) || (! empty($riders->branch_id) && ! in_array($riders->branch_id, app('user_branches')))) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }

    $statusHistories = null;
    $simHistories = null;
    $projectChangeCount = 0;
    $simHistoryCount = 0;
    $activeTab = in_array(request('tab'), ['status', 'sim'], true) ? request('tab') : 'status';

    if (Schema::hasTable('rider_histories')) {
      $statusHistories = RiderHistory::with(['branch', 'customer'])
        ->where('rider_id', $id)
        ->whereNotIn('event_type', ['sim_assign', 'sim_return'])
        ->orderByDesc('effective_date')
        ->orderByDesc('id')
        ->paginate(50, ['*'], 'status_page');
      $projectChangeCount = RiderHistory::where('rider_id', $id)->where('event_type', 'project_change')->count();
    }

    if (Schema::hasTable('sim_histories')) {
      $simHistories = SimHistory::with('sim')
        ->where('rider_id', $id)
        ->orderByDesc('note_date')
        ->orderByDesc('id')
        ->paginate(50, ['*'], 'sim_page');
      $simHistoryCount = SimHistory::where('rider_id', $id)->count();
    }

    return view('riders.history', compact(
      'riders',
      'statusHistories',
      'simHistories',
      'projectChangeCount',
      'simHistoryCount',
      'activeTab'
    ));
  }

  public function contract($company_slug, $id)
  {
    $riders = Riders::find($id);
    if (empty($riders) || (! in_array($riders->branch_id, app('user_branches')) && ! $riders->branch_id)) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }

    return view('riders.contract', compact('riders'));
  }

  public function contract_upload(Request $request, $company_slug, $id)
  {
    if (isset($request->contract)) {

      $doc = $request->contract;
      $extension = $doc->extension();
      $name = time() . '.' . $extension;
      $doc->storeAs('contract', $name);

      $rider = Riders::find($request->id);
      if (isset($rider->contract)) {
        if (file_exists(storage_path('app/contract/' . $rider->contract))) {
          unlink(storage_path('app/contract/' . $rider->contract));
        }
      }

      $rider->contract = $name;
      $rider->save();

      return redirect(route('riders.index'))->with('success', $rider->name . '( ' . $rider->rider_id . ' ) Contract uploaded.');
    } else {
      $rider = Riders::findOrFail($id);

      AgreementCategory::ensureDefaultsForCompany();

      $agreements = AgreementCategory::query()
        ->assignedToModule('riders')
        ->where('status', true)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->with('defaultTemplate')
        ->get();

      return view('riders.contract-modal', compact('rider', 'agreements'));
    }
  }

  public function picture_upload(Request $request, $company_slug, $id)
  {
    if (isset($request->image_name)) {

      $image_name = $request->image_name;
      $extension = $image_name->extension();
      $name = time() . '.' . $extension;
      $image_name->storeAs('profile', $name);

      $rider = Riders::find($request->id);
      if (isset($rider->image_name)) {
        if (file_exists(storage_path('app/profile/' . $rider->image_name))) {
          unlink(storage_path('app/profile/' . $rider->image_name));
        }
      }

      $rider->image_name = $name;
      $rider->save();

      if (request()->ajax()) {
        return response()->json(['success' => true, 'message' => 'Profile picture uploaded successfully.']);
      }

      Flash::success('Profile picture uploaded successfully.');

      return redirect()->back();
      // redirect(url('rider'))->with('success', $rider->name . '( ' . $rider->rider_id . ' ) Profile Picture uploaded.');
    }
  }

  public function job_status($company_slug, $id, Request $request)
  {
    $riders = Riders::find($id);

    if ($request->isMethod('post')) {
      $input = $request->all();
      $input['RID'] = $id;
      $input['status_by'] = auth()->user()->id;
      JobStatus::create($input);

      /*  $rider = Riders::find($id);
             $rider->job_status = $input['job_status'];
             $rider->save(); */
      return 'Timeline added successfully';
    }

    return view('riders.job_status-modal', compact('riders'));
  }

  public function updateRider()
  {
    $riders = Riders::all();

    $parentAccount = Accounts::firstOrCreate(
      ['name' => 'Riders', 'account_type' => 'Liability', 'parent_id' => null],
      ['name' => 'Riders', 'account_type' => 'Liability', 'account_code' => Account::code()]
    );

    foreach ($riders as $rider) {

      $account = new Accounts;
      $account->account_code = 'RD' . str_pad($rider->rider_id, 4, '0', STR_PAD_LEFT);
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

  public function ledger($company_slug, $rider_id, LedgerDataTable $ledgerDataTable)
  {
    $riders = $this->findAccessibleRider((int) $rider_id);
    if (empty($riders)) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }
    $files = Transactions::where('account_id', $riders->account_id)->get();
    $account_id = $riders->account_id;

    return $ledgerDataTable->with(['account_id' => $account_id])->render('riders.ledger', compact('files', 'riders'));
  }

  public function items($company_slug, $rider_id)
  {
    $riders = $this->findAccessibleRider((int) $rider_id);
    if (empty($riders) || (! empty($riders->branch_id) && ! in_array($riders->branch_id, app('user_branches')))) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }

    return view('riders.items', compact('riders'));
  }

  public function additems($company_slug, $rider_id)
  {
    $rider = $this->findAccessibleRider((int) $rider_id);
    if (empty($rider) || (! empty($rider->branch_id) && ! in_array($rider->branch_id, app('user_branches')))) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }

    return view('riders.additems', compact('rider'));
  }

  public function storeitems(Request $request, $company_slug, $rider_id)
  {
    $rider = $this->findAccessibleRider((int) $rider_id);

    if (empty($rider)) {
      return response()->json([
        'success' => false,
        'message' => 'Rider not found',
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

        if (! empty($duplicates)) {
          $duplicateItems = Items::whereIn('id', array_unique($duplicates))->pluck('name');

          return response()->json([
            'success' => false,
            'message' => 'The following items are duplicates or already assigned: ' . implode(', ', $duplicateItems->toArray()),
          ], 422);
        }

        // If no duplicates, proceed with saving
        foreach ($items['id'] as $key => $val) {
          if ($val != 0) {
            $riderItemPrice = new RiderItemPrice;
            $riderItemPrice->item_id = $val;
            $riderItemPrice->price = $items['price'][$key] ?? 0;
            $riderItemPrice->RID = $rider_id;
            $riderItemPrice->save();
          }
        }

        return response()->json([
          'success' => true,
          'message' => 'Items added successfully',
        ]);
      }
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error adding items: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Add a single item for a rider (inline add)
   */
  public function additem(Request $request, $company_slug, $rider_id)
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
          'message' => 'Rider not found',
        ], 404);
      }

      // Check duplicate in DB
      $exists = RiderItemPrice::where('RID', $rider_id)
        ->where('item_id', $request->item_id)
        ->exists();

      if ($exists) {
        return response()->json([
          'success' => false,
          'message' => 'This item is already assigned to the rider',
        ], 422);
      }

      $rip = new RiderItemPrice;
      $rip->RID = $rider_id;
      $rip->item_id = (int) $request->item_id;
      $rip->price = (float) $request->price;
      $rip->save();

      $item = Items::find($rip->item_id);

      return response()->json([
        'success' => true,
        'message' => 'Item added successfully',
        'data' => [
          'id' => $rip->id,
          'item_id' => $rip->item_id,
          'item_name' => $item->name ?? 'Item',
          'price' => $rip->price,
        ],
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error adding item: ' . $e->getMessage(),
      ], 500);
    }
  }

  public function edititem($rider_id, $company_slug, $item_id)
  {
    try {
      $riderItem = RiderItemPrice::where('RID', $rider_id)
        ->where('id', $item_id)
        ->firstOrFail();

      return response()->json([
        'success' => true,
        'data' => $riderItem,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Item not found',
      ], 404);
    }
  }

  public function updateitem(Request $request, $company_slug, $rider_id, $item_id)
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
          'message' => 'This item is already assigned to the rider',
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
        'message' => 'Item updated successfully',
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating item: ' . $e->getMessage(),
      ], 500);
    }
  }

  public function deleteitem($rider_id, $company_slug, $item_id)
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

  public function attendance($company_slug, $rider_id, RiderAttendanceDataTable $riderAttendanceDataTable)
  {
    $riders = $this->findAccessibleRider((int) $rider_id);
    if (empty($riders) || (! empty($riders->branch_id) && ! in_array($riders->branch_id, app('user_branches')))) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }

    return $riderAttendanceDataTable->with(['rider_id' => $rider_id, 'riders' => $riders])->render('riders.attendance');
  }

  public function activities($company_slug, $rider_id)
  {
    $riders = $this->findAccessibleRider((int) $rider_id);
    if (empty($riders) || (! empty($riders->branch_id) && ! in_array($riders->branch_id, app('user_branches')))) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }
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

    return view('riders.activities', compact('data', 'filters', 'totals', 'riders'));
  }

  public function activitiesPdf($company_slug, $rider_id)
  {
    $rider = $this->findAccessibleRider((int) $rider_id);
    if (empty($rider)) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }
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
    // Use dompdf directly (dompdf/dompdf package is installed)
    $dompdf = new Dompdf;
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

  public function activitiesPrint($company_slug, $rider_id)
  {
    $rider = $this->findAccessibleRider((int) $rider_id);
    if (empty($rider)) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }
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
    return view('riders.activities_print', compact('data', 'filters', 'totals', 'rider', 'month'));
  }

  public function invoices($company_slug, $rider_id, RiderInvoicesDataTable $riderInvoicesDataTable)
  {
    $riders = $this->findAccessibleRider((int) $rider_id);
    if (empty($riders) || (! empty($riders->branch_id) && ! in_array($riders->branch_id, app('user_branches')))) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }

    return $riderInvoicesDataTable->with(['rider_id' => $rider_id])->render('riders.invoices', compact('riders'));
  }

  public function emails($company_slug, $rider_id, RiderEmailsDataTable $riderEmailsDataTable)
  {
    $riders = $this->findAccessibleRider((int) $rider_id);
    if (empty($riders) || (! empty($riders->branch_id) && ! in_array($riders->branch_id, app('user_branches')))) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }

    return $riderEmailsDataTable->with(['rider_id' => $rider_id])->render('riders.emails', compact('riders'));
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
        Excel::import(new ImportVoucher, $request->file('file'));
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
        Excel::import(new ImportRiderVoucherOnly, $request->file('file'));
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

  public function visaloan($company_slug, $rider_id)
  {
    $rider = Riders::find($rider_id);
    if (empty($rider) || (! in_array($rider->branch_id, app('user_branches')) && ! $rider->branch_id)) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();

    return view('riders.visaloan-modal', compact('rider', 'account', 'accounts', 'bank_accounts'));
  }

  public function advanceloan($company_slug, $rider_id)
  {
    $rider = Riders::find($rider_id);
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();

    return view('riders.advanceloan-modal', compact('rider', 'account', 'accounts', 'bank_accounts'));
  }

  public function files($company_slug, $rider_id)
  {
    $riders = $this->findAccessibleRider((int) $rider_id);
    if (empty($riders) || (! empty($riders->branch_id) && ! in_array($riders->branch_id, app('user_branches')))) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }
    $expectedFiles = RiderDocumentType::expectedFilesStructure();

    $files = CompanyQuery::table('files')
      ->where('type', 'rider')
      ->where('type_id', $rider_id)
      ->get();

    $missingFiles = [];
    $fileStatus = [];

    // Check single documents
    foreach ($expectedFiles['single'] as $key => $name) {
      $found = false;
      foreach ($files as $riderFile) {
        if (str_contains(strtolower($riderFile->name), $key)) {
          $found = true;
          break;
        }
      }

      if (! $found) {
        $missingFiles[$key] = $name;
      }
    }

    // Check dual documents (match file name containing key + front/first or back/second)
    foreach ($expectedFiles['dual'] as $key => $sides) {
      $foundFront = false;
      $foundBack = false;
      foreach ($files as $riderFile) {
        $name = strtolower($riderFile->name);
        if (! str_contains($name, $key)) {
          continue;
        }
        if (str_contains($name, 'back') || str_contains($name, 'second')) {
          $foundBack = true;
        } elseif (str_contains($name, 'front') || str_contains($name, 'first')) {
          $foundFront = true;
        } else {
          $foundBack = true;
          $foundFront = true;
        }
      }
      if (! $foundFront) {
        $missingFiles[$key . '_front'] = $sides['front'];
      }
      if (! $foundBack) {
        $missingFiles[$key . '_back'] = $sides['back'];
      }
    }

    return view('riders.document', compact('missingFiles', 'files', 'riders'));
  }

  public function sendEmail($company_slug, $id, Request $request)
  {
    $rider = $this->findAccessibleRider((int) $id);
    if (empty($rider) || (! empty($rider->branch_id) && ! in_array($rider->branch_id, app('user_branches')))) {
      if ($request->isMethod('post')) {
        return response()->json([
          'success' => false,
          'message' => 'Rider not found.',
        ], 404);
      }
      Flash::error('Rider not found');

      return redirect(route('riders.index', ['company_slug' => $company_slug]));
    }

    if ($request->isMethod('post')) {
      $user = Auth::user();
      $emailService = app(UserEmailService::class);
      $smtpPrep = $emailService->prepareCompanySmtp($user);
      if (! $smtpPrep['ready']) {
        return response()->json([
          'success' => false,
          'message' => $smtpPrep['message'],
        ], $smtpPrep['status'] ?? 422);
      }
      $fromEmail = $smtpPrep['from_email'];
      $fromName = $smtpPrep['from_name'];

      $toEmail = $request->input('email_to');
      if (! is_string($toEmail) || trim($toEmail) === '') {
        return response()->json([
          'success' => false,
          'message' => 'Rider email address is missing.',
        ], 422);
      }
      $toEmail = trim($toEmail);

      $subject = is_string($request->input('email_subject')) && trim($request->input('email_subject')) !== ''
        ? trim($request->input('email_subject'))
        : 'Warning for Attendance and Performance - ' . $rider->name . ' (Rider ID: ' . $rider->rider_id . ')';

      $emailHeading = is_string($request->input('email_heading')) && trim($request->input('email_heading')) !== ''
        ? trim($request->input('email_heading'))
        : 'Warning for Attendance and Performance  Rider I,D ' . $rider->rider_id;

      $brandingService = app(CompanyEmailBrandingService::class);
      $data = $brandingService->mergeIntoMailData([
        'html' => $request->input('email_message'),
        'email_heading' => $emailHeading,
        'rider_name' => $rider->name,
        'rider_id' => $rider->rider_id,
      ]);

      try {
        $fileName = $id . "_monthly_activity_{$request->month}.xlsx";
        $filePath = storage_path("app/public/{$fileName}");
        Excel::store(new MonthlyActivityExport($id, $request->month), "public/{$fileName}");
        $brandingService->sendBrandedEmail('emails.general', $data, function ($message) use ($toEmail, $subject, $filePath, $fromEmail, $fromName) {
          $message->to([$toEmail]);
          $message->from($fromEmail, $fromName);
          $message->replyTo($fromEmail, $fromName);
          $message->subject($subject);
          $message->attach($filePath);
          $message->priority(3);
        });
        RiderEmails::create([
          'rider_id' => $id,
          'mail_to' => $toEmail,
          'subject' => $subject,
          'message' => $request->email_message,
        ]);
      } catch (\Throwable $e) {
        report($e);

        return response()->json([
          'success' => false,
          'message' => $emailService->formatMailFailureMessage($e),
        ], 500);
      }

      return response()->json([
        'success' => true,
        'message' => 'Email sent successfully.',
      ]);
    }

    $emailBranding = app(CompanyEmailBrandingService::class)->resolveForEmail();

    return view('riders.send_email', compact('rider', 'emailBranding'));
  }

  public function exportRiders()
  {
    return Excel::download(new RiderExport, 'Riders_export_' . now() . '.xlsx');
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
      $userSettings = UserTableSettings::getSettings(auth()->id(), 'riders_table');

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
      'quick_search' => $request->input('quick_search') ?: session('riders_filter.quick_search'),
    ];

    // Create customizable export
    $export = new CustomizableRiderExport($visibleColumns, $columnOrder, $filters);

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

  public function updateSection(Request $request, $company_slug, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    $section = $request->input('section');
    $data = $request->except(['_token', 'section']);
    $data = SimAssigneeContactSync::stripManagedContactFromRequestData($data, $rider, 'rider');
    $prevFleetSupervisor = $rider->fleet_supervisor;

    try {
      // Update only the fields for the specific section
      $rider->update($data);
      $rider->refresh();
      if (array_key_exists('fleet_supervisor', $data)) {
        RiderHistoryLogger::fleetSupervisorChange(
          $rider,
          $prevFleetSupervisor,
          $rider->fleet_supervisor,
          now()->toDateString(),
          Bikes::where('rider_id', $rider->id)->first()
        );
      } elseif (array_key_exists('status', $data) || array_key_exists('rider_status', $data)) {
        Riders::syncDisplayStatus($rider);
      }

      return response()->json([
        'success' => true,
        'message' => ucfirst($section) . ' information updated successfully',
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating ' . $section . ' information',
      ], 500);
    }
  }

  public function setRiderTopOption(Request $request, $company_slug, $id)
  {
    $rider = $this->ridersRepository->find($id);
    if (empty($rider)) {
      return response()->json(['success' => false, 'message' => 'Rider not found'], 404);
    }

    $rules = [
      'option_id' => 'nullable|integer|exists:rider_top_options,id',
    ];
    if ($request->filled('option_id')) {
      $rules['effective_date'] = ['required', 'date', 'before_or_equal:' . now()->toDateString()];
    } else {
      $rules['effective_date'] = ['nullable', 'date', 'before_or_equal:' . now()->toDateString()];
    }
    $validated = $request->validate($rules);

    $optionId = $validated['option_id'] ?? null;
    $effectiveDate = $validated['effective_date'] ?? now()->toDateString();

    $option = null;
    if (! empty($optionId)) {
      $optionQuery = CompanyQuery::table('rider_top_options as o')
        ->join('rider_top_categories as c', 'c.id', '=', 'o.category_id')
        ->where('o.id', $optionId)
        ->where('c.show_in_view_cards', 1);
      if (CompanyContext::shouldApplyScope() && ($companyId = CompanyContext::id())) {
        $optionQuery->where('c.company_id', $companyId);
      }
      $option = $optionQuery
        ->select('o.id', 'o.name', 'c.rider_column')
        ->first();
      if (! $option) {
        return response()->json(['success' => false, 'message' => 'Invalid Rider Top option for view cards.'], 422);
      }
    }

    $prevOptionId = $rider->rider_top_option_id;
    $prevRiderStatus = $rider->rider_status;
    $prevEmployment = $rider->status;

    $rider->rider_top_option_id = $option?->id;
    $rider->rider_status = $option ? (string) $option->name : null;
    if ($rider->rider_status !== null) {
      $inactiveStatuses = ['Absconder', 'Vacation', 'Cancel'];
      $rider->status = in_array($rider->rider_status, $inactiveStatuses, true) ? 3 : 1;
    }
    $rider->save();

    RiderHistoryLogger::record(
      (int) $rider->id,
      'status_change',
      $option ? ('View card: ' . $option->name) : 'View card cleared',
      null,
      [
        'previous_rider_status' => $prevRiderStatus,
        'new_rider_status' => $rider->rider_status,
        'previous_option_id' => $prevOptionId,
        'new_option_id' => $rider->rider_top_option_id,
        'previous_employment_status' => $prevEmployment,
        'new_employment_status' => $rider->status,
      ],
      $effectiveDate,
      RiderHistoryLogger::resolveBranchId($rider)
    );

    Riders::syncDisplayStatus($rider->fresh());

    return response()->json([
      'success' => true,
      'message' => 'Rider view card option and status updated successfully.',
      'option_id' => $option?->id,
      'option_label' => $option?->name,
      'rider_status' => $rider->rider_status,
    ]);
  }

  public function toggleAbsconder(Request $request, $company_slug, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      // Toggle the absconder status
      $rider->rider_status = ($rider->rider_status === 'Absconder') ? null : 'Absconder';
      $rider->save();

      return response()->json([
        'success' => true,
        'message' => 'Absconder status updated successfully',
        'rider_status' => $rider->rider_status,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating absconder status',
      ], 500);
    }
  }

  public function toggleFlowup(Request $request, $company_slug, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      // Toggle the flowup status
      $rider->rider_status = ($rider->rider_status === 'Follow Up') ? null : 'Follow Up';
      $rider->save();

      return response()->json([
        'success' => true,
        'message' => 'Flowup status updated successfully',
        'rider_status' => $rider->rider_status,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating flowup status',
      ], 500);
    }
  }

  public function toggleLlicense(Request $request, $company_slug, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      // Toggle the l_license status
      $rider->rider_status = ($rider->rider_status === 'Learning License') ? null : 'Learning License';
      $rider->save();

      return response()->json([
        'success' => true,
        'message' => 'Learning license status updated successfully',
        'rider_status' => $rider->rider_status,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating learning license status',
      ], 500);
    }
  }

  public function toggleWalker(Request $request, $company_slug, $id)
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
      $rider->rider_status = $isSettingWalker ? 'Walker' : null;

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
              'warehouse' => 'Return',
              'return_date' => $today,
              'updated_by' => Auth::id(),
            ]);
          }

          // Update bike to returned
          $bike->update([
            'rider_id' => null,
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
        'message' => 'Error updating designation',
      ], 500);
    }
  }

  /**
   * Toggle Vacation: when turning ON, set designation to Vacation, return bike automatically, and set status to inactive.
   * Similar to Walker but for vacation leave.
   */
  public function toggleVacation(Request $request, $company_slug, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      $isSettingVacation = $rider->designation !== 'Vacation';
      $rider->designation = $isSettingVacation ? 'Vacation' : null;

      if ($isSettingVacation) {
        $rider->status = 3; // Inactive while on vacation
      } else {
        $rider->status = 1; // Active when vacation is turned off
      }
      $rider->rider_status = $isSettingVacation ? 'Vacation' : null;

      $rider->save();

      $bikeReturned = false;
      // When setting to Vacation: return the bike automatically (same logic as Walker)
      if ($isSettingVacation) {
        $bike = Bikes::where('rider_id', $rider->id)->first();
        if ($bike) {
          $today = Carbon::now()->toDateString();

          $lastHistory = BikeHistory::where('bike_id', $bike->id)
            ->where('rider_id', $rider->id)
            ->whereNull('return_date')
            ->latest('note_date')
            ->first();
          if ($lastHistory) {
            $lastHistory->update([
              'warehouse' => 'Return',
              'return_date' => $today,
              'updated_by' => Auth::id(),
            ]);
          }

          $bike->update([
            'rider_id' => null,
            'warehouse' => 'Return',
          ]);
          $bikeReturned = true;
        }
      }

      $message = 'Vacation status updated successfully.';
      if ($isSettingVacation && $bikeReturned) {
        $message .= ' Bike returned.';
      }

      return response()->json([
        'success' => true,
        'message' => $message,
        'designation' => $rider->designation,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating vacation status',
      ], 500);
    }
  }

  public function toggleMol(Request $request, $company_slug, $id)
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
        'mol' => $rider->mol,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating MOL status',
      ], 500);
    }
  }

  public function togglePro(Request $request, $company_slug, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      // Toggle the PRO status
      $rider->rider_status = ($rider->rider_status === 'PRO') ? null : 'PRO';
      $rider->save();

      return response()->json([
        'success' => true,
        'message' => 'PRO status updated successfully',
        'rider_status' => $rider->rider_status,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating PRO status',
      ], 500);
    }
  }

  /**
   * Set rider status option (single-select). Only updates the status option; does not change designation or rider status.
   * No bike return. Designation remains unchanged.
   */
  public function setRiderStatusOption(Request $request, $company_slug, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    $type = trim((string) $request->input('type', 'none'));

    try {
      // Single status column only.
      $rider->rider_status = null;

      if ($type !== 'none') {
        $labels = [
          'absconder' => 'Absconder',
          'flowup' => 'Follow Up',
          'llicense' => 'Learning License',
          'walker' => 'Walker',
          'vacation' => 'Vacation',
          'cancel' => 'Cancel',
          'pro' => 'PRO',
        ];
        $statusLabel = $labels[$type] ?? $type;
        $statusLabel = trim((string) $statusLabel);

        $statusCategory = RiderTopCategory::where('rider_column', 'rider_status')->first();
        if ($statusCategory) {
          $configuredStatuses = RiderTopOption::where('category_id', $statusCategory->id)
            ->pluck('name')
            ->map(fn($v) => trim((string) $v))
            ->filter(fn($v) => $v !== '')
            ->values()
            ->all();
          if (! in_array($statusLabel, $configuredStatuses, true)) {
            return response()->json(['success' => false, 'message' => 'Status is not configured in Rider Settings.'], 422);
          }
        }
        $rider->rider_status = $statusLabel;
      }
      if ($rider->rider_status !== null) {
        $inactiveStatuses = ['Absconder', 'Vacation', 'Cancel'];
        $rider->status = in_array($rider->rider_status, $inactiveStatuses, true) ? 3 : 1;
      }

      $rider->save();

      $statusLabel = $type === 'none' ? null : $rider->rider_status;

      return response()->json([
        'success' => true,
        'message' => $type === 'none' ? 'Status option cleared.' : 'Status option updated.',
        'statusLabel' => $statusLabel,
        'rider_status' => $rider->rider_status,
        'designation' => $rider->designation,
        'status' => $rider->status,
        'absconder' => $rider->rider_status === 'Absconder' ? 1 : 0,
        'flowup' => $rider->rider_status === 'Follow Up' ? 1 : 0,
        'l_license' => $rider->rider_status === 'Learning License' ? 1 : 0,
        'pro' => $rider->rider_status === 'PRO' ? 1 : 0,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating status: ' . $e->getMessage(),
      ], 500);
    }
  }

  public function returnBike(Request $request, $company_slug, $id)
  {
    $rider = $this->ridersRepository->find($id);

    if (empty($rider)) {
      return response()->json(['error' => 'Rider not found'], 404);
    }

    try {
      $bike = Bikes::where('rider_id', $rider->id)->first();
      if (! $bike) {
        return response()->json([
          'success' => false,
          'message' => 'No bike currently assigned to this rider',
        ], 400);
      }

      $returnDate = $request->input('return_date');
      $returnDate = $returnDate ? Carbon::parse($returnDate)->toDateString() : Carbon::now()->toDateString();
      $notes = $request->input('notes');
      $riderBeforeReturn = RiderHistoryLogger::riderSnapshot($rider);

      // Close last open bike history for this rider and bike
      $lastHistory = BikeHistory::where('bike_id', $bike->id)
        ->where('rider_id', $rider->id)
        ->whereNull('return_date')
        ->latest('note_date')
        ->first();
      if ($lastHistory) {
        $historyUpdate = [
          'warehouse' => 'Return',
          'return_date' => $returnDate,
          'notes' => $notes,
        ];
        $historyUpdate = BikeHistoryLogger::mergeStructuredUpdate(
          $historyUpdate,
          $bike,
          $rider,
          'Return'
        );
        $lastHistory->update($historyUpdate);
      }

      // Update bike to returned
      $bike->update([
        'rider_id' => null,
        'warehouse' => 'Return',
      ]);

      // Update rider state
      $rider->status = 3; // Return
      $rider->designation = null;
      $rider->customer_id = null;
      $rider->save();

      $riderHistoryDetails = RiderHistoryLogger::detailsFromBikeHistoryNotes(
        $notes ?: ($lastHistory->notes ?? null)
      );

      RiderHistoryLogger::bikeAssignStatusChange(
        (int) $rider->id,
        'Bike return: Return',
        $riderHistoryDetails,
        $riderBeforeReturn,
        RiderHistoryLogger::riderSnapshot($rider->fresh()),
        $returnDate,
        'rider_return_bike',
        RiderHistoryLogger::resolveBranchId($rider, $bike),
        ['warehouse_action' => 'Return', 'bike_id' => $bike->id, 'bike_plate' => $bike->plate],
        'Return',
        $rider,
        $bike
      );

      return response()->json([
        'success' => true,
        'message' => 'Bike returned successfully',
        'return_date' => $returnDate,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error returning bike',
      ], 500);
    }
  }

  public function storeadvanceloan(Request $request)
  {
    try {
      if (! VoucherType::isCodeAllowedForModule('AL', 'riders')) {
        return response()->json(['errors' => ['error' => 'Advance Loan voucher type (AL) is not assigned to the Riders module. Please assign it in Voucher Settings.']], 422);
      }
      \DB::beginTransaction();

      // Validate the request
      $request->validate([
        'account_id' => 'required|array|min:2',
        'account_id.*' => 'required|integer',
        'dr_amount' => 'required|array',
        'dr_amount.*' => 'required|numeric|min:0',
        'narration' => 'required|array|min:2',
        'narration.*' => 'required|string',
        'branch_id' => 'required|numeric|exists:branches,id',
      ]);

      // Get rider account (first entry should be the rider's liability account)
      $riderAccountId = $request->account_id[0];

      if (empty($riderAccountId)) {
        throw new \Exception('Rider account ID is required');
      }

      $riderAccount = Accounts::find($riderAccountId);

      if (! $riderAccount) {
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
      $transCode = Account::trans_code();

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
        'reference_number' => $request->reference_number ?? null,
        'trans_code' => $transCode,
        'Created_By' => auth()->id(),
        'status' => 1,
        'branch_id' => $request->branch_id,
        'custom_field_values' => $request->input('voucher_custom_fields', []),
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
        'branch_id' => $request->branch_id,
        'billing_month' => $voucherData['billing_month'],
        'created_By' => auth()->id(),
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
        'Created_By' => auth()->id(),
        'branch_id' => $request->branch_id,
      ];

      Transactions::create($creditTransaction);

      \DB::commit();

      // Return success response
      return response()->json([
        'success' => true,
        'message' => 'Advance loan recorded successfully',
        'voucher_id' => $voucher->id,
        'trans_code' => $transCode,
        'reload' => true,
      ]);
    } catch (\Exception $e) {
      \DB::rollback();

      // Log the request data for debugging
      \Log::error('Advance loan error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error recording advance loan: ' . $e->getMessage(),
        'debug' => [
          'account_ids' => $request->account_id ?? 'not provided',
          'dr_amounts' => $request->dr_amount ?? 'not provided',
          'narrations' => $request->narration ?? 'not provided',
        ],
      ], 500);
    }
  }

  public function cod($company_slug, $rider_id)
  {
    $rider = Riders::find($rider_id);
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();

    return view('riders.cod-modal', compact('rider', 'account', 'accounts', 'bank_accounts'));
  }

  public function penalty($company_slug, $rider_id)
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
      if (! VoucherType::isCodeAllowedForModule('COD', 'riders')) {
        return response()->json(['errors' => ['error' => 'COD voucher type is not assigned to the Riders module. Please assign it in Voucher Settings.']], 422);
      }
      \DB::beginTransaction();

      // Validate the request
      $request->validate([
        'account_id' => 'required|array|min:2',
        'account_id.*' => 'required|integer',
        'dr_amount' => 'required|array',
        'dr_amount.*' => 'required|numeric|min:0',
        'narration' => 'required|array|min:2',
        'narration.*' => 'required|string',
        'branch_id' => 'required|numeric|exists:branches,id',
      ]);

      // Get rider account (first entry should be the rider's liability account)
      $riderAccountId = $request->account_id[0];

      if (empty($riderAccountId)) {
        throw new \Exception('Rider account ID is required');
      }

      $riderAccount = Accounts::find($riderAccountId);

      if (! $riderAccount) {
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
      $transCode = Account::trans_code();

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
        'reference_number' => $request->reference_number ?? null,
        'trans_code' => $transCode,
        'Created_By' => auth()->id(),
        'status' => 1,
        'branch_id' => $request->branch_id,
        'custom_field_values' => $request->input('voucher_custom_fields', []),
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
        'branch_id' => $request->branch_id,
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
        'Created_By' => auth()->id(),
        'branch_id' => $request->branch_id,
      ];

      Transactions::create($creditTransaction);

      \DB::commit();

      // Return success response
      return response()->json([
        'success' => true,
        'message' => 'COD amount recorded successfully',
        'voucher_id' => $voucher->id,
        'trans_code' => $transCode,
        'reload' => true,
      ]);
    } catch (\Exception $e) {
      \DB::rollback();

      // Log the request data for debugging
      \Log::error('COD error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error recording COD amount: ' . $e->getMessage(),
        'debug' => [
          'account_ids' => $request->account_id ?? 'not provided',
          'dr_amounts' => $request->dr_amount ?? 'not provided',
          'narrations' => $request->narration ?? 'not provided',
        ],
      ], 500);
    }
  }

  public function storepenalty(Request $request)
  {
    try {
      if (! VoucherType::isCodeAllowedForModule('PN', 'riders')) {
        return response()->json(['errors' => ['error' => 'Penalty voucher type (PN) is not assigned to the Riders module. Please assign it in Voucher Settings.']], 422);
      }
      \DB::beginTransaction();

      // Validate the request
      $request->validate([
        'account_id' => 'required|array|min:2',
        'account_id.*' => 'required|integer',
        'dr_amount' => 'required|array',
        'dr_amount.*' => 'required|numeric|min:0',
        'narration' => 'required|array|min:2',
        'narration.*' => 'required|string',
        'branch_id' => 'required|numeric|exists:branches,id',
      ]);

      // Get rider account (first entry should be the rider's liability account)
      $riderAccountId = $request->account_id[0];

      if (empty($riderAccountId)) {
        throw new \Exception('Rider account ID is required');
      }

      $riderAccount = Accounts::find($riderAccountId);

      if (! $riderAccount) {
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
      $transCode = Account::trans_code();

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
        'reference_number' => $request->reference_number ?? null,
        'trans_code' => $transCode,
        'Created_By' => auth()->id(),
        'status' => 1,
        'branch_id' => $request->branch_id,
        'custom_field_values' => $request->input('voucher_custom_fields', []),
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
        'Created_By' => auth()->id(),
        'branch_id' => $request->branch_id,
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
        'Created_By' => auth()->id(),
        'branch_id' => $request->branch_id,
      ];

      Transactions::create($creditTransaction);

      \DB::commit();

      // Return success response
      return response()->json([
        'success' => true,
        'message' => 'Penalty amount recorded successfully',
        'voucher_id' => $voucher->id,
        'trans_code' => $transCode,
        'reload' => true,
      ]);
    } catch (\Exception $e) {
      \DB::rollback();

      // Log the request data for debugging
      \Log::error('Penalty error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error recording penalty amount: ' . $e->getMessage(),
        'debug' => [
          'account_ids' => $request->account_id ?? 'not provided',
          'dr_amounts' => $request->dr_amount ?? 'not provided',
          'narrations' => $request->narration ?? 'not provided',
        ],
      ], 500);
    }
  }

  public function incentive($company_slug, $rider_id)
  {
    $rider = $this->findAccessibleRider((int) $rider_id);
    if (empty($rider)) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();

    return view('riders.incentive-modal', compact('rider', 'account', 'accounts', 'bank_accounts'));
  }

  public function payment($company_slug, $rider_id)
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
      if (! VoucherType::isCodeAllowedForModule('PAY', 'riders')) {
        return response()->json(['errors' => ['error' => 'Payment voucher type (PAY) is not assigned to the Riders module. Please assign it in Voucher Settings.']], 422);
      }
      \DB::beginTransaction();

      // Validate the request
      $request->validate([
        'account_id' => 'required|array|min:2',
        'account_id.*' => 'required|integer',
        'dr_amount' => 'required|array',
        'dr_amount.*' => 'required|numeric|min:0',
        'narration' => 'required|array|min:2',
        'narration.*' => 'required|string',
        'branch_id' => 'required|numeric|exists:branches,id',
      ]);

      // Get rider account (first entry should be the rider's liability account)
      $riderAccountId = $request->account_id[0];

      if (empty($riderAccountId)) {
        throw new \Exception('Rider account ID is required');
      }

      $riderAccount = Accounts::find($riderAccountId);

      if (! $riderAccount) {
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
      $transCode = Account::trans_code();

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
        'reference_number' => $request->reference_number ?? null,
        'trans_code' => $transCode,
        'Created_By' => auth()->id(),
        'status' => 1,
        'branch_id' => $request->branch_id,
        'custom_field_values' => $request->input('voucher_custom_fields', []),
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
        'Created_By' => auth()->id(),
        'branch_id' => $request->branch_id,
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
        'Created_By' => auth()->id(),
        'branch_id' => $request->branch_id,
      ];

      Transactions::create($creditTransaction);

      \DB::commit();

      // Return success response
      return response()->json([
        'success' => true,
        'message' => 'Payment amount recorded successfully',
        'voucher_id' => $voucher->id,
        'trans_code' => $transCode,
        'reload' => true,
      ]);
    } catch (\Exception $e) {
      \DB::rollback();

      // Log the request data for debugging
      \Log::error('Payment error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error recording payment amount: ' . $e->getMessage(),
        'debug' => [
          'account_ids' => $request->account_id ?? 'not provided',
          'dr_amounts' => $request->dr_amount ?? 'not provided',
          'narrations' => $request->narration ?? 'not provided',
        ],
      ], 500);
    }
  }

  public function payments(Request $request)
  {
    $accountIds = Riders::whereNotNull('account_id')->pluck('account_id')->toArray();

    if (empty($accountIds)) {
      Flash::error('No Riders found heheh');

      return redirect()->back();
    }

    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Payment::query()->latest('date_of_payment');
    $query->whereIn('payee_account_id', $accountIds);

    $data = $this->applyPagination($query, $paginationParams);

    return view('riders.payments', compact('data'));
  }

  public function storeincentive(Request $request)
  {
    try {
      if (! VoucherType::isCodeAllowedForModule('INC', 'riders')) {
        return response()->json(['errors' => ['error' => 'Incentive voucher type (INC) is not assigned to the Riders module. Please assign it in Voucher Settings.']], 422);
      }
      \DB::beginTransaction();

      // Validate the request
      $request->validate([
        'account_id' => 'required|array|min:2',
        'account_id.*' => 'required|integer',
        'dr_amount' => 'required|array',
        'dr_amount.*' => 'required|numeric|min:0',
        'narration' => 'required|array|min:2',
        'narration.*' => 'required|string',
        'branch_id' => 'required|numeric|exists:branches,id',
      ]);

      // Get rider account (first entry should be the rider's liability account)
      $riderAccountId = $request->account_id[0];

      if (empty($riderAccountId)) {
        throw new \Exception('Rider account ID is required');
      }

      $riderAccount = Accounts::find($riderAccountId);

      if (! $riderAccount) {
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
      $transCode = Account::trans_code();

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
        'status' => 1,
        'branch_id' => $request->branch_id,
        'custom_field_values' => $request->input('voucher_custom_fields', []),
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
        'branch_id' => $request->branch_id,
        'debit' => $riderAmount,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id(),
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
        'branch_id' => $request->branch_id,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id(),
      ];

      Transactions::create($creditTransaction);

      \DB::commit();

      // Return success response
      return response()->json([
        'success' => true,
        'message' => 'Incentive amount recorded successfully',
        'voucher_id' => $voucher->id,
        'trans_code' => $transCode,
        'reload' => true,
      ]);
    } catch (\Exception $e) {
      \DB::rollback();

      // Log the request data for debugging
      \Log::error('Incentive error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error recording incentive amount: ' . $e->getMessage(),
        'debug' => [
          'account_ids' => $request->account_id ?? 'not provided',
          'dr_amounts' => $request->dr_amount ?? 'not provided',
          'narrations' => $request->narration ?? 'not provided',
        ],
      ], 500);
    }
  }

  public function vendorcharges($company_slug, $rider_id)
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
  public function voucher($company_slug, $rider_id)
  {
    $rider = $this->findAccessibleRider((int) $rider_id);
    if (empty($rider)) {
      Flash::error('Rider not found');

      return redirect(route('riders.index'));
    }
    $account = Accounts::where('ref_id', $rider_id)->where('account_type', 'expense')->first();
    $accounts = Accounts::dropdown(null);
    $bank_accounts = Accounts::bankAccountsDropdown();
    $voucherTypes = VoucherType::activeCodeLabelMapForModule('riders');

    return view('riders.voucher-modal', compact('rider', 'account', 'accounts', 'bank_accounts', 'voucherTypes'));
  }

  public function storevendorcharges(Request $request)
  {
    try {
      if (! VoucherType::isCodeAllowedForModule('VC', 'riders')) {
        return response()->json(['errors' => ['error' => 'Vendor Charges voucher type (VC) is not assigned to the Riders module. Please assign it in Voucher Settings.']], 422);
      }
      \DB::beginTransaction();

      // Validate the request
      $request->validate([
        'account_id' => 'required|array|min:2',
        'account_id.*' => 'required|integer',
        'dr_amount' => 'required|array',
        'dr_amount.*' => 'required|numeric|min:0',
        'narration' => 'required|array|min:2',
        'narration.*' => 'required|string',
        'branch_id' => 'required|numeric|exists:branches,id',
      ]);

      // Get rider account (first entry should be the rider's liability account)
      $riderAccountId = $request->account_id[0];

      if (empty($riderAccountId)) {
        throw new \Exception('Rider account ID is required');
      }

      $riderAccount = Accounts::find($riderAccountId);

      if (! $riderAccount) {
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
      $transCode = Account::trans_code();

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
        'reference_number' => $request->reference_number ?? null,
        'trans_code' => $transCode,
        'Created_By' => auth()->id(),
        'status' => 1,
        'branch_id' => $request->branch_id,
        'custom_field_values' => $request->input('voucher_custom_fields', []),
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
        'branch_id' => $request->branch_id,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id(),
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
        'branch_id' => $request->branch_id,
        'billing_month' => $voucherData['billing_month'],
        'Created_By' => auth()->id(),
      ];

      Transactions::create($creditTransaction);

      \DB::commit();

      // Return success response
      return response()->json([
        'success' => true,
        'message' => 'Vendor charges recorded successfully',
        'voucher_id' => $voucher->id,
        'trans_code' => $transCode,
        'reload' => true,
      ]);
    } catch (\Exception $e) {
      \DB::rollback();

      // Log the request data for debugging
      \Log::error('Vendor charges error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error recording vendor charges: ' . $e->getMessage(),
        'debug' => [
          'account_ids' => $request->account_id ?? 'not provided',
          'dr_amounts' => $request->dr_amount ?? 'not provided',
          'narrations' => $request->narration ?? 'not provided',
        ],
      ], 500);
    }
  }

  public function storeDropdownOption($company_slug, Request $request)
  {
    $validated = $request->validate([
      'option_value' => 'required|string|max:255',
      'field_key' => 'nullable|string|max:80',
      'custom_field_id' => 'nullable|integer|exists:rider_custom_fields,id',
    ]);

    $optionValue = trim((string) $validated['option_value']);
    if ($optionValue === '') {
      return response()->json(['success' => false, 'message' => 'Option value is required.'], 422);
    }

    if (empty($validated['field_key']) && empty($validated['custom_field_id'])) {
      return response()->json(['success' => false, 'message' => 'Field target is required.'], 422);
    }

    $companyId = auth()->user()->company_id ?? null;

    if (! empty($validated['field_key'])) {
      $assignment = RiderFieldCategoryAssignment::where('field_key', $validated['field_key'])->first();
      if (! $assignment) {
        return response()->json(['success' => false, 'message' => 'Field assignment not found.'], 404);
      }

      if ($assignment->category_id) {
        $category = RiderCategory::query()->where('id', $assignment->category_id)->first();
        if (! $category) {
          return response()->json(['success' => false, 'message' => 'Field is outside your company scope.'], 403);
        }
      }

      $config = is_array($assignment->input_config) ? $assignment->input_config : [];
      $raw = $config['options'] ?? '';
      $lines = is_array($raw) ? $raw : preg_split('/\r\n|\r|\n/', (string) $raw);
      $lines = array_values(array_filter(array_map(fn($v) => trim((string) $v), $lines), fn($v) => $v !== ''));
      $exists = collect($lines)->contains(fn($v) => mb_strtolower($v) === mb_strtolower($optionValue));
      if (! $exists) {
        $lines[] = $optionValue;
      }
      $config['options'] = implode("\n", $lines);
      $assignment->input_type = $assignment->input_type ?: 'dropdown';
      $assignment->input_config = $config;
      $assignment->save();

      return response()->json(['success' => true, 'message' => 'Option added successfully.', 'reload' => true]);
    }

    $field = RiderCustomField::findOrFail((int) $validated['custom_field_id']);
    if ($field->category_id) {
      $category = RiderCategory::query()->where('id', $field->category_id)->first();
      if (! $category) {
        return response()->json(['success' => false, 'message' => 'Custom field is outside your company scope.'], 403);
      }
    }

    $config = is_array($field->config) ? $field->config : [];
    $raw = $config['options'] ?? '';
    $lines = is_array($raw) ? $raw : preg_split('/\r\n|\r|\n/', (string) $raw);
    $lines = array_values(array_filter(array_map(fn($v) => trim((string) $v), $lines), fn($v) => $v !== ''));
    $exists = collect($lines)->contains(fn($v) => mb_strtolower($v) === mb_strtolower($optionValue));
    if (! $exists) {
      $lines[] = $optionValue;
    }
    $config['options'] = implode("\n", $lines);
    $field->config = $config;
    if (! $field->data_type) {
      $field->data_type = 'dropdown';
    }
    $field->save();

    return response()->json(['success' => true, 'message' => 'Option added successfully.', 'reload' => true]);
  }

  public function dropdownOptionModal($company_slug, Request $request)
  {
    $fieldKey = trim((string) $request->query('field_key', ''));
    $customFieldId = trim((string) $request->query('custom_field_id', ''));
    $fieldLabel = trim((string) $request->query('label', 'Field'));

    return view('riders.dropdown_option_modal', [
      'fieldKey' => $fieldKey,
      'customFieldId' => $customFieldId,
      'fieldLabel' => $fieldLabel,
    ]);
  }

  /**
   * Add new recruiter to dropdown options
   */
  public function addRecruiter(Request $request)
  {
    try {
      $request->validate([
        'recruiter_name' => 'required|string|max:255',
      ]);

      $recruiterName = trim($request->recruiter_name);

      // Get the recruiter dropdown
      $dropdown = Dropdowns::where('key', 'recuriter')->first();

      if (! $dropdown) {
        // Create new dropdown if it doesn't exist
        $dropdown = Dropdowns::create([
          'name' => 'Recruiter',
          'label' => 'Recruiter',
          'key' => 'recuriter',
          'values' => json_encode([$recruiterName]),
          'status' => true,
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

        if (! $exists) {
          // Add new recruiter to the list
          $existingValues[] = $recruiterName;
          $dropdown->values = json_encode($existingValues);
          $dropdown->save();
        }
      }

      return response()->json([
        'success' => true,
        'message' => 'Recruiter added successfully',
        'recruiter_name' => $recruiterName,
      ]);
    } catch (\Exception $e) {
      \Log::error('Add recruiter error', [
        'request_data' => $request->all(),
        'error' => $e->getMessage(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error adding recruiter: ' . $e->getMessage(),
      ], 500);
    }
  }
}
