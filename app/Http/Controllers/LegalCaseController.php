<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLegalCaseRequest;
use App\Http\Requests\UpdateLegalCaseRequest;
use App\Models\Riders;
use App\Models\Employee;
use App\Models\legal_cases;
use App\Models\LegalCaseStatus;
use App\Models\LegalCaseAccount;
use App\Models\Settings;
use App\Repositories\LegalCasesRepository;
use App\Support\CompanyAuthRedirect;
use App\Support\CompanyContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Flash;
use DB;

class LegalCaseController extends AppBaseController
{
    use GlobalPagination;

    protected $legalCaseRepo;

    public function __construct(LegalCasesRepository $legalCaseRepo)
    {
        $this->legalCaseRepo = $legalCaseRepo;
    }

    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!user_can('legalcase_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $userBranches = app('user_branches');
        $query = LegalCaseAccount::query()
            ->with(['rider', 'employee'])
            ->orderByDesc('id');

        if (!auth()->user()->isAdmin()) {
            if (!empty($userBranches)) {
                $query->where(function ($q) use ($userBranches) {
                    $q->whereHas('rider', function ($rq) use ($userBranches) {
                        $rq->whereIn('branch_id', $userBranches)->orWhereNull('branch_id');
                    })->orWhereHas('employee', function ($eq) use ($userBranches) {
                        $eq->whereIn('branch_id', $userBranches)->orWhereNull('branch_id');
                    });
                });
            } else {
                $query->where(function ($q) {
                    $q->whereHas('rider', function ($rq) {
                        $rq->whereNull('branch_id');
                    })->orWhereHas('employee', function ($eq) {
                        $eq->whereNull('branch_id');
                    });
                });
            }
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('quick_search')) {
            $term = trim((string) $request->quick_search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhereHas('rider', function ($qr) use ($term) {
                        $qr->where('name', 'like', '%' . $term . '%')
                            ->orWhere('rider_id', 'like', '%' . $term . '%')
                            ->orWhere('person_code', 'like', '%' . $term . '%');
                    })
                    ->orWhereHas('employee', function ($qe) use ($term) {
                        $qe->where('name', 'like', '%' . $term . '%')
                            ->orWhere('employee_id', 'like', '%' . $term . '%')
                            ->orWhere('person_code', 'like', '%' . $term . '%');
                    });
            });
        }

        $legalCaseStatusFilterModel = null;
        if ($request->filled('case_status_id')) {
            $legalCaseStatusFilterModel = LegalCaseStatus::find($request->case_status_id);
        }

        $sliderBaseQuery = clone $query;

        $topEnabledRaw = (string) (Settings::query()
            ->where('name', 'legal_case_top_enabled')
            ->value('value') ?? '1');
        $topEnabled = in_array(strtolower(trim($topEnabledRaw)), ['1', 'true', 'yes', 'on'], true);

        $selectedTopIdsRaw = (string) (Settings::query()
            ->where('name', 'legal_case_top_status_ids')
            ->value('value') ?? '');
        $selectedTopIds = collect(json_decode($selectedTopIdsRaw, true))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $legalCaseStatuses = collect();
        if ($topEnabled && !empty($selectedTopIds)) {
            $legalCaseStatuses = LegalCaseStatus::query()
                ->where('is_active', 1)
                ->whereIn('id', $selectedTopIds)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();
            $statusOrderMap = array_flip($selectedTopIds);
            $legalCaseStatuses = $legalCaseStatuses
                ->sortBy(fn ($status) => $statusOrderMap[(int) $status->id] ?? PHP_INT_MAX)
                ->values();
        }

        $legalCaseStatusSliderCounts = [];
        foreach ($legalCaseStatuses as $vsRow) {
            $legalCaseStatusSliderCounts[$vsRow->id] = [
                'completed' => (clone $sliderBaseQuery)->tap(function ($q) use ($vsRow) {
                    $this->applyLegalCaseAccountMatchesCase($q, function ($sub) use ($vsRow) {
                        $sub->where('lc.case_status', $vsRow->name)->where('lc.step_status', 'completed');
                    });
                })->count(),
                'pending' => (clone $sliderBaseQuery)->tap(function ($q) use ($vsRow) {
                    $this->applyLegalCaseAccountMatchesCase($q, function ($sub) use ($vsRow) {
                        $sub->where('lc.case_status', $vsRow->name)->where('lc.step_status', 'pending');
                    });
                })->count(),
            ];
        }

        if ($request->filled('step_status')) {
            $status = $request->step_status;
            if ($legalCaseStatusFilterModel && in_array($status, ['pending', 'completed'], true)) {
                $this->applyLegalCaseAccountMatchesCase($query, function ($sub) use ($legalCaseStatusFilterModel, $status) {
                    $sub->where('lc.case_status', $legalCaseStatusFilterModel->name)
                        ->where('lc.step_status', $status);
                });
            } elseif (!$legalCaseStatusFilterModel) {
                if ($status === 'completed') {
                    $this->applyLegalCaseAccountMatchesCase($query, function ($sub) {
                        $sub->whereRaw('1 = 1');
                    });
                    $query->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('legal_cases as lc')
                            ->whereNull('lc.deleted_at')
                            ->where('lc.step_status', 'pending')
                            ->whereColumn('lc.legal_case_account_id', 'legal_case_accounts.id');
                        $this->applyLegalCaseCompanyScope($sub);
                    });
                } elseif ($status === 'pending') {
                    $this->applyLegalCaseAccountMatchesCase($query, function ($sub) {
                        $sub->where('lc.step_status', 'pending');
                    });
                }
            }
        } elseif ($legalCaseStatusFilterModel) {
            $this->applyLegalCaseAccountMatchesCase($query, function ($sub) use ($legalCaseStatusFilterModel) {
                $sub->where('lc.case_status', $legalCaseStatusFilterModel->name);
            });
        }

        $statsQuery = clone $query;
        $data = $this->applyPagination($query, $paginationParams);
        $riders = Riders::orderBy('name')->get();
        $employees = Employee::query()->where('status', 1)->orderBy('name')->get();
        $accountIds = $statsQuery->pluck('id')->toArray();
        $legalCaseRows = legal_cases::whereIn('legal_case_account_id', $accountIds)->get();
        $stats = [
            'pending_steps' => $legalCaseRows->where('step_status', 'pending')->count(),
            'completed_steps' => $legalCaseRows->where('step_status', 'completed')->count(),
        ];

        $nextPendingByAccountId = $this->mapNextPendingCasesForPage($data);
        $urgentExpiryByAccountId = $this->mapUrgentExpiryForPage($data);

        if ($request->ajax()) {
            $tableData = view('legal_cases.account_table', [
                'data' => $data,
                'nextPendingByAccountId' => $nextPendingByAccountId,
                'urgentExpiryByAccountId' => $urgentExpiryByAccountId,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'riders' => $riders,
                'stats' => $stats,
            ]);
        }

        return view('legal_cases.account_index', [
            'data' => $data,
            'riders' => $riders,
            'employees' => $employees,
            'stats' => $stats,
            'riderIds' => $accountIds,
            'legalCaseStatuses' => $legalCaseStatuses,
            'legalCaseStatusSliderCounts' => $legalCaseStatusSliderCounts,
            'nextPendingByAccountId' => $nextPendingByAccountId,
            'urgentExpiryByAccountId' => $urgentExpiryByAccountId,
        ]);
    }

    private function mapNextPendingCasesForPage($paginatorOrCollection): array
    {
        $items = $paginatorOrCollection instanceof \Illuminate\Contracts\Pagination\Paginator
            ? collect($paginatorOrCollection->items())
            : collect($paginatorOrCollection);

        if ($items->isEmpty()) {
            return [];
        }

        $ids = $items->pluck('id')->map(static fn ($id) => (int) $id)->all();

        $pendingRows = legal_cases::query()
            ->where('step_status', 'pending')
            ->whereIn('legal_case_account_id', $ids)
            ->orderByRaw('CASE WHEN date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('date', 'asc')
            ->orderBy('billing_month', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $nextByAccountId = [];
        foreach ($pendingRows as $row) {
            $accountId = (int) $row->legal_case_account_id;
            if (!in_array($accountId, $ids, true) || isset($nextByAccountId[$accountId])) {
                continue;
            }
            $nextByAccountId[$accountId] = $row;
        }

        return $nextByAccountId;
    }

    private function mapUrgentExpiryForPage($paginatorOrCollection, int $withinDays = 10): array
    {
        if (!Schema::hasColumn((new legal_cases)->getTable(), 'expiry_date')) {
            return [];
        }

        $items = $paginatorOrCollection instanceof \Illuminate\Contracts\Pagination\Paginator
            ? collect($paginatorOrCollection->items())
            : collect($paginatorOrCollection);

        if ($items->isEmpty()) {
            return [];
        }

        $ids = $items->pluck('id')->map(static fn ($id) => (int) $id)->all();
        $threshold = now()->addDays($withinDays)->startOfDay();

        $rows = legal_cases::query()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $threshold)
            ->whereIn('legal_case_account_id', $ids)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $byAccountId = [];
        foreach ($rows as $row) {
            $accountId = (int) $row->legal_case_account_id;
            if (!in_array($accountId, $ids, true) || isset($byAccountId[$accountId])) {
                continue;
            }
            $byAccountId[$accountId] = $row;
        }

        return $byAccountId;
    }

    private function applyLegalCaseAccountMatchesCase($accountQuery, callable $constraintsOnSubquery): void
    {
        $accountQuery->whereExists(function ($sub) use ($constraintsOnSubquery) {
            $sub->select(DB::raw(1))
                ->from('legal_cases as lc')
                ->whereNull('lc.deleted_at')
                ->whereColumn('lc.legal_case_account_id', 'legal_case_accounts.id');
            $constraintsOnSubquery($sub);
            $this->applyLegalCaseCompanyScope($sub);
        });
    }

    private function applyLegalCaseCompanyScope($subquery): void
    {
        if (!Schema::hasColumn((new legal_cases)->getTable(), 'company_id')) {
            return;
        }
        if (!CompanyContext::shouldApplyScope()) {
            return;
        }
        $cid = CompanyContext::id();
        if ($cid === null) {
            return;
        }
        $subquery->where('lc.company_id', $cid);
    }

    public function accountcreate(Request $request, $company_slug)
    {
        $request->validate([
            'person_key' => ['required', 'string', 'regex:/^(rider|employee):\d+$/'],
        ]);

        [$personType, $personId] = explode(':', $request->person_key, 2);
        $personId = (int) $personId;

        DB::beginTransaction();
        try {
            if ($personType === 'rider') {
                $rider = Riders::findOrFail($personId);
                if (LegalCaseAccount::where('rider_id', $rider->id)->exists()) {
                    Flash::error('Legal case account already exists for this rider.');
                    return redirect()->back();
                }

                $account = LegalCaseAccount::create([
                    'name' => $rider->name,
                    'rider_id' => $rider->id,
                    'branch_id' => $rider->branch_id,
                    'account_id' => $rider->account_id,
                    'company_id' => auth()->user()->company_id ?? null,
                ]);

                $this->seedLegalCaseEntriesForAccount($account, [
                    'branch_id' => $rider->branch_id,
                    'rider_id' => $rider->id,
                ]);
            } else {
                $employee = Employee::findOrFail($personId);
                if (LegalCaseAccount::where('employee_id', $employee->id)->exists()) {
                    Flash::error('Legal case account already exists for this employee.');
                    return redirect()->back();
                }

                $account = LegalCaseAccount::create([
                    'name' => $employee->name,
                    'employee_id' => $employee->id,
                    'branch_id' => $employee->branch_id,
                    'account_id' => $employee->account_id,
                    'company_id' => auth()->user()->company_id ?? null,
                ]);

                $this->seedLegalCaseEntriesForAccount($account, [
                    'branch_id' => $employee->branch_id,
                    'employee_id' => $employee->id,
                ]);
            }

            DB::commit();
            Flash::success('Legal case account created and active status entries generated.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Flash::error('Error creating legal case account: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    private function seedLegalCaseEntriesForAccount(LegalCaseAccount $account, array $personFields): void
    {
        $activeStatuses = LegalCaseStatus::query()
            ->where('is_active', 1)
            ->orderBy('display_order')
            ->get();

        foreach ($activeStatuses as $status) {
            legal_cases::create(array_merge($personFields, [
                'date' => Carbon::today()->format('Y-m-d'),
                'legal_case_account_id' => $account->id,
                'case_status' => $status->name,
                'detail' => $status->description ?? ('Auto-generated from active status: ' . $status->name),
                'reference_number' => 'LC-' . $account->id . '-' . $status->id,
                'billing_month' => Carbon::today()->startOfMonth()->format('Y-m-d'),
                'step_status' => 'pending',
                'company_id' => auth()->user()->company_id ?? null,
            ]));
        }
    }

    public function editaccount(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:legal_case_accounts,id',
            'rider_id' => 'required|exists:riders,id',
        ]);

        $rider = Riders::findOrFail($request->rider_id);
        $account = LegalCaseAccount::findOrFail($request->id);
        $account->rider_id = $rider->id;
        $account->name = $rider->name;
        $account->save();

        Flash::success('Legal case account updated successfully.');
        return redirect()->back();
    }

    public function deleteaccount($company_slug, $id)
    {
        $hasCompleted = legal_cases::where('legal_case_account_id', $id)
            ->where('step_status', 'completed')
            ->exists();

        if ($hasCompleted) {
            Flash::error('Cannot delete account. Completed legal case entries exist for this account.');
            return redirect()->back();
        }

        legal_cases::where('legal_case_account_id', $id)->delete();
        LegalCaseAccount::where('id', $id)->delete();

        Flash::success('Account deleted successfully.');
        return redirect()->back();
    }

    public function generatentries(Request $request, $company_slug, $id)
    {
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!user_can('legalcase_view')) {
            abort(403, 'Unauthorized action.');
        }

        $account = LegalCaseAccount::with(['rider', 'employee'])->where('id', $id)->firstOrFail();
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = legal_cases::query()
            ->where('legal_case_account_id', $account->id)
            ->orderBy('id', 'asc');

        if ($request->filled('date')) {
            $query->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->date));
        }
        if ($request->filled('case_status')) {
            $query->where('case_status', $request->case_status);
        }
        if ($request->filled('step_status')) {
            $query->where('step_status', $request->step_status);
        }

        $data = $this->applyPagination($query, $paginationParams);

        if ($request->ajax()) {
            $tableData = view('legal_cases.table', [
                'data' => $data,
                'account' => $account,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        $legalCaseStatuses = LegalCaseStatus::orderBy('display_order', 'asc')->where('is_active', 1)->get();

        $viewData = [
            'data' => $data,
            'account' => $account,
            'legalCaseStatuses' => $legalCaseStatuses,
        ];

        if ($account->employee_id) {
            return view('legal_cases.index_employee', $viewData);
        }

        $viewData['riders'] = Riders::findOrFail($account->rider_id);

        return view('legal_cases.index', $viewData);
    }

    public function create($company_slug, $id)
    {
        $data = LegalCaseAccount::where('id', $id)->firstOrFail();
        $legalCaseStatuses = LegalCaseStatus::orderBy('display_order', 'asc')->where('is_active', 1)->get();

        return view('legal_cases.create', compact('data', 'legalCaseStatuses'));
    }

    public function store(StoreLegalCaseRequest $request)
    {
        $validated = $request->validated();

        try {
            $billingMonth = $validated['billing_month'] . '-01';
            legal_cases::create([
                'rider_id' => $validated['rider_id'],
                'legal_case_account_id' => $validated['rider_id'],
                'case_status' => $validated['case_status'],
                'billing_month' => $billingMonth,
                'date' => $request->date,
                'detail' => $validated['detail'] ?? null,
                'reference_number' => $validated['reference_number'],
                'step_status' => 'pending',
                'company_id' => auth()->user()->company_id ?? null,
            ]);

            Flash::success('Legal case entry added successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            report($e);
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function show(string $company_slug, string $id)
    {
        //
    }

    public function edit(string $company_slug, string $id)
    {
        $legalCases = legal_cases::findOrFail($id);
        $data = LegalCaseAccount::where('id', $legalCases->legal_case_account_id)->firstOrFail();
        $legalCaseStatuses = LegalCaseStatus::orderBy('display_order', 'asc')->where('is_active', 1)->get();

        return view('legal_cases.edit', compact('data', 'legalCases', 'legalCaseStatuses'));
    }

    public function update(UpdateLegalCaseRequest $request)
    {
        try {
            $legalCases = legal_cases::findOrFail($request->id);
            $billingMonth = $request->billing_month . '-01';

            $legalCases->case_status = $request->case_status;
            $legalCases->billing_month = $billingMonth;
            $legalCases->date = $request->date;
            $legalCases->detail = $request->detail;
            $legalCases->reference_number = $request->reference_number;
            $legalCases->save();

            Flash::success('Legal case updated successfully.');
        } catch (\Exception $e) {
            Flash::error('Error updating legal case: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function inlineUpdate(Request $request)
    {
        if (!user_can('legalcase_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'id' => 'required|exists:legal_cases,id',
            'date' => 'required|date',
            'billing_month' => 'required|date_format:Y-m',
        ]);

        $row = legal_cases::findOrFail($validated['id']);
        $row->date = $validated['date'];
        $row->billing_month = $validated['billing_month'] . '-01';
        $row->save();

        return response()->json([
            'success' => true,
            'message' => 'Legal case updated.',
            'date' => Carbon::parse($row->date)->format('Y-m-d'),
            'billing_month' => Carbon::parse($row->billing_month)->format('Y-m'),
        ]);
    }

    public function completeStep(Request $request)
    {
        if (!user_can('legalcase_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'id' => 'required|exists:legal_cases,id',
        ]);

        $case = legal_cases::findOrFail($validated['id']);

        if ($case->step_status === 'completed') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This step is already completed.',
                ], 422);
            }
            Flash::info('This step is already completed.');
            return redirect()->back();
        }

        $case->step_status = 'completed';
        $case->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Step completed successfully.',
                'step_status' => 'completed',
            ]);
        }

        Flash::success('Step completed successfully.');
        return redirect()->back();
    }

    public function destroy($company_slug, string $id)
    {
        $legalCase = legal_cases::find($id);

        if (empty($legalCase)) {
            Flash::error('Legal case entry not found');
            return redirect()->back();
        }

        $legalCase->deleted_by = auth()->id();
        $legalCase->save();
        $legalCase->delete();

        Flash::success('Legal case entry deleted successfully.');
        return redirect()->back();
    }
}
