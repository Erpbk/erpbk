@extends('layouts.settingsPanelLayout')

@section('title', 'Voucher Approval Detail')

@section('content')
@php
    $companySlug = $settingsCompanySlug ?? \App\Support\CompanyRouteContext::slug();
    $def = \App\Support\VoucherApprovalRegistry::definition((string) $voucher->voucher_type);
@endphp
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="mb-3">
        <a href="{{ route('settings-panel.voucher-approvals.index', ['company_slug' => $companySlug]) }}" class="btn btn-sm btn-outline-secondary">&larr; Back</a>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">{{ $voucher->formatted_id }}</h5>
                <small class="text-muted">Trans code {{ $voucher->trans_code }} · Type {{ $voucher->voucher_type }} · Module {{ $def['module'] ?? '—' }}</small>
            </div>
            <x-voucher-approval.status-badge :voucher="$voucher" />
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><strong>Amount</strong><div>{{ number_format((float) $voucher->amount, 2) }}</div></div>
                <div class="col-md-3"><strong>Created</strong><div>{{ optional($voucher->created_at)->format('d M Y H:i') }}</div></div>
                <div class="col-md-3"><strong>Creator ID</strong><div>{{ $voucher->Created_By ?? '—' }}</div></div>
                <div class="col-md-3"><strong>Attachment</strong><div>{{ $voucher->attach_file ?: '—' }}</div></div>
                <div class="col-md-6"><strong>Remarks</strong><div>{{ $voucher->remarks ?: '—' }}</div></div>
                <div class="col-md-6"><strong>Reason</strong><div>{{ $voucher->reason ?: '—' }}</div></div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <x-voucher-approval.panel :voucher="$voucher" />
    </div>

    <div class="card">
        <div class="card-header"><strong>Complete activity &amp; status history</strong></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>From → To</th>
                        <th>Remark</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($voucher->approvalEvents as $event)
                        <tr>
                            <td>{{ optional($event->created_at)->format('d M Y H:i:s') }}</td>
                            <td>{{ str_replace('_', ' ', $event->action) }}</td>
                            <td>{{ $event->user_name ?? 'System' }}</td>
                            <td>{{ $event->from_status ?? '—' }} → {{ $event->to_status ?? '—' }}</td>
                            <td>{{ $event->remark }}</td>
                            <td>{{ $event->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted text-center py-3">No history yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
