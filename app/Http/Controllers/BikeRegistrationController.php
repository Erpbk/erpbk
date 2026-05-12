<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Helpers\HeadAccount;
use App\Models\Accounts;
use App\Models\BikeRegistration;
use App\Models\BikeRegistrationAccount;
use App\Models\BikeRegistrationDetail;
use App\Models\BikeRegistrationStatus;
use App\Models\Bikes;
use App\Models\LedgerEntry;
use App\Models\Riders;
use App\Models\Settings;
use App\Models\Transactions;
use App\Models\VoucherType;
use App\Models\Vouchers;
use App\Repositories\BikeRegistrationsRepository;
use App\Services\TransactionService;
use App\Support\CompanyAuthRedirect;
use App\Support\CompanyContext;
use App\Support\CompanyQuery;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Carbon\Carbon;
use DB;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BikeRegistrationController extends AppBaseController
{
    use GlobalPagination, TracksCascadingDeletions;

    protected $bikeRegistrationRepo;

    public function __construct(BikeRegistrationsRepository $bikeRegistrationRepo)
    {
        $this->bikeRegistrationRepo = $bikeRegistrationRepo;
    }

    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!auth()->user()->hasPermissionTo('bike_registration_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $userBranches = app('user_branches');
        $query = BikeRegistrationAccount::query()
            ->with([
                'bike',
                'rider.bikes' => static function ($q) {
                    $q->orderByDesc('id');
                },
            ])
            ->orderByDesc('id');

        if (!auth()->user()->isAdmin()) {
            if (!empty($userBranches)) {
                $query->where(function ($w) use ($userBranches) {
                    $w->whereHas('rider', function ($q) use ($userBranches) {
                        $q->whereIn('branch_id', $userBranches)->orWhereNull('branch_id');
                    })->orWhereHas('bike', function ($q) use ($userBranches) {
                        $q->whereIn('branch_id', $userBranches)->orWhereNull('branch_id');
                    });
                });
            } else {
                $query->where(function ($w) {
                    $w->whereHas('rider', function ($q) {
                        $q->whereNull('branch_id');
                    })->orWhereHas('bike', function ($q) {
                        $q->whereNull('branch_id');
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
                    ->orWhereExists(function ($sub) use ($term) {
                        $sub->select(DB::raw(1))
                            ->from('bikes')
                            ->whereNull('bikes.deleted_at')
                            ->whereColumn('bikes.rider_id', 'bike_registration_accounts.rider_id')
                            ->where(function ($b) use ($term) {
                                $b->where('bikes.plate', 'like', '%' . $term . '%')
                                    ->orWhere('bikes.bike_code', 'like', '%' . $term . '%')
                                    ->orWhere('bikes.chassis_number', 'like', '%' . $term . '%')
                                    ->orWhere('bikes.model', 'like', '%' . $term . '%');
                            });
                    })
                    ->orWhereExists(function ($sub) use ($term) {
                        $sub->select(DB::raw(1))
                            ->from('bikes')
                            ->whereNull('bikes.deleted_at')
                            ->whereColumn('bikes.id', 'bike_registration_accounts.bike_id')
                            ->where(function ($b) use ($term) {
                                $b->where('bikes.plate', 'like', '%' . $term . '%')
                                    ->orWhere('bikes.bike_code', 'like', '%' . $term . '%')
                                    ->orWhere('bikes.chassis_number', 'like', '%' . $term . '%')
                                    ->orWhere('bikes.model', 'like', '%' . $term . '%');
                            });
                    });
            });
        }

        $registrationStatusFilterModel = null;
        if ($request->filled('registration_status_id')) {
            $registrationStatusFilterModel = BikeRegistrationStatus::find($request->registration_status_id);
        }

        $sliderBaseQuery = clone $query;

        $bikeTopEnabledRaw = (string) (Settings::query()
            ->where('name', 'bike_registration_top_enabled')
            ->value('value') ?? '1');
        $bikeTopEnabled = in_array(strtolower(trim($bikeTopEnabledRaw)), ['1', 'true', 'yes', 'on'], true);

        $selectedBikeTopIdsRaw = (string) (Settings::query()
            ->where('name', 'bike_registration_top_status_ids')
            ->value('value') ?? '');
        $selectedBikeTopIds = collect(json_decode($selectedBikeTopIdsRaw, true))
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $registrationStatuses = collect();
        if ($bikeTopEnabled && !empty($selectedBikeTopIds)) {
            $registrationStatusesQuery = BikeRegistrationStatus::query()
                ->where('is_active', 1)
                ->whereIn('id', $selectedBikeTopIds)
                ->orderBy('display_order')
                ->orderBy('id');
            $registrationStatuses = $registrationStatusesQuery->get();
            $statusOrderMap = array_flip($selectedBikeTopIds);
            $registrationStatuses = $registrationStatuses
                ->sortBy(fn($status) => $statusOrderMap[(int) $status->id] ?? PHP_INT_MAX)
                ->values();
        }

        $registrationStatusSliderCounts = [];
        foreach ($registrationStatuses as $rsRow) {
            $registrationStatusSliderCounts[$rsRow->id] = [
                'paid' => (clone $sliderBaseQuery)->tap(function ($q) use ($rsRow) {
                    $this->applyBikeRegistrationAccountMatches($q, function ($sub) use ($rsRow) {
                        $sub->where('br.registration_status', $rsRow->name)->where('br.payment_status', 'paid');
                    });
                })->count(),
                'unpaid' => (clone $sliderBaseQuery)->tap(function ($q) use ($rsRow) {
                    $this->applyBikeRegistrationAccountMatches($q, function ($sub) use ($rsRow) {
                        $sub->where('br.registration_status', $rsRow->name)->where('br.payment_status', 'unpaid');
                    });
                })->count(),
            ];
        }

        if ($request->filled('payment_status')) {
            $status = $request->payment_status;
            if ($registrationStatusFilterModel && in_array($status, ['paid', 'unpaid'], true)) {
                $this->applyBikeRegistrationAccountMatches($query, function ($sub) use ($registrationStatusFilterModel, $status) {
                    $sub->where('br.registration_status', $registrationStatusFilterModel->name)
                        ->where('br.payment_status', $status);
                });
            } elseif (!$registrationStatusFilterModel) {
                if ($status === 'paid') {
                    $this->applyBikeRegistrationAccountMatches($query, function ($sub) {
                        $sub->whereRaw('1 = 1');
                    });
                    $query->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('bike_registrations as br')
                            ->whereNull('br.deleted_at')
                            ->where('br.payment_status', 'unpaid')
                            ->where(function ($link) {
                                $link->whereColumn('br.bike_registration_account_id', 'bike_registration_accounts.id')
                                    ->orWhere(function ($l2) {
                                        $l2->where('br.bike_registration_account_id', HeadAccount::BIKE_REGISTRATION_EXPENSE_ACCOUNT)
                                            ->whereColumn('br.rider_id', 'bike_registration_accounts.rider_id');
                                    });
                            });
                        $this->applyBikeRegistrationCompanyScopeForAlias($sub);
                    });
                } elseif ($status === 'unpaid') {
                    $this->applyBikeRegistrationAccountMatches($query, function ($sub) {
                        $sub->where('br.payment_status', 'unpaid');
                    });
                }
            }
        } elseif ($registrationStatusFilterModel) {
            $this->applyBikeRegistrationAccountMatches($query, function ($sub) use ($registrationStatusFilterModel) {
                $sub->where('br.registration_status', $registrationStatusFilterModel->name);
            });
        }

        $statsQuery = clone $query;
        $data = $this->applyPagination($query, $paginationParams);
        $bikes = Bikes::query()
            ->with('rider')
            ->orderByRaw('CASE WHEN COALESCE(status, 1) = 1 THEN 0 ELSE 1 END')
            ->orderBy('plate')
            ->get();
        $expenseAccountIds = $statsQuery->pluck('id')->toArray();
        $bikeRows = BikeRegistration::whereIn('bike_registration_account_id', $expenseAccountIds)->get();
        $stats = [
            'unpaid_accounts' => $bikeRows->where('payment_status', 'unpaid')->count(),
            'paid_amount' => $bikeRows->where('payment_status', 'paid')->sum('amount'),
            'unpaid_amount' => $bikeRows->where('payment_status', 'unpaid')->sum('amount'),
        ];

        $nextUnpaidByAccountId = $this->mapNextUnpaidForPage($data);
        $urgentExpiryByAccountId = $this->mapUrgentExpiryForPage($data);

        if ($request->ajax()) {
            $tableData = view('bike_registration.account_table', [
                'data' => $data,
                'nextUnpaidByAccountId' => $nextUnpaidByAccountId,
                'urgentExpiryByAccountId' => $urgentExpiryByAccountId,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'bikes' => $bikes,
                'stats' => $stats,
            ]);
        }

        return view('bike_registration.account_index', [
            'data' => $data,
            'bikes' => $bikes,
            'stats' => $stats,
            'riderIds' => $expenseAccountIds,
            'registrationStatuses' => $registrationStatuses,
            'registrationStatusSliderCounts' => $registrationStatusSliderCounts,
            'nextUnpaidByAccountId' => $nextUnpaidByAccountId,
            'urgentExpiryByAccountId' => $urgentExpiryByAccountId,
        ]);
    }

    private function mapNextUnpaidForPage($paginatorOrCollection): array
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

        $headId = (int) HeadAccount::BIKE_REGISTRATION_EXPENSE_ACCOUNT;

        $unpaidRows = BikeRegistration::query()
            ->where('payment_status', 'unpaid')
            ->where(function ($q) use ($ids, $riderToEaId, $headId) {
                $q->whereIn('bike_registration_account_id', $ids)
                    ->orWhere(function ($q2) use ($riderToEaId, $headId) {
                        $q2->where('bike_registration_account_id', $headId)
                            ->whereIn('rider_id', array_keys($riderToEaId));
                    });
            })
            ->orderByRaw('CASE WHEN date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('date', 'asc')
            ->orderBy('billing_month', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $nextByEaId = [];
        foreach ($unpaidRows as $row) {
            $eaId = null;
            $rowEa = (int) $row->bike_registration_account_id;
            if (in_array($rowEa, $ids, true)) {
                $eaId = $rowEa;
            } elseif ($rowEa === $headId && $row->rider_id !== null) {
                $rid = (int) $row->rider_id;
                if (isset($riderToEaId[$rid])) {
                    $eaId = $riderToEaId[$rid];
                }
            }
            if ($eaId === null || isset($nextByEaId[$eaId])) {
                continue;
            }
            $nextByEaId[$eaId] = $row;
        }

        return $nextByEaId;
    }

    private function mapUrgentExpiryForPage($paginatorOrCollection, int $withinDays = 10): array
    {
        if (!Schema::hasColumn((new BikeRegistration)->getTable(), 'expiry_date')) {
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

        $headId = (int) HeadAccount::BIKE_REGISTRATION_EXPENSE_ACCOUNT;
        $threshold = now()->addDays($withinDays)->startOfDay();

        $rows = BikeRegistration::query()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $threshold)
            ->where(function ($q) use ($ids, $riderToEaId, $headId) {
                $q->whereIn('bike_registration_account_id', $ids)
                    ->orWhere(function ($q2) use ($riderToEaId, $headId) {
                        $q2->where('bike_registration_account_id', $headId)
                            ->whereIn('rider_id', array_keys($riderToEaId));
                    });
            })
            ->orderBy('expiry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $byEaId = [];
        foreach ($rows as $row) {
            $eaId = null;
            $rowEa = (int) $row->bike_registration_account_id;
            if (in_array($rowEa, $ids, true)) {
                $eaId = $rowEa;
            } elseif ($rowEa === $headId && $row->rider_id !== null) {
                $rid = (int) $row->rider_id;
                if (isset($riderToEaId[$rid])) {
                    $eaId = $riderToEaId[$rid];
                }
            }
            if ($eaId === null || isset($byEaId[$eaId])) {
                continue;
            }
            $byEaId[$eaId] = $row;
        }

        return $byEaId;
    }

    private function applyBikeRegistrationAccountMatches($expenseAccountQuery, callable $constraintsOnSubquery): void
    {
        $headId = HeadAccount::BIKE_REGISTRATION_EXPENSE_ACCOUNT;
        $expenseAccountQuery->whereExists(function ($sub) use ($constraintsOnSubquery, $headId) {
            $sub->select(DB::raw(1))
                ->from('bike_registrations as br')
                ->whereNull('br.deleted_at')
                ->where(function ($link) use ($headId) {
                    $link->whereColumn('br.bike_registration_account_id', 'bike_registration_accounts.id')
                        ->orWhere(function ($l2) use ($headId) {
                            $l2->where('br.bike_registration_account_id', $headId)
                                ->whereColumn('br.rider_id', 'bike_registration_accounts.rider_id');
                        });
                });
            $constraintsOnSubquery($sub);
            $this->applyBikeRegistrationCompanyScopeForAlias($sub);
        });
    }

    private function applyBikeRegistrationCompanyScopeForAlias($subquery): void
    {
        if (!Schema::hasColumn((new BikeRegistration)->getTable(), 'company_id')) {
            return;
        }
        if (!CompanyContext::shouldApplyScope()) {
            return;
        }
        $cid = CompanyContext::id();
        if ($cid === null) {
            return;
        }
        $subquery->where('br.company_id', $cid);
    }

    public function accountcreate(Request $request, $company_slug)
    {
        $request->validate([
            'bike_id' => 'required|exists:bikes,id',
        ]);
        $bike = Bikes::findOrFail($request->bike_id);

        if (BikeRegistrationAccount::where('bike_id', $bike->id)->exists()) {
            Flash::error('Bike registration account already exists for this bike.');

            return redirect()->back();
        }

        $rider = $bike->rider_id ? Riders::find($bike->rider_id) : null;

        $accountName = $rider
            ? $rider->name
            : trim(($bike->plate ?: 'Bike') . ($bike->model ? ' — ' . $bike->model : ''));

        DB::beginTransaction();
        try {
            $expenseAccount = BikeRegistrationAccount::create([
                'name' => $accountName ?: ($bike->plate ?? 'Bike registration'),
                'rider_id' => $rider?->id,
                'bike_id' => $bike->id,
                'branch_id' => $rider?->branch_id ?? $bike->branch_id,
                'account_id' => $rider?->account_id,
                'company_id' => auth()->user()->company_id ?? null,
            ]);

            $activeStatuses = BikeRegistrationStatus::query()
                ->where('is_active', 1)
                ->orderBy('display_order')
                ->get();
                foreach ($activeStatuses as $status) {
                    $br = BikeRegistration::create([
                        'branch_id' => $expenseAccount->branch_id,
                        'trans_date' => Carbon::today()->format('Y-m-d'),
                        'trans_code' => Account::trans_code(),
                        'date' => Carbon::today()->format('Y-m-d'),
                        'rider_id' => $expenseAccount->rider_id !== null ? (string) $expenseAccount->rider_id : null,
                        'bike_registration_account_id' => $expenseAccount->id,
                    'registration_status' => $status->name,
                    'detail' => $status->description ?? ('Auto-generated from active registration status: ' . $status->name),
                    'reference_number' => 'BR-' . $expenseAccount->id . '-' . $status->id,
                    'billing_month' => Carbon::today()->startOfMonth()->format('Y-m-d'),
                    'amount' => (float) ($status->default_fee ?? 0),
                    'payment_status' => 'unpaid',
                ]);
                BikeRegistrationDetail::create([
                    'bike_registration_id' => $br->id,
                    'description' => $br->detail,
                    'amount' => $br->amount,
                    'sort_order' => 1,
                ]);
            }
            DB::commit();
            Flash::success('Bike registration account created and active status entries generated.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Flash::error('Error creating bike registration account: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function editaccount(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:bike_registration_accounts,id',
            'rider_id' => 'required|exists:riders,id',
        ]);
        $rider = Riders::findOrFail($request->rider_id);
        $account = BikeRegistrationAccount::findOrFail($request->id);
        $account->rider_id = $rider->id;
        $account->name = $rider->name;
        $account->save();
        Flash::success('Bike registration account updated successfully.');

        return redirect()->back();
    }

    public function deleteaccount($company_slug, $id)
    {
        $account = BikeRegistrationAccount::findOrFail($id);

        $paidExists = $this->bikeRegistrationExpenseListingQuery($account)
            ->where('payment_status', 'paid')
            ->exists();

        if ($paidExists) {
            Flash::error('Cannot delete account. Paid bike registration entries exist for this account.');

            return redirect()->back();
        }

        $entries = $this->bikeRegistrationExpenseListingQuery($account)->orderBy('id')->get();
        $accountLabel = $account->name ?? ('Bike Registration Account #' . $account->id);

        DB::beginTransaction();
        try {
            foreach ($entries as $bikeRegistration) {
                $entryLabel = 'Bike Registration #' . $bikeRegistration->id . ' — ' . ($bikeRegistration->registration_status ?? '');
                try {
                    $this->trackCascadeDeletion(
                        BikeRegistrationAccount::class,
                        $account->id,
                        $accountLabel,
                        BikeRegistration::class,
                        $bikeRegistration->id,
                        $entryLabel,
                        'hasMany',
                        'bike_registrations',
                        'soft',
                        'Cascade deletion from Bike Registration Account deletion'
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to track account→registration cascade: ' . $e->getMessage());
                }

                $this->purgeBikeRegistrationEntryAndRelatedRecords($bikeRegistration);
            }

            BikeRegistrationAccount::where('id', $id)->delete();

            DB::commit();

            $msg = 'Account deleted successfully.';
            if ($entries->isNotEmpty()) {
                $msg .= ' Cascaded deletion of ' . $entries->count() . ' expense row(s), related transactions, vouchers, and details.';
            }
            Flash::success($msg);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            Flash::error('Could not delete account: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function generatentries(Request $request, $company_slug, $id)
    {
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!auth()->user()->hasPermissionTo('bike_registration_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $account = BikeRegistrationAccount::with(['rider', 'bike'])->where('id', $id)->firstOrFail();

        $query = $this->bikeRegistrationExpenseListingQuery($account)
            ->with('vouchers')
            ->orderBy('id', 'asc');

        if ($request->has('trans_date') && !empty($request->trans_date)) {
            $fromDate = Carbon::createFromFormat('Y-m-d', $request->trans_date);
            $query->where('trans_date', $fromDate);
        }
        if ($request->has('trans_code') && !empty($request->trans_code)) {
            $query->where('trans_code', $request->trans_code);
        }
        if ($request->filled('date')) {
            $toDate = Carbon::createFromFormat('Y-m-d', $request->date);
            $query->where('date', '<=', $toDate);
        }
        if ($request->has('registration_status') && !empty($request->registration_status)) {
            $query->where('registration_status', $request->registration_status);
        }
        if ($request->has('payment_status') && !empty($request->payment_status)) {
            $query->where('payment_status', $request->payment_status);
        }

        $data = $this->applyPagination($query, $paginationParams);
        $registrationStatuses = BikeRegistrationStatus::orderBy('display_order', 'asc')->where('is_active', 1)->get();
        $expenseTotals = $this->bikeRegistrationExpenseTotals($account);

        if ($request->ajax()) {
            $tableData = view('bike_registration.table', [
                'data' => $data,
                'account' => $account,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            $panelHtml = view('bike_registration.partials.expenses_panel', [
                'data' => $data,
                'account' => $account,
                'expenseTotals' => $expenseTotals,
                'embeddedInModal' => $request->boolean('modal'),
            ])->render();

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'panelHtml' => $panelHtml,
                'expenseTotals' => $expenseTotals,
            ]);
        }

        $bikes = $account->bike;
        if (!$bikes && $account->bike_id) {
            $bikes = Bikes::find($account->bike_id);
        }

        return view('bike_registration.index', [
            'data' => $data,
            'account' => $account,
            'bikes' => $bikes,
            'registrationStatuses' => $registrationStatuses,
            'expenseTotals' => $expenseTotals,
        ]);
    }

    /**
     * Base query for bike registration expense rows tied to an expense account (supports rider-less accounts).
     */
    protected function bikeRegistrationExpenseListingQuery(BikeRegistrationAccount $account)
    {
        $headId = (int) HeadAccount::BIKE_REGISTRATION_EXPENSE_ACCOUNT;
        $riderId = $account->rider_id;

        return BikeRegistration::query()
            ->where(function ($q) use ($account, $riderId, $headId) {
                $q->where('bike_registration_account_id', $account->id);

                if ($riderId !== null && $riderId !== '') {
                    $riderKey = (string) $riderId;
                    $q->orWhere(function ($legacy) use ($headId, $riderKey) {
                        $legacy->where('bike_registration_account_id', $headId)
                            ->where('rider_id', $riderKey);
                    });
                } else {
                    $q->orWhere(function ($legacyNoRider) use ($account, $headId) {
                        $legacyNoRider->where('bike_registration_account_id', $headId)
                            ->where(function ($rid) {
                                $rid->whereNull('rider_id')->orWhere('rider_id', '');
                            })
                            ->where('reference_number', 'like', 'BR-' . $account->id . '-%');
                    });
                }
            });
    }

    /**
     * @return array{totalUnpaid: float|int, totalPaid: float|int, unpaidCount: int, paidCount: int}
     */
    protected function bikeRegistrationExpenseTotals(BikeRegistrationAccount $account): array
    {
        $base = $this->bikeRegistrationExpenseListingQuery($account);

        return [
            'totalUnpaid' => (clone $base)->where('payment_status', 'unpaid')->sum('amount'),
            'totalPaid' => (clone $base)->where('payment_status', 'paid')->sum('amount'),
            'unpaidCount' => (clone $base)->where('payment_status', 'unpaid')->count(),
            'paidCount' => (clone $base)->where('payment_status', 'paid')->count(),
        ];
    }

    public function create($company_slug, $id)
    {
        $data = BikeRegistrationAccount::where('id', $id)->first();
        $registrationStatuses = BikeRegistrationStatus::orderBy('display_order', 'asc')->where('is_active', 1)->get();

        return view('bike_registration.create', compact('data', 'registrationStatuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rider_id' => 'required|exists:bike_registration_accounts,id',
            'registration_status' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bike_registrations')->where(function ($query) use ($request) {
                    return $query->where('bike_registration_account_id', $request->rider_id)->whereNull('deleted_at');
                }),
            ],
            'billing_month' => 'required|date_format:Y-m',
            'detail' => 'nullable|string',
            'reference_number' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'attach_file' => 'nullable|string|max:255',
        ]);

        try {
            $expenseAccount = BikeRegistrationAccount::findOrFail($validated['rider_id']);
            $trans_code = Account::trans_code();
            $billingMonth = $validated['billing_month'] . '-01';
            $trans_date = Carbon::today();
            $br = BikeRegistration::create([
                'rider_id' => $expenseAccount->rider_id !== null ? (string) $expenseAccount->rider_id : null,
                'bike_registration_account_id' => $expenseAccount->id,
                'registration_status' => $validated['registration_status'],
                'billing_month' => $billingMonth,
                'date' => $request->date,
                'amount' => $request->amount,
                'payment_status' => 'unpaid',
                'detail' => $validated['detail'],
                'reference_number' => $validated['reference_number'],
                'trans_date' => $trans_date,
                'trans_code' => $trans_code,
            ]);
            BikeRegistrationDetail::create([
                'bike_registration_id' => $br->id,
                'description' => $validated['detail'],
                'amount' => $request->amount,
                'sort_order' => 1,
            ]);
            Flash::success('Bike registration expense added successfully.');

            return redirect()->back();
        } catch (\Exception $e) {
            report($e);
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function viewvoucher($company_slug, $id)
    {
        $data = BikeRegistration::where('id', $id)->first();
        $accounts = null;
        if ($data && $data->bike_registration_account_id) {
            $accounts = BikeRegistrationAccount::find($data->bike_registration_account_id);
        }
        if (!$accounts && $data && $data->rider_id) {
            $accounts = BikeRegistrationAccount::where('rider_id', $data->rider_id)->first();
        }

        return view('bike_registration.viewvoucher', compact('data', 'accounts'));
    }

    public function payfine(Request $request)
    {
        DB::beginTransaction();
        $expenseAccount = null;

        try {
            $expense = BikeRegistration::findOrFail($request->id);
            if ($request->filled('bike_registration_account_id')) {
                $expenseAccount = BikeRegistrationAccount::find($request->bike_registration_account_id);
            }
            if (!$expenseAccount && $expense->bike_registration_account_id) {
                $expenseAccount = BikeRegistrationAccount::find($expense->bike_registration_account_id);
            }
            if (!$expenseAccount && $request->filled('rider_id')) {
                $expenseAccount = BikeRegistrationAccount::where('rider_id', $request->rider_id)->first();
            }
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
                $docFile = $photo ? $photo->store('vouchers', 'public') : null;
                $remarks = $request->voucher_type === 'BR' ? 'Bike Registration Voucher' : 'Journal Voucher';

                $trans_code = Account::trans_code();
                $TransactionService = new TransactionService();

                $billingMonth = $expense->billing_month ?? date('Y-m-01');
                $transDate = $expense->trans_date;

                if ($expense->amount > 0) {
                    $TransactionService->recordTransaction([
                        'account_id' => HeadAccount::BIKE_REGISTRATION_EXPENSE_ACCOUNT,
                        'reference_id' => $expense->id,
                        'reference_type' => 'BR',
                        'trans_code' => $trans_code,
                        'trans_date' => $transDate,
                        'narration' => $expense->detail ?? 'Bike Registration Payment',
                        'debit' => $expense->amount,
                        'billing_month' => $billingMonth,
                        'branch_id' => $expense->branch_id,
                    ]);
                }
                if ($expense->amount > 0) {
                    $TransactionService->recordTransaction([
                        'account_id' => $request->account,
                        'reference_id' => $expense->id,
                        'reference_type' => 'BR',
                        'trans_code' => $trans_code,
                        'trans_date' => $transDate,
                        'narration' => $expense->detail ?? 'Bike Registration Payment',
                        'credit' => $expense->amount,
                        'billing_month' => $billingMonth,
                        'branch_id' => $expense->branch_id,
                    ]);
                }
                Vouchers::create([
                    'branch_id' => $expense->branch_id,
                    'trans_date' => $transDate,
                    'trans_code' => $trans_code,
                    'trip_date' => $request->trip_date,
                    'billing_month' => $billingMonth,
                    'payment_type' => $payment_type_flag,
                    'voucher_type' => $request->voucher_type,
                    'remarks' => $remarks,
                    'amount' => $expense->amount,
                    'reference_number' => $expense->reference_number ?? null,
                    'Created_By' => $request->Created_By,
                    'attach_file' => $docFile,
                    'ref_id' => $expense->id,
                    'rider_id' => $expenseAccount?->rider_id,
                    'custom_field_values' => $request->input('voucher_custom_fields', []),
                ]);

                $total_amount = floatval($expense->amount);
                $lastLedger = CompanyQuery::table('ledger_entries')
                    ->where('account_id', $request->account)
                    ->orderBy('billing_month', 'desc')
                    ->first();

                $opening_balance = $lastLedger ? $lastLedger->closing_balance : 0.00;
                $debit_balance = $credit_balance = 0.00;

                if ($payment_type_flag === 1) {
                    $debit_balance = $total_amount;
                    $closing_balance = $opening_balance + $total_amount;
                } elseif ($payment_type_flag === 0) {
                    $credit_balance = $total_amount;
                    $closing_balance = $opening_balance - $total_amount;
                } else {
                    $closing_balance = $opening_balance;
                }

                CompanyQuery::table('ledger_entries')->insert([
                    'account_id' => $request->account,
                    'billing_month' => $billingMonth,
                    'opening_balance' => $opening_balance,
                    'debit_balance' => $debit_balance,
                    'credit_balance' => $credit_balance,
                    'closing_balance' => $closing_balance,
                    'branch_id' => $expense->branch_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $expense->save();
            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Bike registration marked paid with transaction and ledger entries.',
                    'generatentries_url' => $expenseAccount ? route('BikeRegistration.generatentries', $expenseAccount->id) : null,
                ]);
            }

            Flash::success('Bike registration marked paid with transaction and ledger entries.');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            Flash::error('Error: ' . $e->getMessage());
        }

        if ($expenseAccount) {
            return redirect(route('BikeRegistration.generatentries', $expenseAccount->id));
        }

        return redirect()->back();
    }

    public function edit(string $company_slug, string $id)
    {
        $bikeRegistration = BikeRegistration::find($id);
        if (empty($bikeRegistration)) {
            Flash::error('Bike registration not found');

            return redirect(route('BikeRegistration.index'));
        }
        $data = BikeRegistrationAccount::where('id', $bikeRegistration->bike_registration_account_id ?? $bikeRegistration->rider_id)->first();
        $registrationStatuses = BikeRegistrationStatus::orderBy('display_order', 'asc')->where('is_active', 1)->get();

        return view('bike_registration.edit', compact('data', 'bikeRegistration', 'registrationStatuses'));
    }

    public function update(Request $request)
    {
        DB::beginTransaction();

        try {
            $bikeRegistration = BikeRegistration::findOrFail($request->id);
            $billingMonth = $request->billing_month . '-01';

            $request->validate([
                'reference_number' => 'required|string|max:255',
            ]);

            $bikeRegistration->registration_status = $request->registration_status;
            $bikeRegistration->billing_month = $billingMonth;
            $bikeRegistration->date = $request->date;
            $bikeRegistration->amount = $request->amount;
            $bikeRegistration->detail = $request->detail;
            $bikeRegistration->reference_number = $request->reference_number;
            $bikeRegistration->save();

            if ($bikeRegistration->payment_status == 'paid') {
                $vouchers = Vouchers::where('ref_id', $bikeRegistration->id)
                    ->where('voucher_type', 'BR')
                    ->first();
                if ($vouchers) {
                    $vouchers->reference_number = $bikeRegistration->reference_number;
                    $vouchers->amount = $bikeRegistration->amount;
                    $vouchers->save();
                }

                $transactions = Transactions::where('reference_id', $bikeRegistration->id)
                    ->where('reference_type', 'BR')
                    ->get();

                foreach ($transactions as $transaction) {
                    if ($transaction->debit > 0) {
                        $transaction->debit = $bikeRegistration->amount;
                    }
                    if ($transaction->credit > 0) {
                        $transaction->credit = $bikeRegistration->amount;
                    }
                    $transaction->save();
                }
            }

            DB::commit();
            Flash::success('Bike registration updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error updating bike registration: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function inlineUpdate(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('bike_registration_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'id' => 'required|exists:bike_registrations,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'billing_month' => 'required|date_format:Y-m',
        ]);

        $row = BikeRegistration::findOrFail($validated['id']);
        $row->amount = $validated['amount'];
        $row->date = $validated['date'];
        $row->billing_month = $validated['billing_month'] . '-01';
        $row->save();

        return response()->json([
            'success' => true,
            'message' => 'Bike registration updated.',
            'amount' => number_format((float) $row->amount, 2),
            'date' => Carbon::parse($row->date)->format('Y-m-d'),
            'billing_month' => Carbon::parse($row->billing_month)->format('Y-m'),
        ]);
    }

    public function editVoucherCreditForm(Request $request, $company_slug, $bikeRegistrationId)
    {
        if (!auth()->user()->hasPermissionTo('bike_registration_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $expense = BikeRegistration::with('vouchers')->findOrFail($bikeRegistrationId);

        if ($expense->payment_status !== 'paid') {
            return response(
                '<div class="alert alert-warning m-2 mb-0">Only paid entries have a payment voucher to edit.</div>',
                200
            );
        }

        $voucher = $expense->vouchers->first();
        if (!$voucher) {
            $voucher = Vouchers::where('ref_id', $expense->id)->where('voucher_type', 'BR')->first();
        }
        if (!$voucher) {
            return response(
                '<div class="alert alert-warning m-2 mb-0">No BR voucher found for this expense.</div>',
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

        $debitAccountName = Accounts::where('id', HeadAccount::BIKE_REGISTRATION_EXPENSE_ACCOUNT)->value('name') ?? 'Bike registration expense';
        $currentCreditName = Accounts::where('id', $creditTx->account_id)->value('name') ?? ('#' . $creditTx->account_id);

        $paymentAccounts = $this->bikeRegistrationPaymentAccountOptions();
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

        return view('bike_registration.edit_voucher_credit', [
            'expense' => $expense,
            'voucher' => $voucher,
            'creditTransaction' => $creditTx,
            'debitAccountName' => $debitAccountName,
            'currentCreditName' => $currentCreditName,
            'paymentAccounts' => $paymentAccounts,
            'editDeleteFlags' => VoucherType::getEditDeleteFlagsByModule('vouchers'),
        ]);
    }

    public function updateVoucherCredit(Request $request, $company_slug)
    {
        if (!auth()->user()->hasPermissionTo('bike_registration_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'bike_registration_id' => 'required|exists:bike_registrations,id',
            'credit_account_id' => 'required|exists:accounts,id',
        ]);

        $expense = BikeRegistration::findOrFail($validated['bike_registration_id']);

        if ($expense->payment_status !== 'paid') {
            Flash::error('Only paid entries can update the payment account.');

            return redirect()->back();
        }

        $newAccountId = (int) $validated['credit_account_id'];
        if ($newAccountId === (int) HeadAccount::BIKE_REGISTRATION_EXPENSE_ACCOUNT) {
            Flash::error('Cannot use the bike registration expense account as the payment (credit) side.');

            return redirect()->back();
        }

        $voucher = Vouchers::where('ref_id', $expense->id)->where('voucher_type', 'BR')->first();
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

    private function bikeRegistrationPaymentAccountOptions()
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

    public function destroy($company_slug, string $id)
    {
        $bikeRegistration = BikeRegistration::find($id);

        if (empty($bikeRegistration)) {
            Flash::error('Bike registration entry not found');

            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $this->purgeBikeRegistrationEntryAndRelatedRecords($bikeRegistration);

            DB::commit();
            Flash::success('Bike registration entry deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error deleting Bike Registration ID: {$id} - " . $e->getMessage());
            Flash::error('Error deleting bike registration: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Remove one bike registration expense row and cascade-related transactions, vouchers, ledger slice, and details.
     */
    private function purgeBikeRegistrationEntryAndRelatedRecords(BikeRegistration $bikeRegistration): void
    {
        $id = $bikeRegistration->id;
        $billingMonth = $bikeRegistration->billing_month;
        $riderAccountId = $bikeRegistration->rider_id
            ? CompanyQuery::table('accounts')->where('ref_id', $bikeRegistration->rider_id)->value('id')
            : null;
        $identifier = 'Bike Registration #' . $id . ' - ' . ($bikeRegistration->registration_status ?? '') . ' (Amount: ' . number_format((float) $bikeRegistration->amount, 2) . ')';

        $relatedTransactions = Transactions::where('reference_id', $bikeRegistration->id)
            ->where('reference_type', 'BR')
            ->get();

        $transCodeTransactions = Transactions::where('trans_code', $bikeRegistration->trans_code)
            ->where('reference_type', 'BR')
            ->get();

        $relatedVouchers = Vouchers::withTrashed()
            ->where('ref_id', $bikeRegistration->id)
            ->where('voucher_type', 'BR')
            ->whereNull('deleted_at')
            ->get();

        foreach ($relatedTransactions as $transaction) {
            try {
                $this->trackCascadeDeletion(
                    BikeRegistration::class,
                    $bikeRegistration->id,
                    $identifier,
                    Transactions::class,
                    $transaction->id,
                    'Transaction #' . $transaction->id . ' (Trans Code: ' . $transaction->trans_code . ', Amount: ' . ($transaction->debit > 0 ? number_format($transaction->debit, 2) : number_format($transaction->credit, 2)) . ')',
                    'hasMany',
                    'transactions',
                    'soft',
                    'Cascade deletion from Bike Registration deletion - transaction by reference_id'
                );
            } catch (\Exception $e) {
                \Log::error("Failed to track cascade deletion for transaction {$transaction->id}: " . $e->getMessage());
            }
        }

        foreach ($transCodeTransactions as $transaction) {
            if ($relatedTransactions->contains('id', $transaction->id)) {
                continue;
            }
            try {
                $this->trackCascadeDeletion(
                    BikeRegistration::class,
                    $bikeRegistration->id,
                    $identifier,
                    Transactions::class,
                    $transaction->id,
                    'Transaction #' . $transaction->id . ' (Trans Code: ' . $transaction->trans_code . ', Amount: ' . ($transaction->debit > 0 ? number_format($transaction->debit, 2) : number_format($transaction->credit, 2)) . ')',
                    'hasMany',
                    'transactions',
                    'soft',
                    'Cascade deletion from Bike Registration deletion - transaction by trans_code'
                );
            } catch (\Exception $e) {
                \Log::error("Failed to track cascade deletion for transaction {$transaction->id}: " . $e->getMessage());
            }
        }

        foreach ($relatedVouchers as $voucher) {
            try {
                $this->trackCascadeDeletion(
                    BikeRegistration::class,
                    $bikeRegistration->id,
                    $identifier,
                    Vouchers::class,
                    $voucher->id,
                    'Voucher #' . $voucher->id . ' (' . $voucher->voucher_type . '-' . str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT) . ', Amount: ' . number_format((float) $voucher->amount, 2) . ')',
                    'hasMany',
                    'vouchers',
                    'soft',
                    'Cascade deletion from Bike Registration deletion - voucher'
                );
            } catch (\Exception $e) {
                \Log::error("Failed to track cascade deletion for voucher {$voucher->id}: " . $e->getMessage());
            }
        }

        Transactions::where('reference_id', $bikeRegistration->id)
            ->where('reference_type', 'BR')
            ->delete();

        Transactions::where('trans_code', $bikeRegistration->trans_code)
            ->where('reference_type', 'BR')
            ->delete();

        foreach ($relatedVouchers as $voucher) {
            $voucher->deleted_by = auth()->id();
            $voucher->save();
            $voucher->delete();
        }

        if ($riderAccountId) {
            $ledgerEntry = CompanyQuery::table('ledger_entries')
                ->where('account_id', $riderAccountId)
                ->where('billing_month', $billingMonth)
                ->first();

            if ($ledgerEntry) {
                try {
                    $this->trackCascadeDeletion(
                        BikeRegistration::class,
                        $bikeRegistration->id,
                        $identifier,
                        LedgerEntry::class,
                        $ledgerEntry->id,
                        "Ledger Entry #{$ledgerEntry->id} (Account ID: {$riderAccountId}, Billing Month: {$billingMonth})",
                        'hasOne',
                        'ledger_entry',
                        'hard',
                        'Cascade deletion from Bike Registration deletion - ledger entry recalculation'
                    );
                } catch (\Exception $e) {
                    \Log::error("Failed to track cascade deletion for ledger entry {$ledgerEntry->id}: " . $e->getMessage());
                }
            }

            $this->recalculateLedgerAfterDeletion($riderAccountId, $billingMonth);
        }

        BikeRegistrationDetail::where('bike_registration_id', $bikeRegistration->id)->delete();

        $bikeRegistration->deleted_by = auth()->id();
        $bikeRegistration->save();
        $bikeRegistration->delete();
    }

    private function recalculateLedgerAfterDeletion($accountId, $billingMonth)
    {
        CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->delete();

        $lastLedger = CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', '<', $billingMonth)
            ->orderBy('billing_month', 'desc')
            ->first();

        $openingBalance = $lastLedger ? $lastLedger->closing_balance : 0.00;

        $monthTransactions = Transactions::where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->get();

        $debitTotal = $monthTransactions->sum('debit');
        $creditTotal = $monthTransactions->sum('credit');
        $closingBalance = $openingBalance + $debitTotal - $creditTotal;

        if ($monthTransactions->count() > 0) {
            CompanyQuery::insert('ledger_entries', [
                'account_id' => $accountId,
                'billing_month' => $billingMonth,
                'opening_balance' => $openingBalance,
                'debit_balance' => $debitTotal,
                'credit_balance' => $creditTotal,
                'closing_balance' => $closingBalance,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        \Log::info("Recalculated ledger for account {$accountId} and billing month {$billingMonth}");
    }

    public function getRegistrationStatusFee(Request $request)
    {
        $request->validate([
            'registration_status' => 'required|string',
        ]);
        try {
            $status = BikeRegistrationStatus::where('name', $request->registration_status)->where('is_active', 1)->first();

            if ($status) {
                return response()->json([
                    'success' => true,
                    'amount' => $status->default_fee ?? 0,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Registration status not found',
                'amount' => 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching fee: ' . $e->getMessage(),
                'amount' => 0,
            ], 500);
        }
    }

    public function show(string $company_slug, string $id)
    {
        return redirect()->route('BikeRegistration.edit', ['company_slug' => $company_slug, 'id' => $id]);
    }
}
