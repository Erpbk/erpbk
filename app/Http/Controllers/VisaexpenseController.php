<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Support\CompanyAuthRedirect;
use App\Support\GlobalAccounts;
use App\Helpers\Common;
use App\Http\Requests\StoreVisaExpenseRequest;
use App\Http\Requests\UpdateVisaExpenseRequest;
use App\Http\Controllers\Concerns\ManagesVisaInstallments;
use App\Http\Controllers\Concerns\ManagesExpenseEntryDeletion;
use App\Http\Controllers\AppBaseController;
use App\Models\Bikes;
use App\Models\Branch;
use App\Models\Employee;
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
use App\Support\VisaRenewalCategoryService;
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
    use GlobalPagination, TracksCascadingDeletions, ManagesVisaInstallments, ManagesExpenseEntryDeletion;

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

        if (!user_can('visaexpense_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $userBranches = app('user_branches');
        $query = ExpenseAccount::query()
            ->visa()
            ->with(['rider', 'employee', 'renewalCategory'])
            ->orderByDesc('id');

        if (!auth()->user()->isAdmin()) {
            if (!empty($userBranches)) {
                $query->where(function ($outer) use ($userBranches) {
                    $outer->where(function ($q) use ($userBranches) {
                        $q->whereNotNull('rider_id')
                            ->whereHas('rider', function ($rq) use ($userBranches) {
                                $rq->whereIn('branch_id', $userBranches)->orWhereNull('branch_id');
                            });
                    })->orWhere(function ($q) use ($userBranches) {
                        $q->whereNotNull('employee_id')
                            ->whereHas('employee', function ($eq) use ($userBranches) {
                                $eq->whereIn('branch_id', $userBranches)->orWhereNull('branch_id');
                            });
                    });
                });
            } else {
                $query->where(function ($outer) {
                    $outer->where(function ($q) {
                        $q->whereNotNull('rider_id')
                            ->whereHas('rider', function ($rq) {
                                $rq->whereNull('branch_id');
                            });
                    })->orWhere(function ($q) {
                        $q->whereNotNull('employee_id')
                            ->whereHas('employee', function ($eq) {
                                $eq->whereNull('branch_id');
                            });
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
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $visaStatuses = collect();
        if ($visaTopEnabled && !empty($selectedVisaTopIds)) {
            $visaStatusesQuery = VisaStatus::query()
                ->with('renewalCategory')
                ->where('is_active', 1)
                ->whereIn('id', $selectedVisaTopIds)
                ->orderBy('display_order')
                ->orderBy('id');
            $visaStatuses = $visaStatusesQuery->get();
            $statusOrderMap = array_flip($selectedVisaTopIds);
            $visaStatuses = $visaStatuses
                ->sortBy(fn($status) => $statusOrderMap[(int) $status->id] ?? PHP_INT_MAX)
                ->values();
        }

        $visaStatusSliderCounts = [];
        foreach ($visaStatuses as $vsRow) {
            $visaStatusSliderCounts[$vsRow->id] = [
                'paid' => (clone $sliderBaseQuery)->tap(function ($q) use ($vsRow) {
                    $this->applyExpenseAccountMatchesVisaExpense($q, function ($sub) use ($vsRow) {
                        $sub->where('ve.visa_status', $vsRow->name)
                            ->when($vsRow->visa_renewal_category_id, fn ($q2) => $q2->where('ve.renewal_category_id', $vsRow->visa_renewal_category_id))
                            ->where('ve.payment_status', 'paid');
                    });
                })->count(),
                'unpaid' => (clone $sliderBaseQuery)->tap(function ($q) use ($vsRow) {
                    $this->applyExpenseAccountMatchesVisaExpense($q, function ($sub) use ($vsRow) {
                        $sub->where('ve.visa_status', $vsRow->name)
                            ->when($vsRow->visa_renewal_category_id, fn ($q2) => $q2->where('ve.renewal_category_id', $vsRow->visa_renewal_category_id))
                            ->where('ve.payment_status', 'unpaid');
                    });
                })->count(),
            ];
        }

        if ($request->filled('payment_status')) {
            $status = $request->payment_status;
            if ($visaStatusFilterModel && in_array($status, ['paid', 'unpaid'], true)) {
                $this->applyExpenseAccountMatchesVisaExpense($query, function ($sub) use ($visaStatusFilterModel, $status) {
                    $sub->where('ve.visa_status', $visaStatusFilterModel->name)
                        ->when($visaStatusFilterModel->visa_renewal_category_id, fn ($q2) => $q2->where('ve.renewal_category_id', $visaStatusFilterModel->visa_renewal_category_id))
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
                                $link->whereColumn('ve.expense_account_id', 'expense_accounts.id');
                                $headId = VisaRenewalCategoryService::visaExpenseHeadAccountId();
                                if ($headId !== null) {
                                    $link->orWhere(function ($l2) use ($headId) {
                                        $l2->where('ve.expense_account_id', $headId)
                                            ->whereColumn('ve.rider_id', 'expense_accounts.rider_id')
                                            ->whereColumn('ve.renewal_category_id', 'expense_accounts.renewal_category_id');
                                    });
                                }
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
                $sub->where('ve.visa_status', $visaStatusFilterModel->name)
                    ->when($visaStatusFilterModel->visa_renewal_category_id, fn ($q2) => $q2->where('ve.renewal_category_id', $visaStatusFilterModel->visa_renewal_category_id));
            });
        }

        $statsQuery = clone $query;
        $data = $this->applyPagination($query, $paginationParams);
        $riders = Riders::orderBy('name')->get();
        $employees = Employee::query()
            ->where(function ($q) {
                $q->where('status', 'active')
                    ->orWhere('status', 1)
                    ->orWhere('status', '1');
            })
            ->orderBy('name')
            ->get();
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
                'employees' => $employees,
                'stats' => $stats,
            ]);
        }

        return view('visa_expenses.account_index', [
            'data' => $data,
            'riders' => $riders,
            'employees' => $employees,
            'stats' => $stats,
            'riderIds' => $expenseAccountIds,
            'visaStatuses' => $visaStatuses,
            'visaStatusSliderCounts' => $visaStatusSliderCounts,
            'nextUnpaidVisaByAccountId' => $nextUnpaidVisaByAccountId,
            'urgentVisaExpiryByAccountId' => $urgentVisaExpiryByAccountId,
            'renewalCategories' => VisaRenewalCategoryService::activeOrdered(),
            'visaHeadAccountConfigured' => VisaRenewalCategoryService::visaExpenseHeadAccountId() !== null,
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
        $defaultCategoryId = (int) VisaRenewalCategoryService::defaultCategory()->id;
        $riderCategoryToEaId = [];
        foreach ($items as $accountRow) {
            if ($accountRow->rider_id === null) {
                continue;
            }
            $catId = (int) ($accountRow->renewal_category_id ?? $defaultCategoryId);
            $riderCategoryToEaId[(int) $accountRow->rider_id . ':' . $catId] = (int) $accountRow->id;
        }

        $headId = VisaRenewalCategoryService::visaExpenseHeadAccountId();

        $unpaidRows = visa_expenses::query()
            ->where('payment_status', 'unpaid')
            ->where(function ($q) use ($ids, $riderCategoryToEaId, $headId, $defaultCategoryId) {
                $q->whereIn('expense_account_id', $ids);
                if ($headId !== null) {
                    $q->orWhere(function ($q2) use ($riderCategoryToEaId, $headId, $defaultCategoryId) {
                        $q2->where('expense_account_id', $headId);
                        if (!empty($riderCategoryToEaId)) {
                            $q2->where(function ($q3) use ($riderCategoryToEaId, $defaultCategoryId) {
                                foreach ($riderCategoryToEaId as $key => $eaId) {
                                    [$riderId, $catId] = array_map('intval', explode(':', $key, 2));
                                    $q3->orWhere(function ($q4) use ($riderId, $catId, $defaultCategoryId) {
                                        $q4->where('rider_id', $riderId)
                                            ->where('renewal_category_id', $catId ?: $defaultCategoryId);
                                    });
                                }
                            });
                        }
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
            } elseif ($veEa === $headId && $ve->rider_id !== null) {
                $catId = (int) ($ve->renewal_category_id ?? $defaultCategoryId);
                $key = (int) $ve->rider_id . ':' . $catId;
                if (isset($riderCategoryToEaId[$key])) {
                    $eaId = $riderCategoryToEaId[$key];
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

        $ids = $items->pluck('id')->map(static fn($id) => (int) $id)->all();
        $defaultCategoryId = (int) VisaRenewalCategoryService::defaultCategory()->id;
        $riderCategoryToEaId = [];
        foreach ($items as $accountRow) {
            if ($accountRow->rider_id === null) {
                continue;
            }
            $catId = (int) ($accountRow->renewal_category_id ?? $defaultCategoryId);
            $riderCategoryToEaId[(int) $accountRow->rider_id . ':' . $catId] = (int) $accountRow->id;
        }

        $headId = VisaRenewalCategoryService::visaExpenseHeadAccountId();
        $threshold = now()->addDays($withinDays)->startOfDay();

        $rows = visa_expenses::query()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $threshold)
            ->where(function ($q) use ($ids, $riderCategoryToEaId, $headId, $defaultCategoryId) {
                $q->whereIn('expense_account_id', $ids);
                if ($headId !== null) {
                    $q->orWhere(function ($q2) use ($riderCategoryToEaId, $headId, $defaultCategoryId) {
                        $q2->where('expense_account_id', $headId);
                        if (!empty($riderCategoryToEaId)) {
                            $q2->where(function ($q3) use ($riderCategoryToEaId, $defaultCategoryId) {
                                foreach ($riderCategoryToEaId as $key => $eaId) {
                                    [$riderId, $catId] = array_map('intval', explode(':', $key, 2));
                                    $q3->orWhere(function ($q4) use ($riderId, $catId, $defaultCategoryId) {
                                        $q4->where('rider_id', $riderId)
                                            ->where('renewal_category_id', $catId ?: $defaultCategoryId);
                                    });
                                }
                            });
                        }
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
            } elseif ($veEa === $headId && $ve->rider_id !== null) {
                $catId = (int) ($ve->renewal_category_id ?? $defaultCategoryId);
                $key = (int) $ve->rider_id . ':' . $catId;
                if (isset($riderCategoryToEaId[$key])) {
                    $eaId = $riderCategoryToEaId[$key];
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
        $headId = VisaRenewalCategoryService::visaExpenseHeadAccountId();
        $expenseAccountQuery->whereExists(function ($sub) use ($constraintsOnVeSubquery, $headId) {
            $sub->select(DB::raw(1))
                ->from('visa_expenses as ve')
                ->whereNull('ve.deleted_at')
                ->where(function ($link) use ($headId) {
                    $link->whereColumn('ve.expense_account_id', 'expense_accounts.id');
                    if ($headId !== null) {
                        $link->orWhere(function ($l2) use ($headId) {
                            $l2->where('ve.expense_account_id', $headId)
                                ->whereColumn('ve.rider_id', 'expense_accounts.rider_id')
                                ->whereColumn('ve.renewal_category_id', 'expense_accounts.renewal_category_id');
                        });
                    }
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
            'person_key' => ['required', 'string', 'regex:/^(rider|employee):\d+$/'],
            'renewal_category_id' => 'required|exists:visa_renewal_categories,id',
        ]);

        [$personType, $personId] = explode(':', $request->person_key, 2);
        $personId = (int) $personId;
        $categoryId = (int) $request->renewal_category_id;
        $category = VisaRenewalCategoryService::findActive($categoryId);

        if (!$category) {
            Flash::error('The selected renewal category is not active.');
            return redirect()->back()->withInput();
        }

        if (VisaRenewalCategoryService::accountForPersonCategory($personType, $personId, $categoryId)) {
            Flash::error('A visa expense account already exists for this ' . $personType . ' in the "' . $category->name . '" category.');
            return redirect()->back()->withInput();
        }

        if (!VisaRenewalCategoryService::canCreateAccountForPersonCategory($personType, $personId, $categoryId)) {
            $next = VisaRenewalCategoryService::nextCreatableCategoryForPerson($personType, $personId);
            if ($next) {
                Flash::error('You must fully pay all entries in the previous renewal category before creating an account for "' . $category->name . '". The next allowed category is "' . $next->name . '".');
            } else {
                Flash::error('Cannot create a new visa expense account for "' . $category->name . '". Complete all unpaid entries in the previous renewal category first, or this category is not yet available in sequence.');
            }
            return redirect()->back()->withInput();
        }

        if ($personType === 'employee') {
            $person = Employee::findOrFail($personId);
            $personName = $person->name;
            $branchId = $person->branch_id;
            $ledgerAccountId = $person->account_id;
            $personFields = [
                'rider_id' => null,
                'employee_id' => $person->id,
            ];
            $refPrefix = 'VS-E' . $person->id;
        } else {
            $person = Riders::findOrFail($personId);
            $personName = $person->name;
            $branchId = $person->branch_id;
            $ledgerAccountId = $person->account_id;
            $personFields = [
                'rider_id' => $person->id,
                'employee_id' => null,
            ];
            $refPrefix = 'VS-' . $person->id;
        }

        $generatedExpenseTotal = 0.0;
        $activeStatuses = collect();
        DB::beginTransaction();
        try {
            if ($ledgerAccountId && ExpenseAccount::query()->visa()->where('account_id', $ledgerAccountId)->exists()) {
                $ledgerAccountId = null;
            }

            $expenseAccount = ExpenseAccount::create(array_merge($personFields, [
                'name' => $personName . ' - ' . $category->name,
                'module' => ExpenseAccount::MODULE_VISA,
                'renewal_category_id' => $categoryId,
                'branch_id' => $branchId,
                'account_id' => $ledgerAccountId,
                'company_id' => auth()->user()->company_id ?? null,
            ]));

            $activeStatuses = VisaRenewalCategoryService::activeStatusesForCategory($categoryId);

            foreach ($activeStatuses as $status) {
                $amount = (float) ($status->default_fee ?? 0);
                $generatedExpenseTotal += $amount;
                visa_expenses::create(array_merge($personFields, [
                    'branch_id' => $branchId,
                    'trans_date' => Carbon::today()->format('Y-m-d'),
                    'trans_code' => Account::trans_code(),
                    'date' => Carbon::today()->format('Y-m-d'),
                    'expense_account_id' => $expenseAccount->id,
                    'renewal_category_id' => $categoryId,
                    'visa_status' => $status->name,
                    'detail' => $status->description ?? ('Auto-generated from active visa status: ' . $status->name),
                    'reference_number' => $refPrefix . '-' . $status->id . '-' . $categoryId,
                    'billing_month' => Carbon::today()->startOfMonth()->format('Y-m-d'),
                    'amount' => $amount,
                    'payment_status' => 'unpaid',
                ]));
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Flash::error('Error creating visa expense account: ' . $e->getMessage());
            return redirect()->back();
        }

        $expenseAccount->load('renewalCategory');
        $installmentResult = ['created' => false, 'message' => ''];
        try {
            DB::beginTransaction();
            $installmentResult = $this->autoCreateInstallmentPlanForRenewal($expenseAccount, $generatedExpenseTotal);
            DB::commit();
        } catch (\Throwable $installmentError) {
            DB::rollBack();
            report($installmentError);
            $installmentResult = [
                'created' => false,
                'message' => 'Visa expenses were created, but auto installment plan failed: ' . $installmentError->getMessage(),
            ];
        }

        $successMessage = 'Visa expense account created for ' . $category->name . ' and tickets generated for that category only.';
        if ($activeStatuses->isEmpty()) {
            $successMessage = 'Visa expense account created for ' . $category->name . '. No tickets were generated because this category has no visa statuses yet.';
        }
        if (!empty($installmentResult['message'])) {
            $successMessage .= ' ' . $installmentResult['message'];
        }
        if (!empty($installmentResult['created'])) {
            Flash::success($successMessage);
        } else {
            Flash::warning($successMessage);
        }

        return redirect()->back();
    }

    public function eligibleRenewalCategories(Request $request, $company_slug, $personType, $personId)
    {
        $personType = strtolower((string) $personType);
        abort_unless(in_array($personType, ['rider', 'employee'], true), 404);

        $categories = VisaRenewalCategoryService::creatableCategoriesForPerson($personType, (int) $personId);

        return response()->json([
            'categories' => $categories->map(static fn($c) => [
                'id' => (int) $c->id,
                'name' => $c->name,
            ])->values(),
        ]);
    }

    public function editaccount(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:expense_accounts,id',
            'person_key' => ['required', 'string', 'regex:/^(rider|employee):\d+$/'],
        ]);

        [$personType, $personId] = explode(':', $request->person_key, 2);
        $personId = (int) $personId;
        $account = ExpenseAccount::query()->visa()->with('renewalCategory')->findOrFail($request->id);

        if ($personType === 'employee') {
            $person = Employee::findOrFail($personId);
            $account->employee_id = $person->id;
            $account->rider_id = null;
            $suffix = $account->renewalCategory?->name;
            $account->name = $person->name . ($suffix ? ' - ' . $suffix : '');
            $account->branch_id = $person->branch_id;
        } else {
            $person = Riders::findOrFail($personId);
            $account->rider_id = $person->id;
            $account->employee_id = null;
            $suffix = $account->renewalCategory?->name;
            $account->name = $person->name . ($suffix ? ' - ' . $suffix : '');
            $account->branch_id = $person->branch_id;
        }

        $account->module = ExpenseAccount::MODULE_VISA;
        $account->save();

        Flash::success('Visa expense account updated successfully.');
        return redirect()->back();
    }

    public function deleteaccount($company_slug, $id)
    {
        $account = ExpenseAccount::query()->visa()->findOrFail($id);

        $hasInstallments = visa_installment_plan::where('rider_id', $id)->exists();
        $extraBlock = $hasInstallments
            ? 'Cannot delete account. Installment Plan entries exist for this account.'
            : null;

        return $this->destroyExpenseAccount(
            $account,
            visa_expenses::class,
            'Cannot delete account. Paid Visa Expense entries exist. Reverse those payments first.',
            $extraBlock
        );
    }

    public function generatentries(Request $request, $company_slug, $id)
    {
        // Check if user is authenticated first
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!user_can('visaexpense_view')) {
            abort(403, 'Unauthorized action.');
        }
        $account = ExpenseAccount::query()->visa()->with(['rider', 'employee', 'renewalCategory'])->where('id', $id)->firstOrFail();
        $riderId = $account->rider_id ? (int) $account->rider_id : null;
        $activeRenewalCategory = VisaRenewalCategoryService::resolveCategoryForAccount($account);
        $activeCategoryId = (int) $activeRenewalCategory->id;

        $this->checkAndAutoMarkInstallments($account);

        $siblingAccounts = VisaRenewalCategoryService::siblingAccountsForPerson($account, (int) $account->id);
        $canAddExpense = true;

        // Use global pagination traits
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = VisaRenewalCategoryService::expensesForAccountQuery((int) $account->id, $riderId, $activeCategoryId)
            ->with('vouchers')
            ->orderBy('id', 'asc');
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
            ->orderBy('date', 'asc');
        $this->applyInstallmentRiderScope($installmentQuery, $account);
        $installmentData = $this->applyPagination($installmentQuery, $paginationParams);
        $installmentStats = $this->installmentPlanSummary($account);

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
        $visaStatuses = VisaRenewalCategoryService::activeStatusesForCategory($activeCategoryId);
        $viewData = [
            'data' => $data,
            'installmentData' => $installmentData,
            'installmentStats' => $installmentStats,
            'account' => $account,
            'visaStatuses' => $visaStatuses,
            'activeRenewalCategory' => $activeRenewalCategory,
            'siblingAccounts' => $siblingAccounts,
            'canAddExpense' => $canAddExpense,
        ];

        if ($account->isEmployeeAccount()) {
            $viewData['riders'] = $account->employee;
            return view('visa_expenses.index_employee', $viewData);
        }

        $viewData['riders'] = $account->rider ?? Riders::findOrFail($riderId);
        return view('visa_expenses.index', $viewData);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($company_slug, $id)
    {
        $data = ExpenseAccount::query()->visa()->with('renewalCategory')->where('id', $id)->firstOrFail();
        $activeRenewalCategory = VisaRenewalCategoryService::resolveCategoryForAccount($data);
        $visaStatuses = VisaRenewalCategoryService::activeStatusesForCategory((int) $activeRenewalCategory->id);

        return view('visa_expenses.create', compact('data', 'visaStatuses', 'activeRenewalCategory'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $expenseAccount = ExpenseAccount::query()->visa()->findOrFail($request->rider_id);
        $renewalCategoryId = (int) ($expenseAccount->renewal_category_id ?? VisaRenewalCategoryService::defaultCategory()->id);

        $validated = $request->validate([
            'rider_id'       => 'required|exists:expense_accounts,id',
            'visa_status'    => [
                'required',
                'string',
                'max:255',
                Rule::exists('visa_statuses', 'name')->where(function ($query) use ($renewalCategoryId) {
                    $query->where('visa_renewal_category_id', $renewalCategoryId)
                        ->where('is_active', 1)
                        ->whereNull('deleted_at');
                    $companyId = CompanyContext::id();
                    if ($companyId !== null && Schema::hasColumn('visa_statuses', 'company_id')) {
                        $query->where('company_id', $companyId);
                    }
                }),
                Rule::unique('visa_expenses')->where(function ($query) use ($request, $renewalCategoryId) {
                    return $query->where('expense_account_id', $request->rider_id)
                        ->where('renewal_category_id', $renewalCategoryId)
                        ->whereNull('deleted_at');
                }),
            ],
            'billing_month'  => 'required|date_format:Y-m',
            'detail'         => 'nullable|string',
            'reference_number' => 'required|string|max:255',
            'amount'         => 'required|numeric|min:0',
            'attach_file'    => 'nullable|string|max:255',
        ], [
            'visa_status.exists' => 'The selected visa status does not belong to this visa category.',
            'visa_status.unique' => 'This visa status already has a ticket in this category.',
        ]);

        try {
            $trans_code = Account::trans_code();
            $billingMonth = $validated['billing_month'] . "-01";
            $trans_date = Carbon::today();
            $visaExpenses = visa_expenses::create([
                'rider_id'       => $expenseAccount->rider_id,
                'employee_id'    => $expenseAccount->employee_id,
                'expense_account_id' => $expenseAccount->id,
                'renewal_category_id' => $renewalCategoryId,
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
        $accounts = ExpenseAccount::query()->visa()->with(['rider', 'employee'])->find($data->expense_account_id)
            ?? ExpenseAccount::query()
            ->visa()
            ->with(['rider', 'employee'])
            ->where(function ($q) use ($data) {
                if ($data->employee_id) {
                    $q->where('employee_id', $data->employee_id);
                } else {
                    $q->where('rider_id', $data->rider_id);
                }
            })
            ->when($data->renewal_category_id, fn($q) => $q->where('renewal_category_id', $data->renewal_category_id))
            ->orderBy('id')
            ->first();
        return view('visa_expenses.viewvoucher', compact('data', 'accounts'));
    }

    /**
     * Expense-voucher-style payment modal for an unpaid Visa Expense entry.
     */
    public function payForm(Request $request, $company_slug, $id)
    {
        if (!user_can('visaexpense_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $data = visa_expenses::findOrFail($id);

        if ($data->payment_status === 'paid') {
            return response(
                '<div class="alert alert-warning m-2 mb-0">This Visa Expense is already paid.</div>',
                200
            );
        }

        $accounts = ExpenseAccount::query()->visa()->with(['rider', 'employee'])->find($data->expense_account_id)
            ?? ExpenseAccount::query()
            ->visa()
            ->with(['rider', 'employee'])
            ->where(function ($q) use ($data) {
                if ($data->employee_id) {
                    $q->where('employee_id', $data->employee_id);
                } else {
                    $q->where('rider_id', $data->rider_id);
                }
            })
            ->when($data->renewal_category_id, fn($q) => $q->where('renewal_category_id', $data->renewal_category_id))
            ->orderBy('id')
            ->firstOrFail();

        $rider = $accounts->isEmployeeAccount()
            ? ($accounts->employee ?? Employee::find($accounts->employee_id))
            : ($accounts->rider ?? Riders::find($accounts->rider_id));

        $bankCashAccounts = $this->visaExpensePaymentAccountOptions();
        if ($bankCashAccounts->isEmpty()) {
            $bankCashAccounts = Accounts::bankAndCashDropdown()
                ->filter(static fn($label, $accId) => $accId !== '' && $accId !== null);
        }

        return view('visa_expenses.pay_form', compact('data', 'accounts', 'rider', 'bankCashAccounts'));
    }

    public function payfine(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'id' => 'required|exists:visa_expenses,id',
                'account' => 'required|exists:accounts,id',
                'expiry_date' => 'required|date',
                'narration' => 'nullable|string|max:1000',
                'vat_percent' => 'nullable|numeric|min:0|max:100',
                'vat_amount' => 'nullable|numeric|min:0',
                'attach_file' => 'nullable|file|max:10240',
            ]);

            $expense = visa_expenses::findOrFail($validated['id']);
            $expenseAccount = ExpenseAccount::query()->visa()->find($expense->expense_account_id);
            if (!$expenseAccount) {
                $expenseAccount = ExpenseAccount::query()
                    ->visa()
                    ->where(function ($q) use ($request, $expense) {
                        if ($request->filled('employee_id') || $expense->employee_id) {
                            $q->where('employee_id', $request->input('employee_id', $expense->employee_id));
                        } else {
                            $q->where('rider_id', $request->rider_id);
                        }
                    })
                    ->when($expense->renewal_category_id, fn($q) => $q->where('renewal_category_id', $expense->renewal_category_id))
                    ->first();
            }

            if (!$expenseAccount) {
                throw new \RuntimeException('Visa expense account not found for this payment.');
            }

            if ($expense->payment_status === 'paid') {
                Flash::error('This Visa Expense is already paid.');
                DB::rollBack();

                return redirect(VisaRenewalCategoryService::generatentriesUrl(
                    (int) $expenseAccount->id,
                    (int) $expenseAccount->rider_id
                ));
            }

            $expense->pay_account = $validated['account'];
            $expense->payment_status = 'paid';
            $expense->expiry_date = $validated['expiry_date'];

            $payAccount = Accounts::findOrFail($validated['account']);
            $payment_type_flag = match ($request->input('payment_type', $payAccount->account_type)) {
                'Liability' => 1,
                'Asset' => 0,
                default => ($payAccount->account_type === 'Liability' ? 1 : 0),
            };

            $docFile = null;
            if ($request->hasFile('attach_file')) {
                $docFile = $request->file('attach_file')->store('vouchers', 'public');
            }

            $narration = trim((string) ($validated['narration'] ?? ''));
            if ($narration === '') {
                $narration = $expense->detail ?: 'Visa Expense Payment';
            }

            $baseAmount = round((float) $expense->amount, 2);
            $vatPercent = round((float) ($validated['vat_percent'] ?? 0), 4);
            $vatAmount = round((float) ($validated['vat_amount'] ?? 0), 2);
            if ($vatAmount <= 0 && $vatPercent > 0 && $baseAmount > 0) {
                $vatAmount = round(($baseAmount * $vatPercent) / 100, 2);
            }
            $grandTotal = round($baseAmount + $vatAmount, 2);

            $trans_code = Account::trans_code();
            $TransactionService = new TransactionService();

            $billingMonth = $expense->billing_month ?? date('Y-m-01');
            $transDate = $expense->trans_date ?? Carbon::today()->format('Y-m-d');

            if ($baseAmount > 0) {
                $TransactionService->recordTransaction([
                    'account_id'     => GlobalAccounts::id('VISA_EXPENSE_ACCOUNT'),
                    'reference_id'   => $expense->id,
                    'reference_type' => 'LV',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $transDate,
                    'narration'      => $narration,
                    'debit'          => $baseAmount,
                    'billing_month'  => $billingMonth,
                    'branch_id'      => $expense->branch_id,
                ]);
            }

            if ($vatAmount > 0) {
                $TransactionService->recordTransaction([
                    'account_id'     => GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'),
                    'reference_id'   => $expense->id,
                    'reference_type' => 'LV',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $transDate,
                    'narration'      => 'VAT: ' . $narration,
                    'debit'          => $vatAmount,
                    'billing_month'  => $billingMonth,
                    'branch_id'      => $expense->branch_id,
                ]);
            }

            if ($grandTotal > 0) {
                $TransactionService->recordTransaction([
                    'account_id'     => $validated['account'],
                    'reference_id'   => $expense->id,
                    'reference_type' => 'LV',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $transDate,
                    'narration'      => $narration,
                    'credit'         => $grandTotal,
                    'billing_month'  => $billingMonth,
                    'branch_id'      => $expense->branch_id,
                ]);
            }

            Vouchers::create([
                'branch_id'        => $expense->branch_id,
                'trans_date'       => $transDate,
                'trans_code'       => $trans_code,
                'trip_date'        => $request->trip_date,
                'billing_month'    => $billingMonth,
                'payment_type'     => $payment_type_flag,
                'voucher_type'     => 'LV',
                'remarks'          => $vatAmount > 0
                    ? ('Visa Expense Voucher (incl. VAT ' . number_format($vatAmount, 2) . ')')
                    : 'Visa Expense Voucher',
                'amount'           => $grandTotal,
                'reference_number' => $expense->reference_number ?? null,
                'Created_By'       => $request->Created_By ?? auth()->id(),
                'attach_file'      => $docFile,
                'pay_account'      => $validated['account'],
                'ref_id'           => $expense->id,
                'custom_field_values' => $request->input('voucher_custom_fields', []),
            ]);

            $total_amount = $grandTotal;
            $lastLedger = CompanyQuery::table('ledger_entries')
                ->where('account_id', $validated['account'])
                ->orderBy('billing_month', 'desc')
                ->first();

            $opening_balance = $lastLedger ? $lastLedger->closing_balance : 0.00;
            $debit_balance = $credit_balance = 0.00;

            if ($payment_type_flag === 1) {
                $debit_balance = $total_amount;
                $closing_balance = $opening_balance + $total_amount;
            } else {
                $credit_balance = $total_amount;
                $closing_balance = $opening_balance - $total_amount;
            }

            CompanyQuery::table('ledger_entries')->insert([
                'account_id'      => $validated['account'],
                'billing_month'   => $billingMonth,
                'opening_balance' => $opening_balance,
                'debit_balance'   => $debit_balance,
                'credit_balance'  => $credit_balance,
                'closing_balance' => $closing_balance,
                'branch_id'       => $expense->branch_id,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $expense->save();
            DB::commit();
            Flash::success('Visa Expense Paid Successfully with Transaction and Ledger Entries.');
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());

            if (isset($expenseAccount) && $expenseAccount) {
                return redirect(VisaRenewalCategoryService::generatentriesUrl(
                    (int) $expenseAccount->id,
                    (int) $expenseAccount->rider_id
                ))->withInput();
            }

            return redirect()->back()->withInput();
        }

        return redirect(VisaRenewalCategoryService::generatentriesUrl(
            (int) $expenseAccount->id,
            (int) $expenseAccount->rider_id
        ));
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
        $data = ExpenseAccount::query()->visa()->where('id', $visaExpenses->expense_account_id ?? $visaExpenses->rider_id)->first();
        if (empty($visaExpenses)) {
            Flash::error('Visa Expenses not found');

            return redirect(route('visaExpenses.index'));
        }
        $categoryId = (int) ($visaExpenses->renewal_category_id
            ?? $data->renewal_category_id
            ?? VisaRenewalCategoryService::defaultCategory()->id);
        $visaStatuses = VisaRenewalCategoryService::activeStatusesForCategory($categoryId);
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
        if (!user_can('visaexpense_edit')) {
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
        if (!user_can('visaexpense_edit')) {
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

        $debitAccountName = Accounts::where('id', GlobalAccounts::id('VISA_EXPENSE_ACCOUNT'))->value('name') ?? 'Visa expense';
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
        if (!user_can('visaexpense_edit')) {
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
        if ($newAccountId === (int) GlobalAccounts::id('VISA_EXPENSE_ACCOUNT')) {
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

        return $this->destroyExpenseEntry(
            $visaExpenses,
            'LV',
            'LV',
            'Visa Expense Entry not found',
            'Visa Expenses Entry deleted successfully.',
            'Visa Expense payment reversed. Status is now unpaid.'
        );
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
     * Resolve visa expense context to an ExpenseAccount from expense_accounts.id, riders.id, or legacy accounts.id.
     */
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
