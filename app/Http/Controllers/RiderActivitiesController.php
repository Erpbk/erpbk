<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateRiderActivitiesRequest;
use App\Http\Requests\UpdateRiderActivitiesRequest;
use App\Imports\ImportRiderActivities;
use App\Imports\ImportLiveActivities;
use App\Models\Customers;
use App\Models\RiderActivities;
use App\Models\liveactivities;
use App\Models\Riders;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\RiderActivities\RiderActivityImportMappingService;
use App\Repositories\RiderActivitiesRepository;
use App\Traits\GlobalPagination;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class RiderActivitiesController extends AppBaseController
{
    use GlobalPagination;

    /** @var RiderActivitiesRepository */
    private $riderActivitiesRepository;

    public function __construct(RiderActivitiesRepository $riderActivitiesRepo)
    {
        $this->riderActivitiesRepository = $riderActivitiesRepo;
        $this->middleware('permission:riders_activities_view')->only('index', 'show');
        $this->middleware('permission:riders_activities_create')->only('create', 'store');
        $this->middleware('permission:riders_activities_edit')->only('edit', 'update');
        $this->middleware('permission:riders_activities_edit|riders_activities_create')->only('import', 'importErrors');
        $this->middleware('permission:riders_activities_delete')->only('destroy');
        $this->middleware('permission:riders_live_activities_view')->only('liveactivities', 'liveimportactivities');
        $this->middleware('permission:riders_live_activities_create')->only('liveimportactivities', 'liveimportErrors');
    }

    /**
     * Display a listing of the RiderActivities.
     */
    public function index(Request $request)
    {
        $isAllTab = $request->input('tab') === 'all';

        if ($isAllTab) {
            return $this->allRiderActivities($request);
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = RiderActivities::query()
            ->with(['rider' => function ($q) {
                $q->withTrashed();
            }])
            ->orderByDesc('date');
        $query->whereHas('rider', function ($riderQuery) {
            $riderQuery->withTrashed();
        });
        if ($request->filled('id')) {
            $rider = Riders::withTrashed()->where('rider_id', (int) $request->id)->first();
            if ($rider) {
                $query->where('rider_id', $rider->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        if ($request->filled('rider_id')) {
            $rider = Riders::withTrashed()->where('id', trim($request->rider_id))->first();
            if ($rider) {
                $query->where('rider_id', $rider->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('from_date_range')) {
            if ($request->from_date_range === 'Today') {
                $query->whereDate('date', '>=', Carbon::today());
            } else if ($request->from_date_range === 'Yesterday') {
                $query->whereDate('date', '>=', Carbon::yesterday());
            } else if ($request->from_date_range === 'Last 7 Days') {
                $query->whereDate('date', '>=', Carbon::today()->subDays(7));
            } else if ($request->from_date_range === 'Last 30 Days') {
                $query->whereDate('date', '>=', Carbon::today()->subDays(30));
            } else if ($request->from_date_range === 'Last 90 Days') {
                $query->whereDate('date', '>=', Carbon::today()->subDays(90));
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if ($request->filled('billing_month')) {
            try {
                $month = $request->billing_month;
                $year = date('Y', strtotime($month . '-01'));
                $monthNum = date('m', strtotime($month . '-01'));
                $query->whereYear('date', $year)->whereMonth('date', $monthNum);
            } catch (\Throwable $th) {
                Log::warning('Invalid billing_month supplied for rider activities filter', [
                    'value' => $request->billing_month,
                    'error' => $th->getMessage(),
                ]);
            }
        }

        if ($request->filled('valid_day')) {
            $validDay = $request->valid_day;
            if ($validDay == 'Off') {
                // Filter for records where hours = 0
                $query->where('login_hr', 0);
            } elseif ($validDay == 'Yes') {
                // Valid: (orders >= 5 AND hours >= 10) OR (orders >= 10 and hours > 0)
                $query->where(function ($q) {
                    $q->where(function ($subQ) {
                        // Case 1: 5+ orders AND 10+ hours
                        $subQ->where('delivered_orders', '>=', 5)
                            ->where('login_hr', '>=', 10);
                    })->orWhere(function ($subQ) {
                        // Case 2: 10+ orders (with hours > 0)
                        $subQ->where('delivered_orders', '>=', 10)
                            ->where('login_hr', '>', 0);
                    });
                });
            } elseif ($validDay == 'No') {
                // Invalid: hours > 0 but doesn't meet valid criteria
                $query->where('login_hr', '>', 0)
                    ->where(function ($q) {
                        // Not valid: neither (5+ orders AND 10+ hours) nor (10+ orders)
                        $q->where(function ($subQ) {
                            // Less than 5 orders OR less than 10 hours
                            $subQ->where('delivered_orders', '<', 5)
                                ->orWhere('login_hr', '<', 10);
                        })->where('delivered_orders', '<', 10); // AND less than 10 orders
                    });
            }
        }

        if ($request->filled('fleet_supervisor')) {
            $query->whereHas('rider', function ($q) use ($request) {
                $q->withTrashed()->where('fleet_supervisor', $request->fleet_supervisor);
            });
        }

        if ($request->filled('payout_type')) {
            $query->where('payout_type', $request->payout_type);
        }

        if ($request->filled('bike_assignment_status')) {
            $query->whereHas('rider', function ($q) use ($request) {
                $q->withTrashed();
                if ($request->bike_assignment_status === 'Active') {
                    $q->whereHas('bikes', function ($q) {
                        $q->where('warehouse', 'Active');
                    });
                } elseif ($request->bike_assignment_status === 'Inactive') {
                    $q->whereDoesntHave('bikes', function ($q) {
                        $q->where('warehouse', 'Active');
                    });
                }
            });
        }

        // Get all data for totals calculation (before pagination)
        $allData = (clone $query)->get();

        // Calculate totals from all filtered data
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

        $data = $this->applyPagination($query, $paginationParams);

        if (method_exists($data, 'appends')) {
            $data->appends($request->query());
        }

        $riders = Riders::withTrashed()
            ->select('id', 'name', 'rider_id', 'status')
            ->orderBy('name')
            ->get();

        $fleetSupervisors = Riders::withTrashed()
            ->whereNotNull('fleet_supervisor')
            ->where('fleet_supervisor', '!=', '')
            ->distinct()
            ->orderBy('fleet_supervisor')
            ->pluck('fleet_supervisor');

        $payoutTypes = RiderActivities::query()
            ->whereNotNull('payout_type')
            ->where('payout_type', '!=', '')
            ->distinct()
            ->orderBy('payout_type')
            ->pluck('payout_type');

        $projects = collect();
        $isAllTab = false;
        $isConsolidated = false;

        if ($request->ajax()) {
            $tableData = view('rider_activities.table', [
                'data' => $data,
                'totals' => $totals,
                'isConsolidated' => false,
                'isAllTab' => false,
            ])->render();
            $paginationLinks = method_exists($data, 'links')
                ? $data->links('components.global-pagination')->render()
                : '';

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'totals' => $totals,
            ]);
        }

        return view('rider_activities.index', compact(
            'data',
            'riders',
            'fleetSupervisors',
            'payoutTypes',
            'totals',
            'projects',
            'isAllTab',
            'isConsolidated'
        ));
    }

    /**
     * All Rider Activities tab: one consolidated row per rider.
     */
    protected function allRiderActivities(Request $request)
    {
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $isConsolidated = true;

        $query = RiderActivities::query()
            ->with(['rider' => function ($q) {
                $q->withTrashed()->with('customer');
            }])
            ->orderByDesc('date')
            ->whereHas('rider', function ($riderQuery) {
                // Include active and inactive riders (soft-deleted included).
                $riderQuery->withTrashed();
            });

        if ($request->filled('rider_id')) {
            $rider = Riders::withTrashed()->where('id', trim($request->rider_id))->first();
            if ($rider) {
                $query->where('rider_id', $rider->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if ($request->filled('billing_month')) {
            try {
                $month = $request->billing_month;
                $year = date('Y', strtotime($month . '-01'));
                $monthNum = date('m', strtotime($month . '-01'));
                $query->whereYear('date', $year)->whereMonth('date', $monthNum);
            } catch (\Throwable $th) {
                Log::warning('Invalid billing_month supplied for rider summary filter', [
                    'value' => $request->billing_month,
                    'error' => $th->getMessage(),
                ]);
            }
        }

        if ($request->filled('fleet_supervisor')) {
            $query->whereHas('rider', function ($q) use ($request) {
                $q->withTrashed()->where('fleet_supervisor', $request->fleet_supervisor);
            });
        }

        if ($request->filled('customer_id')) {
            $query->whereHas('rider', function ($q) use ($request) {
                $q->withTrashed()->where('customer_id', $request->customer_id);
            });
        }

        if ($request->filled('rider_status')) {
            $query->whereHas('rider', function ($q) use ($request) {
                $q->withTrashed();
                $status = strtolower(trim((string) $request->rider_status));
                if ($status === 'active') {
                    $q->where('status', 1);
                } elseif ($status === 'inactive') {
                    $q->whereIn('status', [0, 2, 3]);
                } elseif ($status === 'vacation') {
                    $q->where('status', 4);
                } elseif ($status === 'absconded') {
                    $q->where('status', 5);
                }
            });
        }

        $allData = (clone $query)->get();

        $validActivities = $allData->filter(function ($item) {
            return $this->resolveActivityDayStatus($item) === 'Valid';
        });

        $totals = [
            'working_days' => $validActivities->count(),
            'valid_days' => $validActivities->count(),
            'invalid_days' => $allData->filter(function ($item) {
                return $this->resolveActivityDayStatus($item) === 'Invalid';
            })->count(),
            'off_days' => $allData->filter(function ($item) {
                return $this->resolveActivityDayStatus($item) === 'Off';
            })->count(),
            'total_orders' => $validActivities->sum('delivered_orders'),
            'total_rejected' => $validActivities->sum('rejected_orders'),
            'total_hours' => $validActivities->sum('login_hr'),
            'avg_ontime' => ($validActivities->where('ontime_orders_percentage', '>', 0)->avg('ontime_orders_percentage') ?? 0) * 100,
        ];

        $data = $this->buildConsolidatedRiderActivities($allData, $request, $paginationParams);

        $riders = Riders::withTrashed()
            ->select('id', 'name', 'rider_id', 'status')
            ->orderBy('name')
            ->get();

        $fleetSupervisors = Riders::withTrashed()
            ->whereNotNull('fleet_supervisor')
            ->where('fleet_supervisor', '!=', '')
            ->distinct()
            ->orderBy('fleet_supervisor')
            ->pluck('fleet_supervisor');

        $customerIds = Riders::withTrashed()
            ->whereNotNull('customer_id')
            ->where('customer_id', '!=', '')
            ->distinct()
            ->pluck('customer_id');

        $projects = Customers::query()
            ->whereIn('id', $customerIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $payoutTypes = collect();
        $isAllTab = true;

        if ($request->ajax()) {
            $tableData = view('rider_activities.table', [
                'data' => $data,
                'totals' => $totals,
                'isConsolidated' => $isConsolidated,
                'isAllTab' => false,
                'hideDay' => true,
            ])->render();
            $paginationLinks = method_exists($data, 'links')
                ? $data->links('components.global-pagination')->render()
                : '';

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'totals' => $totals,
                'isConsolidated' => $isConsolidated,
            ]);
        }

        return view('rider_activities.index', compact(
            'data',
            'riders',
            'fleetSupervisors',
            'payoutTypes',
            'totals',
            'projects',
            'isAllTab',
            'isConsolidated'
        ));
    }

    /**
     * Build one consolidated activity row per rider, then paginate.
     */
    protected function buildConsolidatedRiderActivities($activities, Request $request, array $paginationParams = [])
    {
        $perPage = (int) ($paginationParams['per_page_numeric'] ?? $paginationParams['per_page'] ?? $this->getDefaultPerPage());
        if ($perPage < 1) {
            $perPage = $this->getDefaultPerPage();
        }
        $page = max(1, (int) $request->get('page', 1));

        if ($activities->isEmpty()) {
            return new LengthAwarePaginator(
                [],
                0,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        }

        $dateFromFilter = $request->filled('from_date')
            ? Carbon::parse($request->from_date)->toDateString()
            : null;
        $dateToFilter = $request->filled('to_date')
            ? Carbon::parse($request->to_date)->toDateString()
            : null;

        $consolidatedRows = $activities
            ->groupBy('rider_id')
            ->map(function ($riderActivities) use ($dateFromFilter, $dateToFilter) {
                $first = $riderActivities->first();
                $validDays = 0;
                $invalidDays = 0;
                $offDays = 0;
                $validActivities = collect();

                foreach ($riderActivities as $activity) {
                    $status = $this->resolveActivityDayStatus($activity);
                    if ($status === 'Valid') {
                        $validDays++;
                        $validActivities->push($activity);
                    } elseif ($status === 'Invalid') {
                        $invalidDays++;
                    } else {
                        $offDays++;
                    }
                }

                // Aggregate metrics from valid days only
                $avgOntime = $validActivities->where('ontime_orders_percentage', '>', 0)->avg('ontime_orders_percentage');
                $dateFrom = $dateFromFilter ?: $riderActivities->min('date');
                $dateTo = $dateToFilter ?: $riderActivities->max('date');

                if ($dateFrom) {
                    $dateFrom = Carbon::parse($dateFrom)->toDateString();
                }
                if ($dateTo) {
                    $dateTo = Carbon::parse($dateTo)->toDateString();
                }

                $riderName = optional($first->rider)->name ?? '';

                return (object) [
                    'id' => $first->id,
                    'rider_id' => $first->rider_id,
                    'd_rider_id' => $first->d_rider_id,
                    'date' => $dateFrom ?: $first->date,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'is_consolidated' => true,
                    'activity_days' => $validDays,
                    'delivered_orders' => $validActivities->sum('delivered_orders'),
                    'rejected_orders' => $validActivities->sum('rejected_orders'),
                    'login_hr' => round((float) $validActivities->sum('login_hr'), 2),
                    'ontime_orders_percentage' => $avgOntime ? round((float) $avgOntime, 2) : null,
                    'delivery_rating' => null,
                    'valid_days_count' => $validDays,
                    'invalid_days_count' => $invalidDays,
                    'off_days_count' => $offDays,
                    'rider' => $first->rider,
                    'rider_name' => $riderName,
                ];
            })
            ->sortBy('rider_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $total = $consolidatedRows->count();
        $items = $consolidatedRows->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    /**
     * Resolve Valid / Invalid / Off status for an activity day.
     */
    protected function resolveActivityDayStatus($activity): string
    {
        $orders = (float) ($activity->delivered_orders ?? 0);
        $hours = (float) ($activity->login_hr ?? 0);

        if ($hours == 0) {
            return 'Off';
        }

        if (($orders >= 5 && $hours >= 10) || $orders >= 10) {
            return 'Valid';
        }

        return 'Invalid';
    }

    /**
     * Show the form for creating a new RiderActivities.
     */
    public function create()
    {
        return view('rider_activities.create');
    }

    /**
     * Store a newly created RiderActivities in storage.
     */
    public function store(CreateRiderActivitiesRequest $request)
    {
        $input = $request->all();

        $this->riderActivitiesRepository->create($input);

        flash('Rider Activities saved successfully.')->success();

        return redirect(route('riderActivities.index'));
    }

    /**
     * Display the specified RiderActivities.
     */
    public function show($company_slug, $id)
    {
        $riderActivities = $this->riderActivitiesRepository->find($id);

        if (empty($riderActivities)) {
            flash('Rider Activities not found.')->error();

            return redirect(route('riderActivities.index'));
        }

        return view('rider_activities.show')->with('riderActivities', $riderActivities);
    }

    /**
     * Show the form for editing the specified RiderActivities.
     */
    public function edit($company_slug, $id)
    {
        $riderActivities = $this->riderActivitiesRepository->find($id);

        if (empty($riderActivities)) {
            flash('Rider Activities not found.')->error();

            return redirect(route('riderActivities.index'));
        }

        return view('rider_activities.edit')->with('riderActivities', $riderActivities);
    }

    /**
     * Update the specified RiderActivities in storage.
     */
    public function update($company_slug, $id, UpdateRiderActivitiesRequest $request)
    {
        $riderActivities = $this->riderActivitiesRepository->find($id);

        if (empty($riderActivities)) {
            flash('Rider Activities not found.')->error();

            return redirect(route('riderActivities.index'));
        }

        $this->riderActivitiesRepository->update($request->all(), $id);

        flash('Rider Activities updated successfully.')->success();

        return redirect(route('riderActivities.index'));
    }

    /**
     * Remove the specified RiderActivities from storage.
     *
     * @throws \Exception
     */
    public function destroy($company_slug, $id)
    {
        $riderActivities = $this->riderActivitiesRepository->find($id);

        if (empty($riderActivities)) {
            flash('Rider Activities not found.')->error();

            return redirect(route('riderActivities.index'));
        }

        $this->riderActivitiesRepository->delete($id);

        flash('Rider Activities deleted successfully.')->success();

        return redirect(route('riderActivities.index'));
    }

    /**
     * Handle Noon rider activities import.
     */
    public function import(Request $request, RiderActivityImportMappingService $importMappingService)
    {
        $customers = Customers::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $configuredCustomerIds = $importMappingService->getConfiguredCustomerIds(RiderActivityImportMappingService::TYPE_RIDER);

        if ($request->isMethod('post')) {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls|max:51200',
                'customer_id' => 'required|integer|exists:customers,id',
            ], [
                'file.required' => 'Please select a file to upload.',
                'file.mimes' => 'The file must be a CSV or Excel document.',
                'customer_id.required' => 'Please select a project for this import.',
            ]);

            $customerId = (int) $request->input('customer_id');

            if (!$importMappingService->isImportReady($customerId, RiderActivityImportMappingService::TYPE_RIDER)) {
                session()->flash('error', 'Import is not configured for the selected project. Configure column mappings in Rider Activity Import Settings first.');
                return redirect()->route('rider.activities_import');
            }

            // Clear previous import summary
            session()->forget('activities_import_summary');

            $import = new ImportRiderActivities($customerId, $importMappingService);

            try {
                Excel::import($import, $request->file('file'));
            } catch (\Illuminate\Validation\ValidationException $ve) {
                // Handle validation errors (Rider ID not found, etc.)
                $summary = session('activities_import_summary', []);
                $unmatchedRiderIds = $summary['unmatched_rider_ids'] ?? [];
                $errors = $ve->errors();
                $fileErrors = is_array($errors['file'] ?? null) ? $errors['file'] : [];

                if (!empty($unmatchedRiderIds)) {
                    $listedIds = implode(', ', array_map(fn ($id) => "'{$id}'", $unmatchedRiderIds));
                    $errorMessage = 'The following rider_id(s) from the sheet do not exist or do not match any rider: ' . $listedIds . '.';
                    $otherErrors = array_values(array_filter($fileErrors, function ($message) {
                        return stripos($message, 'do not exist or do not match any rider') === false;
                    }));
                    if (!empty($otherErrors)) {
                        $errorMessage .= ' | ' . implode(' | ', $otherErrors);
                    }
                } else {
                    $errorMessage = !empty($fileErrors)
                        ? implode(' | ', $fileErrors)
                        : ($errors['file'][0] ?? 'Import validation failed');
                }
                session()->flash('error', 'Import failed: ' . $errorMessage);
                return redirect()->route('riderActivities.index');
            } catch (\Throwable $th) {
                // Error popup (includes other system errors)
                // Also check session for any errors that might have been recorded
                $summary = session('activities_import_summary', []);
                $errors = $summary['errors'] ?? [];

                if (!empty($errors)) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $riderId = $error['rider_id'] ?? 'N/A';
                        $errorMessages[] = 'Row(' . $error['row'] . ') - ' . $error['error_type'] . ': ' . $error['message'] . ($riderId !== 'N/A' ? ' (Rider ID: ' . $riderId . ')' : '');
                    }
                    session()->flash('error', 'Import failed: ' . implode(' | ', $errorMessages));
                } else {
                    session()->flash('error', 'Import failed: ' . $th->getMessage());
                }
                return redirect()->route('riderActivities.index');
            }

            // Always check session summary for errors after import completes
            $summary = session('activities_import_summary', []);
            $errors = $summary['errors'] ?? [];
            $missingRecords = $summary['missing_records'] ?? [];
            $successCount = $summary['success'] ?? 0;

            // Log the summary for debugging
            Log::info('Rider Activities Import - Controller Summary Check', [
                'success_count' => $successCount,
                'error_count' => count($errors),
                'missing_records_count' => count($missingRecords),
                'summary' => $summary
            ]);

            // Never show success if there are critical errors OR if no records were successfully imported
            if (!empty($errors)) {
                $unmatchedRiderIds = $summary['unmatched_rider_ids'] ?? [];
                if (empty($unmatchedRiderIds)) {
                    foreach ($errors as $error) {
                        if (($error['error_type'] ?? '') === 'Rider Not Found' && !empty($error['rider_id'])) {
                            $unmatchedRiderIds[] = $error['rider_id'];
                        }
                    }
                    $unmatchedRiderIds = array_values(array_unique($unmatchedRiderIds));
                }

                $errorMessages = [];
                if (!empty($unmatchedRiderIds)) {
                    $listedIds = implode(', ', array_map(fn ($id) => "'{$id}'", $unmatchedRiderIds));
                    $errorMessages[] = 'The following rider_id(s) from the sheet do not exist or do not match any rider: ' . $listedIds . '.';
                }

                foreach ($errors as $error) {
                    if (($error['error_type'] ?? '') === 'Rider Not Found') {
                        continue;
                    }
                    $riderId = $error['rider_id'] ?? 'N/A';
                    $errorMessages[] = 'Row(' . $error['row'] . ') - ' . $error['error_type'] . ': ' . $error['message'] . ($riderId !== 'N/A' ? ' (Rider ID: ' . $riderId . ')' : '');
                }

                if (empty($errorMessages)) {
                    $errorMessages[] = 'Import validation failed.';
                }

                session()->flash('error', 'Import failed: ' . implode(' | ', $errorMessages));
            } elseif ($successCount == 0) {
                session()->flash('error', 'Import failed: No records were imported. Please check that your file contains valid data.');
            } else {
                $message = "Rider activities imported successfully. {$successCount} record(s) saved.";
                session()->flash('success', $message);
            }

            return redirect()->route('riderActivities.index');
        }

        $summary = session('activities_import_summary');
        $defaultCustomerId = RiderActivityImportMappingService::DEFAULT_CUSTOMER_ID;
        $importSettingsUrl = null;
        $companySlug = $request->route('company_slug') ?? session('company_slug');
        if ($companySlug) {
            $importSettingsUrl = route('settings-panel.rider-activity-import-settings.index', [
                'company_slug' => $companySlug,
                'import_type' => RiderActivityImportMappingService::TYPE_RIDER,
            ]);
        }

        return view('rider_activities.import', compact(
            'summary',
            'customers',
            'configuredCustomerIds',
            'defaultCustomerId',
            'importSettingsUrl'
        ));
    }


    /**
     * Display last Rider Activities import errors.
     */
    public function importErrors(Request $request)
    {
        $summary = session('activities_import_summary', []);
        $errors = $summary['errors'] ?? [];
        $missingRecords = $summary['missing_records'] ?? [];

        // Apply date filters if provided
        if ($request->filled('from_date')) {
            $fromDate = $request->from_date;
            $missingRecords = array_filter($missingRecords, function ($record) use ($fromDate) {
                $recordDate = $record['date'] ?? null;
                if (!$recordDate || $recordDate == 'N/A') {
                    return false;
                }
                return strtotime($recordDate) >= strtotime($fromDate);
            });
        }

        if ($request->filled('to_date')) {
            $toDate = $request->to_date;
            $missingRecords = array_filter($missingRecords, function ($record) use ($toDate) {
                $recordDate = $record['date'] ?? null;
                if (!$recordDate || $recordDate == 'N/A') {
                    return false;
                }
                return strtotime($recordDate) <= strtotime($toDate);
            });
        }

        // Handle from_date_range shortcuts
        if ($request->filled('from_date_range')) {
            $fromDateRange = $request->from_date_range;
            $fromDate = null;

            if ($fromDateRange === 'Today') {
                $fromDate = Carbon::today()->toDateString();
            } else if ($fromDateRange === 'Yesterday') {
                $fromDate = Carbon::yesterday()->toDateString();
            } else if ($fromDateRange === 'Last 7 Days') {
                $fromDate = Carbon::today()->subDays(7)->toDateString();
            } else if ($fromDateRange === 'Last 30 Days') {
                $fromDate = Carbon::today()->subDays(30)->toDateString();
            } else if ($fromDateRange === 'Last 90 Days') {
                $fromDate = Carbon::today()->subDays(90)->toDateString();
            }

            if ($fromDate) {
                $missingRecords = array_filter($missingRecords, function ($record) use ($fromDate) {
                    $recordDate = $record['date'] ?? null;
                    if (!$recordDate || $recordDate == 'N/A') {
                        return false;
                    }
                    return strtotime($recordDate) >= strtotime($fromDate);
                });
            }
        }

        // Re-index arrays after filtering
        $missingRecords = array_values($missingRecords);

        return view('rider_activities.import_errors', [
            'summary' => $summary,
            'errors' => $errors,
            'missingRecords' => $missingRecords,
            'importType' => 'Rider Activities',
            'importRoute' => route('rider.activities_import'),
        ]);
    }

    /**
     * Display last Live Activities import errors.
     */
    public function liveimportErrors(Request $request)
    {
        $summary = session('activities_import_summary', []);
        $errors = $summary['errors'] ?? [];
        $missingRecords = $summary['missing_records'] ?? [];

        // Apply date filters if provided
        if ($request->filled('from_date')) {
            $fromDate = $request->from_date;
            $missingRecords = array_filter($missingRecords, function ($record) use ($fromDate) {
                $recordDate = $record['date'] ?? null;
                if (!$recordDate || $recordDate == 'N/A') {
                    return false;
                }
                return strtotime($recordDate) >= strtotime($fromDate);
            });
        }

        if ($request->filled('to_date')) {
            $toDate = $request->to_date;
            $missingRecords = array_filter($missingRecords, function ($record) use ($toDate) {
                $recordDate = $record['date'] ?? null;
                if (!$recordDate || $recordDate == 'N/A') {
                    return false;
                }
                return strtotime($recordDate) <= strtotime($toDate);
            });
        }

        // Handle from_date_range shortcuts
        if ($request->filled('from_date_range')) {
            $fromDateRange = $request->from_date_range;
            $fromDate = null;

            if ($fromDateRange === 'Today') {
                $fromDate = Carbon::today()->toDateString();
            } else if ($fromDateRange === 'Yesterday') {
                $fromDate = Carbon::yesterday()->toDateString();
            } else if ($fromDateRange === 'Last 7 Days') {
                $fromDate = Carbon::today()->subDays(7)->toDateString();
            } else if ($fromDateRange === 'Last 30 Days') {
                $fromDate = Carbon::today()->subDays(30)->toDateString();
            } else if ($fromDateRange === 'Last 90 Days') {
                $fromDate = Carbon::today()->subDays(90)->toDateString();
            }

            if ($fromDate) {
                $missingRecords = array_filter($missingRecords, function ($record) use ($fromDate) {
                    $recordDate = $record['date'] ?? null;
                    if (!$recordDate || $recordDate == 'N/A') {
                        return false;
                    }
                    return strtotime($recordDate) >= strtotime($fromDate);
                });
            }
        }

        // Re-index arrays after filtering
        $missingRecords = array_values($missingRecords);

        return view('rider_live_activities.import_errors', [
            'summary' => $summary,
            'errors' => $errors,
            'missingRecords' => $missingRecords,
            'importType' => 'Live Activities',
            'importRoute' => route('rider.live_activities_import'),
        ]);
    }
    public function liveactivities(Request $request)
    {
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = liveactivities::query()
            ->with('rider')
            ->orderByDesc('date');
        $query->whereHas('rider');
        if ($request->filled('id')) {
            $rider = Riders::where('rider_id', (int) $request->id)->first();
            if ($rider) {
                $query->where('rider_id', $rider->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('rider_id')) {
            $rider = Riders::where('id', trim($request->rider_id))->first();
            if ($rider) {
                $query->where('rider_id', $rider->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('from_date_range')) {
            if ($request->from_date_range === 'Today') {
                $query->whereDate('date', '>=', Carbon::today());
            } else if ($request->from_date_range === 'Yesterday') {
                $query->whereDate('date', '>=', Carbon::yesterday());
            } else if ($request->from_date_range === 'Last 7 Days') {
                $query->whereDate('date', '>=', Carbon::today()->subDays(7));
            } else if ($request->from_date_range === 'Last 30 Days') {
                $query->whereDate('date', '>=', Carbon::today()->subDays(30));
            } else if ($request->from_date_range === 'Last 90 Days') {
                $query->whereDate('date', '>=', Carbon::today()->subDays(90));
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if ($request->filled('billing_month')) {
            try {
                $month = $request->billing_month;
                $year = date('Y', strtotime($month . '-01'));
                $monthNum = date('m', strtotime($month . '-01'));
                $query->whereYear('date', $year)->whereMonth('date', $monthNum);
            } catch (\Throwable $th) {
                Log::warning('Invalid billing_month supplied for rider live activities filter', [
                    'value' => $request->billing_month,
                    'error' => $th->getMessage(),
                ]);
            }
        }

        if ($request->filled('valid_day')) {
            $validDay = $request->valid_day;
            if ($validDay == 'Off') {
                // Filter for records where hours = 0
                $query->where('login_hr', 0);
            } elseif ($validDay == 'Yes') {
                // Valid: (orders >= 5 AND hours >= 10) OR (orders >= 10 and hours > 0)
                $query->where(function ($q) {
                    $q->where(function ($subQ) {
                        // Case 1: 5+ orders AND 10+ hours
                        $subQ->where('delivered_orders', '>=', 5)
                            ->where('login_hr', '>=', 10);
                    })->orWhere(function ($subQ) {
                        // Case 2: 10+ orders (with hours > 0)
                        $subQ->where('delivered_orders', '>=', 10)
                            ->where('login_hr', '>', 0);
                    });
                });
            } elseif ($validDay == 'No') {
                // Invalid: hours > 0 but doesn't meet valid criteria
                $query->where('login_hr', '>', 0)
                    ->where(function ($q) {
                        // Not valid: neither (5+ orders AND 10+ hours) nor (10+ orders)
                        $q->where(function ($subQ) {
                            // Less than 5 orders OR less than 10 hours
                            $subQ->where('delivered_orders', '<', 5)
                                ->orWhere('login_hr', '<', 10);
                        })->where('delivered_orders', '<', 10); // AND less than 10 orders
                    });
            }
        }

        if ($request->filled('fleet_supervisor')) {
            $query->whereHas('rider', function ($q) use ($request) {
                $q->where('fleet_supervisor', $request->fleet_supervisor);
            });
        }

        if ($request->filled('payout_type')) {
            $query->where('payout_type', $request->payout_type);
        }

        if ($request->filled('bike_assignment_status')) {
            $query->whereHas('rider', function ($q) use ($request) {
                if ($request->bike_assignment_status === 'Active') {
                    $q->whereHas('bikes', function ($q) {
                        $q->where('warehouse', 'Active');
                    });
                } elseif ($request->bike_assignment_status === 'Inactive') {
                    $q->whereDoesntHave('bikes', function ($q) {
                        $q->where('warehouse', 'Active');
                    });
                }
            });
        }

        // Get all data for totals calculation (before pagination)
        $allData = (clone $query)->get();

        // Calculate totals from all filtered data
        $totals = [
            'total_orders' => $allData->sum('delivered_orders'),
            'total_rejected' => $allData->sum('rejected_orders'),
            'total_hours' => $allData->sum('login_hr'),
            'avg_ontime' => $allData->where('ontime_orders_percentage', '>', 0)->avg('ontime_orders_percentage') ?? 0,
        ];

        $data = $this->applyPagination($query, $paginationParams);

        if (method_exists($data, 'appends')) {
            $data->appends($request->query());
        }

        $riders = Riders::select('id', 'name', 'rider_id')
            ->orderBy('name')
            ->get();

        $fleetSupervisors = Riders::query()
            ->whereNotNull('fleet_supervisor')
            ->where('fleet_supervisor', '!=', '')
            ->distinct()
            ->orderBy('fleet_supervisor')
            ->pluck('fleet_supervisor');

        $payoutTypes = liveactivities::query()
            ->whereNotNull('payout_type')
            ->where('payout_type', '!=', '')
            ->distinct()
            ->orderBy('payout_type')
            ->pluck('payout_type');

        if ($request->ajax()) {
            $tableData = view('rider_live_activities.table', ['data' => $data, 'totals' => $totals])->render();
            $paginationLinks = method_exists($data, 'links')
                ? $data->links('components.global-pagination')->render()
                : '';

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'totals' => $totals,
            ]);
        }

        $importSummary = session('activities_import_summary');

        return view('rider_live_activities.index', compact('data', 'riders', 'fleetSupervisors', 'payoutTypes', 'totals', 'importSummary'));
    }
    public function liveimportactivities(Request $request, RiderActivityImportMappingService $importMappingService)
    {
        $customers = Customers::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $configuredCustomerIds = $importMappingService->getConfiguredCustomerIds(RiderActivityImportMappingService::TYPE_LIVE);

        if ($request->isMethod('post')) {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls|max:51200',
                'customer_id' => 'required|integer|exists:customers,id',
            ], [
                'file.required' => 'Please select a file to upload.',
                'file.mimes' => 'The file must be a CSV or Excel document.',
                'customer_id.required' => 'Please select a project for this import.',
            ]);

            $customerId = (int) $request->input('customer_id');

            if (!$importMappingService->isImportReady($customerId, RiderActivityImportMappingService::TYPE_LIVE)) {
                session()->flash('error', 'Import is not configured for the selected project. Configure column mappings in Live Activity Import Settings first.');
                return redirect()->route('rider.live_activities_import');
            }

            session()->forget('activities_import_summary');

            $import = new ImportLiveActivities($customerId, $importMappingService);

            try {
                Excel::import($import, $request->file('file'));
            } catch (\Illuminate\Validation\ValidationException $ve) {
                $errors = $ve->errors();
                $errorMessage = is_array($errors['file'] ?? null)
                    ? implode(' | ', $errors['file'])
                    : ($errors['file'][0] ?? 'Import validation failed');
                session()->flash('error', 'Import failed: ' . $errorMessage);
                return redirect()->route('rider.liveactivities');
            } catch (\Throwable $th) {
                $summary = session('activities_import_summary', []);
                $errors = $summary['errors'] ?? [];

                if (!empty($errors)) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $riderId = $error['rider_id'] ?? 'N/A';
                        $errorMessages[] = 'Row(' . $error['row'] . ') - ' . $error['error_type'] . ': ' . $error['message'] . ($riderId !== 'N/A' ? ' (Rider ID: ' . $riderId . ')' : '');
                    }
                    session()->flash('error', 'Import failed: ' . implode(' | ', $errorMessages));
                } else {
                    session()->flash('error', 'Import failed: ' . $th->getMessage());
                }
                return redirect()->route('rider.liveactivities');
            }

            $summary = session('activities_import_summary', []);
            $errors = $summary['errors'] ?? [];
            $missingRecords = $summary['missing_records'] ?? [];
            $successCount = $summary['success'] ?? 0;

            if (!empty($errors)) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $riderId = $error['rider_id'] ?? 'N/A';
                    $errorMessages[] = 'Row(' . $error['row'] . ') - ' . $error['error_type'] . ': ' . $error['message'] . ($riderId !== 'N/A' ? ' (Rider ID: ' . $riderId . ')' : '');
                }
                session()->flash('error', 'Import failed: ' . implode(' | ', $errorMessages));
            } elseif ($successCount == 0 && empty($missingRecords)) {
                session()->flash('error', 'Import failed: No records were imported. Please check that your file contains valid data.');
            } else {
                $message = "Live activities imported successfully. {$successCount} record(s) saved.";
                if (!empty($missingRecords)) {
                    $message .= " " . count($missingRecords) . " record(s) skipped due to missing riders. Check Missing Records list for details.";
                }
                session()->flash('success', $message);
            }

            return redirect()->route('rider.liveactivities');
        }

        $summary = session('activities_import_summary');
        $defaultCustomerId = RiderActivityImportMappingService::DEFAULT_CUSTOMER_ID;
        $importSettingsUrl = null;
        $companySlug = $request->route('company_slug') ?? session('company_slug');
        if ($companySlug) {
            $importSettingsUrl = route('settings-panel.rider-activity-import-settings.index', [
                'company_slug' => $companySlug,
                'import_type' => RiderActivityImportMappingService::TYPE_LIVE,
            ]);
        }

        return view('rider_live_activities.import', compact(
            'summary',
            'customers',
            'configuredCustomerIds',
            'defaultCustomerId',
            'importSettingsUrl'
        ));
    }
}
