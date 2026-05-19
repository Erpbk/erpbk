<?php

namespace App\Http\Controllers;

use App\DataTables\SimsDataTable;
use App\Http\Requests\CreateSimsRequest;
use App\Http\Requests\UpdateSimsRequest;
use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Concerns\AppliesModuleTopBarFilters;
use App\Models\SimHistory;
use App\Repositories\SimsRepository;
use App\Models\Sims;
use App\Models\Riders;
use App\Models\Employee;
use App\Services\EmployeeHistoryLogger;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Flash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SimExport;
use App\Models\User;
use App\Models\SimCompany;
use App\Services\RiderHistoryLogger;
use App\Support\ModuleFieldSettings;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SimsController extends AppBaseController
{
    use AppliesModuleTopBarFilters, GlobalPagination, TracksCascadingDeletions;
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
            ->with(['branch', 'riders', 'employee'])
            ->orderBy('id', 'asc');
        $this->applyModuleTopBarFilters($query, $request, 'sims');
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

        $this->validate($request, $this->simValidationRules(), $this->simValidationMessages());

        $input['company'] = $this->resolveSimTelecomCompany($input);
        if ($input['company'] === '' && ModuleFieldSettings::isSchemaFieldRequired('sims', 'company')) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'company' => [
                        'Telecom company (e.g. du / etisalat) is required. Send a "company" value, choose a vendor with a known name, or use a UAE mobile number so it can be inferred from the prefix.',
                    ],
                ],
            ], 422);
        }
        if ($input['company'] === '') {
            unset($input['company']);
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

        $this->validate(
            $request,
            $this->simValidationRules((int) $sims->id, false),
            $this->simValidationMessages()
        );

        $input['company'] = $this->resolveSimTelecomCompany(array_merge(
            $sims->only(['number', 'company', 'vendor']),
            $input
        ));
        if ($input['company'] === '' && ModuleFieldSettings::isSchemaFieldRequired('sims', 'company')) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'company' => [
                        'Telecom company (e.g. du / etisalat) is required. Choose a vendor with a known name or use a UAE mobile number so it can be inferred from the prefix.',
                    ],
                ],
            ], 422);
        }
        if ($input['company'] === '') {
            unset($input['company']);
        }

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

        $branchId = $sims->branch_id ? (int) $sims->branch_id : null;

        if ($request->isMethod('post')) {
            $assigneeType = $request->input('assignee_type', 'rider');
            $rules = [
                'assignee_type' => 'required|in:rider,employee',
                'assign_to' => [
                    'required',
                    'integer',
                    function ($attribute, $value, $fail) use ($assigneeType, $sims, $branchId) {
                        if ($assigneeType === 'employee') {
                            $employee = Employee::find($value);
                            if (!$employee) {
                                $fail('The selected employee does not exist.');
                                return;
                            }
                            if ($employee->status !== 'active') {
                                $fail('Employee is not active. Cannot assign SIM.');
                                return;
                            }
                            if ($branchId && (int) $employee->branch_id !== $branchId) {
                                $fail('Employee must belong to the same branch as this SIM.');
                                return;
                            }
                        } else {
                            $rider = Riders::find($value);
                            if (!$rider) {
                                $fail('The selected rider does not exist.');
                                return;
                            }
                            if ((int) $rider->status !== 1) {
                                $fail('Rider is not active. Cannot assign SIM.');
                                return;
                            }
                            if ($branchId && (int) $rider->branch_id !== $branchId) {
                                $fail('Rider must belong to the same branch as this SIM.');
                                return;
                            }
                        }

                        $taken = Sims::query()
                            ->where('assign_to', $value)
                            ->where('assign_type', $assigneeType)
                            ->where('id', '!=', $sims->id)
                            ->exists();
                        if ($taken) {
                            $fail($assigneeType === 'employee'
                                ? 'This employee already has a SIM assigned.'
                                : 'This rider already has a SIM assigned.');
                        }
                    },
                ],
                'note_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                    function ($attribute, $value, $fail) use ($sims) {
                        $lastHistory = $sims->histories()->orderBy('created_at', 'desc')->first();
                        if ($lastHistory && $lastHistory->return_date) {
                            $assignDate = \Carbon\Carbon::parse($value)->startOfDay();
                            $returnDate = \Carbon\Carbon::parse($lastHistory->return_date)->startOfDay();
                            if ($assignDate->lt($returnDate)) {
                                $fail('Assign date cannot be before the last return date: ' . $lastHistory->return_date);
                            }
                        }
                    },
                ],
            ];

            $messages = [
                'assignee_type.required' => 'Please select user type (Rider or Employee).',
                'assign_to.required' => 'Please select who to assign the SIM to.',
                'note_date.required' => 'Assign date is required.',
                'note_date.date' => 'Assign date must be a valid date.',
                'note_date.before_or_equal' => 'Assign date cannot be in the future.',
            ];
            $this->validate($request, $rules, $messages);

            try {
                $assignTo = (int) $request->assign_to;
                $assignBranchId = $branchId;

                if ($assigneeType === 'employee') {
                    $employee = Employee::findOrFail($assignTo);
                    $assignBranchId = $employee->branch_id ?? $branchId;

                    $sims->update([
                        'assign_to' => $assignTo,
                        'assign_type' => 'employee',
                        'status' => 1,
                        'branch_id' => $assignBranchId,
                    ]);

                    $sims->histories()->create([
                        'note_date' => $request->note_date,
                        'assigned_by' => auth()->id(),
                        'notes' => $request->notes ?? '',
                        'employee_id' => $assignTo,
                        'rider_id' => null,
                    ]);

                    if (Schema::hasColumn('employees', 'company_contact')) {
                        Employee::where('id', $assignTo)->update(['company_contact' => $sims->number]);
                    }

                    EmployeeHistoryLogger::simAssigned(
                        $employee,
                        $sims->fresh(),
                        $request->note_date ?? null,
                        $request->notes ?? null
                    );
                } else {
                    $rider = Riders::findOrFail($assignTo);
                    $assignBranchId = $rider->branch_id ?? $branchId;

                    $sims->update([
                        'assign_to' => $assignTo,
                        'assign_type' => 'rider',
                        'status' => 1,
                        'branch_id' => $assignBranchId,
                    ]);

                    $sims->histories()->create([
                        'note_date' => $request->note_date,
                        'assigned_by' => auth()->id(),
                        'notes' => $request->notes ?? '',
                        'rider_id' => $assignTo,
                        'employee_id' => null,
                    ]);

                    if (Schema::hasColumn('riders', 'company_contact')) {
                        Riders::where('id', $assignTo)->update(['company_contact' => $sims->number]);
                    }

                    RiderHistoryLogger::simAssigned(
                        $rider,
                        $sims->fresh(),
                        $request->note_date ?? null,
                        $request->notes ?? null
                    );
                }
            } catch (\Exception $e) {
                \Log::error('Error assigning SIM: ' . $e->getMessage(), [
                    'sim_id' => $sims->id,
                    'assign_to' => $request->assign_to ?? null,
                    'assignee_type' => $assigneeType ?? null,
                ]);
                return response()->json([
                    'errors' => ['error' => 'Failed to assign SIM. Please try again.'],
                    'message' => 'Server error occurred.',
                ], 500);
            }

            return response()->json([
                'message' => 'Sim assignment updated successfully.',
            ]);
        }

        $branchScopedOptions = [
            'assign_to_rider' => Riders::dropdownForBranch($branchId),
            'assign_to_employee' => Employee::dropdownForBranch($branchId),
        ];

        return view('sims.assign', [
            'sims' => $sims,
            'riders' => $branchScopedOptions['assign_to_rider'],
            'employees' => $branchScopedOptions['assign_to_employee'],
            'branchScopedOptions' => $branchScopedOptions,
            'assignFields' => \App\Support\SimAssignFields::assignModalFields('assign'),
            'simBranchName' => $sims->branch?->name,
        ]);
    }

    public function return(Request $request, $company_slug, $id)
    {
        $sims = Sims::with(['riders', 'employee'])->find($id);
        $assigneeName = $this->simAssigneeDisplayName($sims);

        if (empty($sims)) {
            return response()->json(['errors' => ['error' => 'Sim not found!']], 422);
        }

        if ($request->isMethod('get')) {
            return view('sims.return', [
                'sims' => $sims,
                'assignee_name' => $assigneeName,
                'assignFields' => \App\Support\SimAssignFields::assignModalFields('return'),
            ]);
        }

        $rider = $sims->assign_type === 'employee' ? null : Riders::find($sims->assign_to);
        $employee = $sims->assign_type === 'employee' ? Employee::find($sims->assign_to) : null;



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

            $sims->update([
                'assign_to' => null,
                'assign_type' => null,
                'status' => 0,
            ]);

            if ($rider && Schema::hasColumn('riders', 'company_contact')) {
                Riders::where('id', $rider->id)->update(['company_contact' => null]);
            }
            if ($employee && Schema::hasColumn('employees', 'company_contact')) {
                Employee::where('id', $employee->id)->update(['company_contact' => null]);
            }

            $history = $sims->histories()->orderBy('created_at', 'desc')->first();
            if ($history) {
                $history->update([
                    'return_date' => $request->return_date,
                    'returned_by' => auth()->id(),
                    'notes' => $request->notes ?? '',
                ]);
            }

            if ($rider) {
                RiderHistoryLogger::simReturned(
                    $rider,
                    $sims,
                    $request->return_date ?? null,
                    $request->notes ?? null
                );
            }
            if ($employee) {
                EmployeeHistoryLogger::simReturned(
                    $employee,
                    $sims,
                    $request->return_date ?? null,
                    $request->notes ?? null
                );
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
            return $this->respondSimDeleteError('Cannot delete SIM because it is currently assigned. Please return the SIM before deleting.');
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
    /**
     * Validation rules from SIM field settings (module_key: sims).
     */
    private function simValidationRules(?int $ignoreSimId = null, bool $includeNumber = true): array
    {
        $baseRules = [
            'emi' => 'nullable|string|min:15|max:25',
            'company' => 'nullable|string|max:191',
            'vendor' => 'nullable|integer',
            'fleet_supervisor' => 'nullable|string|max:50',
            'branch_id' => 'nullable|numeric|exists:branches,id',
        ];

        if ($includeNumber) {
            $numberRule = ['nullable', 'string', 'min:10', 'max:13'];
            if ($ignoreSimId !== null) {
                $numberRule[] = Rule::unique('sims', 'number')->ignore($ignoreSimId);
            } else {
                $numberRule[] = Rule::unique('sims', 'number');
            }
            $baseRules['number'] = $numberRule;
        }

        $fieldFilter = $includeNumber
            ? null
            : ['company', 'vendor', 'fleet_supervisor', 'emi', 'branch_id'];

        return ModuleFieldSettings::validationRulesForModule('sims', $baseRules, [
            'fields' => $fieldFilter,
        ]);
    }

    private function simValidationMessages(): array
    {
        return [
            'number.required' => 'SIM number is required',
            'number.min' => 'SIM number must be at least 10 characters long',
            'number.max' => 'SIM number cannot exceed 13 characters',
            'number.unique' => 'This SIM number already exists',
            'company.required' => 'Telecom company is required',
            'company.max' => 'Telecom company name cannot exceed 191 characters',
            'emi.required' => 'EMI number is required',
            'emi.min' => 'EMI number must be at least 15 characters',
            'emi.max' => 'EMI number cannot exceed 25 characters',
            'branch_id.required' => 'Please select relevant branch',
            'branch_id.exists' => 'Selected branch does not exist',
            'vendor.required' => 'Vendor is required',
            'fleet_supervisor.required' => 'Fleet supervisor is required',
        ];
    }

    private function simAssigneeDisplayName(?Sims $sims): string
    {
        if (!$sims || !$sims->assign_to) {
            return 'N/A';
        }

        if ($sims->assign_type === 'employee') {
            $employee = $sims->employee ?? Employee::find($sims->assign_to);

            return $employee
                ? trim($employee->employee_id . '-' . $employee->name)
                : 'N/A';
        }

        $rider = $sims->riders ?? Riders::find($sims->assign_to);

        return $rider ? trim($rider->rider_id . '-' . $rider->name) : 'N/A';
    }

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
