<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateRiderActivitiesRequest;
use App\Http\Requests\UpdateRiderActivitiesRequest;
use App\Imports\ImportRiderActivities;
use App\Imports\ImportLiveActivities;
use App\Models\RiderActivities;
use App\Models\liveactivities;
use App\Models\Riders;
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
    }

    /**
     * Display a listing of the RiderActivities.
     */
    public function index(Request $request)
    {
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = RiderActivities::query()
            ->with('rider')
            ->orderByDesc('date');
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

        $riders = Riders::select('id', 'name', 'rider_id')
            ->orderBy('name')
            ->get();

        $fleetSupervisors = Riders::query()
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

        if ($request->ajax()) {
            $tableData = view('rider_activities.table', ['data' => $data, 'totals' => $totals])->render();
            $paginationLinks = method_exists($data, 'links')
                ? $data->links('components.global-pagination')->render()
                : '';

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'totals' => $totals,
            ]);
        }

        return view('rider_activities.index', compact('data', 'riders', 'fleetSupervisors', 'payoutTypes', 'totals'));
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
    public function show($id)
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
    public function edit($id)
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
    public function update($id, UpdateRiderActivitiesRequest $request)
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
    public function destroy($id)
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
    public function import(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls|max:51200',
            ], [
                'file.required' => 'Please select a file to upload.',
                'file.mimes' => 'The file must be a CSV or Excel document.',
            ]);

            // Clear previous import summary
            session()->forget('activities_import_summary');

            $import = new ImportRiderActivities();

            try {
                Excel::import($import, $request->file('file'));
            } catch (\Illuminate\Validation\ValidationException $ve) {
                // Handle validation errors (Rider ID not found, etc.)
                $errors = $ve->errors();
                $errorMessage = is_array($errors['file'] ?? null)
                    ? implode(' | ', $errors['file'])
                    : ($errors['file'][0] ?? 'Import validation failed');
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
            $successCount = $summary['success'] ?? 0;

            // Log the summary for debugging
            Log::info('Rider Activities Import - Controller Summary Check', [
                'success_count' => $successCount,
                'error_count' => count($errors),
                'summary' => $summary
            ]);

            // Never show success if there are errors OR if no records were successfully imported
            if (!empty($errors)) {
                // If there are errors, show error message instead of success
                $errorMessages = [];
                foreach ($errors as $error) {
                    $riderId = $error['rider_id'] ?? 'N/A';
                    $errorMessages[] = 'Row(' . $error['row'] . ') - ' . $error['error_type'] . ': ' . $error['message'] . ($riderId !== 'N/A' ? ' (Rider ID: ' . $riderId . ')' : '');
                }
                session()->flash('error', 'Import failed: ' . implode(' | ', $errorMessages));
            } elseif ($successCount == 0) {
                session()->flash('error', 'Import failed: No records were imported. Please check that your file contains valid data with matching Rider IDs.');
            } else {
                // Success popup only if no errors and records were imported
                session()->flash('success', "Rider activities imported successfully. {$successCount} record(s) saved.");
            }

            return redirect()->route('riderActivities.index');
        }

        $summary = session('activities_import_summary');

        return view('rider_activities.import', compact('summary'));
    }


    /**
     * Display last Live Activities import errors.
     */
    public function liveimportErrors()
    {
        $summary = session('activities_import_summary', []);
        $errors = $summary['errors'] ?? [];

        return view('rider_live_activities.import_errors', [
            'summary' => $summary,
            'errors' => $errors,
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
    public function liveimportactivities(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls|max:51200',
            ], [
                'file.required' => 'Please select a file to upload.',
                'file.mimes' => 'The file must be a CSV or Excel document.',
            ]);

            // Clear previous import summary
            session()->forget('activities_import_summary');

            $import = new ImportLiveActivities();

            try {
                Excel::import($import, $request->file('file'));
            } catch (\Illuminate\Validation\ValidationException $ve) {
                // Handle validation errors (Rider ID not found, etc.)
                $errors = $ve->errors();
                $errorMessage = is_array($errors['file'] ?? null)
                    ? implode(' | ', $errors['file'])
                    : ($errors['file'][0] ?? 'Import validation failed');
                session()->flash('error', 'Import failed: ' . $errorMessage);
                return redirect()->route('rider.liveactivities');
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
                return redirect()->route('rider.liveactivities');
            }

            // Always check session summary for errors after import completes
            $summary = session('activities_import_summary', []);
            $errors = $summary['errors'] ?? [];
            $successCount = $summary['success'] ?? 0;

            // Never show success if there are errors OR if no records were successfully imported
            if (!empty($errors)) {
                // If there are errors, show error message instead of success
                $errorMessages = [];
                foreach ($errors as $error) {
                    $riderId = $error['rider_id'] ?? 'N/A';
                    $errorMessages[] = 'Row(' . $error['row'] . ') - ' . $error['error_type'] . ': ' . $error['message'] . ($riderId !== 'N/A' ? ' (Rider ID: ' . $riderId . ')' : '');
                }
                session()->flash('error', 'Import failed: ' . implode(' | ', $errorMessages));
            } elseif ($successCount == 0) {
                session()->flash('error', 'Import failed: No records were imported. Please check that your file contains valid data with matching Rider IDs.');
            } else {
                // Success popup only if no errors and records were imported
                session()->flash('success', "Live activities imported successfully. {$successCount} record(s) saved.");
            }

            return redirect()->route('rider.liveactivities');
        }

        $summary = session('activities_import_summary');

        return view('rider_live_activities.import', compact('summary'));
    }
}
