<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Helpers\HeadAccount;
use App\Helpers\Common;
use App\Http\Requests\StoreVisaExpenseRequest;
use App\Http\Requests\UpdateVisaExpenseRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Bikes;
use App\Models\Riders;
use App\Models\visa_expenses;
use App\Models\Accounts;
use App\Models\Vouchers;
use App\Models\VoucherType;
use App\Models\LedgerEntry;
use App\Models\visa_installment_plan;
use App\Models\Transactions;
use App\Models\VisaStatus;
use App\Models\ExpenseAccount;
use App\Models\Settings;
use App\Repositories\VisaExpensesRepository;
use App\Services\TransactionService;
use App\Support\CompanyAuthRedirect;
use App\Support\CompanyContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use App\Support\CompanyQuery;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Flash;
use DB;

class VisaexpenseController extends AppBaseController
{
    use GlobalPagination, TracksCascadingDeletions;

    protected $visaRepo;
    public function __construct(VisaExpensesRepository $visaRepo)
    {
        $this->visaRepo = $visaRepo;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check if user is authenticated first
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!auth()->user()->hasPermissionTo('visaexpense_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $userBranches = app('user_branches');
        $query = ExpenseAccount::query()
            ->with('rider')
            ->orderByDesc('id');

        if (!auth()->user()->isAdmin()) {
            if (!empty($userBranches)) {
                $query->whereHas('rider', function ($q) use ($userBranches) {
                    $q->whereIn('branch_id', $userBranches)->orWhereNull('branch_id');
                });
            } else {
                $query->whereHas('rider', function ($q) {
                    $q->whereNull('branch_id');
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
                    });
            });
        }

        $visaStatusFilterModel = null;
        if ($request->filled('visa_status_id')) {
            $visaStatusFilterModel = VisaStatus::find($request->visa_status_id);
        }

        $sliderBaseQuery = clone $query;

        $visaTopEnabledRaw = (string) (Settings::query()
            ->where('name', 'visa_expense_top_enabled')
            ->value('value') ?? '1');
        $visaTopEnabled = in_array(strtolower(trim($visaTopEnabledRaw)), ['1', 'true', 'yes', 'on'], true);

        $selectedVisaTopIdsRaw = (string) (Settings::query()
            ->where('name', 'visa_expense_top_status_ids')
            ->value('value') ?? '');
        $selectedVisaTopIds = collect(json_decode($selectedVisaTopIdsRaw, true))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $visaStatuses = collect();
        if ($visaTopEnabled && !empty($selectedVisaTopIds)) {
            $visaStatusesQuery = VisaStatus::query()
                ->where('is_active', 1)
                ->whereIn('id', $selectedVisaTopIds)
                ->orderBy('display_order')
                ->orderBy('id');
            $visaStatuses = $visaStatusesQuery->get();
            $statusOrderMap = array_flip($selectedVisaTopIds);
            $visaStatuses = $visaStatuses
                ->sortBy(fn ($status) => $statusOrderMap[(int) $status->id] ?? PHP_INT_MAX)
                ->values();
        }

        $visaStatusSliderCounts = [];
        foreach ($visaStatuses as $vsRow) {
            $visaStatusSliderCounts[$vsRow->id] = [
                'paid' => (clone $sliderBaseQuery)->tap(function ($q) use ($vsRow) {
                    $this->applyExpenseAccountMatchesVisaExpense($q, function ($sub) use ($vsRow) {
                        $sub->where('ve.visa_status', $vsRow->name)->where('ve.payment_status', 'paid');
                    });
                })->count(),
                'unpaid' => (clone $sliderBaseQuery)->tap(function ($q) use ($vsRow) {
                    $this->applyExpenseAccountMatchesVisaExpense($q, function ($sub) use ($vsRow) {
                        $sub->where('ve.visa_status', $vsRow->name)->where('ve.payment_status', 'unpaid');
                    });
                })->count(),
            ];
        }

        if ($request->filled('payment_status')) {
            $status = $request->payment_status;
            if ($visaStatusFilterModel && in_array($status, ['paid', 'unpaid'], true)) {
                $this->applyExpenseAccountMatchesVisaExpense($query, function ($sub) use ($visaStatusFilterModel, $status) {
                    $sub->where('ve.visa_status', $visaStatusFilterModel->name)
                        ->where('ve.payment_status', $status);
                });
            } elseif (!$visaStatusFilterModel) {
                if ($status === 'paid') {
                    $this->applyExpenseAccountMatchesVisaExpense($query, function ($sub) {
                        $sub->whereRaw('1 = 1');
                    });
                    $query->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('visa_expenses as ve')
                            ->whereNull('ve.deleted_at')
                            ->where('ve.payment_status', 'unpaid')
                            ->where(function ($link) {
                                $link->whereColumn('ve.expense_account_id', 'expense_accounts.id')
                                    ->orWhere(function ($l2) {
                                        $l2->where('ve.expense_account_id', HeadAccount::VISA_EXPENSE_ACCOUNT)
                                            ->whereColumn('ve.rider_id', 'expense_accounts.rider_id');
                                    });
                            });
                        $this->applyVisaExpenseCompanyScopeForVeAlias($sub);
                    });
                } elseif ($status === 'unpaid') {
                    $this->applyExpenseAccountMatchesVisaExpense($query, function ($sub) {
                        $sub->where('ve.payment_status', 'unpaid');
                    });
                }
            }
        } elseif ($visaStatusFilterModel) {
            $this->applyExpenseAccountMatchesVisaExpense($query, function ($sub) use ($visaStatusFilterModel) {
                $sub->where('ve.visa_status', $visaStatusFilterModel->name);
            });
        }

        $statsQuery = clone $query;
        $data = $this->applyPagination($query, $paginationParams);
        $riders = Riders::orderBy('name')->get();
        $expenseAccountIds = $statsQuery->pluck('id')->toArray();
        $visaAccounts = visa_expenses::whereIn('expense_account_id', $expenseAccountIds)->get();
        $stats = [
            'unpaid_accounts' => $visaAccounts->where('payment_status', 'unpaid')->count(),
            'paid_amount' => $visaAccounts->where('payment_status', 'paid')->sum('amount'),
            'unpaid_amount' => $visaAccounts->where('payment_status', 'unpaid')->sum('amount'),
        ];

        $nextUnpaidVisaByAccountId = $this->mapNextUnpaidVisaExpensesForPage($data);
        $urgentVisaExpiryByAccountId = $this->mapUrgentVisaExpiryForPage($data);

        if ($request->ajax()) {
            $tableData = view('visa_expenses.account_table', [
                'data' => $data,
                'nextUnpaidVisaByAccountId' => $nextUnpaidVisaByAccountId,
                'urgentVisaExpiryByAccountId' => $urgentVisaExpiryByAccountId,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'riders' => $riders,
                'stats' => $stats,
            ]);
        }

        return view('visa_expenses.account_index', [
            'data' => $data,
            'riders' => $riders,
            'stats' => $stats,
            'riderIds' => $expenseAccountIds,
            'visaStatuses' => $visaStatuses,
            'visaStatusSliderCounts' => $visaStatusSliderCounts,
            'nextUnpaidVisaByAccountId' => $nextUnpaidVisaByAccountId,
            'urgentVisaExpiryByAccountId' => $urgentVisaExpiryByAccountId,
        ]);
    }

    /**
     * Earliest unpaid visa expense per expense account on this results page (one query).
     *
     * @param  \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection|array  $paginatorOrCollection
     * @return array<int, \App\Models\visa_expenses>
     */
    private function mapNextUnpaidVisaExpensesForPage($paginatorOrCollection): array
    {
        $items = $paginatorOrCollection instanceof \Illuminate\Contracts\Pagination\Paginator
            ? collect($paginatorOrCollection->items())
            : collect($paginatorOrCollection);

        if ($items->isEmpty()) {
            return [];
        }

        $ids = $items->pluck('id')->map(static fn($id) => (int) $id)->all();
        $riderToEaId = [];
        foreach ($items as $accountRow) {
            if ($accountRow->rider_id !== null) {
                $riderToEaId[(int) $accountRow->rider_id] = (int) $accountRow->id;
            }
        }

        $headId = (int) HeadAccount::VISA_EXPENSE_ACCOUNT;

        $unpaidRows = visa_expenses::query()
            ->where('payment_status', 'unpaid')
            ->where(function ($q) use ($ids, $riderToEaId, $headId) {
                $q->whereIn('expense_account_id', $ids)
                    ->orWhere(function ($q2) use ($riderToEaId, $headId) {
                        $q2->where('expense_account_id', $headId)
                            ->whereIn('rider_id', array_keys($riderToEaId));
                    });
            })
            ->orderByRaw('CASE WHEN date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('date', 'asc')
            ->orderBy('billing_month', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $nextByEaId = [];
        foreach ($unpaidRows as $ve) {
            $eaId = null;
            $veEa = (int) $ve->expense_account_id;
            if (in_array($veEa, $ids, true)) {
                $eaId = $veEa;
            } elseif ($veEa === $headId && $ve->rider_id !== null) {
                $rid = (int) $ve->rider_id;
                if (isset($riderToEaId[$rid])) {
                    $eaId = $riderToEaId[$rid];
                }
            }
            if ($eaId === null || isset($nextByEaId[$eaId])) {
                continue;
            }
            $nextByEaId[$eaId] = $ve;
        }

        return $nextByEaId;
    }

    /**
     * Earliest visa expense with expiry_date on or before today + $withinDays (inclusive), per expense account on this page.
     * Same account linkage as {@see mapNextUnpaidVisaExpensesForPage()}.
     *
     * @param  \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection|array  $paginatorOrCollection
     * @return array<int, \App\Models\visa_expenses>
     */
    private function mapUrgentVisaExpiryForPage($paginatorOrCollection, int $withinDays = 10): array
    {
        if (!Schema::hasColumn((new visa_expenses)->getTable(), 'expiry_date')) {
            return [];
        }

        $items = $paginatorOrCollection instanceof \Illuminate\Contracts\Pagination\Paginator
            ? collect($paginatorOrCollection->items())
            : collect($paginatorOrCollection);

        if ($items->isEmpty()) {
            return [];
        }

        $ids = $items->pluck('id')->map(static fn ($id) => (int) $id)->all();
        $riderToEaId = [];
        foreach ($items as $accountRow) {
            if ($accountRow->rider_id !== null) {
                $riderToEaId[(int) $accountRow->rider_id] = (int) $accountRow->id;
            }
        }

        $headId = (int) HeadAccount::VISA_EXPENSE_ACCOUNT;
        $threshold = now()->addDays($withinDays)->startOfDay();

        $rows = visa_expenses::query()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $threshold)
            ->where(function ($q) use ($ids, $riderToEaId, $headId) {
                $q->whereIn('expense_account_id', $ids)
                    ->orWhere(function ($q2) use ($riderToEaId, $headId) {
                        $q2->where('expense_account_id', $headId)
                            ->whereIn('rider_id', array_keys($riderToEaId));
                    });
            })
            ->orderBy('expiry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $byEaId = [];
        foreach ($rows as $ve) {
            $eaId = null;
            $veEa = (int) $ve->expense_account_id;
            if (in_array($veEa, $ids, true)) {
                $eaId = $veEa;
            } elseif ($veEa === $headId && $ve->rider_id !== null) {
                $rid = (int) $ve->rider_id;
                if (isset($riderToEaId[$rid])) {
                    $eaId = $riderToEaId[$rid];
                }
            }
            if ($eaId === null || isset($byEaId[$eaId])) {
                continue;
            }
            $byEaId[$eaId] = $ve;
        }

        return $byEaId;
    }

    /**
     * Correlate visa_expenses to expense_accounts the same way as generatentries:
     * direct expense_account_id = expense_accounts.id, or legacy head-account id + rider match.
     */
    private function applyExpenseAccountMatchesVisaExpense($expenseAccountQuery, callable $constraintsOnVeSubquery): void
    {
        $headId = HeadAccount::VISA_EXPENSE_ACCOUNT;
        $expenseAccountQuery->whereExists(function ($sub) use ($constraintsOnVeSubquery, $headId) {
            $sub->select(DB::raw(1))
                ->from('visa_expenses as ve')
                ->whereNull('ve.deleted_at')
                ->where(function ($link) use ($headId) {
                    $link->whereColumn('ve.expense_account_id', 'expense_accounts.id')
                        ->orWhere(function ($l2) use ($headId) {
                            $l2->where('ve.expense_account_id', $headId)
                                ->whereColumn('ve.rider_id', 'expense_accounts.rider_id');
                        });
                });
            $constraintsOnVeSubquery($sub);
            $this->applyVisaExpenseCompanyScopeForVeAlias($sub);
        });
    }

    private function applyVisaExpenseCompanyScopeForVeAlias($subquery): void
    {
        if (!Schema::hasColumn((new visa_expenses)->getTable(), 'company_id')) {
            return;
        }
        if (!CompanyContext::shouldApplyScope()) {
            return;
        }
        $cid = CompanyContext::id();
        if ($cid === null) {
            return;
        }
        $subquery->where('ve.company_id', $cid);
    }

    public function accountcreate(Request $request, $company_slug)
    {
        $request->validate([
            'rider_id' => 'required|exists:riders,id',
        ]);
        $rider = Riders::findOrFail($request->rider_id);
        $exists = ExpenseAccount::where('rider_id', $rider->id)->exists();
        if ($exists) {
            Flash::error('Visa expense account already exists for this rider.');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $expenseAccount = ExpenseAccount::create([
                'name' => $rider->name,
                'rider_id' => $rider->id,
                'branch_id' => $rider->branch_id,
                'account_id' => $rider->account_id,
                'company_id' => auth()->user()->company_id ?? null,
            ]);

            $activeStatuses = VisaStatus::query()
                ->where('is_active', 1)
                ->orderBy('display_order')
                ->get();
            foreach ($activeStatuses as $status) {
                visa_expenses::create([
                    'branch_id' => $rider->branch_id,
                    'trans_date' => Carbon::today()->format('Y-m-d'),
                    'trans_code' => Account::trans_code(),
                    'date' => Carbon::today()->format('Y-m-d'),
                    'rider_id' => $expenseAccount->rider_id, // legacy compatibility
                    'expense_account_id' => HeadAccount::VISA_EXPENSE_ACCOUNT,
                    'visa_status' => $status->name,
                    'detail' => $status->description ?? ('Auto-generated from active visa status: ' . $status->name),
                    'reference_number' => 'VS-' . $expenseAccount->rider_id . '-' . $status->id,
                    'billing_month' => Carbon::today()->startOfMonth()->format('Y-m-d'),
                    'amount' => (float) ($status->default_fee ?? 0),
                    'payment_status' => 'unpaid',
                ]);
            }
            DB::commit();
            Flash::success('Visa expense account created and active status entries generated.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Flash::error('Error creating visa expense account: ' . $e->getMessage());
        }
        return redirect()->back();
    }
    public function editaccount(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:expense_accounts,id',
            'rider_id' => 'required|exists:riders,id',
        ]);
        $rider = Riders::findOrFail($request->rider_id);
        $account = ExpenseAccount::findOrFail($request->id);
        $account->rider_id = $rider->id;
        $account->name = $rider->name;
        $account->save();
        Flash::success('Visa expense account updated successfully.');
        return redirect()->back();
    }

    public function deleteaccount($company_slug, $id)
    {
        // Check if any visa_expenses exist for this account
        $hasExpenses = visa_expenses::where('expense_account_id', $id)
            ->Where('payment_status', 'paid')
            ->exists();

        if ($hasExpenses) {
            Flash::error('Cannot delete account. Visa Expense entries exist for this account.');
            return redirect()->back();
        }

        // Check if any installment plans exist for this account
        $hasInstallments = visa_installment_plan::where('rider_id', $id)->exists();

        if ($hasInstallments) {
            Flash::error('Cannot delete account. Installment Plan entries exist for this account.');
            return redirect()->back();
        }

        // Check if any transactions exist for this account related to visa expenses
        $hasTransactions = Transactions::where('account_id', $id)
            ->where(function ($query) {
                $query->where('reference_type', 'LV')
                    ->orWhere('reference_type', 'VL')
                    ->orWhere('reference_type', 'VE');
            })
            ->exists();

        if ($hasTransactions) {
            Flash::error('Cannot delete account. Transactions related to Visa Expenses exist for this account.');
            return redirect()->back();
        }

        // Check if any vouchers exist for this account related to visa expenses
        $account = ExpenseAccount::findOrFail($id);
        $riderId = $account->rider_id;
        if ($riderId) {
            $hasVouchers = Vouchers::where('rider_id', $riderId)
                ->where(function ($query) {
                    $query->where('voucher_type', 'LV')
                        ->orWhere('voucher_type', 'VL');
                })
                ->exists();

            if ($hasVouchers) {
                Flash::error('Cannot delete account. Vouchers related to Visa Expenses exist for this account.');
                return redirect()->back();
            }
        }

        // No related records — safe to delete
        $visaexpense = visa_expenses::where('expense_account_id', $id)->delete();
        ExpenseAccount::where('id', $id)->delete();
        Flash::success('Account deleted successfully.');
        return redirect()->back();
    }

    public function generatentries(Request $request, $company_slug, $id)
    {
        // Check if user is authenticated first
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!auth()->user()->hasPermissionTo('visaexpense_view')) {
            abort(403, 'Unauthorized action.');
        }
        $account = ExpenseAccount::with('rider')->where('id', $id)->first();
        // Auto-mark installments as paid if their date has arrived.
        $riderId = $account->rider_id;
        $this->checkAndAutoMarkInstallments($riderId);
        // Use global pagination traits
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = visa_expenses::query()
            ->with('vouchers')
            ->orderBy('id', 'asc')
            ->where(function ($q) use ($riderId) {
                $q->where('expense_account_id', HeadAccount::VISA_EXPENSE_ACCOUNT)->orWhere('rider_id', $riderId);
            });
        if ($request->has('trans_date') && !empty($request->trans_date)) {
            $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->trans_date);
            $query->where('trans_date', $fromDate);
        }
        if ($request->has('trans_code') && !empty($request->trans_code)) {
            $query->where('trans_code', $request->trans_code);
        }
        if ($request->filled('date')) {
            $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->date);
            $query->where('date', '<=', $toDate);
        }
        if ($request->has('visa_status') && !empty($request->visa_status)) {
            $query->where('visa_status', $request->visa_status);
        }
        if ($request->has('payment_status') && !empty($request->payment_status)) {
            $query->where('payment_status', $request->payment_status);
        }
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        $installmentQuery = visa_installment_plan::query()
            ->with(['vouchers', 'installmentTransactions'])
            ->where('rider_id', $riderId)
            ->orderBy('date', 'asc');
        $installmentData = $this->applyPagination($installmentQuery, $paginationParams);

        if ($request->ajax()) {
            $tableData = view('visa_expenses.table', [
                'data' => $data,
                'account' => $account,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }
        $visaStatuses = VisaStatus::orderBy('display_order', 'asc')->where('is_active', 1)->get();
        $riders = Riders::findOrFail($riderId);
        \Log::info('visa expense entries', ['rider_id' => $id, 'rider' => $riders]);
        return view('visa_expenses.index', [
            'data' => $data,
            'installmentData' => $installmentData,
            'account' => $account,
            'visaStatuses' => $visaStatuses,
            'riders' => $riders,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($company_slug, $id)
    {
        $data = ExpenseAccount::where('id', $id)->first();
        $visaStatuses = VisaStatus::orderBy('display_order', 'asc')->where('is_active', 1)->get();
        return view('visa_expenses.create', compact('data', 'visaStatuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rider_id'       => 'required|exists:expense_accounts,id',
            'visa_status'    => [
                'required',
                'string',
                'max:255',
                Rule::unique('visa_expenses')->where(function ($query) use ($request) {
                    return $query->where('expense_account_id', $request->rider_id)->where('deleted_at', null);
                }),
            ],
            'billing_month'  => 'required|date_format:Y-m',
            'detail'         => 'nullable|string',
            'reference_number' => 'required|string|max:255',
            'amount'         => 'required|numeric|min:0',
            'attach_file'    => 'nullable|string|max:255',
        ]);

        try {
            $trans_code = Account::trans_code();
            $billingMonth = $validated['billing_month'] . "-01";
            $trans_date = Carbon::today();
            $visaExpenses = visa_expenses::create([
                'rider_id'       => $validated['rider_id'],
                'expense_account_id' => $validated['rider_id'],
                'visa_status'    => $validated['visa_status'],
                'billing_month'  => $billingMonth,
                'date'           => $request->date,
                'amount'         => $request->amount,
                'payment_status' => 'unpaid',
                'detail'         => $validated['detail'],
                'reference_number' => $validated['reference_number'],
                'trans_date'     => $trans_date,
                'trans_code'     => $trans_code,
            ]);
            Flash::success('Visa Expenses added successfully ');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }
    public function viewvoucher($company_slug, $id)
    {

        $data = visa_expenses::where('id', $id)->first();
        $accounts = ExpenseAccount::where('rider_id', $data->rider_id)->first();
        return view('visa_expenses.viewvoucher', compact('data', 'accounts'));
    }
    public function installmentPlan(Request $request, $company_slug, $id)
    {
        // Debug session information
        \Log::info('InstallmentPlan Access Debug', [
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'session_data' => session()->all(),
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method(),
        ]);

        // Check if user is authenticated first
        if (!auth()->check()) {
            \Log::warning('User not authenticated when accessing installment plan', [
                'session_id' => session()->getId(),
                'request_url' => $request->fullUrl(),
            ]);
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!auth()->user()->hasPermissionTo('visaexpense_view')) {
            abort(403, 'Unauthorized action.');
        }

        // Auto-mark installments as paid if their date has arrived
        $this->checkAndAutoMarkInstallments($id);

        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = visa_installment_plan::query()
            ->with(['vouchers', 'installmentTransactions'])
            ->where('rider_id', $id)
            ->orderBy('date', 'asc');
        // Apply filters
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('billing_month') && !empty($request->billing_month)) {
            $query->where('billing_month', 'like', '%' . $request->billing_month . '%');
        }

        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        $account = Accounts::where('id', $id)->first();

        if ($request->ajax()) {
            $tableData = view('visa_expenses.installmentPlanTable', [
                'data' => $data,
                'account' => $account,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        return view('visa_expenses.installmentPlan', compact('data', 'account'));
    }

    public function createInstallmentPlanForm(Request $request, $company_slug, $riderId)
    {
        if (!auth()->user()->hasPermissionTo('visaexpense_create')) {
            abort(403, 'Unauthorized action.');
        }

        // Auto-mark installments silently in the background
        $this->checkAndAutoMarkInstallments($riderId);

        $account = ExpenseAccount::find($riderId);
        if (!$account) {
            // Backward compatibility: allow legacy account ids too.
            $account = Accounts::findOrFail($riderId);
        }




        // Check if there's already an installment plan for this rider in the current month
        $currentMonth = Carbon::now()->format('Y-m');
        $existingCurrentMonthPlan = visa_installment_plan::where('rider_id', $riderId)
            ->where('billing_month', $currentMonth)
            ->exists();

        if ($existingCurrentMonthPlan) {
            Flash::warning('An installment plan already exists for this rider in ' . Carbon::now()->format('F Y') . '. You can still create a new plan, but please select a different starting month.');
        }
        return view('visa_expenses.createInstallmentPlan', compact('account', 'existingCurrentMonthPlan'));
    }

    public function createInstallmentPlan(Request $request, $company_slug)
    {
        if (!auth()->user()->hasPermissionTo('visaexpense_create')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'rider_id' => 'required|integer',
            'total_amount' => 'required|numeric|min:1',
            'billing_month' => 'required|string',
            'number_of_installments' => 'required|integer|min:1|max:12',
            'installment_amounts' => 'required|array|min:1',
            'installment_amounts.*' => 'required|numeric|min:0',
            'reference_number' => 'required|string|max:255',
            'branch_id' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            // Resolve rider account context (new expense_accounts flow + legacy accounts flow).
            $expenseAccount = ExpenseAccount::find($validated['rider_id']);
            $riderAccount = $request->rider_id;


            // Check if there's already an installment plan for this rider in the current month
            $currentMonth = Carbon::parse($validated['billing_month'] . '-01')->format('Y-m');
            $existingCurrentMonthPlan = visa_installment_plan::where('rider_id', $validated['rider_id'])
                ->where('billing_month', $currentMonth)
                ->exists();

            if ($existingCurrentMonthPlan) {
                Flash::error('Cannot create installment plan. An installment plan already exists for this rider in ' . Carbon::parse($validated['billing_month'] . '-01')->format('F Y') . '.');
                return redirect()->back();
            }

            // Validate that installment amounts sum to total amount.
            // Allow tiny rounding drift (e.g. 833.33 x 6 = 4999.98 for 5000.00 total),
            // then auto-adjust the last installment to keep exact total consistency.
            $installmentAmounts = array_map(static fn($amount) => round((float) $amount, 2), $validated['installment_amounts']);
            $sumOfInstallments = round(array_sum($installmentAmounts), 2);
            $totalAmount = round((float) $validated['total_amount'], 2);
            $difference = round($totalAmount - $sumOfInstallments, 2);

            if (abs($difference) <= 0.05 && count($installmentAmounts) > 0) {
                $lastIndex = count($installmentAmounts) - 1;
                $installmentAmounts[$lastIndex] = round($installmentAmounts[$lastIndex] + $difference, 2);
                $sumOfInstallments = round(array_sum($installmentAmounts), 2);
                $difference = round($totalAmount - $sumOfInstallments, 2);
            }

            if (abs($difference) > 0.01) {
                Flash::error('The sum of individual installment amounts (' . number_format($sumOfInstallments, 2) . ') does not match the total amount (' . number_format($totalAmount, 2) . ').');
                return redirect()->back()->withInput();
            }

            // Validate number of installments matches the array length
            if (count($installmentAmounts) != $validated['number_of_installments']) {
                Flash::error('Number of installment amounts does not match the selected number of installments.');
                return redirect()->back()->withInput();
            }

            // Find the liability account using ref_id from rider account
            $liabilityAccount = Accounts::where('ref_id', $riderAccount)
                ->where('account_type', 'Liability')
                ->where('parent_id', 1)
                ->first();

            if (!$liabilityAccount) {
                Flash::error('Liability account not found for this rider. Please create the liability account first.');
                return redirect()->back();
            }
            $rider = Riders::findOrFail($riderAccount);


            $existingInstallmentCount  = visa_installment_plan::where('rider_id', $validated['rider_id'])->count();

            // Create multiple installment entries for consecutive months
            for ($i = 0; $i < $validated['number_of_installments']; $i++) {
                // Calculate billing month for this installment (consecutive months)
                $billingDate = Carbon::parse($validated['billing_month'] . '-01')->addMonths($i);
                $billingMonth = $billingDate->format('Y-m-d');
                $billingMonthFormatted = $billingDate->format('Y-m');

                // Calculate installment date - set to 10th of next month from billing month
                $installmentDate = $billingDate->copy()->addMonth()->day(10)->format('Y-m-d');

                // Get the individual installment amount
                $installmentAmount = $installmentAmounts[$i];
                // Create installment entry
                $installment = visa_installment_plan::create([
                    'rider_id' => $riderAccount,
                    'branch_id' => $validated['branch_id'],
                    'billing_month' => $billingMonthFormatted,
                    'amount' => $installmentAmount,
                    'total_amount' => $totalAmount, // Store the total amount for reference
                    'reference_number' => $validated['reference_number'],
                    'narration' => $rider->rider_id . ' - ' . $rider->name . '<b> - installment ' . ($i + 1 + $existingInstallmentCount) . '</b>',
                    'status' => visa_installment_plan::STATUS_PENDING,
                    'date' => $installmentDate,
                    'created_by' => auth()->user()->id,
                ]);

                // Generate unique transaction code for each installment
                $trans_code = Account::trans_code();
                $trans_date = Carbon::today();
                $TransactionService = new TransactionService();

                // Create separate voucher for each installment
                $voucher = Vouchers::create([
                    'trans_date' => $trans_date,
                    'trans_code' => $trans_code,
                    'billing_month' => $billingMonth,
                    'payment_type' => 1, // Liability payment
                    'voucher_type' => 'VL', // Visa Loan
                    'remarks' => 'Loan Voucher - <b>Installment ' . ($i + 1 + $existingInstallmentCount) . ' of '
                        . ($validated['number_of_installments'] + $existingInstallmentCount) . '</b>'
                        . ' (Amount: <b>' . number_format($installmentAmount, 2) . '</b>)',
                    'amount' => $installmentAmount,
                    'reference_number' => $validated['reference_number'],
                    'Created_By' => auth()->user()->id,
                    'ref_id' => $installment->id,
                    'custom_field_values' => [],
                    'branch_id' => $validated['branch_id'],
                ]);
                // Debit the liability account for each installment
                $TransactionService->recordTransaction([
                    'account_id' => $liabilityAccount->id,
                    'reference_id' => $installment->id,
                    'reference_type' => 'VL',
                    'trans_code' => $trans_code,
                    'trans_date' => $trans_date,
                    'narration' => $rider->rider_id . ' - ' . $rider->name . '<b> - installment ' . ($i + 1 + $existingInstallmentCount) . '</b>',
                    'debit' => $installmentAmount,
                    'branch_id' => $validated['branch_id'],
                    'billing_month' => $billingMonth,
                    'created_by' => auth()->user()->id,
                ]);

                // Credit the rider account (visa expense account) for each installment
                $TransactionService->recordTransaction([
                    'account_id' => HeadAccount::VISA_EXPENSE_ACCOUNT,
                    'reference_id' => $installment->id,
                    'reference_type' => 'VL',
                    'trans_code' => $trans_code,
                    'trans_date' => $trans_date,
                    'narration' => $rider->rider_id . ' - ' . $rider->name . '<b> - installment ' . ($i + 1 + $existingInstallmentCount) . '</b>',
                    'credit' => $installmentAmount,
                    'branch_id' => $validated['branch_id'],
                    'billing_month' => $billingMonth,
                    'created_by' => auth()->user()->id,
                ]);

                // Create ledger entry for liability account for each installment
                $lastLedger = CompanyQuery::table('ledger_entries')
                    ->where('account_id', $liabilityAccount->id)
                    ->orderBy('billing_month', 'desc')
                    ->first();

                $opening_balance = $lastLedger ? $lastLedger->closing_balance : 0.00;
                $debit_balance = $installmentAmount;
                $credit_balance = 0.00;
                $closing_balance = $opening_balance + $installmentAmount; // Liability increases with debit

                CompanyQuery::insert('ledger_entries', [
                    'account_id' => $liabilityAccount->id,
                    'billing_month' => $billingMonth,
                    'opening_balance' => $opening_balance,
                    'debit_balance' => $debit_balance,
                    'credit_balance' => $credit_balance,
                    'closing_balance' => $closing_balance,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'branch_id' => $validated['branch_id'],
                ]);
            }

            DB::commit();

            $installmentDetails = '';
            foreach ($installmentAmounts as $index => $amount) {
                $installmentDetails .= '<b>Installment ' . ($index + 1 + $existingInstallmentCount) . '</b>: <b>' . number_format($amount, 2) . '</b>, ';
            }
            $installmentDetails = rtrim($installmentDetails, ', ');

            Flash::success($validated['number_of_installments'] . ' installment entries created successfully with individual amounts: ' . $installmentDetails . '. Total amount: ' . number_format($validated['total_amount'], 2));
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error creating installment plan: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function payInstallment(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'installment_id' => 'required|exists:visa_installment_plans,id',
            'status' => 'nullable|in:pending,paid'
        ]);

        try {
            DB::beginTransaction();

            $installment = visa_installment_plan::findOrFail($validated['installment_id']);
            $currentStatus = $installment->status;
            $newStatus = $request->has('status') ? $validated['status'] : visa_installment_plan::STATUS_PAID;

            // If status is already what we want to set it to
            if ($currentStatus === $newStatus) {
                $actionText = $newStatus === visa_installment_plan::STATUS_PAID ? 'paid' : 'pending';
                Flash::info('This installment is already marked as ' . $actionText . '.');
                return redirect()->back();
            }

            // Update the status
            $installment->status = $newStatus;
            $installment->updated_by = auth()->user()->id;
            $installment->save();

            DB::commit();

            $actionText = $newStatus === visa_installment_plan::STATUS_PAID ? 'paid' : 'pending';
            Flash::success('Installment marked as ' . $actionText . ' successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error processing status change: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function updateInstallmentField(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'installment_id' => 'required|exists:visa_installment_plans,id',
            'field' => 'required|in:date,billing_month,amount,narration',
            'value' => 'required',
            'update_subsequent' => 'nullable|in:true,false,1,0',
            'mark_as_paid' => 'nullable|boolean'
        ]);

        // Convert update_subsequent to boolean
        $validated['update_subsequent'] = in_array($request->input('update_subsequent'), ['true', '1', 1, true], true);
        $markAsPaid = in_array($request->input('mark_as_paid'), ['true', '1', 1, true], true);

        try {
            DB::beginTransaction();

            $installment = visa_installment_plan::findOrFail($validated['installment_id']);
            $isPaid = $installment->status === visa_installment_plan::STATUS_PAID;

            ['riderAccount' => $riderAccount, 'rider' => $rider] = $this->resolveInstallmentRiderContext($installment);
            $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                ->where('account_type', 'Liability')
                ->where('parent_id', 1)
                ->first();

            // Update the installment field
            $oldValue = $installment->{$validated['field']};

            if ($validated['field'] === 'billing_month') {
                $installment->billing_month = $validated['value'];
                // Check if billing month already contains day, if not add it
                $billingMonth = (strlen($validated['value']) <= 7) ? $validated['value'] . "-01" : $validated['value'];

                // Only update date for pending installments or if explicitly requested
                if (!$isPaid) {
                    // Update current installment date to 10th of next month from new billing month
                    $newBillingDate = Carbon::parse($validated['value'] . '-01');
                    $installment->date = $newBillingDate->addMonth()->day(10)->format('Y-m-d');
                }
                $installment->updated_by = auth()->user()->id;
                $installment->save();

                // Update subsequent installments if requested
                if ($validated['update_subsequent']) {
                    $this->updateSubsequentInstallments($installment, 'billing_month', $validated['value'], $rider);
                }
            } elseif ($validated['field'] === 'date') {
                $installment->date = $validated['value'];
                $installment->updated_by = auth()->user()->id;
                $installment->save();

                $billingMonth = $installment->billing_month;
                // Ensure billing month has proper format for voucher/transaction updates
                if (strlen($billingMonth) <= 7) {
                    $billingMonth = $billingMonth . "-01";
                }

                // Update subsequent installments if requested
                if ($validated['update_subsequent']) {
                    $this->updateSubsequentInstallments($installment, 'date', $validated['value'], $rider);
                }
            } elseif ($validated['field'] === 'amount') {
                $installment->amount = $validated['value'];
                $installment->updated_by = auth()->user()->id;
                $installment->save();

                $billingMonth = $installment->billing_month;
                // Ensure billing month has proper format for voucher/transaction updates
                if (strlen($billingMonth) <= 7) {
                    $billingMonth = $billingMonth . "-01";
                }

                // Update subsequent installments if requested and this is not a paid installment
                if ($validated['update_subsequent'] && !$isPaid) {
                    $this->recalculateInstallmentAmounts($installment, $validated['value'], $rider);
                }
            } elseif ($validated['field'] === 'narration') {
                $installment->narration = trim((string) $validated['value']);
                $installment->updated_by = auth()->user()->id;
                $installment->save();
            }

            // Update voucher (no narration column on vouchers — narration lives on transactions)
            $voucher = Vouchers::where('ref_id', $installment->id)
                ->where('voucher_type', 'VL')
                ->first();

            if ($voucher && in_array($validated['field'], ['billing_month', 'amount', 'date'], true)) {
                if ($validated['field'] === 'billing_month') {
                    $voucher->billing_month = $billingMonth;
                } elseif ($validated['field'] === 'amount') {
                    $voucher->amount = $validated['value'];
                } elseif ($validated['field'] === 'date') {
                    $voucher->trans_date = $validated['value'];
                }
                $voucher->updated_by = auth()->user()->id;
                $voucher->save();
            }

            // Update transactions
            $transactions = Transactions::where('reference_id', $installment->id)
                ->where('reference_type', 'VL')
                ->get();

            foreach ($transactions as $transaction) {
                if ($validated['field'] === 'billing_month') {
                    $transaction->billing_month = $billingMonth;
                    // Do not change narration here — only explicit "narration" edits update GL text.
                } elseif ($validated['field'] === 'amount') {
                    if ($transaction->credit > 0) {
                        $transaction->credit = $validated['value'];
                    } else {
                        $transaction->debit = $validated['value'];
                    }
                } elseif ($validated['field'] === 'date') {
                    $transaction->trans_date = $validated['value'];
                } elseif ($validated['field'] === 'narration') {
                    $transaction->narration = trim((string) $validated['value']);
                }
                $transaction->updated_at = now();
                $transaction->save();
            }

            // Update ledger entry if amount changed
            if ($validated['field'] === 'amount' && $liabilityAccount) {
                $ledgerEntry = \App\Support\CompanyQuery::table('ledger_entries')
                    ->where('account_id', $liabilityAccount->id)
                    ->where('billing_month', $billingMonth)
                    ->first();

                if ($ledgerEntry) {
                    $difference = $validated['value'] - $oldValue;
                    \App\Support\CompanyQuery::table('ledger_entries')
                        ->where('account_id', $liabilityAccount->id)
                        ->where('billing_month', $billingMonth)
                        ->update([
                            'debit_balance' => $validated['value'],
                            'closing_balance' => $ledgerEntry->opening_balance + $validated['value'],
                            'updated_at' => now(),
                        ]);
                }
            }

            // If this is a paid installment and we want to keep it paid
            if ($markAsPaid && !$isPaid) {
                $installment->status = visa_installment_plan::STATUS_PAID;
                $installment->save();
            }

            DB::commit();

            $message = ucfirst($validated['field']) . ' updated successfully with voucher and transactions.';
            if ($validated['update_subsequent']) {
                $message .= ' Subsequent installments were also updated accordingly.';
            }
            if ($markAsPaid && !$isPaid) {
                $message .= ' Installment marked as paid.';
            }

            Flash::success($message);
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error updating installment: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function finalizePayment(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'changes' => 'nullable|string',
            'deletions' => 'nullable|string',
            'additions' => 'nullable|string',
            'date_changes' => 'nullable|string',
            'billing_changes' => 'nullable|string',
        ]);

        $changes = $request->filled('changes') ? json_decode($validated['changes'], true) : [];
        $deletions = $request->filled('deletions') ? json_decode($validated['deletions'], true) : [];
        $additions = $request->filled('additions') ? json_decode($validated['additions'], true) : [];
        $dateChanges = $request->filled('date_changes') ? json_decode($validated['date_changes'], true) : [];
        $billingChanges = $request->filled('billing_changes') ? json_decode($validated['billing_changes'], true) : [];

        if ((!is_array($changes) || empty($changes)) &&
            (empty($deletions) || !is_array($deletions)) &&
            (empty($additions) || !is_array($additions)) &&
            (empty($dateChanges) || !is_array($dateChanges)) &&
            (empty($billingChanges) || !is_array($billingChanges))
        ) {
            \Flash::warning('Nothing to finalize.');
            return redirect()->back();
        }

        try {
            \DB::beginTransaction();

            $firstRiderId = null;
            $sumPendingVisibleDelta = 0.0;

            foreach ($changes as $installmentId => $newAmount) {
                if (!is_numeric($installmentId)) {
                    throw new \InvalidArgumentException('Invalid installment id provided.');
                }
                if (!is_numeric($newAmount) || $newAmount <= 0) {
                    throw new \InvalidArgumentException('Invalid amount provided for installment ' . $installmentId . '.');
                }

                /** @var \App\Models\visa_installment_plan $installment */
                $installment = visa_installment_plan::findOrFail($installmentId);

                // Allow editing paid installments - this check is removed

                $firstRiderId = $firstRiderId ?: $installment->rider_id;

                ['riderAccount' => $riderAccount, 'rider' => $rider] = $this->resolveInstallmentRiderContext($installment);
                $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                    ->where('account_type', 'Liability')
                    ->where('parent_id', 1)
                    ->first();

                // Existing values
                $oldAmount = (float) $installment->amount;

                // Update installment amount
                $installment->amount = (float) $newAmount;
                $installment->updated_by = auth()->user()->id;
                $installment->save();
                $sumPendingVisibleDelta += ((float)$newAmount - (float)$oldAmount);

                // Ensure billing month format for downstream updates
                $billingMonth = $installment->billing_month;
                if (strlen($billingMonth) <= 7) {
                    $billingMonth = $billingMonth . '-01';
                }

                // Update or create voucher
                $voucher = Vouchers::where('ref_id', $installment->id)
                    ->where('voucher_type', 'VL')
                    ->first();

                if ($voucher) {
                    $voucher->amount = (float) $newAmount;
                    $voucher->updated_by = auth()->user()->id;
                    $voucher->save();
                } else {
                    $trans_code = Account::trans_code();
                    $trans_date = Carbon::parse($installment->date ?? Carbon::today());

                    $voucher = Vouchers::create([
                        'rider_id' => $rider->id,
                        'trans_date' => $trans_date,
                        'trans_code' => $trans_code,
                        'billing_month' => $billingMonth,
                        'payment_type' => 1,
                        'voucher_type' => 'VL',
                        'remarks' => $rider->rider_id . ' - ' . $rider->name . ' - visa loan installment',
                        'amount' => (float) $newAmount,
                        'reference_number' => $installment->reference_number ?? null,
                        'Created_By' => auth()->user()->id,
                        'ref_id' => $installment->id,
                        'custom_field_values' => [],
                        'branch_id' => $rider->branch_id,
                    ]);

                    // Create transactions for the voucher
                    $TransactionService = new TransactionService();
                    $TransactionService->recordTransaction([
                        'account_id' => $liabilityAccount?->id,
                        'reference_id' => $installment->id,
                        'reference_type' => 'VL',
                        'trans_code' => $trans_code,
                        'trans_date' => $trans_date,
                        'narration' => $rider->rider_id . ' - ' . $rider->name . ' - deducting <b> installment </b> - ' . $installment->billing_month,
                        'debit' => (float) $newAmount,
                        'billing_month' => $billingMonth,
                        'created_by' => auth()->user()->id,
                        'branch_id' => $rider->branch_id,
                    ]);

                    $TransactionService->recordTransaction([
                        'account_id' => $riderAccount->id,
                        'reference_id' => $installment->id,
                        'reference_type' => 'VL',
                        'trans_code' => $trans_code,
                        'trans_date' => $trans_date,
                        'narration' => $rider->rider_id . ' - ' . $rider->name . ' - deducting <b> installment </b> - ' . $installment->billing_month,
                        'credit' => (float) $newAmount,
                        'billing_month' => $billingMonth,
                        'created_by' => auth()->user()->id,
                        'branch_id' => $rider->branch_id,
                    ]);
                }

                // Update transactions (if existed prior)
                $transactions = Transactions::where('reference_id', $installment->id)
                    ->where('reference_type', 'VL')
                    ->get();

                foreach ($transactions as $transaction) {
                    if ($transaction->credit > 0) {
                        $transaction->credit = (float) $newAmount;
                    } else {
                        $transaction->debit = (float) $newAmount;
                    }
                    $transaction->updated_at = now();
                    $transaction->save();
                }

                // Update or insert ledger entry for liability account
                if ($liabilityAccount) {
                    $ledgerEntry = \App\Support\CompanyQuery::table('ledger_entries')
                        ->where('account_id', $liabilityAccount->id)
                        ->where('billing_month', $billingMonth)
                        ->first();

                    if ($ledgerEntry) {
                        \App\Support\CompanyQuery::table('ledger_entries')
                            ->where('account_id', $liabilityAccount->id)
                            ->where('billing_month', $billingMonth)
                            ->update([
                                'debit_balance' => (float) $newAmount,
                                'closing_balance' => (float) $ledgerEntry->opening_balance + (float) $newAmount,
                                'updated_at' => now(),
                            ]);
                    } else {
                        $lastLedger = \App\Support\CompanyQuery::table('ledger_entries')
                            ->where('account_id', $liabilityAccount->id)
                            ->orderBy('billing_month', 'desc')
                            ->first();
                        $opening_balance = $lastLedger ? (float) $lastLedger->closing_balance : 0.00;
                        \App\Support\CompanyQuery::insert('ledger_entries', [
                            'account_id' => $liabilityAccount->id,
                            'billing_month' => $billingMonth,
                            'opening_balance' => $opening_balance,
                            'debit_balance' => (float) $newAmount,
                            'credit_balance' => 0.00,
                            'closing_balance' => $opening_balance + (float) $newAmount,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // Apply deletions if any
            if (is_array($deletions) && !empty($deletions)) {
                foreach ($deletions as $deleteId) {
                    /** @var \App\Models\visa_installment_plan $inst */
                    $inst = visa_installment_plan::findOrFail($deleteId);
                    if ($inst->status === visa_installment_plan::STATUS_PAID) {
                        throw new \RuntimeException('Cannot delete a paid installment (ID: ' . $deleteId . ').');
                    }

                    $firstRiderId = $firstRiderId ?: $inst->rider_id;

                    $installmentIdentifier = "Installment Plan #{$deleteId} - Billing Month: {$inst->billing_month} (Amount: " . number_format($inst->amount, 2) . ")";

                    // Get related vouchers before deletion (include soft deleted to be safe)
                    $relatedVouchers = Vouchers::withTrashed()
                        ->where('ref_id', $inst->id)
                        ->where('voucher_type', 'VL')
                        ->whereNull('deleted_at') // Only get non-deleted vouchers
                        ->get();

                    \Log::info("Found " . $relatedVouchers->count() . " vouchers to track for installment {$deleteId} in finalizePayment", [
                        'voucher_ids' => $relatedVouchers->pluck('id')->toArray()
                    ]);

                    // Get related transactions before deletion
                    $relatedTransactions = Transactions::where('reference_id', $inst->id)
                        ->where('reference_type', 'VL')
                        ->get();

                    // Track cascade deletions for vouchers BEFORE deletion
                    foreach ($relatedVouchers as $voucher) {
                        try {
                            \Log::info("Attempting to track cascade deletion for voucher {$voucher->id}", [
                                'primary_model' => visa_installment_plan::class,
                                'primary_id' => $inst->id,
                                'related_model' => Vouchers::class,
                                'related_id' => $voucher->id,
                            ]);

                            $cascadeRecord = $this->trackCascadeDeletion(
                                visa_installment_plan::class,
                                $inst->id,
                                $installmentIdentifier,
                                Vouchers::class,
                                $voucher->id,
                                "Voucher #{$voucher->id} ({$voucher->voucher_type}-" . str_pad($voucher->id, 4, '0', STR_PAD_LEFT) . ", Amount: " . number_format($voucher->amount, 2) . ")",
                                'hasMany',
                                'vouchers',
                                'soft',
                                'Cascade deletion from Installment Plan deletion via finalizePayment - voucher'
                            );

                            if ($cascadeRecord && $cascadeRecord->id) {
                                \Log::info("Cascade deletion tracked successfully for voucher {$voucher->id}, cascade record ID: {$cascadeRecord->id}");
                            } else {
                                \Log::warning("Cascade deletion tracking returned null for voucher {$voucher->id}");
                            }
                        } catch (\Exception $e) {
                            \Log::error("Failed to track cascade deletion for voucher {$voucher->id}: " . $e->getMessage(), [
                                'exception' => $e,
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }

                    // Track cascade deletions for transactions
                    foreach ($relatedTransactions as $transaction) {
                        try {
                            $this->trackCascadeDeletion(
                                visa_installment_plan::class,
                                $inst->id,
                                $installmentIdentifier,
                                Transactions::class,
                                $transaction->id,
                                "Transaction #{$transaction->id} (Trans Code: {$transaction->trans_code}, Amount: " . ($transaction->debit > 0 ? number_format($transaction->debit, 2) : number_format($transaction->credit, 2)) . ")",
                                'hasMany',
                                'transactions',
                                'soft',
                                'Cascade deletion from Installment Plan deletion via finalizePayment - transaction'
                            );
                        } catch (\Exception $e) {
                            \Log::error("Failed to track cascade deletion for transaction {$transaction->id}: " . $e->getMessage());
                        }
                    }

                    // Cascade delete vouchers related to this installment (soft delete with deleted_by)
                    // Use the same collection that was used for tracking
                    foreach ($relatedVouchers as $voucher) {
                        $voucher->deleted_by = auth()->id();
                        $voucher->save();
                        $voucher->delete();
                    }
                    Transactions::where('reference_id', $inst->id)
                        ->where('reference_type', 'VL')
                        ->delete();

                    // Delete related ledger entries for the liability account for that billing month
                    ['riderAccount' => $riderAccount] = $this->resolveInstallmentRiderContext($inst);
                    $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                        ->where('account_type', 'Liability')
                        ->where('parent_id', 1)
                        ->first();
                    if ($liabilityAccount) {
                        // Get ledger entry before deletion
                        $ledgerEntry = \App\Support\CompanyQuery::table('ledger_entries')
                            ->where('account_id', $liabilityAccount->id)
                            ->where('billing_month', $inst->billing_month)
                            ->first();

                        if ($ledgerEntry) {
                            try {
                                $this->trackCascadeDeletion(
                                    visa_installment_plan::class,
                                    $inst->id,
                                    $installmentIdentifier,
                                    \App\Models\LedgerEntry::class,
                                    $ledgerEntry->id,
                                    "Ledger Entry #{$ledgerEntry->id} (Account ID: {$liabilityAccount->id}, Billing Month: {$inst->billing_month})",
                                    'hasOne',
                                    'ledger_entry',
                                    'hard',
                                    'Cascade deletion from Installment Plan deletion via finalizePayment - ledger entry'
                                );
                            } catch (\Exception $e) {
                                \Log::error("Failed to track cascade deletion for ledger entry {$ledgerEntry->id}: " . $e->getMessage());
                            }
                        }

                        \App\Support\CompanyQuery::table('ledger_entries')
                            ->where('account_id', $liabilityAccount->id)
                            ->where('billing_month', $inst->billing_month)
                            ->delete();
                    }

                    // Delete the installment (soft delete with deleted_by)
                    $inst->deleted_by = auth()->id();
                    $inst->save();
                    $inst->delete();
                }
            }

            // Create additions if any
            if (is_array($additions) && !empty($additions)) {
                // Determine context from any existing installment
                $contextInstallment = null;
                if (!empty($changes)) {
                    $firstChangeId = array_key_first($changes);
                    $contextInstallment = visa_installment_plan::find($firstChangeId);
                }
                if (!$contextInstallment && !empty($deletions)) {
                    $contextInstallment = visa_installment_plan::find($deletions[0]);
                }
                if (!$contextInstallment) {
                    $contextInstallment = visa_installment_plan::first();
                }
                if (!$contextInstallment) {
                    throw new \RuntimeException('Unable to determine rider for new installment.');
                }

                $firstRiderId = $firstRiderId ?: $contextInstallment->rider_id;
                ['riderAccount' => $riderAccount, 'rider' => $rider] = $this->resolveInstallmentRiderContext($contextInstallment);
                $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                    ->where('account_type', 'Liability')
                    ->where('parent_id', 1)
                    ->first();
                $existingTotalAmount = (float) (visa_installment_plan::where('rider_id', $contextInstallment->rider_id)->value('total_amount') ?? 0);

                foreach ($additions as $addition) {
                    $amount = isset($addition['amount']) ? (float) $addition['amount'] : null;
                    $bm = $addition['billing_month'] ?? null; // 'YYYY-MM'
                    $date = $addition['date'] ?? null; // 'YYYY-MM-DD'
                    if (!$amount || $amount <= 0 || !$bm) {
                        throw new \InvalidArgumentException('Invalid addition payload.');
                    }
                    if (!$date) {
                        $date = Carbon::parse($bm . '-01')->copy()->addMonth()->day(10)->format('Y-m-d');
                    }

                    $installment = visa_installment_plan::create([
                        'rider_id' => $contextInstallment->rider_id,
                        'billing_month' => $bm,
                        'amount' => $amount,
                        'total_amount' => $existingTotalAmount,
                        'reference_number' => $contextInstallment->reference_number ?? null,
                        'narration' => $rider->rider_id . ' - ' . $rider->name . ' - installment - ' . $bm,
                        'status' => visa_installment_plan::STATUS_PENDING,
                        'date' => $date,
                        'created_by' => auth()->user()->id,
                    ]);

                    // Create voucher and transactions for the new installment
                    $trans_code = Account::trans_code();
                    $trans_date = Carbon::parse($date);
                    $billingMonthFull = strlen($bm) <= 7 ? $bm . '-01' : $bm;

                    Vouchers::create([
                        'rider_id' => $rider->id,
                        'trans_date' => $trans_date,
                        'trans_code' => $trans_code,
                        'billing_month' => $billingMonthFull,
                        'payment_type' => 1,
                        'voucher_type' => 'VL',
                        'remarks' => $rider->rider_id . ' - ' . $rider->name . ' - visa loan installment (new)',
                        'amount' => $amount,
                        'reference_number' => $installment->reference_number ?? null,
                        'Created_By' => auth()->user()->id,
                        'ref_id' => $installment->id,
                        'custom_field_values' => [],
                        'branch_id' => $rider->branch_id,
                    ]);

                    $TransactionService = new TransactionService();
                    if ($liabilityAccount) {
                        $TransactionService->recordTransaction([
                            'account_id' => $liabilityAccount->id,
                            'reference_id' => $installment->id,
                            'reference_type' => 'VL',
                            'trans_code' => $trans_code,
                            'trans_date' => $trans_date,
                            'narration' => $rider->rider_id . ' - ' . $rider->name . ' - deducting installment - ' . $bm,
                            'debit' => $amount,
                            'billing_month' => $billingMonthFull,
                            'created_by' => auth()->user()->id,
                            'branch_id' => $rider->branch_id,
                        ]);

                        // Update or insert ledger entry for liability
                        $ledgerEntry = \App\Support\CompanyQuery::table('ledger_entries')
                            ->where('account_id', $liabilityAccount->id)
                            ->where('billing_month', $billingMonthFull)
                            ->first();
                        if ($ledgerEntry) {
                            \App\Support\CompanyQuery::table('ledger_entries')
                                ->where('account_id', $liabilityAccount->id)
                                ->where('billing_month', $billingMonthFull)
                                ->update([
                                    'debit_balance' => (float) $amount,
                                    'closing_balance' => (float) $ledgerEntry->opening_balance + (float) $amount,
                                    'updated_at' => now(),
                                ]);
                        } else {
                            $lastLedger = \App\Support\CompanyQuery::table('ledger_entries')
                                ->where('account_id', $liabilityAccount->id)
                                ->orderBy('billing_month', 'desc')
                                ->first();
                            $opening_balance = $lastLedger ? (float) $lastLedger->closing_balance : 0.00;
                            \App\Support\CompanyQuery::insert('ledger_entries', [
                                'account_id' => $liabilityAccount->id,
                                'billing_month' => $billingMonthFull,
                                'opening_balance' => $opening_balance,
                                'debit_balance' => (float) $amount,
                                'credit_balance' => 0.00,
                                'closing_balance' => $opening_balance + (float) $amount,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    // Credit rider account
                    $TransactionService->recordTransaction([
                        'account_id' => $riderAccount->id,
                        'reference_id' => $installment->id,
                        'reference_type' => 'VL',
                        'trans_code' => $trans_code,
                        'trans_date' => $trans_date,
                        'narration' => $rider->rider_id . ' - ' . $rider->name . ' - deducting installment - ' . $bm,
                        'credit' => $amount,
                        'billing_month' => $billingMonthFull,
                        'created_by' => auth()->user()->id,
                        'branch_id' => $rider->branch_id,
                    ]);
                }
            }

            // Server-side validation: paid + pending must equal required total
            if ($firstRiderId) {
                $totalAmount = (float) visa_installment_plan::where('rider_id', $firstRiderId)->value('total_amount') ?? 0.0;
                $paidTotal = (float) visa_installment_plan::where('rider_id', $firstRiderId)->where('status', 'paid')->sum('amount');
                $pendingTotal = (float) visa_installment_plan::where('rider_id', $firstRiderId)->where('status', 'pending')->sum('amount');
                $combined = $paidTotal + $pendingTotal;
                if (abs($totalAmount - $combined) > 0.009) {
                    throw new \RuntimeException('Totals mismatch after finalize. Please adjust amounts to match the required total.');
                }
            }

            // Process date changes if any
            if (is_array($dateChanges) && !empty($dateChanges)) {
                foreach ($dateChanges as $installmentId => $newDate) {
                    $installment = visa_installment_plan::findOrFail($installmentId);
                    $installment->date = $newDate;
                    $installment->updated_by = auth()->user()->id;
                    $installment->save();

                    // Update related voucher
                    $voucher = Vouchers::where('ref_id', $installment->id)
                        ->where('voucher_type', 'VL')
                        ->first();
                    if ($voucher) {
                        $voucher->trans_date = $newDate;
                        $voucher->updated_by = auth()->user()->id;
                        $voucher->save();
                    }

                    // Update related transactions
                    $transactions = Transactions::where('reference_id', $installment->id)
                        ->where('reference_type', 'VL')
                        ->get();
                    foreach ($transactions as $transaction) {
                        $transaction->trans_date = $newDate;
                        $transaction->updated_at = now();
                        $transaction->save();
                    }
                }
            }

            // Process billing month changes if any
            if (is_array($billingChanges) && !empty($billingChanges)) {
                foreach ($billingChanges as $installmentId => $newBillingMonth) {
                    $installment = visa_installment_plan::findOrFail($installmentId);
                    $installment->billing_month = $newBillingMonth;
                    $installment->updated_by = auth()->user()->id;
                    $installment->save();

                    // Check if billing month already contains day, if not add it
                    $billingMonthWithDay = (strlen($newBillingMonth) <= 7) ? $newBillingMonth . "-01" : $newBillingMonth;

                    // Update related voucher
                    $voucher = Vouchers::where('ref_id', $installment->id)
                        ->where('voucher_type', 'VL')
                        ->first();
                    if ($voucher) {
                        $voucher->billing_month = $billingMonthWithDay;
                        $voucher->updated_by = auth()->user()->id;
                        $voucher->save();
                    }

                    // Update related transactions
                    $transactions = Transactions::where('reference_id', $installment->id)
                        ->where('reference_type', 'VL')
                        ->get();
                    foreach ($transactions as $transaction) {
                        $transaction->billing_month = $billingMonthWithDay;
                        $transaction->updated_at = now();
                        $transaction->save();
                    }
                }
            }

            \DB::commit();

            \Flash::success('Payment finalized. All changes saved and vouchers updated successfully.');
            if ($firstRiderId) {
                return redirect()->route('VisaExpense.installmentPlan', $firstRiderId);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            \DB::rollBack();
            \Flash::error('Error finalizing payment: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function deleteInstallment($id)
    {
        if (!auth()->user()->hasPermissionTo('visaexpense_delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $installment = visa_installment_plan::findOrFail($id);

            $installmentIdentifier = "Installment Plan #{$id} - Billing Month: {$installment->billing_month} (Amount: " . number_format($installment->amount, 2) . ")";

            // Get related vouchers before deletion (only non-deleted vouchers)
            $relatedVouchers = Vouchers::where('ref_id', $installment->id)
                ->where('voucher_type', 'VL')
                ->get();

            \Log::info("Found " . $relatedVouchers->count() . " vouchers to delete for installment {$id}", [
                'voucher_ids' => $relatedVouchers->pluck('id')->toArray(),
                'installment_id' => $installment->id
            ]);

            // Get related transactions before deletion
            $relatedTransactions = Transactions::where('reference_id', $installment->id)
                ->where('reference_type', 'VL')
                ->get();

            // Track cascade deletions for vouchers BEFORE deletion
            foreach ($relatedVouchers as $voucher) {
                try {
                    \Log::info("Attempting to track cascade deletion for voucher {$voucher->id}", [
                        'primary_model' => visa_installment_plan::class,
                        'primary_id' => $installment->id,
                        'related_model' => Vouchers::class,
                        'related_id' => $voucher->id,
                    ]);

                    $cascadeRecord = $this->trackCascadeDeletion(
                        visa_installment_plan::class,
                        $installment->id,
                        $installmentIdentifier,
                        Vouchers::class,
                        $voucher->id,
                        "Voucher #{$voucher->id} ({$voucher->voucher_type}-" . str_pad($voucher->id, 4, '0', STR_PAD_LEFT) . ", Amount: " . number_format($voucher->amount, 2) . ")",
                        'hasMany',
                        'vouchers',
                        'soft',
                        'Cascade deletion from Installment Plan deletion - voucher'
                    );

                    if ($cascadeRecord && $cascadeRecord->id) {
                        \Log::info("Cascade deletion tracked successfully for voucher {$voucher->id}, cascade record ID: {$cascadeRecord->id}");
                    } else {
                        \Log::warning("Cascade deletion tracking returned null for voucher {$voucher->id}");
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to track cascade deletion for voucher {$voucher->id}: " . $e->getMessage(), [
                        'exception' => $e,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Track cascade deletions for transactions
            foreach ($relatedTransactions as $transaction) {
                try {
                    $this->trackCascadeDeletion(
                        visa_installment_plan::class,
                        $installment->id,
                        $installmentIdentifier,
                        Transactions::class,
                        $transaction->id,
                        "Transaction #{$transaction->id} (Trans Code: {$transaction->trans_code}, Amount: " . ($transaction->debit > 0 ? number_format($transaction->debit, 2) : number_format($transaction->credit, 2)) . ")",
                        'hasMany',
                        'transactions',
                        'soft',
                        'Cascade deletion from Installment Plan deletion - transaction'
                    );
                } catch (\Exception $e) {
                    \Log::error("Failed to track cascade deletion for transaction {$transaction->id}: " . $e->getMessage());
                }
            }

            // Cascade delete vouchers related to this installment (soft delete with deleted_by)
            // Note: Using delete() performs soft deletion since Vouchers model uses SoftDeletes trait
            if ($relatedVouchers->count() > 0) {
                foreach ($relatedVouchers as $voucher) {
                    try {
                        \Log::info("Soft deleting voucher {$voucher->id} for installment {$installment->id}");
                        $voucher->deleted_by = auth()->id();
                        $voucher->save();
                        // Soft delete: sets deleted_at timestamp (does not permanently delete from database)
                        $voucher->delete();
                        \Log::info("Successfully soft deleted voucher {$voucher->id}");
                    } catch (\Exception $e) {
                        \Log::error("Failed to delete voucher {$voucher->id}: " . $e->getMessage(), [
                            'exception' => $e,
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e; // Re-throw to trigger rollback
                    }
                }
            } else {
                \Log::warning("No vouchers found to delete for installment {$installment->id}");
            }

            // Double-check: Ensure all remaining vouchers are deleted (as a safety measure)
            $remainingVouchers = Vouchers::where('ref_id', $installment->id)
                ->where('voucher_type', 'VL')
                ->whereNull('deleted_at')
                ->get();

            if ($remainingVouchers->count() > 0) {
                \Log::warning("Found {$remainingVouchers->count()} remaining vouchers after deletion attempt, soft deleting them now");
                foreach ($remainingVouchers as $voucher) {
                    try {
                        $voucher->deleted_by = auth()->id();
                        $voucher->save();
                        // Soft delete: sets deleted_at timestamp (does not permanently delete from database)
                        $voucher->delete();
                    } catch (\Exception $e) {
                        \Log::error("Failed to delete remaining voucher {$voucher->id}: " . $e->getMessage());
                        throw $e;
                    }
                }
            }

            // Delete related transactions
            Transactions::where('reference_id', $installment->id)
                ->where('reference_type', 'VL')
                ->delete();

            // Delete related ledger entries
            ['riderAccount' => $riderAccount] = $this->resolveInstallmentRiderContext($installment);
            $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                ->where('account_type', 'Liability')
                ->where('parent_id', 1)
                ->first();

            if ($liabilityAccount) {
                // Convert billing_month to proper date format for ledger_entries comparison
                // billing_month in installment is stored as 'YYYY-MM', but ledger_entries expects 'YYYY-MM-DD'
                $billingMonthForLedger = (strlen($installment->billing_month) <= 7) ?
                    $installment->billing_month . '-01' : $installment->billing_month;

                // Get ledger entry before deletion
                $ledgerEntry = \App\Support\CompanyQuery::table('ledger_entries')
                    ->where('account_id', $liabilityAccount->id)
                    ->where('billing_month', $billingMonthForLedger)
                    ->first();

                if ($ledgerEntry) {
                    try {
                        $this->trackCascadeDeletion(
                            visa_installment_plan::class,
                            $installment->id,
                            $installmentIdentifier,
                            \App\Models\LedgerEntry::class,
                            $ledgerEntry->id,
                            "Ledger Entry #{$ledgerEntry->id} (Account ID: {$liabilityAccount->id}, Billing Month: {$installment->billing_month})",
                            'hasOne',
                            'ledger_entry',
                            'hard',
                            'Cascade deletion from Installment Plan deletion - ledger entry'
                        );
                    } catch (\Exception $e) {
                        \Log::error("Failed to track cascade deletion for ledger entry {$ledgerEntry->id}: " . $e->getMessage());
                    }
                }

                \App\Support\CompanyQuery::table('ledger_entries')
                    ->where('account_id', $liabilityAccount->id)
                    ->where('billing_month', $billingMonthForLedger)
                    ->delete();
            }

            // Delete the installment (soft delete with deleted_by)
            $installment->deleted_by = auth()->id();
            $installment->save();
            $installment->delete();

            DB::commit();

            Flash::success('Installment deleted successfully along with voucher and transactions.');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error deleting installment: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function generateInstallmentInvoice($company_slug, $riderId)
    {
        if (!auth()->user()->hasPermissionTo('visaexpense_view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $account = null;
            $rider = null;
            $installments = collect();

            $expenseAccount = ExpenseAccount::with('rider')->find($riderId);
            if ($expenseAccount) {
                $account = $expenseAccount;
                $rider = $expenseAccount->rider ?? Riders::findOrFail($expenseAccount->rider_id);
                $installments = visa_installment_plan::where('rider_id', $expenseAccount->id)
                    ->orderBy('billing_month', 'asc')
                    ->get();
                if ($installments->isEmpty()) {
                    $installments = visa_installment_plan::where('rider_id', $expenseAccount->rider_id)
                        ->orderBy('billing_month', 'asc')
                        ->get();
                }
            }

            if ($installments->isEmpty() && ($ledgerAccount = Accounts::find($riderId))) {
                $account = $ledgerAccount;
                $rider = Riders::findOrFail($ledgerAccount->ref_id);
                $installments = visa_installment_plan::where('rider_id', $riderId)
                    ->orderBy('billing_month', 'asc')
                    ->get();
            }

            if ($installments->isEmpty()) {
                $rider = Riders::findOrFail($riderId);
                $installments = visa_installment_plan::where('rider_id', $riderId)
                    ->orderBy('billing_month', 'asc')
                    ->get();
            }

            if ($installments->isEmpty()) {
                $msg = 'No installment plans found for this rider.';
                if (request()->ajax()) {
                    return response('<div class="alert alert-warning m-3 mb-0">' . e($msg) . '</div>', 200);
                }
                Flash::error($msg);
                return redirect()->back();
            }

            $rider->loadMissing(['vendor', 'sim']);

            if (request()->ajax()) {
                return view('visa_expenses.installmentInvoice_ajax', compact('rider', 'installments', 'account'));
            }

            return view('visa_expenses.installmentInvoice', compact('rider', 'installments', 'account'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response(
                    '<div class="alert alert-danger m-3 mb-0">' . e('Error generating invoice: ' . $e->getMessage()) . '</div>',
                    200
                );
            }
            Flash::error('Error generating invoice: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function payfine(Request $request)
    {
        DB::beginTransaction();

        try {
            $expenseAccount = ExpenseAccount::where('rider_id', $request->rider_id)->first();
            $expense = visa_expenses::findOrFail($request->id);
            $expense->pay_account = $request->account;

            if ($expense->payment_status == 'paid') {
                $expense->payment_status = 'unpaid';
                $expense->expiry_date = null;
            } else {
                $request->validate([
                    'expiry_date' => 'required|date',
                ]);
                $expense->payment_status = 'paid';
                $expense->expiry_date = $request->expiry_date;
                $payment_type_flag = match ($request->payment_type) {
                    'Liability' => 1,
                    'Asset' => 0,
                    default => null,
                };
                $photo = $request->file('attach_file');
                $docFile = $photo->store('vouchers', 'public');
                $remarks = $request->voucher_type === 'LV' ? 'Visa Expense Voucher' : 'Journal Voucher';

                $trans_code = Account::trans_code();
                $TransactionService = new TransactionService();

                $billingMonth = $expense->billing_month ?? date('Y-m-01');
                $transDate = $expense->trans_date;

                // 1. Fine Amount
                if ($expense->amount > 0) {
                    // Debit RTA Account
                    $TransactionService->recordTransaction([
                        'account_id'     => HeadAccount::VISA_EXPENSE_ACCOUNT,
                        'reference_id'   => $expense->id,
                        'reference_type' => 'LV',
                        'trans_code'     => $trans_code,
                        'trans_date'     => $transDate,
                        'narration'      => $expense->detail ?? 'Viss Expense Payment',
                        'debit'          => $expense->amount,
                        'billing_month'  => $billingMonth,
                        'branch_id'       => $expense->branch_id,
                    ]);
                }
                if ($expense->amount > 0) {
                    // Credit Selected Payment Account
                    $TransactionService->recordTransaction([
                        'account_id'     => $request->account,
                        'reference_id'   => $expense->id,
                        'reference_type' => 'LV',
                        'trans_code'     => $trans_code,
                        'trans_date'     => $transDate,
                        'narration'      => $expense->detail ?? 'Visa Expense Payment',
                        'credit'         => $expense->amount,
                        'billing_month'  => $billingMonth,
                        'branch_id'       => $expense->branch_id,
                    ]);
                }
                Vouchers::create([
                    'branch_id'       => $expense->branch_id,
                    'trans_date'    => $transDate,
                    'trans_code'    => $trans_code,
                    'trip_date'     => $request->trip_date,
                    'billing_month' => $billingMonth,
                    'payment_type'  => $payment_type_flag,
                    'voucher_type'  => $request->voucher_type,
                    'remarks'       => $remarks,
                    'amount'        => $expense->amount,
                    'reference_number' => $expense->reference_number ?? null,
                    'Created_By'    => $request->Created_By,
                    'attach_file'   => $docFile,
                    'pay_account'   => $request->account,
                    'ref_id'        => $expense->id,
                    'custom_field_values' => $request->input('voucher_custom_fields', []),
                ]);

                // 5. Ledger Entry (Against Payment Account)
                $total_amount = floatval($expense->amount);
                $lastLedger = CompanyQuery::table('ledger_entries')
                    ->where('account_id', $request->account)
                    ->orderBy('billing_month', 'desc')
                    ->first();

                $opening_balance = $lastLedger ? $lastLedger->closing_balance : 0.00;
                $debit_balance = $credit_balance = 0.00;

                if ($payment_type_flag === 1) { // Liability
                    $debit_balance = $total_amount;
                    $closing_balance = $opening_balance + $total_amount;
                } elseif ($payment_type_flag === 0) { // Asset
                    $credit_balance = $total_amount;
                    $closing_balance = $opening_balance - $total_amount;
                } else {
                    $closing_balance = $opening_balance;
                }

                CompanyQuery::table('ledger_entries')->insert([
                    'account_id'      => $request->account,
                    'billing_month'   => $billingMonth,
                    'opening_balance' => $opening_balance,
                    'debit_balance'   => $debit_balance,
                    'credit_balance'  => $credit_balance,
                    'closing_balance' => $closing_balance,
                    'branch_id'       => $expense->branch_id,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            $expense->save();
            DB::commit();
            Flash::success('Visa Expense Paid Successfully with Transaction and Ledger Entries.');
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());
        }

        return redirect(route('VisaExpense.generatentries', $expenseAccount->id));
    }
    /**
     * Display the specified resource.
     */
    public function show(string $company_slug, string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $company_slug, string $id)
    {
        $visaExpenses = visa_expenses::find($id);
        $data = ExpenseAccount::where('id', $visaExpenses->expense_account_id ?? $visaExpenses->rider_id)->first();
        if (empty($visaExpenses)) {
            Flash::error('Visa Expenses not found');

            return redirect(route('visaExpenses.index'));
        }
        $visaStatuses = VisaStatus::orderBy('display_order', 'asc')->where('is_active', 1)->get();
        return view('visa_expenses.edit', compact('data', 'visaExpenses', 'visaStatuses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        DB::beginTransaction();

        try {
            $visaExpenses = visa_expenses::findOrFail($request->id);
            $oldAmount = $visaExpenses->amount;
            $billingMonth = $request->billing_month . "-01";

            $request->validate([
                'reference_number' => 'required|string|max:255',
            ]);

            $visaExpenses->visa_status = $request->visa_status;
            $visaExpenses->billing_month = $billingMonth;
            $visaExpenses->date = $request->date;
            $visaExpenses->amount = $request->amount;
            $visaExpenses->detail = $request->detail;
            $visaExpenses->reference_number = $request->reference_number;
            $visaExpenses->save();

            // If this visa expense is already paid, update related voucher and transactions amounts
            if ($visaExpenses->payment_status == 'paid') {
                // Update voucher amount(s) linked to this visa expense
                $vouchers = Vouchers::where('ref_id', $visaExpenses->id)
                    ->where('voucher_type', 'LV')
                    ->first();
                if ($vouchers) {
                    $vouchers->reference_number = $visaExpenses->reference_number;
                    $vouchers->amount = $visaExpenses->amount;
                    $vouchers->save();
                }

                // Update related transactions amounts (both debit and credit sides)
                $transactions = Transactions::where('reference_id', $visaExpenses->id)
                    ->where('reference_type', 'LV')
                    ->get();

                foreach ($transactions as $transaction) {
                    if ($transaction->debit > 0) {
                        $transaction->debit = $visaExpenses->amount;
                    }
                    if ($transaction->credit > 0) {
                        $transaction->credit = $visaExpenses->amount;
                    }
                    $transaction->save();
                }
            }

            DB::commit();
            Flash::success('Visa Expense updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error updating Visa Expense: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function inlineUpdate(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'id' => 'required|exists:visa_expenses,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'billing_month' => 'required|date_format:Y-m',
        ]);

        $row = visa_expenses::findOrFail($validated['id']);
        $row->amount = $validated['amount'];
        $row->date = $validated['date'];
        $row->billing_month = $validated['billing_month'] . '-01';
        $row->save();

        return response()->json([
            'success' => true,
            'message' => 'Visa expense updated.',
            'amount' => number_format((float) $row->amount, 2),
            'date' => Carbon::parse($row->date)->format('Y-m-d'),
            'billing_month' => Carbon::parse($row->billing_month)->format('Y-m'),
        ]);
    }

    /**
     * Modal form: change LV voucher credit (payment) account only; debit stays visa expense head.
     */
    public function editVoucherCreditForm(Request $request, $company_slug, $visaExpense)
    {
        if (!auth()->user()->hasPermissionTo('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $expense = visa_expenses::with('vouchers')->findOrFail($visaExpense);

        if ($expense->payment_status !== 'paid') {
            return response(
                '<div class="alert alert-warning m-2 mb-0">Only paid expenses have a payment voucher to edit.</div>',
                200
            );
        }

        $voucher = $expense->vouchers->first();
        if (!$voucher) {
            $voucher = Vouchers::where('ref_id', $expense->id)->where('voucher_type', 'LV')->first();
        }
        if (!$voucher) {
            return response(
                '<div class="alert alert-warning m-2 mb-0">No LV voucher found for this expense.</div>',
                200
            );
        }

        $creditTx = Transactions::where('trans_code', $voucher->trans_code)
            ->where('credit', '>', 0)
            ->orderBy('id')
            ->first();

        if (!$creditTx) {
            return response(
                '<div class="alert alert-danger m-2 mb-0">Could not find credit side transaction for this voucher.</div>',
                200
            );
        }

        $debitAccountName = Accounts::where('id', HeadAccount::VISA_EXPENSE_ACCOUNT)->value('name') ?? 'Visa expense';
        $currentCreditName = Accounts::where('id', $creditTx->account_id)->value('name') ?? ('#' . $creditTx->account_id);

        $paymentAccounts = $this->visaExpensePaymentAccountOptions();
        $currentId = (int) $creditTx->account_id;
        if ($paymentAccounts->isEmpty()) {
            $paymentAccounts = Accounts::bankAndCashDropdown()
                ->filter(static fn($label, $id) => $id !== '' && $id !== null);
        }
        if (!$paymentAccounts->has($currentId)) {
            $nm = Accounts::where('id', $currentId)->value('name');
            if ($nm) {
                $paymentAccounts->put($currentId, $nm . ' (current)');
            }
        }

        $voucher->loadMissing(['transactions.account']);

        return view('visa_expenses.edit_voucher_credit', [
            'expense' => $expense,
            'voucher' => $voucher,
            'creditTransaction' => $creditTx,
            'debitAccountName' => $debitAccountName,
            'currentCreditName' => $currentCreditName,
            'paymentAccounts' => $paymentAccounts,
            'editDeleteFlags' => VoucherType::getEditDeleteFlagsByModule('vouchers'),
        ]);
    }

    /**
     * Apply new credit account on voucher transactions + ledger rollup for old/new accounts.
     */
    public function updateVoucherCredit(Request $request, $company_slug)
    {
        if (!auth()->user()->hasPermissionTo('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'visa_expense_id' => 'required|exists:visa_expenses,id',
            'credit_account_id' => 'required|exists:accounts,id',
        ]);

        $expense = visa_expenses::findOrFail($validated['visa_expense_id']);

        if ($expense->payment_status !== 'paid') {
            Flash::error('Only paid visa expenses can update the payment account.');
            return redirect()->back();
        }

        $newAccountId = (int) $validated['credit_account_id'];
        if ($newAccountId === (int) HeadAccount::VISA_EXPENSE_ACCOUNT) {
            Flash::error('Cannot use the visa expense account as the payment (credit) side.');
            return redirect()->back();
        }

        $voucher = Vouchers::where('ref_id', $expense->id)->where('voucher_type', 'LV')->first();
        if (!$voucher) {
            Flash::error('No voucher found for this expense.');
            return redirect()->back();
        }

        $creditTx = Transactions::where('trans_code', $voucher->trans_code)
            ->where('credit', '>', 0)
            ->orderBy('id')
            ->first();

        if (!$creditTx) {
            Flash::error('Credit transaction not found.');
            return redirect()->back();
        }

        $oldAccountId = (int) $creditTx->account_id;
        if ($oldAccountId === $newAccountId) {
            Flash::info('Payment account unchanged.');
            return redirect()->back();
        }

        $billingMonth = $creditTx->billing_month ?? $expense->billing_month;

        try {
            DB::beginTransaction();

            $creditTx->account_id = $newAccountId;
            $creditTx->updated_at = now();
            $creditTx->save();

            $expense->pay_account = (string) $newAccountId;
            $expense->save();

            if (Schema::hasColumn('vouchers', 'pay_account')) {
                DB::table('vouchers')->where('id', $voucher->id)->update([
                    'pay_account' => $newAccountId,
                    'Updated_By' => auth()->id(),
                    'updated_at' => now(),
                ]);
            }

            $this->recalculateLedgerAfterDeletion($oldAccountId, $billingMonth);
            $this->recalculateLedgerAfterDeletion($newAccountId, $billingMonth);

            DB::commit();
            Flash::success('Payment (credit) account updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            Flash::error('Could not update payment account: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Cash/bank style accounts used when marking visa expense paid (same pool as pay flow).
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function visaExpensePaymentAccountOptions()
    {
        $bank = Accounts::where('name', 'cash & bank')->first();
        if (!$bank) {
            return collect();
        }

        return Accounts::query()
            ->where('status', 1)
            ->where('parent_id', $bank->id)
            ->orderBy('id')
            ->pluck('name', 'id');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($company_slug, string $id)
    {
        $visaExpenses = visa_expenses::find($id);

        if (empty($visaExpenses)) {
            Flash::error('Visa Expense Entry not found');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $billingMonth = $visaExpenses->billing_month;
            $riderAccountId = \App\Support\CompanyQuery::table('accounts')->where('ref_id', $visaExpenses->rider_id)->value('id');
            $visaExpenseIdentifier = "Visa Expense #{$id} - {$visaExpenses->visa_status} (Amount: " . number_format($visaExpenses->amount, 2) . ")";

            // Get related transactions before deletion
            $relatedTransactions = Transactions::where('reference_id', $visaExpenses->id)
                ->where('reference_type', 'LV')
                ->get();

            // Get related transactions by trans_code
            $transCodeTransactions = Transactions::where('trans_code', $visaExpenses->trans_code)
                ->where('reference_type', 'LV')
                ->get();

            // Get related vouchers before deletion (include soft deleted to be safe)
            $relatedVouchers = Vouchers::withTrashed()
                ->where('ref_id', $visaExpenses->id)
                ->where('voucher_type', 'LV')
                ->whereNull('deleted_at') // Only get non-deleted vouchers
                ->get();

            \Log::info("Found " . $relatedVouchers->count() . " vouchers to track for visa expense {$id}", [
                'voucher_ids' => $relatedVouchers->pluck('id')->toArray()
            ]);

            // Track cascade deletions for transactions
            foreach ($relatedTransactions as $transaction) {
                try {
                    $this->trackCascadeDeletion(
                        visa_expenses::class,
                        $visaExpenses->id,
                        $visaExpenseIdentifier,
                        Transactions::class,
                        $transaction->id,
                        "Transaction #{$transaction->id} (Trans Code: {$transaction->trans_code}, Amount: " . ($transaction->debit > 0 ? number_format($transaction->debit, 2) : number_format($transaction->credit, 2)) . ")",
                        'hasMany',
                        'transactions',
                        'soft',
                        'Cascade deletion from Visa Expense deletion - transaction by reference_id'
                    );
                } catch (\Exception $e) {
                    \Log::error("Failed to track cascade deletion for transaction {$transaction->id}: " . $e->getMessage());
                }
            }

            // Track cascade deletions for transactions by trans_code
            foreach ($transCodeTransactions as $transaction) {
                // Skip if already tracked
                if ($relatedTransactions->contains('id', $transaction->id)) {
                    continue;
                }
                try {
                    $this->trackCascadeDeletion(
                        visa_expenses::class,
                        $visaExpenses->id,
                        $visaExpenseIdentifier,
                        Transactions::class,
                        $transaction->id,
                        "Transaction #{$transaction->id} (Trans Code: {$transaction->trans_code}, Amount: " . ($transaction->debit > 0 ? number_format($transaction->debit, 2) : number_format($transaction->credit, 2)) . ")",
                        'hasMany',
                        'transactions',
                        'soft',
                        'Cascade deletion from Visa Expense deletion - transaction by trans_code'
                    );
                } catch (\Exception $e) {
                    \Log::error("Failed to track cascade deletion for transaction {$transaction->id}: " . $e->getMessage());
                }
            }

            // Track cascade deletions for vouchers BEFORE deletion
            foreach ($relatedVouchers as $voucher) {
                try {
                    \Log::info("Attempting to track cascade deletion for voucher {$voucher->id}", [
                        'primary_model' => visa_expenses::class,
                        'primary_id' => $visaExpenses->id,
                        'related_model' => Vouchers::class,
                        'related_id' => $voucher->id,
                    ]);

                    $cascadeRecord = $this->trackCascadeDeletion(
                        visa_expenses::class,
                        $visaExpenses->id,
                        $visaExpenseIdentifier,
                        Vouchers::class,
                        $voucher->id,
                        "Voucher #{$voucher->id} ({$voucher->voucher_type}-" . str_pad($voucher->id, 4, '0', STR_PAD_LEFT) . ", Amount: " . number_format($voucher->amount, 2) . ")",
                        'hasMany',
                        'vouchers',
                        'soft',
                        'Cascade deletion from Visa Expense deletion - voucher'
                    );

                    if ($cascadeRecord && $cascadeRecord->id) {
                        \Log::info("Cascade deletion tracked successfully for voucher {$voucher->id}, cascade record ID: {$cascadeRecord->id}");
                    } else {
                        \Log::warning("Cascade deletion tracking returned null for voucher {$voucher->id}");
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to track cascade deletion for voucher {$voucher->id}: " . $e->getMessage(), [
                        'exception' => $e,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Delete only specific transactions related to this visa expense
            Transactions::where('reference_id', $visaExpenses->id)
                ->where('reference_type', 'LV')
                ->delete();

            // Delete transactions by trans_code if they exist
            Transactions::where('trans_code', $visaExpenses->trans_code)
                ->where('reference_type', 'LV')
                ->delete();

            // Cascade delete vouchers related to this visa expense (soft delete with deleted_by)
            // Use the same collection that was used for tracking
            foreach ($relatedVouchers as $voucher) {
                $voucher->deleted_by = auth()->id();
                $voucher->save();
                $voucher->delete();
            }

            // ✅ FIX: Delete only the ledger entry for this specific billing month, not all entries
            // Recalculate ledger entries after deletion instead of deleting all
            if ($riderAccountId) {
                // Track ledger entry deletion if it exists
                $ledgerEntry = \App\Support\CompanyQuery::table('ledger_entries')
                    ->where('account_id', $riderAccountId)
                    ->where('billing_month', $billingMonth)
                    ->first();

                if ($ledgerEntry) {
                    try {
                        $this->trackCascadeDeletion(
                            visa_expenses::class,
                            $visaExpenses->id,
                            $visaExpenseIdentifier,
                            \App\Models\LedgerEntry::class,
                            $ledgerEntry->id,
                            "Ledger Entry #{$ledgerEntry->id} (Account ID: {$riderAccountId}, Billing Month: {$billingMonth})",
                            'hasOne',
                            'ledger_entry',
                            'hard',
                            'Cascade deletion from Visa Expense deletion - ledger entry recalculation'
                        );
                    } catch (\Exception $e) {
                        \Log::error("Failed to track cascade deletion for ledger entry {$ledgerEntry->id}: " . $e->getMessage());
                    }
                }

                $this->recalculateLedgerAfterDeletion($riderAccountId, $billingMonth);
            }

            // Delete the visa expense record (soft delete with deleted_by)
            $visaExpenses->deleted_by = auth()->id();
            $visaExpenses->save();
            $visaExpenses->delete();

            DB::commit();
            Flash::success('Visa Expenses Entry deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error deleting Visa Expense ID: {$id} - " . $e->getMessage());
            Flash::error('Error deleting Visa Expense: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Recalculate ledger entries after deletion
     * This ensures ledger integrity without deleting all entries
     */
    private function recalculateLedgerAfterDeletion($accountId, $billingMonth)
    {
        // Delete only the ledger entry for this specific billing month
        \App\Support\CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->delete();

        // Get the last ledger entry before this billing month
        $lastLedger = \App\Support\CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', '<', $billingMonth)
            ->orderBy('billing_month', 'desc')
            ->first();

        $openingBalance = $lastLedger ? $lastLedger->closing_balance : 0.00;

        // Recalculate totals for this month after deletion
        $monthTransactions = Transactions::where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->get();

        $debitTotal = $monthTransactions->sum('debit');
        $creditTotal = $monthTransactions->sum('credit');
        $closingBalance = $openingBalance + $debitTotal - $creditTotal;

        // Only insert a new ledger entry if there are still transactions for this month
        if ($monthTransactions->count() > 0) {
            \App\Support\CompanyQuery::insert('ledger_entries', [
                'account_id'      => $accountId,
                'billing_month'   => $billingMonth,
                'opening_balance' => $openingBalance,
                'debit_balance'   => $debitTotal,
                'credit_balance'  => $creditTotal,
                'closing_balance' => $closingBalance,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        \Log::info("Recalculated ledger for account {$accountId} and billing month {$billingMonth}");
    }

    /**
     * visa_installment_plans.rider_id is used inconsistently: expense_accounts.id, riders.id, or accounts.id.
     *
     * @return array{riderAccount: Accounts, rider: Riders}
     */
    private function resolveInstallmentRiderContext(visa_installment_plan $installment): array
    {
        $key = (int) $installment->rider_id;

        if ($expense = ExpenseAccount::find($key)) {
            $rider = $expense->rider ?? Riders::findOrFail($expense->rider_id);
            $riderAccount = Accounts::where('ref_id', $rider->id)->first();
            if (!$riderAccount && $expense->account_id) {
                $riderAccount = Accounts::find($expense->account_id);
            }
            if (!$riderAccount) {
                throw new \RuntimeException(
                    'Could not find a ledger account for this rider (expense account #' . $key . ').'
                );
            }

            return ['riderAccount' => $riderAccount, 'rider' => $rider];
        }

        if ($rider = Riders::find($key)) {
            $riderAccount = Accounts::where('ref_id', $rider->id)->first();
            if (!$riderAccount) {
                throw new \RuntimeException(
                    'Could not find accounts row with ref_id = rider #' . $key . ' for visa installment resolution.'
                );
            }

            return ['riderAccount' => $riderAccount, 'rider' => $rider];
        }

        $riderAccount = Accounts::find($key);
        if (!$riderAccount || !$riderAccount->ref_id) {
            throw new \RuntimeException(
                'Installment rider_id ' . $key . ' could not be resolved to a rider ledger account.'
            );
        }

        return [
            'riderAccount' => $riderAccount,
            'rider' => Riders::findOrFail($riderAccount->ref_id),
        ];
    }

    /**
     * Update subsequent installments based on the current installment change
     */
    private function updateSubsequentInstallments($currentInstallment, $field, $newValue, $rider)
    {
        // Get subsequent pending installments (id > current, same rider), ordered by ID
        // Works for both paid and pending current installment
        $subsequentInstallments = visa_installment_plan::where('rider_id', $currentInstallment->rider_id)
            ->where('id', '>', $currentInstallment->id)
            ->where('status', visa_installment_plan::STATUS_PENDING)
            ->orderBy('id', 'asc')
            ->get();

        if ($subsequentInstallments->isEmpty()) {
            return;
        }

        $monthIncrement = 1;
        foreach ($subsequentInstallments as $subsequentInstallment) {
            if ($field === 'billing_month') {
                // Calculate new billing month for subsequent installments
                $baseDate = Carbon::parse($newValue . '-01');
                $newBillingMonth = $baseDate->copy()->addMonths($monthIncrement)->format('Y-m');
                $billingMonthWithDay = $newBillingMonth . '-01';

                // Calculate new date - set to 10th of next month from billing month
                $newInstallmentDate = $baseDate->copy()->addMonths($monthIncrement)->addMonth()->day(10)->format('Y-m-d');

                $subsequentInstallment->billing_month = $newBillingMonth;
                $subsequentInstallment->date = $newInstallmentDate;
                $subsequentInstallment->updated_by = auth()->user()->id;
                $subsequentInstallment->save();

                // Update related voucher
                $voucher = Vouchers::where('ref_id', $subsequentInstallment->id)
                    ->where('voucher_type', 'VL')
                    ->first();
                if ($voucher) {
                    $voucher->billing_month = $billingMonthWithDay;
                    $voucher->trans_date = $newInstallmentDate;
                    $voucher->updated_by = auth()->user()->id;
                    $voucher->save();
                }

                // Update related transactions
                $transactions = Transactions::where('reference_id', $subsequentInstallment->id)
                    ->where('reference_type', 'VL')
                    ->get();
                foreach ($transactions as $transaction) {
                    $transaction->billing_month = $billingMonthWithDay;
                    $transaction->trans_date = $newInstallmentDate;
                    $transaction->updated_at = now();
                    $transaction->save();
                }
            } elseif ($field === 'date') {
                // Calculate new date for subsequent installments
                $baseDate = Carbon::parse($newValue);
                $newDate = $baseDate->copy()->addMonths($monthIncrement)->format('Y-m-d');

                $subsequentInstallment->date = $newDate;
                $subsequentInstallment->updated_by = auth()->user()->id;
                $subsequentInstallment->save();

                // Update related voucher
                $voucher = Vouchers::where('ref_id', $subsequentInstallment->id)
                    ->where('voucher_type', 'VL')
                    ->first();
                if ($voucher) {
                    $voucher->trans_date = $newDate;
                    $voucher->updated_by = auth()->user()->id;
                    $voucher->save();
                }

                // Update related transactions
                $transactions = Transactions::where('reference_id', $subsequentInstallment->id)
                    ->where('reference_type', 'VL')
                    ->get();
                foreach ($transactions as $transaction) {
                    $transaction->trans_date = $newDate;
                    $transaction->updated_at = now();
                    $transaction->save();
                }
            } elseif ($field === 'amount') {
                // For amount changes, we need to recalculate proportionally
                // This will be handled in the main method, not here
                // Skip subsequent updates for amount field
                break;
            }

            $monthIncrement++;
        }
    }

    /**
     * Recalculate installment amounts proportionally when one amount changes
     */
    private function recalculateInstallmentAmounts($currentInstallment, $newAmount, $rider)
    {
        // Get all installments for this rider (including current one)
        $allInstallments = visa_installment_plan::where('rider_id', $currentInstallment->rider_id)
            ->where('status', visa_installment_plan::STATUS_PENDING)
            ->orderBy('date', 'asc')
            ->get();

        if ($allInstallments->count() <= 1) {
            return; // No other installments to update
        }

        // Calculate current total
        $currentTotal = $allInstallments->sum('amount');

        // Calculate new total (current total - old amount + new amount)
        $oldAmount = $currentInstallment->amount;
        $newTotal = $currentTotal - $oldAmount + $newAmount;

        // Calculate new amount per installment (equal distribution)
        $newAmountPerInstallment = $newTotal / $allInstallments->count();

        // Update all installments with the new calculated amount
        foreach ($allInstallments as $installment) {
            $installment->amount = $newAmountPerInstallment;
            $installment->updated_by = auth()->user()->id;
            $installment->save();

            // Update related voucher
            $voucher = Vouchers::where('ref_id', $installment->id)
                ->where('voucher_type', 'VL')
                ->first();
            if ($voucher) {
                $voucher->amount = $newAmountPerInstallment;
                $voucher->updated_by = auth()->user()->id;
                $voucher->save();
            }

            // Update related transactions
            $transactions = Transactions::where('reference_id', $installment->id)
                ->where('reference_type', 'VL')
                ->get();
            foreach ($transactions as $transaction) {
                if ($transaction->credit > 0) {
                    $transaction->credit = $newAmountPerInstallment;
                } else {
                    $transaction->debit = $newAmountPerInstallment;
                }
                $transaction->updated_at = now();
                $transaction->save();
            }

            // Update related ledger entry
            ['riderAccount' => $riderAccount] = $this->resolveInstallmentRiderContext($installment);
            $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                ->where('account_type', 'Liability')
                ->where('parent_id', 1)
                ->first();

            if ($liabilityAccount) {
                $billingMonthForLedger = (strlen($installment->billing_month) <= 7) ?
                    $installment->billing_month . '-01' : $installment->billing_month;

                $ledgerEntry = \App\Support\CompanyQuery::table('ledger_entries')
                    ->where('account_id', $liabilityAccount->id)
                    ->where('billing_month', $billingMonthForLedger)
                    ->first();

                if ($ledgerEntry) {
                    \App\Support\CompanyQuery::table('ledger_entries')
                        ->where('account_id', $liabilityAccount->id)
                        ->where('billing_month', $billingMonthForLedger)
                        ->update([
                            'debit_balance' => $newAmountPerInstallment,
                            'closing_balance' => $ledgerEntry->opening_balance + $newAmountPerInstallment,
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }

    /**
     * Automatically mark installments as paid when their date equals today
     */
    public function autoMarkInstallmentsAsPaid($riderId = null)
    {
        $today = Carbon::today()->format('Y-m-d');

        $query = visa_installment_plan::where('status', visa_installment_plan::STATUS_PENDING)
            ->where('date', '<=', $today);

        // If rider ID is provided, filter by rider
        if ($riderId) {
            $query->where('rider_id', $riderId);
        }

        $installmentsToUpdate = $query->get();
        if ($installmentsToUpdate->isEmpty()) {
            return 0;
        }

        $updatedCount = 0;
        foreach ($installmentsToUpdate as $installment) {
            try {
                DB::beginTransaction();

                // Extract billing month (assuming 'billing_month' is Y-m-d or Y-m format in DB)
                $billingMonth = Carbon::parse($installment->billing_month);

                // Set the 20th of that billing month
                $billingDueDate = $billingMonth->copy()->day(20);

                // Skip if today's date is before the 20th of that month
                if (Carbon::today()->lt($billingDueDate)) {
                    DB::rollBack();
                    continue;
                }

                // Mark installment as paid
                $installment->status = visa_installment_plan::STATUS_PAID;
                $installment->updated_by = auth()->user()->id ?? 1;
                $installment->save();

                // Update related voucher
                $voucher = Vouchers::where('ref_id', $installment->id)
                    ->where('voucher_type', 'VL')
                    ->first();

                if ($voucher) {
                    $voucher->remarks = ($voucher->remarks ?? '') . ' - Auto-paid on ' . $today;
                    $voucher->save();
                }

                DB::commit();
                $updatedCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Error auto-marking installment as paid: ' . $e->getMessage(), [
                    'installment_id' => $installment->id,
                    'rider_id' => $installment->rider_id
                ]);
            }
        }
        return $updatedCount;
    }

    /**
     * Check and auto-mark installments for a specific rider (silent operation)
     */
    private function checkAndAutoMarkInstallments($riderId)
    {
        try {
            // Only run if user is authenticated
            if (!auth()->check()) {
                return 0;
            }

            $updatedCount = $this->autoMarkInstallmentsAsPaid($riderId);

            // Silent operation - no flash messages to user
            // Only log for admin/debugging purposes
            if ($updatedCount > 0) {
                \Log::info("Auto-marked {$updatedCount} installment(s) as paid for rider {$riderId}");
            }

            return $updatedCount;
        } catch (\Exception $e) {
            // Log error but don't break the main request
            \Log::error("Error in checkAndAutoMarkInstallments for rider {$riderId}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Recalculate installment amounts when one is edited
     * This method ensures the total amount is preserved and remaining installments are adjusted
     */
    public function recalculateInstallments(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'rider_id' => 'required|exists:accounts,id',
            'edited_installment_id' => 'required|exists:visa_installment_plans,id',
            'new_amount' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            // Get all pending installments for this rider
            $installments = visa_installment_plan::where('rider_id', $validated['rider_id'])
                ->where('status', visa_installment_plan::STATUS_PENDING)
                ->orderBy('id', 'asc')
                ->get();

            if ($installments->isEmpty()) {
                Flash::error('No pending installments found for this rider.');
                return redirect()->back();
            }

            // Get the total amount from the first installment (all should have the same total_amount)
            $totalAmount = $installments->first()->total_amount;
            if (!$totalAmount) {
                Flash::error('Total amount not found. Please recreate the installment plan.');
                return redirect()->back();
            }

            // Find the edited installment
            $editedInstallment = $installments->where('id', $validated['edited_installment_id'])->first();
            if (!$editedInstallment) {
                Flash::error('Installment not found.');
                return redirect()->back();
            }

            // Step 1: Calculate remaining balance
            $remainingBalance = $totalAmount - $validated['new_amount'];

            // Step 2: Get remaining installments (excluding the edited one)
            $remainingInstallments = $installments->where('id', '!=', $validated['edited_installment_id']);

            if ($remainingInstallments->isEmpty()) {
                Flash::error('No remaining installments to adjust.');
                return redirect()->back();
            }

            // Step 3: Distribute balance equally among remaining installments
            $amountPerInstallment = $remainingBalance / $remainingInstallments->count();

            // Step 4: Apply rounding - round all but the last installment
            $roundedAmount = floor($amountPerInstallment * 100) / 100; // Round down to 2 decimal places
            $lastInstallment = $remainingInstallments->last();

            // Update all remaining installments
            foreach ($remainingInstallments as $index => $installment) {
                if ($installment->id === $lastInstallment->id) {
                    // Last installment gets the remaining balance to handle rounding
                    $usedAmount = $validated['new_amount'] + ($roundedAmount * ($remainingInstallments->count() - 1));
                    $lastAmount = $totalAmount - $usedAmount;
                    $installment->amount = $lastAmount;
                } else {
                    $installment->amount = $roundedAmount;
                }
                $installment->updated_by = auth()->user()->id;
                $installment->save();

                // Update related voucher
                $voucher = Vouchers::where('ref_id', $installment->id)
                    ->where('voucher_type', 'VL')
                    ->first();
                if ($voucher) {
                    $voucher->amount = $installment->amount;
                    $voucher->updated_by = auth()->user()->id;
                    $voucher->save();
                }

                // Update related transactions
                $transactions = Transactions::where('reference_id', $installment->id)
                    ->where('reference_type', 'VL')
                    ->get();
                foreach ($transactions as $transaction) {
                    if ($transaction->credit > 0) {
                        $transaction->credit = $installment->amount;
                    } else {
                        $transaction->debit = $installment->amount;
                    }
                    $transaction->updated_at = now();
                    $transaction->save();
                }
            }

            // Update the edited installment
            $editedInstallment->amount = $validated['new_amount'];
            $editedInstallment->updated_by = auth()->user()->id;
            $editedInstallment->save();

            // Update related voucher for edited installment
            $voucher = Vouchers::where('ref_id', $editedInstallment->id)
                ->where('voucher_type', 'VL')
                ->first();
            if ($voucher) {
                $voucher->amount = $editedInstallment->amount;
                $voucher->updated_by = auth()->user()->id;
                $voucher->save();
            }

            // Update related transactions for edited installment
            $transactions = Transactions::where('reference_id', $editedInstallment->id)
                ->where('reference_type', 'VL')
                ->get();
            foreach ($transactions as $transaction) {
                if ($transaction->credit > 0) {
                    $transaction->credit = $editedInstallment->amount;
                } else {
                    $transaction->debit = $editedInstallment->amount;
                }
                $transaction->updated_at = now();
                $transaction->save();
            }

            DB::commit();

            Flash::success('Installment amounts recalculated successfully. Total amount preserved: ' . number_format($totalAmount, 2));
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error recalculating installments: ' . $e->getMessage());
            return redirect()->back();
        }
    }
    public function getVisaStatusFee(Request $request)
    {
        $request->validate([
            'visa_status' => 'required|string'
        ]);
        try {
            $visaStatus = VisaStatus::where('name', $request->visa_status)->where('is_active', 1)->first();

            if ($visaStatus) {
                return response()->json([
                    'success' => true,
                    'amount' => $visaStatus->default_fee ?? 0
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Visa status not found',
                'amount' => 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching visa status fee: ' . $e->getMessage(),
                'amount' => 0
            ], 500);
        }
    }
}
