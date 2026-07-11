<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Support\GlobalAccounts;
use App\Helpers\Common;
use App\Http\Requests\StoreLicenseExpenseRequest;
use App\Http\Requests\UpdateLicenseExpenseRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Bikes;
use App\Models\Branch;
use App\Models\Riders;
use App\Models\license_expenses;
use App\Models\Accounts;
use App\Models\Vouchers;
use App\Models\VoucherType;
use App\Models\LedgerEntry;
use App\Models\Transactions;
use App\Models\LicenseStatus;
use App\Models\ExpenseAccount;
use App\Models\Settings;
use App\Repositories\LicenseExpensesRepository;
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

class LicenseexpenseController extends AppBaseController
{
    use GlobalPagination, TracksCascadingDeletions;

    protected $licenseRepo;

    public function __construct(LicenseExpensesRepository $licenseRepo)
    {
        $this->licenseRepo = $licenseRepo;
        $this->middleware('permission:license_expense_view')->only('index', 'generatentries', 'viewvoucher');
        $this->middleware('permission:license_expense_create')->only('accountcreate', 'generatentries', 'create', 'store', 'payfine');
        $this->middleware('permission:license_expense_edit')->only('editaccount', 'updateaccount', 'edit', 'update', 'payfine', 'inlineUpdate', 'editVoucherCreditForm', 'updateVoucherCredit');
        $this->middleware('permission:license_expense_delete')->only('deleteaccount', 'destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
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

        $licenseStatusFilterModel = null;
        if ($request->filled('license_status_id')) {
            $licenseStatusFilterModel = LicenseStatus::find($request->license_status_id);
        }

        $sliderBaseQuery = clone $query;

        $visaTopEnabledRaw = (string) (Settings::query()
            ->where('name', 'license_expense_top_enabled')
            ->value('value') ?? '1');
        $visaTopEnabled = in_array(strtolower(trim($visaTopEnabledRaw)), ['1', 'true', 'yes', 'on'], true);

        $selectedVisaTopIdsRaw = (string) (Settings::query()
            ->where('name', 'license_expense_top_status_ids')
            ->value('value') ?? '');
        $selectedVisaTopIds = collect(json_decode($selectedVisaTopIdsRaw, true))
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $LicenseStatuses = collect();
        if ($visaTopEnabled && !empty($selectedVisaTopIds)) {
            $LicenseStatusesQuery = LicenseStatus::query()
                ->where('is_active', 1)
                ->whereIn('id', $selectedVisaTopIds)
                ->orderBy('display_order')
                ->orderBy('id');
            $LicenseStatuses = $LicenseStatusesQuery->get();
            $statusOrderMap = array_flip($selectedVisaTopIds);
            $LicenseStatuses = $LicenseStatuses
                ->sortBy(fn($status) => $statusOrderMap[(int) $status->id] ?? PHP_INT_MAX)
                ->values();
        }

        $LicenseStatusSliderCounts = [];
        foreach ($LicenseStatuses as $vsRow) {
            $LicenseStatusSliderCounts[$vsRow->id] = [
                'paid' => (clone $sliderBaseQuery)->tap(function ($q) use ($vsRow) {
                    $this->applyExpenseAccountMatchesLicenseExpense($q, function ($sub) use ($vsRow) {
                        $sub->where('ve.license_status', $vsRow->name)->where('ve.payment_status', 'paid');
                    });
                })->count(),
                'unpaid' => (clone $sliderBaseQuery)->tap(function ($q) use ($vsRow) {
                    $this->applyExpenseAccountMatchesLicenseExpense($q, function ($sub) use ($vsRow) {
                        $sub->where('ve.license_status', $vsRow->name)->where('ve.payment_status', 'unpaid');
                    });
                })->count(),
            ];
        }

        if ($request->filled('payment_status')) {
            $status = $request->payment_status;
            if ($licenseStatusFilterModel && in_array($status, ['paid', 'unpaid'], true)) {
                $this->applyExpenseAccountMatchesLicenseExpense($query, function ($sub) use ($licenseStatusFilterModel, $status) {
                    $sub->where('ve.license_status', $licenseStatusFilterModel->name)
                        ->where('ve.payment_status', $status);
                });
            } elseif (!$licenseStatusFilterModel) {
                if ($status === 'paid') {
                    $this->applyExpenseAccountMatchesLicenseExpense($query, function ($sub) {
                        $sub->whereRaw('1 = 1');
                    });
                    $query->whereNotExists(function ($sub) {
                        $headId = $this->licenseExpenseHeadAccountId();
                        $sub->select(DB::raw(1))
                            ->from('license_expenses as ve')
                            ->whereNull('ve.deleted_at')
                            ->where('ve.payment_status', 'unpaid')
                            ->where(function ($link) use ($headId) {
                                $link->whereColumn('ve.expense_account_id', 'expense_accounts.id');
                                if ($headId !== null) {
                                    $link->orWhere(function ($l2) use ($headId) {
                                        $l2->where('ve.expense_account_id', $headId)
                                            ->whereColumn('ve.rider_id', 'expense_accounts.rider_id');
                                    });
                                }
                            });
                        $this->applyLicenseExpenseCompanyScopeForVeAlias($sub);
                    });
                } elseif ($status === 'unpaid') {
                    $this->applyExpenseAccountMatchesLicenseExpense($query, function ($sub) {
                        $sub->where('ve.payment_status', 'unpaid');
                    });
                }
            }
        } elseif ($licenseStatusFilterModel) {
            $this->applyExpenseAccountMatchesLicenseExpense($query, function ($sub) use ($licenseStatusFilterModel) {
                $sub->where('ve.license_status', $licenseStatusFilterModel->name);
            });
        }

        $statsQuery = clone $query;
        $data = $this->applyPagination($query, $paginationParams);
        $riders = Riders::orderBy('name')->get();
        $expenseAccountIds = $statsQuery->pluck('id')->toArray();
        $visaAccounts = license_expenses::whereIn('expense_account_id', $expenseAccountIds)->get();
        $stats = [
            'unpaid_accounts' => $visaAccounts->where('payment_status', 'unpaid')->count(),
            'paid_amount' => $visaAccounts->where('payment_status', 'paid')->sum('amount'),
            'unpaid_amount' => $visaAccounts->where('payment_status', 'unpaid')->sum('amount'),
        ];

        $nextUnpaidVisaByAccountId = $this->mapNextUnpaidLicenseExpensesForPage($data);
        $urgentVisaExpiryByAccountId = $this->mapUrgentVisaExpiryForPage($data);

        if ($request->ajax()) {
            $tableData = view('license_expenses.account_table', [
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

        return view('license_expenses.account_index', [
            'data' => $data,
            'riders' => $riders,
            'stats' => $stats,
            'riderIds' => $expenseAccountIds,
            'LicenseStatuses' => $LicenseStatuses,
            'LicenseStatusSliderCounts' => $LicenseStatusSliderCounts,
            'nextUnpaidVisaByAccountId' => $nextUnpaidVisaByAccountId,
            'urgentVisaExpiryByAccountId' => $urgentVisaExpiryByAccountId,
        ]);
    }

    /**
     * Earliest unpaid License Expense per expense account on this results page (one query).
     *
     * @param  \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection|array  $paginatorOrCollection
     * @return array<int, \App\Models\license_expenses>
     */
    private function mapNextUnpaidLicenseExpensesForPage($paginatorOrCollection): array
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

        $headId = $this->licenseExpenseHeadAccountId();

        $unpaidRows = license_expenses::query()
            ->where('payment_status', 'unpaid')
            ->where(function ($q) use ($ids, $riderToEaId, $headId) {
                $q->whereIn('expense_account_id', $ids);
                if ($headId !== null) {
                    $q->orWhere(function ($q2) use ($riderToEaId, $headId) {
                        $q2->where('expense_account_id', $headId)
                            ->whereIn('rider_id', array_keys($riderToEaId));
                    });
                }
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
            } elseif ($headId !== null && $veEa === $headId && $ve->rider_id !== null) {
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
     * Earliest License Expense with expiry_date on or before today + $withinDays (inclusive), per expense account on this page.
     * Same account linkage as {@see mapNextUnpaidLicenseExpensesForPage()}.
     *
     * @param  \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection|array  $paginatorOrCollection
     * @return array<int, \App\Models\license_expenses>
     */
    private function mapUrgentVisaExpiryForPage($paginatorOrCollection, int $withinDays = 10): array
    {
        if (!Schema::hasColumn((new license_expenses)->getTable(), 'expiry_date')) {
            return [];
        }

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

        $headId = $this->licenseExpenseHeadAccountId();
        $threshold = now()->addDays($withinDays)->startOfDay();

        $rows = license_expenses::query()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $threshold)
            ->where(function ($q) use ($ids, $riderToEaId, $headId) {
                $q->whereIn('expense_account_id', $ids);
                if ($headId !== null) {
                    $q->orWhere(function ($q2) use ($riderToEaId, $headId) {
                        $q2->where('expense_account_id', $headId)
                            ->whereIn('rider_id', array_keys($riderToEaId));
                    });
                }
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
            } elseif ($headId !== null && $veEa === $headId && $ve->rider_id !== null) {
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

    private function licenseExpenseHeadAccountId(): ?int
    {
        return GlobalAccounts::idOrNull('LICENSE_EXPENSE_ACCOUNT');
    }

    /**
     * Correlate license_expenses to expense_accounts the same way as generatentries:
     * direct expense_account_id = expense_accounts.id, or legacy head-account id + rider match.
     */
    private function applyExpenseAccountMatchesLicenseExpense($expenseAccountQuery, callable $constraintsOnVeSubquery): void
    {
        $headId = $this->licenseExpenseHeadAccountId();
        $expenseAccountQuery->whereExists(function ($sub) use ($constraintsOnVeSubquery, $headId) {
            $sub->select(DB::raw(1))
                ->from('license_expenses as ve')
                ->whereNull('ve.deleted_at')
                ->where(function ($link) use ($headId) {
                    $link->whereColumn('ve.expense_account_id', 'expense_accounts.id');
                    if ($headId !== null) {
                        $link->orWhere(function ($l2) use ($headId) {
                            $l2->where('ve.expense_account_id', $headId)
                                ->whereColumn('ve.rider_id', 'expense_accounts.rider_id');
                        });
                    }
                });
            $constraintsOnVeSubquery($sub);
            $this->applyLicenseExpenseCompanyScopeForVeAlias($sub);
        });
    }

    private function applyLicenseExpenseCompanyScopeForVeAlias($subquery): void
    {
        if (!Schema::hasColumn((new license_expenses)->getTable(), 'company_id')) {
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
        $exists = ExpenseAccount::where('rider_id', $rider->id)->first();

        DB::beginTransaction();
        try {
            if ($exists) {
                $expenseAccount = $exists;
            } else {
                $expenseAccount = ExpenseAccount::create([
                    'name' => $rider->name,
                    'rider_id' => $rider->id,
                    'branch_id' => $rider->branch_id,
                    'account_id' => $rider->account_id,
                    'company_id' => auth()->user()->company_id ?? null,
                ]);
            }

            $activeStatuses = LicenseStatus::query()
                ->where('is_active', 1)
                ->orderBy('display_order')
                ->get();
            $created = 0;
            foreach ($activeStatuses as $status) {
                $alreadyExists = license_expenses::query()
                    ->where('license_status', $status->name)
                    ->where(function ($q) use ($expenseAccount, $rider) {
                        $q->where('expense_account_id', $expenseAccount->id)
                            ->orWhere(function ($q2) use ($rider) {
                                $q2->where('expense_account_id', GlobalAccounts::id('LICENSE_EXPENSE_ACCOUNT'))
                                    ->where('rider_id', $rider->id);
                            });
                    })
                    ->exists();
                if ($alreadyExists) {
                    continue;
                }
                license_expenses::create([
                    'branch_id' => $rider->branch_id,
                    'trans_date' => Carbon::today()->format('Y-m-d'),
                    'trans_code' => Account::trans_code(),
                    'date' => Carbon::today()->format('Y-m-d'),
                    'rider_id' => $expenseAccount->rider_id,
                    'expense_account_id' => GlobalAccounts::id('LICENSE_EXPENSE_ACCOUNT'),
                    'license_status' => $status->name,
                    'detail' => $status->description ?? ('Auto-generated from active License Status: ' . $status->name),
                    'reference_number' => 'DL-' . $expenseAccount->rider_id . '-' . $status->id,
                    'billing_month' => Carbon::today()->startOfMonth()->format('Y-m-d'),
                    'amount' => (float) ($status->default_fee ?? 0),
                    'payment_status' => 'unpaid',
                ]);
                $created++;
            }
            DB::commit();
            if ($created > 0) {
                Flash::success('License Expense account ready. ' . $created . ' status entries generated.');
            } else {
                Flash::info('License Expense account already has all active status entries.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Flash::error('Error creating License Expense account: ' . $e->getMessage());
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
        Flash::success('License Expense account updated successfully.');
        return redirect()->back();
    }

    public function deleteaccount($company_slug, $id)
    {
        // Check if any license_expenses exist for this account
        $hasExpenses = license_expenses::where('expense_account_id', $id)
            ->Where('payment_status', 'paid')
            ->exists();

        if ($hasExpenses) {
            Flash::error('Cannot delete account. License Expense entries exist for this account.');
            return redirect()->back();
        }

        // Check if any transactions exist for this account related to License Expenses
        $hasTransactions = Transactions::where('account_id', $id)
            ->where('reference_type', 'LE')
            ->exists();

        if ($hasTransactions) {
            Flash::error('Cannot delete account. Transactions related to License Expenses exist for this account.');
            return redirect()->back();
        }

        // Check if any vouchers exist for this account related to License Expenses
        $account = ExpenseAccount::findOrFail($id);
        $riderId = $account->rider_id;
        if ($riderId) {
            $hasVouchers = Vouchers::where('rider_id', $riderId)
                ->where('voucher_type', 'LE')
                ->exists();

            if ($hasVouchers) {
                Flash::error('Cannot delete account. Vouchers related to License Expenses exist for this account.');
                return redirect()->back();
            }
        }

        // No related records â€” safe to delete
        $LicenseExpense = license_expenses::where('expense_account_id', $id)->delete();
        ExpenseAccount::where('id', $id)->delete();
        Flash::success('Account deleted successfully.');
        return redirect()->back();
    }

    public function generatentries(Request $request, $company_slug, $id)
    {
        $account = ExpenseAccount::with('rider')->where('id', $id)->firstOrFail();
        $riderId = $account->rider_id;
        // Use global pagination traits
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $headId = $this->licenseExpenseHeadAccountId();
        $query = license_expenses::query()
            ->with('vouchers')
            ->orderBy('id', 'asc')
            ->where(function ($q) use ($id, $riderId, $headId) {
                $q->where('expense_account_id', $id);
                if ($headId !== null) {
                    $q->orWhere(function ($q2) use ($riderId, $headId) {
                        $q2->where('expense_account_id', $headId)
                            ->where('rider_id', $riderId);
                    });
                }
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
        if ($request->has('license_status') && !empty($request->license_status)) {
            $query->where('license_status', $request->license_status);
        }
        if ($request->has('payment_status') && !empty($request->payment_status)) {
            $query->where('payment_status', $request->payment_status);
        }
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);

        if ($request->ajax()) {
            $tableData = view('license_expenses.table', [
                'data' => $data,
                'account' => $account,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }
        $LicenseStatuses = LicenseStatus::orderBy('display_order', 'asc')->where('is_active', 1)->get();
        $riders = Riders::findOrFail($riderId);
        \Log::info('License Expense entries', ['rider_id' => $id, 'rider' => $riders]);
        return view('license_expenses.index', [
            'data' => $data,
            'account' => $account,
            'LicenseStatuses' => $LicenseStatuses,
            'riders' => $riders,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($company_slug, $id)
    {
        $data = ExpenseAccount::where('id', $id)->first();
        $licenseStatuses = LicenseStatus::orderBy('display_order', 'asc')->where('is_active', 1)->get();
        return view('license_expenses.create', compact('data', 'licenseStatuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rider_id'       => 'required|exists:expense_accounts,id',
            'license_status'    => [
                'required',
                'string',
                'max:255',
                Rule::unique('license_expenses')->where(function ($query) use ($request) {
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
            $LicenseExpenses = license_expenses::create([
                'rider_id'       => $validated['rider_id'],
                'expense_account_id' => $validated['rider_id'],
                'license_status'    => $validated['license_status'],
                'billing_month'  => $billingMonth,
                'date'           => $request->date,
                'amount'         => $request->amount,
                'payment_status' => 'unpaid',
                'detail'         => $validated['detail'],
                'reference_number' => $validated['reference_number'],
                'trans_date'     => $trans_date,
                'trans_code'     => $trans_code,
            ]);
            Flash::success('License Expenses added successfully ');
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

        $data = license_expenses::where('id', $id)->first();
        $accounts = ExpenseAccount::where('rider_id', $data->rider_id)->first();
        return view('license_expenses.viewvoucher', compact('data', 'accounts'));
    }
    public function payfine(Request $request)
    {
        DB::beginTransaction();

        try {
            $expenseAccount = ExpenseAccount::where('rider_id', $request->rider_id)->first();
            $expense = license_expenses::findOrFail($request->id);
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
                $remarks = $request->voucher_type === 'LE' ? 'License Expense Voucher' : 'Journal Voucher';

                $trans_code = Account::trans_code();
                $TransactionService = new TransactionService();

                $billingMonth = $expense->billing_month ?? date('Y-m-01');
                $transDate = $expense->trans_date;

                // 1. Fine Amount
                if ($expense->amount > 0) {
                    // Debit RTA Account
                    $TransactionService->recordTransaction([
                        'account_id'     => GlobalAccounts::id('LICENSE_EXPENSE_ACCOUNT'),
                        'reference_id'   => $expense->id,
                        'reference_type' => 'LE',
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
                        'reference_type' => 'LE',
                        'trans_code'     => $trans_code,
                        'trans_date'     => $transDate,
                        'narration'      => $expense->detail ?? 'License Expense Payment',
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
            Flash::success('License Expense Paid Successfully with Transaction and Ledger Entries.');
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());
        }

        return redirect(route('LicenseExpense.generatentries', $expenseAccount->id));
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
        $LicenseExpenses = license_expenses::find($id);
        $data = ExpenseAccount::where('id', $LicenseExpenses->expense_account_id ?? $LicenseExpenses->rider_id)->first();
        if (empty($LicenseExpenses)) {
            Flash::error('License Expenses not found');

            return redirect(route('LicenseExpense.index'));
        }
        $licenseStatuses = LicenseStatus::orderBy('display_order', 'asc')->where('is_active', 1)->get();
        return view('license_expenses.edit', compact('data', 'LicenseExpenses', 'licenseStatuses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        DB::beginTransaction();

        try {
            $LicenseExpenses = license_expenses::findOrFail($request->id);
            $oldAmount = $LicenseExpenses->amount;
            $billingMonth = $request->billing_month . "-01";

            $request->validate([
                'reference_number' => 'required|string|max:255',
            ]);

            $LicenseExpenses->license_status = $request->license_status;
            $LicenseExpenses->billing_month = $billingMonth;
            $LicenseExpenses->date = $request->date;
            $LicenseExpenses->amount = $request->amount;
            $LicenseExpenses->detail = $request->detail;
            $LicenseExpenses->reference_number = $request->reference_number;
            $LicenseExpenses->save();

            // If this License Expense is already paid, update related voucher and transactions amounts
            if ($LicenseExpenses->payment_status == 'paid') {
                // Update voucher amount(s) linked to this License Expense
                $vouchers = Vouchers::where('ref_id', $LicenseExpenses->id)
                    ->where('voucher_type', 'LE')
                    ->first();
                if ($vouchers) {
                    $vouchers->reference_number = $LicenseExpenses->reference_number;
                    $vouchers->amount = $LicenseExpenses->amount;
                    $vouchers->save();
                }

                // Update related transactions amounts (both debit and credit sides)
                $transactions = Transactions::where('reference_id', $LicenseExpenses->id)
                    ->where('reference_type', 'LE')
                    ->get();

                foreach ($transactions as $transaction) {
                    if ($transaction->debit > 0) {
                        $transaction->debit = $LicenseExpenses->amount;
                    }
                    if ($transaction->credit > 0) {
                        $transaction->credit = $LicenseExpenses->amount;
                    }
                    $transaction->save();
                }
            }

            DB::commit();
            Flash::success('License Expense updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error updating License Expense: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function inlineUpdate(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:license_expenses,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'billing_month' => 'required|date_format:Y-m',
        ]);

        $row = license_expenses::findOrFail($validated['id']);
        $row->amount = $validated['amount'];
        $row->date = $validated['date'];
        $row->billing_month = $validated['billing_month'] . '-01';
        $row->save();

        return response()->json([
            'success' => true,
            'message' => 'License Expense updated.',
            'amount' => number_format((float) $row->amount, 2),
            'date' => Carbon::parse($row->date)->format('Y-m-d'),
            'billing_month' => Carbon::parse($row->billing_month)->format('Y-m'),
        ]);
    }

    /**
     * Modal form: change LE voucher credit (payment) account only; debit stays License Expense head.
     */
    public function editVoucherCreditForm(Request $request, $company_slug, $LicenseExpense)
    {
        $expense = license_expenses::with('vouchers')->findOrFail($LicenseExpense);

        if ($expense->payment_status !== 'paid') {
            return response(
                '<div class="alert alert-warning m-2 mb-0">Only paid expenses have a payment voucher to edit.</div>',
                200
            );
        }

        $voucher = $expense->vouchers->first();
        if (!$voucher) {
            $voucher = Vouchers::where('ref_id', $expense->id)->where('voucher_type', 'LE')->first();
        }
        if (!$voucher) {
            return response(
                '<div class="alert alert-warning m-2 mb-0">No LE voucher found for this expense.</div>',
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

        $debitAccountName = Accounts::where('id', GlobalAccounts::id('LICENSE_EXPENSE_ACCOUNT'))->value('name') ?? 'License Expense';
        $currentCreditName = Accounts::where('id', $creditTx->account_id)->value('name') ?? ('#' . $creditTx->account_id);

        $paymentAccounts = $this->LicenseExpensePaymentAccountOptions();
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

        return view('license_expenses.edit_voucher_credit', [
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
        $validated = $request->validate([
            'license_expense_id' => 'required|exists:license_expenses,id',
            'credit_account_id' => 'required|exists:accounts,id',
        ]);

        $expense = license_expenses::findOrFail($validated['license_expense_id']);

        if ($expense->payment_status !== 'paid') {
            Flash::error('Only paid License Expenses can update the payment account.');
            return redirect()->back();
        }

        $newAccountId = (int) $validated['credit_account_id'];
        if ($newAccountId === (int) GlobalAccounts::id('LICENSE_EXPENSE_ACCOUNT')) {
            Flash::error('Cannot use the License Expense account as the payment (credit) side.');
            return redirect()->back();
        }

        $voucher = Vouchers::where('ref_id', $expense->id)->where('voucher_type', 'LE')->first();
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
                \App\Support\CompanyQuery::table('vouchers')->where('id', $voucher->id)->update([
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
     * Cash/bank style accounts used when marking License Expense paid (same pool as pay flow).
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function LicenseExpensePaymentAccountOptions()
    {
        $bank = \App\Support\GlobalAccounts::account('BANK');

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
        $LicenseExpenses = license_expenses::find($id);

        if (empty($LicenseExpenses)) {
            Flash::error('License Expense Entry not found');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $billingMonth = $LicenseExpenses->billing_month;
            $riderAccountId = \App\Support\CompanyQuery::table('accounts')->where('ref_id', $LicenseExpenses->rider_id)->value('id');
            $LicenseExpenseIdentifier = "License Expense #{$id} - {$LicenseExpenses->license_status} (Amount: " . number_format($LicenseExpenses->amount, 2) . ")";

            // Get related transactions before deletion
            $relatedTransactions = Transactions::where('reference_id', $LicenseExpenses->id)
                ->where('reference_type', 'LE')
                ->get();

            // Get related transactions by trans_code
            $transCodeTransactions = Transactions::where('trans_code', $LicenseExpenses->trans_code)
                ->where('reference_type', 'LE')
                ->get();

            // Get related vouchers before deletion (include soft deleted to be safe)
            $relatedVouchers = Vouchers::withTrashed()
                ->where('ref_id', $LicenseExpenses->id)
                ->where('voucher_type', 'LE')
                ->whereNull('deleted_at') // Only get non-deleted vouchers
                ->get();

            \Log::info("Found " . $relatedVouchers->count() . " vouchers to track for License Expense {$id}", [
                'voucher_ids' => $relatedVouchers->pluck('id')->toArray()
            ]);

            // Track cascade deletions for transactions
            foreach ($relatedTransactions as $transaction) {
                try {
                    $this->trackCascadeDeletion(
                        license_expenses::class,
                        $LicenseExpenses->id,
                        $LicenseExpenseIdentifier,
                        Transactions::class,
                        $transaction->id,
                        "Transaction #{$transaction->id} (Trans Code: {$transaction->trans_code}, Amount: " . ($transaction->debit > 0 ? number_format($transaction->debit, 2) : number_format($transaction->credit, 2)) . ")",
                        'hasMany',
                        'transactions',
                        'soft',
                        'Cascade deletion from License Expense deletion - transaction by reference_id'
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
                        license_expenses::class,
                        $LicenseExpenses->id,
                        $LicenseExpenseIdentifier,
                        Transactions::class,
                        $transaction->id,
                        "Transaction #{$transaction->id} (Trans Code: {$transaction->trans_code}, Amount: " . ($transaction->debit > 0 ? number_format($transaction->debit, 2) : number_format($transaction->credit, 2)) . ")",
                        'hasMany',
                        'transactions',
                        'soft',
                        'Cascade deletion from License Expense deletion - transaction by trans_code'
                    );
                } catch (\Exception $e) {
                    \Log::error("Failed to track cascade deletion for transaction {$transaction->id}: " . $e->getMessage());
                }
            }

            // Track cascade deletions for vouchers BEFORE deletion
            foreach ($relatedVouchers as $voucher) {
                try {
                    \Log::info("Attempting to track cascade deletion for voucher {$voucher->id}", [
                        'primary_model' => license_expenses::class,
                        'primary_id' => $LicenseExpenses->id,
                        'related_model' => Vouchers::class,
                        'related_id' => $voucher->id,
                    ]);

                    $cascadeRecord = $this->trackCascadeDeletion(
                        license_expenses::class,
                        $LicenseExpenses->id,
                        $LicenseExpenseIdentifier,
                        Vouchers::class,
                        $voucher->id,
                        "Voucher #{$voucher->id} ({$voucher->voucher_type}-" . str_pad($voucher->id, 4, '0', STR_PAD_LEFT) . ", Amount: " . number_format($voucher->amount, 2) . ")",
                        'hasMany',
                        'vouchers',
                        'soft',
                        'Cascade deletion from License Expense deletion - voucher'
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

            // Delete only specific transactions related to this License Expense
            Transactions::where('reference_id', $LicenseExpenses->id)
                ->where('reference_type', 'LE')
                ->delete();

            // Delete transactions by trans_code if they exist
            Transactions::where('trans_code', $LicenseExpenses->trans_code)
                ->where('reference_type', 'LE')
                ->delete();

            // Cascade delete vouchers related to this License Expense (soft delete with deleted_by)
            // Use the same collection that was used for tracking
            foreach ($relatedVouchers as $voucher) {
                $voucher->deleted_by = auth()->id();
                $voucher->save();
                $voucher->delete();
            }

            // âœ… FIX: Delete only the ledger entry for this specific billing month, not all entries
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
                            license_expenses::class,
                            $LicenseExpenses->id,
                            $LicenseExpenseIdentifier,
                            \App\Models\LedgerEntry::class,
                            $ledgerEntry->id,
                            "Ledger Entry #{$ledgerEntry->id} (Account ID: {$riderAccountId}, Billing Month: {$billingMonth})",
                            'hasOne',
                            'ledger_entry',
                            'hard',
                            'Cascade deletion from License Expense deletion - ledger entry recalculation'
                        );
                    } catch (\Exception $e) {
                        \Log::error("Failed to track cascade deletion for ledger entry {$ledgerEntry->id}: " . $e->getMessage());
                    }
                }

                $this->recalculateLedgerAfterDeletion($riderAccountId, $billingMonth);
            }

            // Delete the License Expense record (soft delete with deleted_by)
            $LicenseExpenses->deleted_by = auth()->id();
            $LicenseExpenses->save();
            $LicenseExpenses->delete();

            DB::commit();
            Flash::success('License Expenses Entry deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error deleting License Expense ID: {$id} - " . $e->getMessage());
            Flash::error('Error deleting License Expense: ' . $e->getMessage());
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
     * Resolve License Expense context to an ExpenseAccount from expense_accounts.id, riders.id, or legacy accounts.id.
     */
    private function resolveExpenseAccountContext(int $id): ExpenseAccount
    {
        $expense = ExpenseAccount::with('rider')->find($id);
        if ($expense) {
            return $expense;
        }

        $expense = ExpenseAccount::with('rider')->where('rider_id', $id)->first();
        if ($expense) {
            return $expense;
        }

        $expense = ExpenseAccount::with('rider')->where('account_id', $id)->first();
        if ($expense) {
            return $expense;
        }

        $legacyAccount = Accounts::find($id);
        if ($legacyAccount && $legacyAccount->ref_id) {
            $expense = ExpenseAccount::with('rider')->where('rider_id', $legacyAccount->ref_id)->first();
            if ($expense) {
                return $expense;
            }
        }

        abort(404, 'License Expense account not found.');
    }

    /**
     * @return list<int>
     */
    public function getLicenseStatusFee(Request $request)
    {
        $request->validate([
            'license_status' => 'required|string'
        ]);
        try {
            $LicenseStatus = LicenseStatus::where('name', $request->license_status)->where('is_active', 1)->first();

            if ($LicenseStatus) {
                return response()->json([
                    'success' => true,
                    'amount' => $LicenseStatus->default_fee ?? 0
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'License Status not found',
                'amount' => 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching License Status fee: ' . $e->getMessage(),
                'amount' => 0
            ], 500);
        }
    }
}
