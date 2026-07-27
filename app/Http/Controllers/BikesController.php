<?php

namespace App\Http\Controllers;

use App\Exports\CustomizableBikeExport;
use App\Http\Controllers\Concerns\AppliesModuleTopBarFilters;
use App\Http\Requests\CreateBikesRequest;
use App\Http\Requests\UpdateBikesRequest;
use App\Imports\ImportBikes;
use App\Models\BikeCustomField;
use App\Models\BikeFieldCategoryAssignment;
use App\Models\BikeHistory;
use App\Models\BikeRentCompany;
use App\Models\Bikes;
use App\Models\Customers;
use App\Models\Riders;
use App\Models\UserTableSettings;
use App\Repositories\BikesRepository;
use App\Services\BikeHistoryLogger;
use App\Services\RiderHistoryLogger;
use App\Support\CompanyModuleVisibility;
use App\Support\PublicStorageDisk;
use App\Support\CompanyQuery;
use App\Support\TopBarNumericStatus;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Carbon\Carbon;
use Flash;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BikesController extends AppBaseController
{
    use AppliesModuleTopBarFilters, GlobalPagination, TracksCascadingDeletions;

    /** @var BikesRepository */
    private $bikesRepository;

    public function __construct(BikesRepository $bikesRepo)
    {
        $this->bikesRepository = $bikesRepo;
    }

    protected function mergeBikeAssignCustomFields(Bikes $bike, Request $request): void
    {
        $incoming = $request->input('custom_field_values');
        if (! is_array($incoming) || $incoming === []) {
            return;
        }

        $existing = is_array($bike->custom_field_values) ? $bike->custom_field_values : [];
        $bike->custom_field_values = array_merge($existing, $incoming);
        $bike->save();
    }

    /**
     * Display a listing of the Bikes.
     */
    public function index(Request $request)
    {

        if (! user_can('bike_view')) {
            abort(403, 'Unauthorized action.');
        }
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = Bikes::query()
            ->orderBy('bike_code', 'desc');
        if ($request->has('branch_id') && ! empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->has('bike_code') && ! empty($request->bike_code)) {
            $query->where('bike_code', 'like', '%' . $request->bike_code . '%');
        }
        if ($request->has('plate') && ! empty($request->plate)) {
            $query->where('plate', 'like', '%' . $request->plate . '%');
        }
        if ($request->has('rider_id') && ! empty($request->rider_id)) {
            $query->where('rider_id', $request->rider_id);
        }
        if ($request->has('rider') && ! empty($request->rider)) {
            $query->where('rider_id', $request->rider);
        }
        if ($request->has('company') && ! empty($request->company)) {
            if ($request->company === 'own') {
                $query->where('bike_owner', 'Owned');
            } else {
                $query->where('company', $request->company);
            }
        }
        if ($request->has('emirates') && ! empty($request->emirates)) {
            $query->where('emirates', $request->emirates);
        }
        if ($request->filled('expiry_date_from')) {
            $fromDate = Carbon::createFromFormat('Y-d-m', $request->expiry_date_from);
            $query->where('expiry_date', '>=', $fromDate);
        }

        if ($request->filled('expiry_date_to')) {
            $toDate = Carbon::createFromFormat('Y-d-m', $request->expiry_date_to);
            $query->where('expiry_date', '<=', $toDate);
        }

        $this->applyBikeRoadStatusFilter($query, $request);

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

        $this->applyModuleTopBarFilters($query, $request, 'bike_list');
        $bikeStatusKeys = TopBarNumericStatus::normalizeStatusKeys($request->input('bike_top_wh'));
        if ($bikeStatusKeys !== []) {
            TopBarNumericStatus::applyActiveInactiveOrGroup($query, 'bikes.status', $bikeStatusKeys);
        }

        $statsQuery = clone $query;
        $hasLeasedReturnDate = Schema::hasColumn('bikes', 'leased_return_date');
        // Same warehouse mapping as applyBikeRoadStatusFilter() so cards match the status filter.
        $warehouseByStatus = [
            'on_road' => ['Active'],
            'off_road' => ['Return', 'Vacation', 'Express Garage', 'Inactive'],
            'absconded' => ['Absconded'],
            'theft' => ['Theft'],
            'total_loss' => ['Total Loss'],
            'impound' => ['Impound'],
            'accident' => ['Accident'],
        ];

        $stats = [
            'total' => $statsQuery->count(),
            'returned' => $hasLeasedReturnDate
                ? $statsQuery->clone()->whereNotNull('bikes.leased_return_date')->count()
                : 0,
        ];
        foreach ($warehouseByStatus as $statusKey => $warehouses) {
            $statusCountQuery = $statsQuery->clone()->whereIn('bikes.warehouse', $warehouses);
            if ($hasLeasedReturnDate) {
                $statusCountQuery->whereNull('bikes.leased_return_date');
            }
            $stats[$statusKey] = $statusCountQuery->count();
        }

        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        // Latest history only (notes + dates for status) — not full history rows.
        $this->loadLatestHistoryForBikeTable($data);
        // Get table columns configuration
        $tableColumns = $this->getTableColumns();
        if ($request->ajax()) {
            $tableData = view('bikes.table', [
                'data' => $data,
                'tableColumns' => $tableColumns,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();

            return response()->json([
                'tableData' => $tableData,
                'stats' => $stats,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        $topBarData = $this->moduleTopBarListingData($request, 'bike_list');
        if (Schema::hasTable('bike_top_categories') && Schema::hasColumn('bikes', 'bike_top_option_id')) {
            $userBikeSettings = UserTableSettings::getSettings(auth()->id(), 'bikes_table');
            $allowedIds = null;
            if ($userBikeSettings && is_array($userBikeSettings->additional_settings)) {
                $allowedIds = $userBikeSettings->additional_settings['bike_top_visible_option_ids'] ?? null;
            }

            if (is_array($allowedIds) && count($allowedIds) > 0) {
                $allowedSet = array_flip(array_map('intval', $allowedIds));
                $topBarData['topBarSliderCategories']->each(function ($cat) use ($allowedSet) {
                    $cat->setRelation(
                        'options',
                        $cat->options->filter(fn($o) => isset($allowedSet[(int) $o->id]))->values()
                    );
                });
                $topBarData['topBarSliderCategories'] = $topBarData['topBarSliderCategories']
                    ->filter(fn($c) => $c->options->isNotEmpty())
                    ->values();
            }
        }

        return view('bikes.index', array_merge([
            'data' => $data,
            'stats' => $stats,
            'tableColumns' => $tableColumns,
        ], $topBarData));
    }

    /**
     * Get table columns configuration for bikes
     */
    private function getTableColumns()
    {
        // Get all columns from bikes table
        $filteredColumns = Schema::getColumnListing('bikes');

        // Columns to exclude
        $exclude = ['id', 'vehicle_type', 'created_at', 'updated_at', 'notes', 'traffic_file_number', 'registration_date', 'insurance_expiry', 'insurance_co', 'policy_no', 'leased_date', 'leased_return_by', 'leased_return_date', 'leased_return_company_id', 'bike_owner'];

        // Final filtered columns
        $dbColumns = array_diff($filteredColumns, $exclude);

        // Only include fields enabled in Bike Settings ("Show in form" ON).
        $visibleFieldKeys = $this->getVisibleBikeFieldKeysForTable();
        if (! empty($visibleFieldKeys)) {
            $visibleDbColumns = array_values(array_filter($dbColumns, function ($key) use ($visibleFieldKeys) {
                return isset($visibleFieldKeys[$key]);
            }));

            // Guard against accidental key mismatch causing empty table columns.
            if (! empty($visibleDbColumns)) {
                $dbColumns = $visibleDbColumns;
            }
        }
        $preferredOrder = [
            'bike_code',
            'plate',
            'branch_id',
            'rider_id',
            'name',
            'emirates',
            'company',
            'customer_id',
            'bike_status',
            'notes',
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
            if ($key === 'bike_status') {
                // Combined column: road status + warehouse + leasing return (stacked badges).
                $columns[] = ['data' => 'bike_status', 'title' => 'Status'];
                $added['bike_status'] = true;
                // Prevent the underlying DB columns from being appended separately below.
                $added['warehouse'] = true;
                $added['status'] = true;

                continue;
            }
            if ($key === 'notes') {
                // Notes from latest bike history (not bikes.notes).
                $columns[] = ['data' => 'notes', 'title' => 'Notes'];
                $added['notes'] = true;

                continue;
            }
            if ($key === 'branch_id') {
                $columns[] = ['data' => 'branch_id', 'title' => 'Branch'];
                $added['branch_id'] = true;
            } elseif ($key === 'name') {
                $columns[] = ['data' => 'name', 'title' => 'Name'];
                $added['name'] = true;
                continue;
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

        return \App\Support\RoleFieldAccess::filterTableColumns($columns, 'bike');
    }

    /**
     * Eager-load only the latest history row (notes + status dates), not full histories.
     *
     * @param  \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection  $data
     */
    private function loadLatestHistoryForBikeTable($data): void
    {
        $data->load([
            'latestHistory' => function ($q) {
                // Qualify columns — latestOfMany joins and bare bike_id is ambiguous.
                $q->select([
                    'bike_histories.id',
                    'bike_histories.bike_id',
                    'bike_histories.notes',
                    'bike_histories.return_date',
                    'bike_histories.note_date',
                ]);
            },
        ]);
    }

    /**
     * Filter by road/status badge labels, mapped to warehouse (and leasing return) values.
     */
    private function applyBikeRoadStatusFilter($query, Request $request): void
    {
        if (! $request->filled('status')) {
            return;
        }

        $status = (string) $request->input('status');

        if ($status === 'returned') {
            if (Schema::hasColumn('bikes', 'leased_return_date')) {
                $query->whereNotNull('bikes.leased_return_date');
            }

            return;
        }

        $warehouseByStatus = [
            'on_road' => ['Active'],
            'off_road' => ['Return', 'Vacation', 'Express Garage', 'Inactive'],
            'absconded' => ['Absconded'],
            'theft' => ['Theft'],
            'total_loss' => ['Total Loss'],
            'impound' => ['Impound'],
            'accident' => ['Accident'],
        ];

        if (! isset($warehouseByStatus[$status])) {
            return;
        }

        $warehouses = $warehouseByStatus[$status];
        if (count($warehouses) === 1) {
            $query->where('bikes.warehouse', $warehouses[0]);
        } else {
            $query->whereIn('bikes.warehouse', $warehouses);
        }

        // Status badge prioritizes Returned over warehouse labels
        if (Schema::hasColumn('bikes', 'leased_return_date')) {
            $query->whereNull('bikes.leased_return_date');
        }
    }

    /**
     * Fields that should appear in Bike table/column-control.
     * Role Field Permissions (visible) control per-user visibility at render time.
     */
    private function getVisibleBikeFieldKeysForTable(): array
    {
        if (! Schema::hasTable('bike_field_category_assignments')) {
            return [];
        }

        $keys = BikeFieldCategoryAssignment::query()
            ->select('field_key')
            ->pluck('field_key')
            ->filter()
            ->values()
            ->all();

        return array_fill_keys($keys, true);
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

        if ($request->has('bike_code') && ! empty($request->bike_code)) {
            $query->where('bike_code', 'like', '%' . $request->bike_code . '%');
        }
        if ($request->has('plate') && ! empty($request->plate)) {
            $query->where('plate', 'like', '%' . $request->plate . '%');
        }
        if ($request->has('rider_id') && ! empty($request->rider_id)) {
            $query->where('rider_id', $request->rider_id);
        }
        if ($request->has('rider') && ! empty($request->rider)) {
            $query->where('rider_id', $request->rider);
        }
        if ($request->has('company') && ! empty($request->company)) {
            if ($request->company === 'own') {
                $query->where('bike_owner', 'Owned');
            } else {
                $query->where('company', $request->company);
            }
        }
        if ($request->has('emirates') && ! empty($request->emirates)) {
            $query->where('emirates', $request->emirates);
        }
        if ($request->filled('expiry_date_from')) {
            $fromDate = Carbon::createFromFormat('Y-d-m', $request->expiry_date_from);
            $query->where('expiry_date', '>=', $fromDate);
        }

        if ($request->filled('expiry_date_to')) {
            $toDate = Carbon::createFromFormat('Y-d-m', $request->expiry_date_to);
            $query->where('expiry_date', '<=', $toDate);
        }

        $this->applyBikeRoadStatusFilter($query, $request);

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
        $this->loadLatestHistoryForBikeTable($data);

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
        $vehicleModel = CompanyQuery::table('vehicle_models')->find($request->vehicle_type);

        if ($vehicleModel && strtolower($vehicleModel->name) === 'cyclist') {
            unset(
                $input['bike_code'],
                $input['chassis_number'],
                $input['engine'],
                $input['model_type'],
                $input['policy_no'],
            );
        }

        $input = $this->normalizeBikeInputForDatabase($input, true);
        $input = \App\Support\RoleFieldAccess::stripNonEditableInput($input, 'bike');
        $input['warehouse'] = 'Inactive';

        $emiratesFromForm = trim((string) ($input['emirates'] ?? ''));

        if ($emiratesFromForm === '') {
            $branch_emirates = CompanyQuery::table('branches')->where('id', $input['branch_id'])->first();
            if ($branch_emirates) {
                $input['emirates'] = $branch_emirates->city;
            }
        }
        $input['created_by'] = Auth::user()->id;
        DB::beginTransaction();
        try {
            $bikes = $this->bikesRepository->create($input);
            DB::commit();
            return response()->json(['message' => 'Bike added successfully.', 'reload' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error adding bike: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Display the specified Bikes.
     */
    public function show($company_slug, $id)
    {
        $bikes = $this->bikesRepository->find($id);
        $bikes->load(['rider', 'leasingCompany', 'leasedReturnCompany', 'customer', 'branch']);

        if (empty($bikes)) {
            Flash::error('Bikes not found');

            return redirect(route('bikes.index'));
        }

        $mulkiyaFile = CompanyQuery::table('files')
            ->where('type', 'bike')
            ->where('type_id', $id)
            ->where('name', 'LIKE', '%mulkiya%')
            ->first();

        return view('bikes.show2')->with('bikes', $bikes)->with('mulkiyaFile', $mulkiyaFile);
    }

    /**
     * Show the form for editing the specified Bikes.
     */
    public function edit($company_slug, $id)
    {
        $bikes = $this->bikesRepository->find($id);
        $bikes->load(['rider', 'leasingCompany', 'leasedReturnCompany', 'customer', 'branch']);
        if (empty($bikes)) {
            Flash::error('Bikes not found');

            return redirect(route('bikes.index'));
        }

        return view('bikes.edit')->with('bikes', $bikes);
    }

    /**
     * Update the specified Bikes in storage.
     */
    public function update($company_slug, $id, UpdateBikesRequest $request)
    {
        $bikes = $this->bikesRepository->find($id);

        if (empty($bikes)) {
            return response()->json(['errors' => ['error' => 'Bike not found!']], 422);
        }

        $oldBikeCustomerId = $bikes->customer_id;

        $input = $this->normalizeBikeInputForDatabase($request->all(), false);
        $input = \App\Support\RoleFieldAccess::stripNonEditableInput($input, 'bike', is_array($bikes->custom_field_values ?? null) ? $bikes->custom_field_values : []);
        $bikes = $this->bikesRepository->update($input, $id);
        $bikes->updated_by = Auth::user()->id;
        $bikes->save();

        // Sync customer_id and designation to rider if changed and rider is assigned
        if ($bikes->rider_id && $request->has('customer_id')) {
            $rider = Riders::find($bikes->rider_id);
            if ($rider) {
                $customer_id = $request->customer_id;
                // Determine new designation (copy from assignrider logic)
                $designation = $rider->designation;
                // Bike form field name is `emirates` (not `emirate_hub`)
                $emirate_hub = $request->input('emirates');
                if ($bikes->vehicle_type) {
                    $vehicleModel = CompanyQuery::table('vehicle_models')->where('id', $bikes->vehicle_type)->first();
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
                if ((string) $oldBikeCustomerId !== (string) $customer_id) {
                    RiderHistoryLogger::projectChange(
                        (int) $rider->id,
                        $oldBikeCustomerId !== null && $oldBikeCustomerId !== '' ? (string) $oldBikeCustomerId : null,
                        (string) $customer_id,
                        $oldBikeCustomerId ? optional(Customers::find($oldBikeCustomerId))->name : null,
                        optional(Customers::find($customer_id))->name,
                        now()->toDateString(),
                        'bike_update',
                        RiderHistoryLogger::resolveBranchId($rider, $bikes),
                        $rider,
                        $bikes
                    );
                }
            }
        }

        return response()->json(['message' => 'Bike updated successfully.', 'redirect' => route('bikes.show', $bikes->id)]);
    }

    /**
     * Keep DB writes safe when a non-null bike column is not required by settings.
     */
    private function normalizeBikeInputForDatabase(array $input, bool $isCreate = true): array
    {
        $requiredColumns = $this->bikeNonNullableColumnsWithoutDefault();

        foreach ($requiredColumns as $column => $meta) {
            $hasKey = array_key_exists($column, $input);
            $current = $hasKey ? $input[$column] : null;

            // Create: ensure missing required DB columns are populated.
            // Update: do not overwrite omitted fields; only normalize when key exists.
            if (($isCreate && (! $hasKey || $current === null)) || (! $isCreate && $hasKey && $current === null)) {
                $input[$column] = $this->fallbackValueForSqlType($meta['type']);
            }
        }

        foreach (['leased_return_by', 'leased_return_date'] as $dateCol) {
            if (! array_key_exists($dateCol, $input)) {
                continue;
            }
            $v = $input[$dateCol];
            if ($v === '' || $v === null) {
                $input[$dateCol] = null;
            }
        }

        if (array_key_exists('leased_return_company_id', $input)) {
            $v = $input['leased_return_company_id'];
            if ($v === '' || $v === null) {
                $input['leased_return_company_id'] = null;
            } elseif (is_numeric($v)) {
                $input['leased_return_company_id'] = (int) $v;
            }
        }

        // Company select drives ownership: "own" => Owned / null, else Leased / leasing company id.
        if (array_key_exists('company', $input)) {
            $company = $input['company'];
            if ($company === 'own' || $company === '' || $company === null) {
                $input['bike_owner'] = 'Owned';
                $input['company'] = null;
            } else {
                $input['bike_owner'] = 'Leased';
                $input['company'] = is_numeric($company) ? (int) $company : $company;
            }
        }

        return $input;
    }

    /**
     * Non-null bike columns that have no DB default (must receive a value on insert).
     *
     * @return array<string, array{type:string}>
     */
    private function bikeNonNullableColumnsWithoutDefault(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $rows = DB::select('SHOW COLUMNS FROM bikes');
        $fillable = array_flip((new Bikes)->getFillable());
        $result = [];

        foreach ($rows as $row) {
            $field = (string) ($row->Field ?? '');
            $isNotNull = strtoupper((string) ($row->Null ?? 'YES')) === 'NO';
            $default = $row->Default ?? null;
            $extra = strtolower((string) ($row->Extra ?? ''));
            $type = strtolower((string) ($row->Type ?? ''));

            if (! $isNotNull || $default !== null) {
                continue;
            }
            if ($field === '' || ! isset($fillable[$field])) {
                continue;
            }
            if (str_contains($extra, 'auto_increment')) {
                continue;
            }

            $result[$field] = ['type' => $type];
        }

        $cached = $result;

        return $cached;
    }

    private function fallbackValueForSqlType(string $type)
    {
        $type = strtolower($type);

        if (
            str_contains($type, 'int') ||
            str_contains($type, 'decimal') ||
            str_contains($type, 'float') ||
            str_contains($type, 'double')
        ) {
            return 0;
        }

        if (str_contains($type, 'json')) {
            return '{}';
        }

        // varchar/text/date/datetime/enum/set fallback
        return '';
    }

    /**
     * Remove the specified Bikes from storage.
     *
     * @throws \Exception
     */
    public function destroy($company_slug, $id)
    {
        $bikes = Bikes::find($id);

        if (empty($bikes)) {
            return $this->respondBikeDeleteError('Bike not found!');
        }

        // Prevent deletion if bike is assigned to a rider
        if (! is_null($bikes->rider_id)) {
            return $this->respondBikeDeleteError('Cannot delete bike because it is currently assigned to a rider. Please unassign/return the bike before deleting.');
        }

        // Prevent deletion if bike has any history
        $historyCount = BikeHistory::where('bike_id', $bikes->id)->count();
        if ($historyCount > 0) {
            return $this->respondBikeDeleteError('Cannot delete bike because it has history records. Please keep the record or clear history before deleting.');
        }

        // Prevent deletion if bike is marked active
        if ($bikes->status == 1) {
            return $this->respondBikeDeleteError('Active bikes cannot be deleted. Please deactivate/return the bike before deleting.');
        }

        DB::beginTransaction();
        try {
            // Set deleted_by if column exists
            if (Schema::hasColumn('bikes', 'deleted_by')) {
                $bikes->deleted_by = Auth::id();
                $bikes->save();
            }

            // Soft delete the bike
            $bikes->delete();

            // Log deletion to cascade table (self reference to capture deletion event)
            $this->trackCascadeDeletion(
                Bikes::class,
                $bikes->id,
                $bikes->plate,
                Bikes::class,
                $bikes->id,
                $bikes->plate,
                'self',
                null,
                'soft'
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting bike: ' . $e->getMessage(), ['bike_id' => $bikes->id ?? $id]);

            return $this->respondBikeDeleteError('Failed to delete bike. Please try again.');
        }

        if (request()->ajax()) {
            return response()->json(['message' => 'Bike moved to Recycle Bin.', 'reload' => true]);
        }

        Flash::success('Bike moved to Recycle Bin.');

        return redirect()->back();
    }

    // Return bike
    public function assignrider(Request $request, $company_slug, $id)
    {
        if ($request->isMethod('post')) {
            $rules = [
                'bike_id' => 'required|exists:bikes,id',
                'warehouse' => 'required|string',
                'return_date' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) use ($request) {

                        // Get the last assign date for this bike from bike history
                        $lastAssignDate = CompanyQuery::table('bike_histories')
                            ->where('bike_id', $request->bike_id)
                            ->where('warehouse', 'Active')
                            ->orderBy('note_date', 'desc')
                            ->value('note_date');

                        // If there's a last assign date, check that return date is after it
                        if ($lastAssignDate && strtotime($value) < strtotime($lastAssignDate)) {
                            $fail('Return date cannot be before the last Assignment date (' . Carbon::parse($lastAssignDate)->format('d-m-Y') . ').');

                            return;
                        }
                        // We can alo check if date is in the future
                        if (strtotime($value) >= strtotime('tomorrow')) {
                            $fail('Return date cannot be later than today.');

                            return;
                        }
                    },
                ],
            ];
            $messages = [
                'bike_id.required' => 'Bike ID Required',
                'bike_id.exists' => 'Invalid Bike ID',
            ];

            $this->validate($request, $rules, $messages);

            DB::beginTransaction();
            try {

                $bike = Bikes::findOrFail($request->bike_id);
                $rider = $bike->rider;
                $riderBefore = $rider ? RiderHistoryLogger::riderSnapshot($rider) : null;
                $historyBranchId = RiderHistoryLogger::resolveBranchId($rider, $bike);
                $company = $bike->rentalCompany;
                $designation = $request->designation;
                $message = "*Bike* 🏍️\n";
                $message .= "────────────────\n";
                $message .= "*Bike No:* {$bike->plate}\n";
                if ($rider) {
                    $message .= "*ID:* {$rider->rider_id}\n";
                    $message .= "*Name:* {$rider->name}\n";
                } else {
                    $message .= "*Rental Company:* {$company->name}\n";
                }
                $returnDateFormatted = Carbon::parse($request->return_date)->format('d-m-Y');
                if ($request->warehouse == 'Absconded') {
                    $message .= "*Absconding Date:* {$returnDateFormatted}\n";
                } elseif ($request->warehouse == 'Theft') {
                    $message .= "*Theft Date:* {$returnDateFormatted}\n";
                } elseif ($request->warehouse == 'Impound') {
                    $message .= "*Impound Date:* {$returnDateFormatted}\n";
                } elseif ($request->warehouse == 'Accident') {
                    $message .= "*Accident Date:* {$returnDateFormatted}\n";
                } else {
                    $message .= "*Return Date:* {$returnDateFormatted}\n";
                }
                $message .= '*Time:* ' . now()->setTimezone('Asia/Dubai')->format('h:i a') . "\n";
                if ($rider) {
                    $message .= "*Project:* {$bike->customer->name}\n";
                }
                $message .= "*Emirates:* {$bike->emirates}\n";
                $riderHistoryNote = RiderHistoryLogger::assignModalRiderHistoryNote($request);
                if ($riderHistoryNote !== null) {
                    $message .= "*Note:* {$riderHistoryNote}\n";
                }

                // Status handling
                if ($request->warehouse == 'Absconded') {
                    Riders::where('id', $bike->rider_id)
                        ->update(['status' => 5]);
                    $bike->update(['warehouse' => 'Absconded']);
                    $this->updateBikeHistory($bike, 'Absconded', $bike->rider_id, $message, $request->return_date, 'Absconded');
                    if ($rider && $riderBefore) {
                        RiderHistoryLogger::bikeAssignStatusChange(
                            (int) $rider->id,
                            'Bike return: Absconded',
                            $riderHistoryNote ?? 'Rider Absconded',
                            $riderBefore,
                            array_merge($riderBefore, ['status' => 5]),
                            $request->return_date,
                            'bike_assign_return',
                            $historyBranchId,
                            ['warehouse_action' => 'Absconded', 'bike_id' => $bike->id, 'bike_plate' => $bike->plate],
                            'Absconded',
                            $rider,
                            $bike
                        );
                    }
                } elseif ($request->warehouse == 'Vacation') {
                    Riders::where('id', $bike->rider_id)
                        ->update([
                            'status' => 4,
                            'designation' => null,
                            'customer_id' => null,
                        ]);
                    $this->updateBikeHistory($bike, 'Return', $bike->rider_id, $message, $request->return_date, 'Vacation');
                    $bike->update(['rider_id' => null, 'warehouse' => 'Return', 'customer_id' => null]);
                    if ($rider && $riderBefore) {
                        RiderHistoryLogger::bikeAssignStatusChange(
                            (int) $rider->id,
                            'Bike return: Vacation',
                            $riderHistoryNote,
                            $riderBefore,
                            array_merge($riderBefore, ['status' => 4, 'designation' => null, 'customer_id' => null]),
                            $request->return_date,
                            'bike_assign_return',
                            $historyBranchId,
                            ['warehouse_action' => 'Vacation', 'bike_id' => $bike->id, 'bike_plate' => $bike->plate],
                            'Vacation',
                            $rider,
                            $bike
                        );
                    }
                } elseif ($request->warehouse == 'Return') {
                    if ($rider) {
                        $rider->update([
                            'status' => 3,
                            'designation' => null,
                            'customer_id' => null,
                        ]);
                        $this->updateBikeHistory($bike, 'Return', $bike->rider_id, $message, $request->return_date, 'Return');
                        if ($riderBefore) {
                            RiderHistoryLogger::bikeAssignStatusChange(
                                (int) $rider->id,
                                'Bike return: Return',
                                $riderHistoryNote,
                                $riderBefore,
                                array_merge($riderBefore, ['status' => 3, 'designation' => null, 'customer_id' => null]),
                                $request->return_date,
                                'bike_assign_return',
                                $historyBranchId,
                                ['warehouse_action' => 'Return', 'bike_id' => $bike->id, 'bike_plate' => $bike->plate],
                                'Return',
                                $rider,
                                $bike
                            );
                        }
                    } else {
                        $this->updateBikeHistoryforCompany($bike, 'Return', $bike->rental_company_id, $message, $request->return_date, 'Return');
                    }
                    $bike->update([
                        'rider_id' => null,
                        'rental_company_id' => null,
                        'warehouse' => 'Return',
                        'customer_id' => null,
                    ]);
                } elseif ($request->warehouse == 'Theft') {
                    if ($rider) {
                        $rider->update([
                            'status' => 3,
                            'designation' => null,
                            'customer_id' => null,
                        ]);
                        $this->updateBikeHistory($bike, 'Return', $bike->rider_id, $message, $request->return_date, 'Theft');
                        if ($riderBefore) {
                            RiderHistoryLogger::bikeAssignStatusChange(
                                (int) $rider->id,
                                'Bike return: Theft',
                                $riderHistoryNote,
                                $riderBefore,
                                array_merge($riderBefore, ['status' => 3, 'designation' => null, 'customer_id' => null]),
                                $request->return_date,
                                'bike_assign_return',
                                $historyBranchId,
                                ['warehouse_action' => 'Theft', 'bike_id' => $bike->id, 'bike_plate' => $bike->plate],
                                'Theft',
                                $rider,
                                $bike
                            );
                        }
                    } else {
                        $this->updateBikeHistoryforCompany($bike, 'Return', $bike->rental_company_id, $message, $request->return_date, 'Theft');
                    }
                    $bike->update([
                        'rider_id' => null,
                        'rental_company_id' => null,
                        'warehouse' => 'Theft',
                        'customer_id' => null,
                    ]);
                } elseif ($request->warehouse == 'Total Loss') {
                    if ($rider) {
                        $rider->update([
                            'status' => 3,
                            'designation' => null,
                            'customer_id' => null,
                        ]);
                        $this->updateBikeHistory($bike, 'Return', $bike->rider_id, $message, $request->return_date, 'Total Loss');
                        if ($riderBefore) {
                            RiderHistoryLogger::bikeAssignStatusChange(
                                (int) $rider->id,
                                'Bike return: Total Loss',
                                $riderHistoryNote,
                                $riderBefore,
                                array_merge($riderBefore, ['status' => 3, 'designation' => null, 'customer_id' => null]),
                                $request->return_date,
                                'bike_assign_return',
                                $historyBranchId,
                                ['warehouse_action' => 'Total Loss', 'bike_id' => $bike->id, 'bike_plate' => $bike->plate],
                                'Total Loss',
                                $rider,
                                $bike
                            );
                        }
                    } else {
                        $this->updateBikeHistoryforCompany($bike, 'Return', $bike->rental_company_id, $message, $request->return_date, 'Total Loss');
                    }
                    $bike->update([
                        'rider_id' => null,
                        'rental_company_id' => null,
                        'warehouse' => 'Total Loss',
                        'customer_id' => null,
                    ]);
                } elseif ($request->warehouse == 'Impound') {
                    if ($rider) {
                        $rider->update([
                            'status' => 3,
                            'designation' => null,
                            'customer_id' => null,
                        ]);
                        $this->updateBikeHistory($bike, 'Return', $bike->rider_id, $message, $request->return_date, 'Impound');
                        if ($riderBefore) {
                            RiderHistoryLogger::bikeAssignStatusChange(
                                (int) $rider->id,
                                'Bike return: Impound',
                                $riderHistoryNote,
                                $riderBefore,
                                array_merge($riderBefore, ['status' => 3, 'designation' => null, 'customer_id' => null]),
                                $request->return_date,
                                'bike_assign_return',
                                $historyBranchId,
                                ['warehouse_action' => 'Impound', 'bike_id' => $bike->id, 'bike_plate' => $bike->plate],
                                'Impound',
                                $rider,
                                $bike
                            );
                        }
                    } else {
                        $this->updateBikeHistoryforCompany($bike, 'Return', $bike->rental_company_id, $message, $request->return_date, 'Impound');
                    }
                    $bike->update([
                        'rider_id' => null,
                        'rental_company_id' => null,
                        'warehouse' => 'Impound',
                        'customer_id' => null,
                    ]);
                } elseif ($request->warehouse == 'Accident') {
                    if ($rider) {
                        $rider->update([
                            'status' => 3,
                            'designation' => null,
                            'customer_id' => null,
                        ]);
                        $this->updateBikeHistory($bike, 'Return', $bike->rider_id, $message, $request->return_date, 'Accident');
                        if ($riderBefore) {
                            RiderHistoryLogger::bikeAssignStatusChange(
                                (int) $rider->id,
                                'Bike return: Accident',
                                $riderHistoryNote,
                                $riderBefore,
                                array_merge($riderBefore, ['status' => 3, 'designation' => null, 'customer_id' => null]),
                                $request->return_date,
                                'bike_assign_return',
                                $historyBranchId,
                                ['warehouse_action' => 'Accident', 'bike_id' => $bike->id, 'bike_plate' => $bike->plate],
                                'Accident',
                                $rider,
                                $bike
                            );
                        }
                    } else {
                        $this->updateBikeHistoryforCompany($bike, 'Return', $bike->rental_company_id, $message, $request->return_date, 'Accident');
                    }
                    $bike->update([
                        'rider_id' => null,
                        'rental_company_id' => null,
                        'warehouse' => 'Accident',
                        'customer_id' => null,
                    ]);
                } else {

                    return response()->json([
                        'success' => false,
                        'errors' => 'Invalid warehouse status.',
                    ], 400);
                }

                $this->mergeBikeAssignCustomFields($bike->fresh(), $request);
                DB::commit();

                return response()->json(['message' => 'Rider assignment updated successfully.']);
            } catch (\Exception $e) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'errors' => $e->getMessage(),
                ], 400);
            }
        }

        $assignFields = BikeCustomField::assignModalFields('change');

        return view('bikes.assignBike_change', compact('id', 'assignFields'));
    }

    private function updateBikeHistory($bike, $status, $rider_id, $notes, $return_date, ?string $historyStatus = null)
    {
        $userid = Auth::user()->id;
        $rider = Riders::find($rider_id);

        $lastHistory = BikeHistory::where('bike_id', $bike->id)
            ->where('rider_id', $rider_id)
            ->whereNull('return_date')
            ->latest('note_date')
            ->first();

        if ($bike->warehouse == 'Absconded' && $lastHistory) {
            $notes = $notes;
        }

        $resolvedStatus = $historyStatus ?? BikeHistoryLogger::historyStatusFromWarehouse($status);
        $update = [
            'warehouse' => $status,
            'return_date' => $return_date,
            'notes' => $notes,
            'updated_by' => $userid,
        ];
        $update = BikeHistoryLogger::mergeStructuredUpdate($update, $bike, $rider, $resolvedStatus);
        $lastHistory->update($update);
    }

    private function updateBikeHistoryforCompany($bike, $status, $company_id, $notes, $return_date, ?string $historyStatus = null)
    {
        $userid = Auth::user()->id;

        $lastHistory = BikeHistory::where('bike_id', $bike->id)
            ->where('rental_company_id', $company_id)
            ->whereNull('return_date')
            ->latest('note_date')
            ->first();

        if ($bike->warehouse == 'Absconded' && $lastHistory) {
            $notes = $notes;
        }

        $resolvedStatus = $historyStatus ?? BikeHistoryLogger::historyStatusFromWarehouse($status);
        $update = [
            'warehouse' => $status,
            'return_date' => $return_date,
            'notes' => $notes,
            'updated_by' => $userid,
        ];
        $update = BikeHistoryLogger::mergeStructuredUpdate($update, $bike, null, $resolvedStatus);
        $lastHistory->update($update);
    }

    // assign bike to rider or company
    public function assign_rider(Request $request, $company_slug, $id)
    {
        if ($request->isMethod('post')) {
            $rules = [
                'assign_type' => 'required|in:rider,company',
                'bike_id' => 'required|exists:bikes,id',
                'customer_id' => 'required_if:assign_type,rider|nullable|exists:customers,id',
                'rider_id' => [
                    'required_if:assign_type,rider',
                    'nullable',
                    'exists:riders,id',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($request->input('assign_type') !== 'rider') {
                            return;
                        }
                        if (empty($value)) {
                            $fail('Rider is required when assigning to a rider.');

                            return;
                        }
                        // Check if bike exists
                        $bike = CompanyQuery::table('bikes')->where('id', $request->bike_id)->first();
                        if (! $bike) {
                            $fail('Bike not found.');

                            return;
                        }
                        // Check if bike is inactive (status = 1 or 2)
                        if ($bike->status == 2) {
                            $fail('Cannot assign rider to an inactive bike.');

                            return;
                        }
                        // Block if rider already has another bike actively assigned (same rider, Active warehouse).
                        $assignedBike = CompanyQuery::table('bikes')
                            ->where('rider_id', $value)
                            ->where('id', '!=', (int) $request->bike_id)
                            ->where('warehouse', 'Active')
                            ->first();

                        if ($assignedBike) {
                            $fail('Rider is already assigned to ' . ($assignedBike->plate ? 'bike #' . $assignedBike->plate : 'a vehicle') . '.');
                        }
                    },
                ],
                'rental_company_id' => ['required_if:assign_type,company', 'nullable', 'exists:bike_rent_companies,id'],
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
                        $lastReturnDate = CompanyQuery::table('bike_histories')
                            ->where('bike_id', $request->bike_id)
                            ->whereNot('warehouse', 'Active')
                            ->orderBy('return_date', 'desc')
                            ->value('return_date');

                        // If there's a last return date, check that assignment date is after it
                        if ($lastReturnDate && strtotime($value) < strtotime($lastReturnDate)) {
                            $fail('Assignment date cannot be before the last return date (' . Carbon::parse($lastReturnDate)->format('d-m-Y') . ').');

                            return;
                        }
                        if (strtotime($value) >= strtotime('tomorrow')) {
                            $fail('Assignment date cannot be later than today.');

                            return;
                        }
                    },
                ],
            ];

            $message = [
                'assign_type.required' => 'Assignment type is required.',
                'assign_type.in' => 'Assignment type must be rider or company.',
                'bike_id.required' => 'Bike ID is required.',
                'bike_id.exists' => 'Bike Not Found',
                'note_date.required' => 'Assignment date is required.',
                'customer_id.required' => 'Project is required.',
                'rental_company_id.required_if' => 'Company is required when assignment type is Company.',
                'rental_company_id.exists' => 'Selected company is invalid.',
                'rider_id.required_if' => 'Rider is required when assignment type is Rider.',
                'rider_id.exists' => 'Selected rider is invalid.',
            ];

            $this->validate($request, $rules, $message);

            $data = $request->except(['note']);
            DB::beginTransaction();
            try {
                $bike = Bikes::findOrFail($request->bike_id);
                $rider = null;
                $customer_id = $request->customer_id;
                $assignType = $request->input('assign_type');
                $historyMessage = "*Bike* 🏍️\n";
                $historyMessage .= "────────────────\n";
                $historyMessage .= "*Bike No:* {$bike->plate}\n";

                if ($assignType === 'rider') {
                    $rider = Riders::findOrFail($request->rider_id);
                    $designation = $request->designation;
                    $riderBeforeAssign = RiderHistoryLogger::riderSnapshot($rider);
                    $prevRiderCustomerId = $rider->customer_id;
                    $historyMessage .= "*ID:* {$rider->rider_id}\n";
                    $historyMessage .= "*Name:* {$rider->name}\n";
                } else {
                    $rentCompany = BikeRentCompany::find($request->rental_company_id);
                    $historyMessage .= "*Company:* {$rentCompany->name}\n";
                }

                $historyMessage .= '*Assign Date:* ' . Carbon::parse($request->note_date)->format('d-m-Y') . "\n";
                $historyMessage .= '*Time:* ' . now()->setTimezone('Asia/Dubai')->format('h:i a') . "\n";
                if ($assignType == 'rider') {
                    $project = CompanyQuery::table('customers')->where('id', $customer_id)->first();
                    $historyMessage .= "*Project:* {$project->name}\n";
                }
                $historyMessage .= "*Emirates:* {$bike->emirates}\n";
                $riderHistoryNote = RiderHistoryLogger::assignModalRiderHistoryNote($request);

                if ($assignType === 'rider') {
                    if ($rider->rider_status_option === 'Vacation') {
                        $rider->rider_status_option = null;
                    }
                    $rider->status = 1;
                    $rider->designation = $designation;
                    $rider->customer_id = $customer_id;
                    $rider->emirate_hub = $bike->emirates;
                    $rider->save();

                    if ((string) $prevRiderCustomerId !== (string) $customer_id) {
                        RiderHistoryLogger::projectChange(
                            (int) $rider->id,
                            $prevRiderCustomerId !== null && $prevRiderCustomerId !== '' ? (string) $prevRiderCustomerId : null,
                            (string) $customer_id,
                            $prevRiderCustomerId ? optional(Customers::find($prevRiderCustomerId))->name : null,
                            optional(Customers::find($customer_id))->name,
                            $request->note_date,
                            'bike_assign',
                            RiderHistoryLogger::resolveBranchId($rider, $bike),
                            $rider,
                            $bike
                        );
                    }

                    $warehouseLabel = $request->warehouse ?? $bike->warehouse ?? 'Active';
                    $assignBranchId = RiderHistoryLogger::resolveBranchId($rider, $bike);
                    RiderHistoryLogger::bikeAssignStatusChange(
                        (int) $rider->id,
                        'Bike assigned: Joining',
                        $riderHistoryNote,
                        $riderBeforeAssign,
                        RiderHistoryLogger::riderSnapshot($rider->fresh()),
                        $request->note_date,
                        'bike_assign',
                        $assignBranchId,
                        [
                            'warehouse_action' => $warehouseLabel,
                            'bike_id' => $bike->id,
                            'bike_plate' => $bike->plate,
                            'assign_type' => 'rider',
                        ],
                        'Joining',
                        $rider,
                        $bike
                    );

                    $bikeUpdate = [
                        'rider_id' => $request->rider_id,
                        'rental_company_id' => null,
                        'company' => null,
                        'warehouse' => $request->warehouse ?? $bike->warehouse,
                        'customer_id' => $customer_id,
                    ];
                    foreach (['leased_return_by', 'leased_return_date', 'leased_return_company_id'] as $col) {
                        if (Schema::hasColumn('bikes', $col)) {
                            $bikeUpdate[$col] = null;
                        }
                    }
                    $bike->update($bikeUpdate);
                } else {
                    $bike->update([
                        'rider_id' => null,
                        'rental_company_id' => $request->rental_company_id,
                        'warehouse' => $request->warehouse ?? $bike->warehouse,
                        'customer_id' => $customer_id,
                    ]);
                }

                $data['created_by'] = Auth::id();
                $data['notes'] = $historyMessage;
                $data['customer_id'] = $customer_id ?? null;
                $assignHistoryStatus = ($assignType === 'rider') ? 'Joining' : null;
                $data = array_merge(
                    $data,
                    BikeHistoryLogger::structuredBikeHistoryFields(
                        $bike,
                        ($assignType === 'rider' && isset($rider)) ? $rider : null,
                        $assignHistoryStatus,
                        $customer_id
                    )
                );
                $bikeHistory = BikeHistory::create($data);
                $this->mergeBikeAssignCustomFields($bike->fresh(), $request);
                DB::commit();

                return response()->json(['message' => 'Bike assignment updated successfully.']);
            } catch (QueryException $e) {
                DB::rollBack();

                return response()->json([
                    'success' => 'false',
                    'errors' => $e->getMessage(),
                ], 400);
            }
        }

        $bike = Bikes::find($id);
        $assignFields = BikeCustomField::assignModalFields('active');
        $assignBranchScopedOptions = [
            'rider_id' => Riders::dropdownForBikeAssign($bike?->branch_id ? (int) $bike->branch_id : null),
        ];
        $allowTypeSelection = (CompanyModuleVisibility::enabled('garage_customers') && CompanyModuleVisibility::enabled('bike_on_rent')) ? true : false;

        return view('bikes.assignBike_active', compact('id', 'assignFields', 'bike', 'assignBranchScopedOptions', 'allowTypeSelection'));
    }

    /**
     * Modal: edit leasing return fields (requires bikes.company / leasing company set).
     */
    public function leasingReturn(Request $request, $company_slug, $id)
    {
        $bike = Bikes::findOrFail($id);
        if (empty($bike->company)) {
            abort(404);
        }
        if (! Schema::hasColumn('bikes', 'leased_return_by')) {
            abort(404);
        }
        if (Schema::hasColumn('bikes', 'leased_return_date') && ! empty($bike->leased_return_date)) {
            abort(404);
        }

        if ($request->isMethod('post')) {
            if (! auth()->user()->can('bike_edit')) {
                abort(403, 'Unauthorized');
            }
            if (Schema::hasColumn('bikes', 'leased_return_date') && ! empty($bike->leased_return_date)) {
                return response()->json(['errors' => ['error' => 'This vehicle is already marked as returned to the leasing company.']], 422);
            }

            $assignFields = BikeCustomField::assignModalFields('change');
            $notesField = $assignFields->firstWhere('field_key', 'notes');

            $rules = [
                'leased_return_date' => 'required|date',
            ];
            if (Schema::hasColumn('bikes', 'leased_return_company_id')) {
                $rules['leased_return_company_id'] = 'nullable|integer|exists:leasing_companies,id';
            }
            if ($notesField && ($notesField->resolvedInputSpec()['required'] ?? false)) {
                $rules['note'] = 'required';
            }
            $request->validate($rules);

            $leasedReturnDate = $request->input('leased_return_date');

            $update = [
                'leased_return_date' => $leasedReturnDate,
            ];
            if (Schema::hasColumn('bikes', 'leased_return_company_id')) {
                $cid = $request->input('leased_return_company_id');
                $update['leased_return_company_id'] = ($cid === '' || $cid === null) ? null : (int) $cid;
            }

            $bike->updated_by = Auth::id();
            $bike->fill($update);
            $bike->save();

            $note = RiderHistoryLogger::assignModalRiderHistoryNote($request);
            $leasingCompany = $bike->leasedReturnCompany
                ?? $bike->LeasingCompany
                ?? (isset($update['leased_return_company_id']) && $update['leased_return_company_id']
                    ? \App\Models\LeasingCompanies::find($update['leased_return_company_id'])
                    : null);
            $returnDateFormatted = Carbon::parse($leasedReturnDate)->format('d-m-Y');
            $historyNotes = "*Bike* 🏍️\n";
            $historyNotes .= "────────────────\n";
            $historyNotes .= "*Bike Plate:* {$bike->plate}\n";
            $historyNotes .= '*Returned To:* ' . ($leasingCompany?->name ?? 'N/A') . "\n";
            $historyNotes .= "*Return Date:* {$returnDateFormatted}\n";
            $historyNotes .= '*Time:* ' . now()->setTimezone('Asia/Dubai')->format('h:i a') . "\n";
            if ($note !== null) {
                $historyNotes .= "*Note:* {$note}\n";
            }

            $historyData = [
                'bike_id' => $bike->id,
                'rider_id' => $bike->rider_id,
                'rental_company_id' => $bike->rental_company_id,
                'warehouse' => $bike->warehouse ?: 'Return',
                'note_date' => $leasedReturnDate,
                'return_date' => $leasedReturnDate,
                'notes' => $historyNotes,
                'customer_id' => $bike->customer_id,
                'created_by' => Auth::id(),
            ];
            $historyData = array_merge(
                $historyData,
                BikeHistoryLogger::structuredBikeHistoryFields(
                    $bike->fresh(),
                    $bike->rider,
                    'Returned to Leasing',
                    $bike->customer_id
                )
            );
            BikeHistory::create($historyData);

            return response()->json(['message' => 'Leasing return details saved.', 'reload' => true]);
        }

        $bike->load(['leasedReturnCompany', 'LeasingCompany']);
        $assignFields = BikeCustomField::assignModalFields('change');

        return view('bikes.leasing_return_modal', compact('bike', 'id', 'assignFields'));
    }

    /**
     * Modal + POST: change project on an active rider assignment without unassigning the bike.
     */
    public function changeProject(Request $request, $company_slug, $id)
    {
        $bike = Bikes::with(['rider', 'customer'])->findOrFail($id);

        if (! $this->bikeEligibleForProjectChange($bike)) {
            abort(404);
        }

        if ($request->isMethod('post')) {
            if (! auth()->user()->can('bike_assign_edit')) {
                abort(403, 'Unauthorized');
            }

            if (! $this->bikeEligibleForProjectChange($bike->fresh(['rider', 'customer']))) {
                return response()->json(['message' => 'This assignment is no longer active.'], 422);
            }

            $request->validate([
                'bike_id' => 'required|exists:bikes,id',
                'customer_id' => 'required|exists:customers,id',
            ]);

            $newCustomerId = (string) $request->input('customer_id');
            $currentCustomerId = (string) ($bike->customer_id ?? '');

            if ($newCustomerId === $currentCustomerId) {
                return response()->json(['message' => 'This project is already assigned.'], 422);
            }

            $rider = $bike->rider;
            if (! $rider) {
                return response()->json(['message' => 'No rider is assigned to this vehicle.'], 422);
            }

            $oldProject = $bike->customer;
            $newProject = Customers::find($newCustomerId);
            $changeDate = now()->setTimezone('Asia/Dubai')->toDateString();
            $changeDateFormatted = Carbon::parse($changeDate)->format('d-m-Y');
            $historyBranchId = RiderHistoryLogger::resolveBranchId($rider, $bike);
            $riderBefore = RiderHistoryLogger::riderSnapshot($rider);
            $prevRiderCustomerId = $rider->customer_id;

            $returnMessage = "*Bike* 🏍️\n";
            $returnMessage .= "────────────────\n";
            $returnMessage .= "*Bike No:* {$bike->plate}\n";
            $returnMessage .= "*ID:* {$rider->rider_id}\n";
            $returnMessage .= "*Name:* {$rider->name}\n";
            $returnMessage .= "*Return Date:* {$changeDateFormatted}\n";
            $returnMessage .= '*Time:* ' . now()->setTimezone('Asia/Dubai')->format('h:i a') . "\n";
            $returnMessage .= '*Project:* ' . ($oldProject->name ?? '—') . "\n";
            $returnMessage .= "*Emirates:* {$bike->emirates}\n";
            $returnMessage .= "*Reason:* Project change\n";

            $assignMessage = "*Bike* 🏍️\n";
            $assignMessage .= "────────────────\n";
            $assignMessage .= "*Bike No:* {$bike->plate}\n";
            $assignMessage .= "*ID:* {$rider->rider_id}\n";
            $assignMessage .= "*Name:* {$rider->name}\n";
            $assignMessage .= "*Assign Date:* {$changeDateFormatted}\n";
            $assignMessage .= '*Time:* ' . now()->setTimezone('Asia/Dubai')->format('h:i a') . "\n";
            $assignMessage .= '*Project:* ' . ($newProject->name ?? '—') . "\n";
            $assignMessage .= "*Emirates:* {$bike->emirates}\n";
            $assignMessage .= "*Reason:* Project change\n";

            DB::beginTransaction();
            try {
                $this->closeOpenBikeAssignment($bike, $rider, $returnMessage, $changeDate, 'Return');

                RiderHistoryLogger::bikeAssignStatusChange(
                    (int) $rider->id,
                    'Bike return: Return',
                    'Project change — returned from ' . ($oldProject->name ?? 'previous project'),
                    $riderBefore,
                    $riderBefore,
                    $changeDate,
                    'bike_assign_return',
                    $historyBranchId,
                    [
                        'warehouse_action' => 'Return',
                        'bike_id' => $bike->id,
                        'bike_plate' => $bike->plate,
                        'project_change' => true,
                    ],
                    'Return',
                    $rider,
                    $bike
                );

                $rider->customer_id = $newCustomerId;
                $rider->save();

                RiderHistoryLogger::projectChange(
                    (int) $rider->id,
                    $prevRiderCustomerId !== null && $prevRiderCustomerId !== '' ? (string) $prevRiderCustomerId : null,
                    $newCustomerId,
                    $oldProject->name ?? null,
                    $newProject->name ?? null,
                    $changeDate,
                    'bike_project_change',
                    $historyBranchId,
                    $rider,
                    $bike
                );

                $assignData = [
                    'bike_id' => $bike->id,
                    'rider_id' => $rider->id,
                    'warehouse' => 'Active',
                    'note_date' => $changeDate,
                    'notes' => $assignMessage,
                    'customer_id' => $newCustomerId,
                    'created_by' => Auth::id(),
                ];
                $assignData = array_merge(
                    $assignData,
                    BikeHistoryLogger::structuredBikeHistoryFields($bike, $rider, 'Joining', $newCustomerId)
                );
                BikeHistory::create($assignData);

                $bike->update([
                    'customer_id' => $newCustomerId,
                    'warehouse' => 'Active',
                ]);

                RiderHistoryLogger::bikeAssignStatusChange(
                    (int) $rider->id,
                    'Bike assigned: Joining',
                    'Project change — assigned under ' . ($newProject->name ?? 'new project'),
                    array_merge($riderBefore, ['customer_id' => $prevRiderCustomerId]),
                    RiderHistoryLogger::riderSnapshot($rider->fresh()),
                    $changeDate,
                    'bike_assign',
                    $historyBranchId,
                    [
                        'warehouse_action' => 'Active',
                        'bike_id' => $bike->id,
                        'bike_plate' => $bike->plate,
                        'project_change' => true,
                    ],
                    'Joining',
                    $rider,
                    $bike
                );

                DB::commit();

                return response()->json([
                    'message' => 'Project changed successfully. Previous assignment has been returned and a new assignment has been created.',
                    'reload' => true,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Bike project change failed', [
                    'bike_id' => $bike->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json(['message' => 'Project change failed. Please try again.'], 400);
            }
        }

        $projects = Customers::orderBy('name')->pluck('name', 'id')->prepend('Select', '');
        $currentProjectId = (string) ($bike->customer_id ?? '');

        return view('bikes.change_project_modal', compact('bike', 'id', 'projects', 'currentProjectId'));
    }

    private function bikeEligibleForProjectChange(Bikes $bike): bool
    {
        if (empty($bike->rider_id)) {
            return false;
        }

        if (strtolower((string) $bike->warehouse) !== 'active') {
            return false;
        }

        if (Schema::hasColumn('bikes', 'leased_return_date') && ! empty($bike->leased_return_date)) {
            return false;
        }

        return BikeHistory::where('bike_id', $bike->id)
            ->where('rider_id', $bike->rider_id)
            ->whereNull('return_date')
            ->where('warehouse', 'Active')
            ->exists();
    }

    /**
     * Close the open bike_histories row for a project change (return from current project).
     */
    private function closeOpenBikeAssignment(
        Bikes $bike,
        Riders $rider,
        string $notes,
        string $returnDate,
        ?string $historyStatus = null
    ): void {
        $lastHistory = BikeHistory::where('bike_id', $bike->id)
            ->where('rider_id', $rider->id)
            ->whereNull('return_date')
            ->latest('note_date')
            ->first();

        if (! $lastHistory) {
            throw new \RuntimeException('No open assignment found for this vehicle.');
        }

        $resolvedStatus = $historyStatus ?? BikeHistoryLogger::historyStatusFromWarehouse('Return');
        $update = [
            'warehouse' => 'Return',
            'return_date' => $returnDate,
            'notes' => $notes,
            'updated_by' => Auth::id(),
            'customer_id' => $bike->customer_id,
        ];
        $update = BikeHistoryLogger::mergeStructuredUpdate($update, $bike, $rider, $resolvedStatus);
        $lastHistory->update($update);
    }

    public function assignContract($company_slug, $id)
    {
        $contract = BikeHistory::find($id);

        return view('bikes.assignContract', compact('contract'));
    }

    public function returnContract($company_slug, $id)
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
            PublicStorageDisk::storeUploadedFile($doc, 'contract', $name);

            $contract->contract = $name;
            $contract->updated_by = Auth::id();
            $contract->save();

            return response()->json(['message' => $contract->rider->name . '( ' . $contract->rider->rider_id . ' ) Bike Plate # ' . $contract->bike->plate . ' Contract uploaded.']);
            // return redirect(url('bikes'))->with('success', $contract->rider->name . '( ' . $contract->rider->rider_id . ' ) Bike Plate # ' . $contract->bike->plate . ' Contract uploaded.');
        }

        return view('bikes.contract-modal', compact('contract'));
    }

    /**
     * Show export bikes form
     */
    public function exportBikes(Request $request)
    {
        if (! user_can('bike_view')) {
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
        if (! user_can('bike_view')) {
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
            $userSettings = UserTableSettings::getSettings(auth()->id(), 'bikes_table');

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
                return Excel::download($export, "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
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
        if (! user_can('bike_view')) {
            abort(403, 'Unauthorized action.');
        }

        \Log::info('Stack trace: reached importbikes');

        return view('bikes.import');
    }

    /**
     * Process bike import from Excel file
     */
    public function processImport(Request $request)
    {
        if (! user_can('bike_view')) {
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
                        'message' => 'Error resetting data: ' . $e->getMessage(),
                    ], 500);
                }
            }

            // Process the import
            $import = new ImportBikes;
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
                    'reset' => $reset,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'success_count' => $results['success_count'],
                'error_count' => $results['error_count'],
                'reset' => $reset,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download sample template for bike import
     */
    public function downloadSampleTemplate()
    {
        if (! user_can('bike_view')) {
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
            'leased_date',
            'customer_name',
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
                '2023-01-15',
                'Customer Name',
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
                '2023-02-20',
                'Customer Name',
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
                '2023-03-10',
                'Customer Name',
            ],
        ];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
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
        $writer = new Xlsx($spreadsheet);
        $filename = 'bikes_import_template_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function files($company_slug, $bike_id)
    {
        $bikes = Bikes::find($bike_id);
        $bikes->load(['rider', 'leasingCompany', 'leasedReturnCompany', 'customer', 'branch']);

        $expectedFiles = [
            'mulkiya' => 'Mulkiya',
            'insurance' => 'Bike Insurance',
            'advertising' => 'Advertising Permit',
        ];

        $files = CompanyQuery::table('files')
            ->where('type', 'bike')
            ->where('type_id', $bike_id)
            ->get();
        $missingFiles = [];

        foreach ($expectedFiles as $key => $desc) {
            $found = false;
            foreach ($files as $file) {
                if (str_contains(strtolower($file->name), $key)) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $missingFiles[$key] = $desc;
            }
        }

        return view('bikes.files', compact('missingFiles', 'files', 'bikes'));
    }

    public function maintenance($company_slug, $id)
    {
        $bikes = Bikes::findOrFail($id);
        $bikes->load(['rider', 'leasingCompany', 'leasedReturnCompany', 'customer', 'branch']);
        $maintenances = $bikes->maintenanceRecords()->orderBy('maintenance_date', 'desc')->get();

        return view('bikes.maintenance', compact('bikes', 'maintenances'));
    }

    /**
     * Standardized response for bike deletion errors (supports both AJAX and regular requests).
     */
    private function respondBikeDeleteError(string $message)
    {
        if (request()->ajax()) {
            return response()->json(['errors' => ['error' => $message]], 422);
        }

        Flash::error($message);

        return redirect()->back();
    }
}
