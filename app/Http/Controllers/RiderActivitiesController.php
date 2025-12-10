<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateRiderActivitiesRequest;
use App\Http\Requests\UpdateRiderActivitiesRequest;
use App\Imports\ImportRiderActivities;
use App\Models\RiderActivities;
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
            $query->where('id', (int) $request->id);
        }

        if ($request->filled('rider_id')) {
            $rider = Riders::where('rider_id', trim($request->rider_id))->first();
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

        if ($request->filled('billing_month_from')) {
            try {
                $from = Carbon::parse($request->billing_month_from)->startOfDay();
                $query->whereDate('date', '>=', $from);
            } catch (\Throwable $th) {
                Log::warning('Invalid billing_month_from supplied for rider activities filter', [
                    'value' => $request->billing_month_from,
                    'error' => $th->getMessage(),
                ]);
            }
        }

        if ($request->filled('billing_month_to')) {
            try {
                $to = Carbon::parse($request->billing_month_to)->endOfDay();
                $query->whereDate('date', '<=', $to);
            } catch (\Throwable $th) {
                Log::warning('Invalid billing_month_to supplied for rider activities filter', [
                    'value' => $request->billing_month_to,
                    'error' => $th->getMessage(),
                ]);
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
            $tableData = view('rider_activities.table', ['data' => $data])->render();
            $paginationLinks = method_exists($data, 'links')
                ? $data->links('components.global-pagination')->render()
                : '';

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        return view('rider_activities.index', compact('data', 'riders', 'fleetSupervisors', 'payoutTypes'));
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

                // Success popup
                session()->flash('success', 'Rider activities imported successfully.');
            } catch (\Throwable $th) {
                // Error popup (includes Rider ID not found, date, numeric errors)
                session()->flash('error', 'Import failed: ' . $th->getMessage());
            }

            return redirect()->route('rider.activities_import');
        }

        $summary = session('activities_import_summary');

        return view('rider_activities.import', compact('summary'));
    }


    /**
     * Display last Noon import errors.
     */
    public function importErrors()
    {
        $summary = session('activities_import_summary', []);
        $errors = $summary['errors'] ?? [];

        return view('rider_activities.import_errors', [
            'summary' => $summary,
            'errors' => $errors,
        ]);
    }
}
