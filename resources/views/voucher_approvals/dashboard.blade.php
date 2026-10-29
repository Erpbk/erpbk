@extends('layouts.settingsPanelLayout')

@section('title', 'Voucher Approvals')

@section('content')
@php
    $companySlug = $settingsCompanySlug ?? \App\Support\CompanyRouteContext::slug();
@endphp
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Voucher Approval Dashboard</h4>
            <p class="text-muted mb-0 small">Pending queue, rejected vouchers, and full approval history (company-scoped).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('settings-panel.voucher-approvals.permissions', ['company_slug' => $companySlug]) }}" class="btn btn-sm btn-outline-primary">Permission guide</a>
        </div>
    </div>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link {{ ($activeTab ?? '') === 'all' ? 'active' : '' }}" href="{{ route('settings-panel.voucher-approvals.index', ['company_slug' => $companySlug]) }}">All</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ ($activeTab ?? '') === 'pending' ? 'active' : '' }}" href="{{ route('settings-panel.voucher-approvals.pending', ['company_slug' => $companySlug]) }}">
                Pending @if(($pendingCount ?? 0) > 0)<span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>@endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ ($activeTab ?? '') === 'rejected' ? 'active' : '' }}" href="{{ route('settings-panel.voucher-approvals.rejected', ['company_slug' => $companySlug]) }}">
                Rejected @if(($rejectedCount ?? 0) > 0)<span class="badge bg-danger ms-1">{{ $rejectedCount }}</span>@endif
            </a>
        </li>
    </ul>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Any</option>
                        @foreach(($filters['statuses'] ?? []) as $st)
                            <option value="{{ $st }}" @selected(request('status') === $st)>{{ str_replace('_', ' ', $st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="voucher_type" class="form-select form-select-sm">
                        <option value="">Any</option>
                        @foreach(($filters['types'] ?? []) as $code => $def)
                            <option value="{{ $code }}" @selected(request('voucher_type') === $code)>{{ $code }} — {{ $def['label'] ?? $code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Module</label>
                    <select name="module" class="form-select form-select-sm">
                        <option value="">Any</option>
                        @foreach(($filters['modules'] ?? []) as $mod)
                            <option value="{{ $mod }}" @selected(request('module') === $mod)>{{ $mod }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Voucher #</label>
                    <input type="text" name="voucher_number" value="{{ request('voucher_number') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Voucher</th>
                        <th>Type</th>
                        <th>Module</th>
                        <th>Amount</th>
                        <th>Created</th>
                        <th>Creator</th>
                        <th>Status</th>
                        <th>Approver / Rejector</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $voucher)
                        @php
                            $def = \App\Support\VoucherApprovalRegistry::definition((string) $voucher->voucher_type);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('settings-panel.voucher-approvals.show', ['company_slug' => $companySlug, 'id' => $voucher->id]) }}">
                                    {{ $voucher->formatted_id }}
                                </a>
                                <div class="small text-muted">#{{ $voucher->trans_code }}</div>
                            </td>
                            <td>{{ $voucher->voucher_type }}</td>
                            <td>{{ $def['module'] ?? '—' }}</td>
                            <td>{{ number_format((float) $voucher->amount, 2) }}</td>
                            <td>{{ optional($voucher->created_at)->format('d M Y') }}</td>
                            <td>{{ $voucher->Created_By }}</td>
                            <td><x-voucher-approval.status-badge :voucher="$voucher" /></td>
                            <td class="small">
                                @if($voucher->isApprovalApproved())
                                    {{ $voucher->approvedByUser->name ?? $voucher->approved_by }}
                                    <div class="text-muted">{{ optional($voucher->approved_at)->format('d M Y H:i') }}</div>
                                @elseif($voucher->isApprovalRejected())
                                    {{ $voucher->rejectedByUser->name ?? $voucher->rejected_by }}
                                    <div class="text-muted">{{ optional($voucher->rejected_at)->format('d M Y H:i') }}</div>
                                    @if($voucher->rejection_reason)
                                        <div class="text-danger">{{ \Illuminate\Support\Str::limit($voucher->rejection_reason, 80) }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('settings-panel.voucher-approvals.show', ['company_slug' => $companySlug, 'id' => $voucher->id]) }}">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No vouchers match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($vouchers, 'links'))
            <div class="card-footer">{{ $vouchers->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
