<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vouchers;
use App\Support\VoucherApprovalRegistry;
use App\Traits\GlobalPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VoucherApprovalDashboardController extends Controller
{
    use GlobalPagination;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, string $company_slug): View
    {
        $this->authorizeDashboard();

        $query = $this->baseQuery($request)
            ->with(['approvedByUser', 'rejectedByUser', 'branch'])
            ->orderByRaw("CASE approval_status WHEN 'pending_approval' THEN 0 WHEN 'rejected' THEN 1 ELSE 2 END")
            ->orderByDesc('id');

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $vouchers = $this->applyPagination($query, $paginationParams);

        return view('voucher_approvals.dashboard', [
            'vouchers' => $vouchers,
            'filters' => $this->filterOptions(),
            'activeTab' => 'all',
            'pendingCount' => Vouchers::query()->pendingApproval()->count(),
            'rejectedCount' => Vouchers::query()->approvalRejected()->count(),
        ]);
    }

    public function pending(Request $request, string $company_slug): View
    {
        $this->authorizeDashboard();

        $request->merge(['status' => 'pending_approval']);
        $query = $this->baseQuery($request)
            ->with(['approvedByUser', 'rejectedByUser', 'branch'])
            ->orderByDesc('id');

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $vouchers = $this->applyPagination($query, $paginationParams);

        return view('voucher_approvals.dashboard', [
            'vouchers' => $vouchers,
            'filters' => $this->filterOptions(),
            'activeTab' => 'pending',
            'pendingCount' => Vouchers::query()->pendingApproval()->count(),
            'rejectedCount' => Vouchers::query()->approvalRejected()->count(),
        ]);
    }

    public function rejected(Request $request, string $company_slug): View
    {
        $this->authorizeDashboard();

        $request->merge(['status' => 'rejected']);
        $query = $this->baseQuery($request)
            ->with(['approvedByUser', 'rejectedByUser', 'approvalEvents', 'branch'])
            ->orderByDesc('rejected_at');

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $vouchers = $this->applyPagination($query, $paginationParams);

        return view('voucher_approvals.dashboard', [
            'vouchers' => $vouchers,
            'filters' => $this->filterOptions(),
            'activeTab' => 'rejected',
            'pendingCount' => Vouchers::query()->pendingApproval()->count(),
            'rejectedCount' => Vouchers::query()->approvalRejected()->count(),
        ]);
    }

    public function show(Request $request, string $company_slug, int $id): View
    {
        $this->authorizeDashboard();

        $voucher = Vouchers::query()
            ->with([
                'approvedByUser',
                'rejectedByUser',
                'approvalEvents.user',
                'transactions',
                'branch',
            ])
            ->findOrFail($id);

        return view('voucher_approvals.show', compact('voucher'));
    }

    protected function authorizeDashboard(): void
    {
        Gate::authorize('voucher.approvalDashboard');
    }

    protected function baseQuery(Request $request)
    {
        $query = Vouchers::query();

        if ($request->filled('status')) {
            $query->where('approval_status', $request->status);
        }

        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', strtoupper((string) $request->voucher_type));
        }

        if ($request->filled('module')) {
            $types = collect(VoucherApprovalRegistry::types())
                ->filter(fn ($def) => ($def['module'] ?? null) === $request->module)
                ->keys()
                ->all();
            if ($types !== []) {
                $query->whereIn('voucher_type', $types);
            }
        }

        if ($request->filled('created_by')) {
            $query->where('Created_By', $request->created_by);
        }

        if ($request->filled('approved_by')) {
            $query->where('approved_by', $request->approved_by);
        }

        if ($request->filled('rejected_by')) {
            $query->where('rejected_by', $request->rejected_by);
        }

        if ($request->filled('voucher_number')) {
            $term = $request->voucher_number;
            $query->where(function ($q) use ($term) {
                $q->where('id', $term)
                    ->orWhere('trans_code', 'like', '%' . $term . '%')
                    ->orWhere('reference_number', 'like', '%' . $term . '%');
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
    }

    protected function filterOptions(): array
    {
        $types = VoucherApprovalRegistry::types();
        $modules = collect($types)->pluck('module')->filter()->unique()->sort()->values()->all();

        return [
            'types' => $types,
            'modules' => $modules,
            'users' => User::query()->orderBy('name')->limit(500)->get(['id', 'name']),
            'statuses' => ['pending_approval', 'approved', 'rejected'],
        ];
    }
}
