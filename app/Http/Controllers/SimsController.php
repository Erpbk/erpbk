<?php

namespace App\Http\Controllers;

use App\DataTables\SimsDataTable;
use App\Http\Requests\CreateSimsRequest;
use App\Http\Requests\UpdateSimsRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\SimHistory;
use App\Repositories\SimsRepository;
use App\Models\Sims;
use App\Models\Riders;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Flash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SimExport;
use App\Models\User;
use App\Models\SimCompany;
use Illuminate\Support\Facades\Schema;

class SimsController extends AppBaseController
{
    use GlobalPagination, TracksCascadingDeletions;
    /** @var SimsRepository $simsRepository*/
    private $simsRepository;

    public function __construct(SimsRepository $simsRepo)
    {
        $this->simsRepository = $simsRepo;
    }

    /**
     * Display a listing of the Sims.
     */
    public function index(Request $request)
    {

        if (!auth()->user()->hasPermissionTo('sim_view')) {
            abort(403, 'Unauthorized action.');
        }
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = Sims::query()
            ->with('branch')
            ->orderBy('id', 'asc');
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->has('number') && !empty($request->number)) {
            $query->where('number', 'like', '%' . $request->number . '%');
        }
        if ($request->has('emi') && !empty($request->emi)) {
            $query->where('emi', 'like', '%' . $request->emi . '%');
        }
        if ($request->has('company') && !empty($request->company)) {
            $query->where('company', $request->company);
        }
        if ($request->has('status') && !empty($request->status)) {
            if ($request->status == 'active')
                $query->where('status', '1');
            else
                $query->where('status', '0');
        }

        $statsQuery = clone $query;

        // Calculate statistics
        $stats = [
            'total' => $statsQuery->count(),
            'active' => $statsQuery->clone()->where('status', '1')->count(),
            'inactive' => $statsQuery->clone()->where('status', '0')->count(),
            'du' => $statsQuery->clone()->whereIn('company', ['du', 'Du', 'DU'])->count(),
            'etisalat' => $statsQuery->clone()->whereIn('company', ['etisalat', 'Etisalat'])->count(),
        ];

        $tableColumns = $this->getTableColumns();

        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        if ($request->ajax()) {
            $tableData = view('sims.table', [
                'data' => $data,
                'stats' => $stats,
                'tableColumns' => $tableColumns,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'stats' => $stats,
            ]);
        }

        return view('sims.index', [
            'data' => $data,
            'stats' => $stats,
            'tableColumns' => $tableColumns,
        ]);
    }

    private function getTableColumns()
    {
        $computedColumns = [
            'rider_name' => 'Rider Name',
        ];

        // Get all columns from sims table
        $filteredColumns = \Illuminate\Support\Facades\Schema::getColumnListing('sims');

        // Columns to exclude
        $exclude = ['id', 'created_at', 'updated_at', 'deleted_by', 'deleted_at', 'fleet_supervisor', 'created_by', 'updated_by', 'company_id'];

        // Final filtered columns
        $dbColumns = array_diff($filteredColumns, $exclude);

        // Preferred order (can include both DB and computed columns)
        $preferredOrder = [
            'number',
            'branch_id',
            'company',
            'emi',
            'assign_to',
            'rider_name', // Computed column
            'vendor',
            'status',
        ];

        $columns = [];
        $added = [];
        $makeTitle = function ($key) use ($computedColumns) {
            return $computedColumns[$key] ?? ucwords(str_replace('_', ' ', $key));
        };

        // Process preferred order
        foreach ($preferredOrder as $key) {
            // Check if it's a valid column (either in DB or computed)
            if (in_array($key, $dbColumns) || array_key_exists($key, $computedColumns)) {
                if ($key == 'branch_id') {
                    $columns[] = ['data' => $key, 'title' => 'Branch'];
                } else {
                    $columns[] = ['data' => $key, 'title' => $makeTitle($key)];
                }
                $added[$key] = true;
            }
        }

        // Add remaining DB columns
        foreach ($dbColumns as $key) {
            if (!isset($added[$key])) {
                $columns[] = ['data' => $key, 'title' => $makeTitle($key)];
            }
        }

        // Add remaining computed columns not in preferred order
        foreach ($computedColumns as $key => $title) {
            if (!isset($added[$key])) {
                $columns[] = ['data' => $key, 'title' => $title];
            }
        }

        // Append fixed utility columns (must match frontend expectations)
        $columns = array_merge($columns, [
            ['data' => 'action', 'title' => 'Actions'],
        ]);

        return $columns;
    }

    /**
     * Show the form for creating a new Sims.
     */
    public function create()
    {
        return view('sims.create');
    }

    /**
     * Store a newly created Sims in storage.
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $input['created_by'] = auth()->id();
        $input['status'] = 0;

        // Validation rules
        $rules = [
            'number' => 'required|string|min:10|max:13|unique:sims,number',
            'emi' => 'required|min:15|max:25',
            'company' => 'nullable|string|max:191',
            'vendor' => 'nullable|integer',
            'fleet_supervisor' => 'nullable|string|max:50',
            'branch_id' => 'required|numeric|exists:branches,id',
        ];

        // Custom validation messages
        $messages = [
            'number.required' => 'SIM number is required',
            'number.min' => 'SIM number must be at least 10 characters long',
            'number.max' => 'SIM number cannot exceed 13 characters',
            'number.unique' => 'This SIM number already exists',
            'company.max' => 'Telecom company name cannot exceed 191 characters',
            'emi.required' => 'EMI number is required',
            'emi.min' => 'EMI number must be at least 15 characters',
            'emi.max' => 'EMI number cannot exceed 20 characters',
            'branch_id.required' => 'Please select Relevant Branch',
            'branch_id.exists' => 'Selected Branch Does Not Exist',
        ];

        $this->validate($request, $rules, $messages);

        $input['company'] = $this->resolveSimTelecomCompany($input);
        if ($input['company'] === '') {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'company' => [
                        'Telecom company (e.g. du / etisalat) is required. Send a "company" value, choose a vendor with a known name, or use a UAE mobile number so it can be inferred from the prefix.',
                    ],
                ],
            ], 422);
        }

        try {
            $sims = Sims::create($input);

            return response()->json([
                'message' => 'Sim added successfully.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating SIM: ' . $e->getMessage());
            return response()->json([
                'errors' => ['error' => 'Failed to create SIM. Please try again.'],
                'message' => 'Server error occurred.'
            ], 500);
        }
    }

    /**
     * Display the specified Sims.
     */
    public function show($company_slug, $id)
    {
        $sims = Sims::with('branch')->find($id);

        if (empty($sims)) {
            Flash::error('Sims not found');

            return redirect(route('sims.index'));
        }

        $simHistories = SimHistory::where('sim_id', $sims->id)->orderBy('created_at', 'desc')->get();

        return view('sims.show')->with('sims', $sims)->with('simHistories', $simHistories);
    }

    /**
     * Show the form for editing the specified Sims.
     */
    public function edit($company_slug, $id)
    {
        $sims = Sims::find($id);

        if (empty($sims)) {
            Flash::error('Sims not found');

            return redirect(route('sims.index'));
        }

        return view('sims.edit')->with('sims', $sims);
    }

    /**
     * Update the specified Sims in storage.
     */
    public function update($company_slug, $id, UpdateSimsRequest $request)
    {
        $sims = Sims::find($id);

        if (empty($sims)) {
            return response()->json(['errors' => ['error' => 'Sim not found!']], 422);
        }

        // Add updated_by from authenticated user
        $input = $request->all();
        $input['updated_by'] = auth()->id();

        // Define validation rules
        $rules = [
            'company' => 'required|string|max:191',
            'vendor' => 'nullable|integer',
            'fleet_supervisor' => 'nullable|string|max:50',
            'emi' => 'required|min:15|max:25',
            'branch_id' => 'required|numeric|exists:branches,id',
        ];

        // Custom validation messages
        $messages = [
            'company.required' => 'Company name is required',
            'company.max' => 'Company name cannot exceed 191 characters',
            'emi.required' => 'EMI number is required',
            'emi.min' => 'EMI number must be at least 15 characters',
            'emi.max' => 'EMI number cannot exceed 25 characters',
            'branch_id.required' => 'Please Select Relevant Branch',
            'branch_id.exists' => 'Selected Branch Does not Exist',
        ];

        // Perform validation
        $this->validate($request, $rules, $messages);

        try {
            $sims->update($input);
            return response()->json([
                'message' => 'Sim updated successfully.',
                'data' => $sims
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating SIM: ' . $e->getMessage());

            return response()->json([
                'errors' => ['error' => 'Failed to update SIM. Please try again.'],
                'message' => 'Server error occurred.'
            ], 500);
        }
    }

    /**
     * Remove the specified Sims from storage.
     *
     * @throws \Exception
     */

    public function assign(Request $request, $company_slug, $id)
    {
        $sims = Sims::find($id);
        if (empty($sims)) {
            return response()->json(['errors' => ['error' => 'Sim not found!']], 422);
        }

        if ($request->isMethod('post')) {
            $input = $request->all();
            $rules = [
                'assign_to' => [
                    'required',
                    'exists:riders,id',
                    'unique:sims,assign_to',
                    function ($attribute, $value, $fail) {
                        //Check rider status
                        $rider = Riders::find($value);
                        if ($rider && !($rider->status == 1)) {
                            $fail('Rider is not active. Cannot assign SIM.');
                        }
                    }
                ],
                'note_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                    function ($attribute, $value, $fail) use ($sims) {
                        // Check if assign date is after last return date
                        $lastHistory = $sims->histories()->orderBy('created_at', 'desc')->first();
                        if ($lastHistory && $lastHistory->return_date) {
                            $assignDate = \Carbon\Carbon::parse($value)->startOfDay();
                            $returnDate = \Carbon\Carbon::parse($lastHistory->return_date)->startOfDay();
                            if ($assignDate->lt($returnDate)) { // Changed to lt() (less than, not equal)
                                $fail('Assign date cannot be before the last return date: ' . $lastHistory->return_date);
                            }
                        }
                    }
                ]
            ];

            $messages = [
                'assign_to.required' => 'Please select a rider to assign the SIM.',
                'assign_to.exists' => 'The selected rider does not exist.',
                'assign_to.unique' => 'This rider already has a SIM assigned.',
                'note_date.required' => 'Assign date is required.',
                'note_date.date' => 'Assign date must be a valid date.',
                'note_date.before_or_equal' => 'Assign date cannot be in the future.',
            ];
            $this->validate($request, $rules, $messages);

            try {
                $rider = Riders::find($input['assign_to']);
                if (!$rider) {
                    return response()->json([
                        'errors' => ['assign_to' => ['Selected rider not found.']],
                        'message' => 'Validation failed.'
                    ], 422);
                }
                $input['status'] = 1; // Set SIM status to active upon assignment
                $input['branch_id'] = $rider->branch_id;
                $sims->update($input);

                // Create a new history record for this assignment
                $sims->histories()->create([
                    'note_date' => $input['note_date'],
                    'assigned_by' => auth()->id(),
                    'notes' => $input['notes'] ?? '',
                    'rider_id' => $input['assign_to'],
                ]);

                if (Schema::hasColumn('riders', 'company_contact')) {
                    Riders::where('id', $input['assign_to'])->update(['company_contact' => $sims->number]);
                }
            } catch (\Exception $e) {
                \Log::error('Error assigning SIM: ' . $e->getMessage(), [
                    'sim_id' => $sims->id,
                    'assign_to' => $input['assign_to'] ?? null,
                ]);
                return response()->json([
                    'errors' => ['error' => 'Failed to assign SIM. Please try again.'],
                    'message' => 'Server error occurred.'
                ], 500);
            }

            return response()->json([
                'message' => 'Sim assignment updated successfully.',
            ]);
        }

        return view('sims.assign')->with('sims', $sims);
    }

    public function return(Request $request, $company_slug, $id)
    {
        $sims = Sims::find($id);
        $rider = Riders::find($sims->assign_to);
        $rider_name = $rider ? $rider->rider_id . "-" . $rider->name : 'N/A';

        if (empty($sims)) {
            return response()->json(['errors' => ['error' => 'Sim not found!']], 422);
        }

        if ($request->isMethod('get')) {
            return view('sims.return')->with('sims', $sims)->with('rider_name', $rider_name);
        }



        $rules = [
            'return_date' => [
                'required',
                'date',
                'before_or_equal:today',
                function ($attribute, $value, $fail) use ($sims) {
                    // Check if return date is after last assigned date
                    $lastHistory = $sims->histories()->orderBy('created_at', 'desc')->first();
                    if ($lastHistory && $value < $lastHistory->note_date) {
                        $fail('Return date cannot be before the last assigned date: ' . $lastHistory->note_date);
                    }
                }
            ]
        ];
        $messages = [
            'return_date.required' => 'Return date is required',
            'return_date.date' => 'Return date must be a valid date',
            'return_date.before_or_equal' => 'Return date cannot be in the future',
        ];
        $this->validate($request, $rules, $messages);

        try {

            $input = [];
            $input['assign_to'] = null;
            $input['status'] = 0; // Set status to inactive
            $sims->update($input);

            // Clear company_contact of the rider who had this SIM (only if column exists).
            if ($rider && Schema::hasColumn('riders', 'company_contact')) {
                Riders::where('id', $rider->id)->update(['company_contact' => null]);
            }

            $history = $sims->histories()->orderBy('created_at', 'desc')->first();
            if ($history) {
                $history->update([
                    'return_date' => $request->return_date,
                    'returned_by' => auth()->id(),
                    'notes' => $request->notes ?? '',
                ]);
            }

            return response()->json([
                'message' => 'Sim returned successfully.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error returning SIM: ' . $e->getMessage());
            return response()->json([
                'errors' => ['error' => 'Failed to return SIM. Please try again.'],
                'message' => 'Server error occurred.'
            ], 500);
        }
    }

    public function destroy($company_slug, $id)
    {
        // Find including soft deleted
        //$sims = Sims::withTrashed()->find($id);

        $sims = Sims::find($id);

        if (empty($sims)) {
            return $this->respondSimDeleteError('Sim not found!');
        }

        // Prevent deletion if SIM is assigned to a rider
        if (!is_null($sims->assign_to)) {
            return $this->respondSimDeleteError('Cannot delete SIM because it is currently assigned to a rider. Please return the SIM before deleting.');
        }

        // Prevent deletion if SIM has any history
        $historyCount = $sims->histories()->count();
        if ($historyCount > 0) {
            return $this->respondSimDeleteError('Cannot delete SIM because it has usage history. Please keep the record or clear history before deleting.');
        }

        if ($sims->status == 1) {
            return $this->respondSimDeleteError('Active SIMs cannot be deleted. Please return the SIM before deleting.');
        }

        DB::beginTransaction();
        try {
            $sims->delete(); // Soft delete

            // Log deletion to cascade table for audit (self-reference to capture the deletion event)
            $this->trackCascadeDeletion(
                \App\Models\Sims::class,
                $sims->id,
                $sims->number,
                \App\Models\Sims::class,
                $sims->id,
                $sims->number,
                'self',
                null,
                'soft'
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting SIM: ' . $e->getMessage(), ['sim_id' => $sims->id ?? $id]);
            return $this->respondSimDeleteError('Failed to delete SIM. Please try again.');
        }

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sim moved to Recycle Bin.'
            ]);
        }

        // For regular requests
        return redirect()->back()->with('message', 'Sim moved to Recycle Bin.');
    }

    public function export()
    {
        $filename = 'sims_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new SimExport, $filename);
    }

    public function import(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('sims.import');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        try {
            $import = new \App\Imports\SimImport();
            $file = $request->file('file');
            Excel::import($import, $file);
            $results = $import->getResults();
            $importedCount = $results['stats']['imported'];
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'results' => $results,
                    'message' => 'Sim data imported successfully.',
                    'redirect' => route('sims.index')
                ]);
            }
            Flash::success("Sims imported successfully. Records imported: {$importedCount}");
            return redirect()->back();
        } catch (\Exception $e) {
            \Log::error('Error importing SIM data: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'errors' => ['error' => 'Failed to import SIM data. Please check the file and try again.'],
                'message' => 'Server error occurred.' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * `sims.company` stores the telecom operator (du / etisalat), not the business `company_id`.
     */
    private function resolveSimTelecomCompany(array $input): string
    {
        $fromRequest = trim((string) ($input['company'] ?? ''));
        if ($fromRequest !== '') {
            return $fromRequest;
        }

        if (!empty($input['vendor'])) {
            $name = SimCompany::where('id', $input['vendor'])->value('name');
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        $digits = preg_replace('/\D/', '', (string) ($input['number'] ?? ''));
        if (strlen($digits) >= 3) {
            $prefix = substr($digits, 0, 3);
            if (in_array($prefix, ['052', '055'], true)) {
                return 'du';
            }
            if (in_array($prefix, ['050', '054', '056', '058'], true)) {
                return 'etisalat';
            }
        }

        return '';
    }

    /**
     * Standardized response for SIM deletion errors (supports both AJAX and regular requests).
     */
    private function respondSimDeleteError(string $message)
    {
        if (request()->ajax()) {
            return response()->json(['errors' => ['error' => $message]], 422);
        }

        return redirect()->back()->with('error', $message);
    }
}
