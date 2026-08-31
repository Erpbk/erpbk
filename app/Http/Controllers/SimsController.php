<?php

namespace App\Http\Controllers;

use App\DataTables\SimsDataTable;
use App\Http\Requests\CreateSimsRequest;
use App\Http\Requests\UpdateSimsRequest;
use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Concerns\AppliesModuleTopBarFilters;
use App\Models\SimInvoiceItem;
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
use App\Support\TopBarNumericStatus;

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

        if (!user_can('sim_view')) {
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

        $statsQuery = clone $query;

        $companyCounts = (clone $statsQuery)
            ->reorder()
            ->toBase()
            ->select('company', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('company')
            ->pluck('aggregate', 'company');

        $companyStats = SimCompany::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (SimCompany $company) use ($companyCounts) {
                $byId = (int) ($companyCounts[$company->id] ?? $companyCounts[(string) $company->id] ?? 0);
                $byName = (int) ($companyCounts[$company->name] ?? 0);

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'count' => $byId + $byName,
                ];
            })
            ->all();

        $userAbscondedCount = (int) (clone $statsQuery)
            ->whereAssigneeAbsconded()
            ->reorder()
            ->toBase()
            ->selectRaw("COUNT(DISTINCT CONCAT(IFNULL(assign_type, 'rider'), '-', assign_to)) as aggregate")
            ->value('aggregate');

        $stats = [
            'total' => $statsQuery->count(),
            'active' => $statsQuery->clone()->where('status', (string) Sims::STATUS_ASSIGNED)->count(),
            'deactivated' => $statsQuery->clone()->where('status', (string) Sims::STATUS_DEACTIVATED)->count(),
            'in_office' => $statsQuery->clone()->where('status', (string) Sims::STATUS_IN_OFFICE)->count(),
            'user_absconded' => $userAbscondedCount,
            'companies' => $companyStats,
        ];

        if ($request->filled('company')) {
            $this->applySimCompanyFilter($query, $request->input('company'));
        }

        $simListKeys = TopBarNumericStatus::normalizeStatusKeys($request->input('list_status'));
        if ($simListKeys !== []) {
            $query->where(function ($q) use ($simListKeys) {
                if (in_array(TopBarNumericStatus::ACTIVE_KEY, $simListKeys, true)) {
                    $q->orWhere('status', (string) Sims::STATUS_ASSIGNED);
                }
                if (in_array(TopBarNumericStatus::INACTIVE_KEY, $simListKeys, true)) {
                    $q->orWhere('status', (string) Sims::STATUS_DEACTIVATED);
                }
            });
        } elseif ($request->filled('status')) {
            $statusFilter = strtolower((string) $request->status);
            if (in_array($statusFilter, ['active', 'assigned'], true)) {
                $query->where('status', (string) Sims::STATUS_ASSIGNED);
            } elseif (in_array($statusFilter, ['in_office', 'in-office', 'office'], true)) {
                $query->where('status', (string) Sims::STATUS_IN_OFFICE);
            } elseif (in_array($statusFilter, ['deactivated', 'inactive'], true)) {
                $query->where('status', (string) Sims::STATUS_DEACTIVATED);
            } elseif (in_array($statusFilter, ['user_absconded', 'absconded'], true)) {
                $query->whereAssigneeAbsconded();
            }
        }

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

    private function applySimCompanyFilter($query, mixed $company): void
    {
        $company = is_scalar($company) ? trim((string) $company) : '';
        if ($company === '') {
            return;
        }

        $companyModel = ctype_digit($company)
            ? SimCompany::find((int) $company)
            : SimCompany::query()->where('name', $company)->first();

        if ($companyModel) {
            $query->where(function ($q) use ($companyModel) {
                $q->where('company', $companyModel->id)
                    ->orWhere('company', (string) $companyModel->id)
                    ->orWhere('company', $companyModel->name);
            });

            return;
        }

        $query->where('company', $company);
    }

    private function getTableColumns()
    {
        $computedColumns = [
            'rider_name' => 'Name',
        ];

        // Get all columns from sims table
        $filteredColumns = \Illuminate\Support\Facades\Schema::getColumnListing('sims');

        // Columns to exclude
        $exclude = ['id', 'created_at', 'updated_at', 'deleted_by', 'deleted_at', 'fleet_supervisor', 'created_by', 'updated_by', 'company_id', 'branch_id','assign_type'];

        // Final filtered columns
        $dbColumns = array_diff($filteredColumns, $exclude);

        // Preferred order (can include both DB and computed columns)
        $preferredOrder = [
            'number',
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

        // Drop columns the current user may not view so the Column Control panel
        // stays index-aligned with the (also permission-gated) table body.
        return \App\Support\RoleFieldAccess::filterTableColumns($columns, 'sim');
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
        $input['status'] = Sims::STATUS_IN_OFFICE;

        $this->validate($request, $this->simValidationRules(), $this->simValidationMessages());

        try {
            $sims = Sims::create($input);

            return response()->json([
                'message' => 'Sim added successfully.',
                'reload' => true,
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
        $sims = Sims::with([
            'branch',
            'telecomCompany',
            'vendors',
            'riders',
            'employee',
            'createdBy',
            'updatedBy',
        ])->find($id);

        if (empty($sims)) {
            Flash::error('Sims not found');

            return redirect(route('sims.index'));
        }

        $histories = $sims->histories()
            ->with(['rider', 'employee', 'assignedBy.roles', 'returnedBy.roles'])
            ->orderByDesc('note_date')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString()
            ->appends(['tab' => 'assignments']);

        $invoiceItems = SimInvoiceItem::query()
            ->with(['invoice.company'])
            ->where('sim_id', $sims->id)
            ->whereHas('invoice')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'invoice_page')
            ->withQueryString()
            ->appends(['tab' => 'invoices']);

        $invoiceTotals = SimInvoiceItem::query()
            ->where('sim_id', $sims->id)
            ->whereHas('invoice')
            ->selectRaw('COUNT(*) as bills, COALESCE(SUM(rental_amount), 0) as rental, COALESCE(SUM(total_amount), 0) as total')
            ->first();

        return view('sims.show', compact('sims', 'histories', 'invoiceItems', 'invoiceTotals'));
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

        try {
            $sims->update($input);
            return response()->json([
                'message' => 'Sim updated successfully.',
                'reload' => true,
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

        // Deactivated SIMs are out of service until an admin activates them.
        if ($sims->isDeactivated()) {
            return response()->json([
                'errors' => ['error' => 'This SIM is deactivated and cannot be assigned. Activate it first.'],
            ], 422);
        }

        $branchId = $sims->branch_id ? (int) $sims->branch_id : null;
        $assignTargets = \App\Support\CompanyModuleVisibility::simAssignTargets();
        $allowTypeSelection = count($assignTargets) >= 2;
        $defaultAssigneeType = count($assignTargets) === 1 ? $assignTargets[0] : 'rider';
        $assignFormLocked = $assignTargets === [];

        if ($request->isMethod('post')) {
            if ($assignFormLocked) {
                return response()->json([
                    'errors' => ['error' => 'No assignable user module is enabled for this company.'],
                ], 422);
            }

            $assigneeType = count($assignTargets) === 1
                ? $assignTargets[0]
                : $request->input('assignee_type', $defaultAssigneeType);
            $request->merge(['assignee_type' => $assigneeType]);
            $rules = [
                'assignee_type' => 'required|in:' . implode(',', $assignTargets),
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
                        } else {
                            $rider = Riders::find($value);
                            if (!$rider) {
                                $fail('The selected rider does not exist.');
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
                'assignee_type.required' => 'Please select user type.',
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
                        'status' => Sims::STATUS_ASSIGNED,
                        'branch_id' => $assignBranchId,
                    ]);

                    $sims->histories()->create([
                        'note_date' => $request->note_date,
                        'assigned_by' => auth()->id(),
                        'notes' => $request->notes ?? '',
                        'employee_id' => $assignTo,
                        'rider_id' => null,
                    ]);

                    \App\Support\SimAssigneeContactSync::sync($employee, $sims->number);

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
                        'status' => Sims::STATUS_ASSIGNED,
                        'branch_id' => $assignBranchId,
                    ]);

                    $sims->histories()->create([
                        'note_date' => $request->note_date,
                        'assigned_by' => auth()->id(),
                        'notes' => $request->notes ?? '',
                        'rider_id' => $assignTo,
                        'employee_id' => null,
                    ]);

                    \App\Support\SimAssigneeContactSync::sync($rider, $sims->number);

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
                'reload' => true,
            ]);
        }

        $branchScopedOptions = [
            'assign_to_rider' => in_array('rider', $assignTargets, true)
                ? Riders::dropdownForSimAssign()
                : ['' => 'Select'],
            'assign_to_employee' => in_array('employee', $assignTargets, true)
                ? Employee::dropdownForSimAssign()
                : ['' => 'Select'],
        ];

        return view('sims.assign', [
            'sims' => $sims,
            'riders' => $branchScopedOptions['assign_to_rider'],
            'employees' => $branchScopedOptions['assign_to_employee'],
            'branchScopedOptions' => $branchScopedOptions,
            'assignFields' => \App\Support\SimAssignFields::assignModalFields('assign'),
            'assignTargets' => $assignTargets,
            'allowTypeSelection' => $allowTypeSelection,
            'defaultAssigneeType' => $defaultAssigneeType,
            'assignFormLocked' => $assignFormLocked,
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
                    $returnDate = \Carbon\Carbon::parse($value)->startOfDay();
                    if ($lastHistory && $returnDate < $lastHistory->note_date) {
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
                'status' => Sims::STATUS_IN_OFFICE,
            ]);

            if ($rider) {
                \App\Support\SimAssigneeContactSync::clear($rider);
            }
            if ($employee) {
                \App\Support\SimAssigneeContactSync::clear($employee);
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
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error returning SIM: ' . $e->getMessage());
            return response()->json([
                'errors' => ['error' => 'Failed to return SIM. Please try again.'],
                'message' => 'Server error occurred.'
            ], 500);
        }
    }

    /**
     * Bulk activate / deactivate SIMs. Deactivating takes an in-office SIM out
     * of service; activating returns it to the office so it can be assigned.
     */
    public function activateDeactivate(Request $request)
    {
        if (!user_can('sims_sim_edit')) {
            if ($request->isMethod('get')) {
                abort(403, 'Unauthorized action.');
            }

            return response()->json([
                'errors' => ['error' => 'You do not have permission to change SIM status.'],
            ], 403);
        }

        if ($request->isMethod('get')) {
            return view('sims.activate_deactivate', [
                'inOfficeSims' => $this->simStatusPickList(Sims::STATUS_IN_OFFICE),
                'deactivatedSims' => $this->simStatusPickList(Sims::STATUS_DEACTIVATED),
            ]);
        }

        $data = $this->validate($request, [
            'mode' => 'required|in:activate,deactivate',
            'sim_ids' => 'required|array|min:1',
            'sim_ids.*' => 'integer|exists:sims,id',
        ], [
            'mode.required' => 'Please choose whether to activate or deactivate.',
            'sim_ids.required' => 'Please select at least one SIM.',
            'sim_ids.min' => 'Please select at least one SIM.',
        ]);

        $deactivating = $data['mode'] === 'deactivate';
        $from = $deactivating ? Sims::STATUS_IN_OFFICE : Sims::STATUS_DEACTIVATED;
        $to = $deactivating ? Sims::STATUS_DEACTIVATED : Sims::STATUS_IN_OFFICE;

        $updated = Sims::query()
            ->whereIn('id', array_map('intval', $data['sim_ids']))
            ->where('status', $from)
            ->whereNull('assign_to')
            ->update([
                'status' => $to,
                'updated_by' => auth()->id(),
            ]);

        if ($updated === 0) {
            return response()->json([
                'errors' => ['error' => $deactivating
                    ? 'No in-office SIMs were updated. Select SIMs that are currently in office.'
                    : 'No deactivated SIMs were updated. Select SIMs that are currently deactivated.'],
            ], 422);
        }

        $noun = $updated === 1 ? 'SIM' : 'SIMs';

        return response()->json([
            'message' => $deactivating
                ? "{$updated} {$noun} deactivated."
                : "{$updated} {$noun} activated and returned to office.",
            'reload' => true,
        ]);
    }

    /**
     * Unassigned SIMs in a given status, shaped for the shared picker partial.
     *
     * @return list<array{id: int, primary: string, secondary: string}>
     */
    private function simStatusPickList(int $status): array
    {
        return Sims::query()
            ->with('telecomCompany')
            ->where('status', $status)
            ->whereNull('assign_to')
            ->orderBy('number')
            ->get()
            ->map(fn (Sims $sim) => [
                'id' => (int) $sim->id,
                'primary' => (string) $sim->number,
                'secondary' => (string) ($sim->telecomCompany?->name ?? ''),
            ])
            ->all();
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

        if ((int) $sims->status === Sims::STATUS_ASSIGNED) {
            return $this->respondSimDeleteError('Assigned SIMs cannot be deleted. Please return the SIM before deleting.');
        }

        DB::beginTransaction();
        try {
            $sims->delete(); // Soft delete — may only queue a delete request
            $queued = (bool) request()->attributes->get('delete_approval_created');

            // Do not write cascade audit rows until the SIM is actually in the bin.
            if (! $queued) {
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
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting SIM: ' . $e->getMessage(), ['sim_id' => $sims->id ?? $id]);
            return $this->respondSimDeleteError('Failed to delete SIM. Please try again.');
        }

        $message = delete_outcome_message(
            'Sim',
            route('settings-panel.trash.index') . '?module=sims'
        );

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'queued' => (bool) request()->attributes->get('delete_approval_created'),
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('message', $message);
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
            'file' => 'required|file|max:10240',
        ]);

        $uploadedFile = $request->file('file');
        $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'The file field must be a file of type: xlsx, xls, csv.',
                'errors' => [
                    'file' => ['The file field must be a file of type: xlsx, xls, csv.'],
                ],
            ], 422);
        }

        try {
            $import = new \App\Imports\SimImport();
            Excel::import($import, $uploadedFile);

            $results = $import->getResults();
            $stats = $results['stats'] ?? [];
            $failedRaw = $results['failed'] ?? [];

            // Map importer output into FuelData-style payload keys.
            $failedRows = collect($failedRaw)->map(function ($row) {
                return [
                    'row_number' => $row['excel_row'] ?? ($row['row_number'] ?? null),
                    'number' => $row['number'] ?? null,
                    'company' => $row['company'] ?? null,
                    'emi' => $row['emi'] ?? null,
                    'vendor' => $row['vendor'] ?? null,
                    'reason' => $row['reason'] ?? 'Unknown error',
                    'details' => $row['details'] ?? ($row['exception'] ?? '-'),
                ];
            })->values()->all();

            $result = [
                'success_count' => $stats['imported'] ?? 0,
                'failed_count' => count($failedRows),
                'total_rows' => $stats['total'] ?? 0,
                'failed_rows' => $failedRows,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error importing SIM data: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to import SIM data. ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $headers = [
            'Number',
            'Company',
            'EMI',
            'Vendor',
        ];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            // Sample data
            fputcsv($file, [
                '0554563213',
                'du,etisalat,etc',
                'Empty or 7001055670',
                'Empty or (Keeta, Noon, Careem, etc)',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sim_import_template.csv"'
        ]);
    }
    /**
     * Validation rules from SIM field settings (module_key: sims).
     */
    private function simValidationRules(?int $ignoreSimId = null, bool $includeNumber = true): array
    {
        $baseRules = [
            'emi' => 'nullable|string|min:15|max:25',
            'company' => 'nullable|exists:sim_companies,id',
            'vendor' => 'nullable|exists:customers,id',
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
            'company.exists' => 'Telecom company does not exist',
            'emi.required' => 'EMI number is required',
            'emi.min' => 'EMI number must be at least 15 characters',
            'emi.max' => 'EMI number cannot exceed 25 characters',
            'branch_id.required' => 'Please select relevant branch',
            'branch_id.exists' => 'Selected branch does not exist',
            'vendor.required' => 'Vendor is required',
            'vendor.exists' => 'Vendor does not exist',
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
