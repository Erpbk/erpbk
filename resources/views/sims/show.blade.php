@extends('layouts.app')
@section('title', 'SIM Details')

@push('third_party_stylesheets')
<style>
    .sim-page .sim-mock-wrap {
        background: #e8edf3;
        border-radius: 12px;
        padding: 22px 12px 18px;
        margin-bottom: 4px;
        display: flex;
        justify-content: center;
    }

    .sim-page .sim-mock {
        position: relative;
        width: 100%;
        max-width: 268px;
        background: linear-gradient(155deg, #2a2a2e 0%, #1a1a1d 48%, #111113 100%);
        color: #f8fafc;
        padding: 16px 14px 14px;
        /* Mini-SIM silhouette: rounded body + large cut corner */
        clip-path: polygon(
            0 8px,
            8px 0,
            calc(100% - 42px) 0,
            100% 42px,
            100% calc(100% - 8px),
            calc(100% - 8px) 100%,
            8px 100%,
            0 calc(100% - 8px)
        );
        box-shadow: 0 10px 20px rgba(15, 23, 42, .32);
    }

    .sim-page .sim-mock::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 42px;
        height: 42px;
        background: linear-gradient(135deg, transparent 49%, rgba(255, 255, 255, .12) 50%, rgba(255, 255, 255, .06) 100%);
        pointer-events: none;
    }

    .sim-page .sim-mock-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        padding-right: 28px;
    }

    .sim-page .sim-mock-carrier {
        font-weight: 700;
        letter-spacing: .6px;
        font-size: 13px;
        text-transform: uppercase;
    }

    .sim-page .sim-mock-tag {
        font-size: 10px;
        letter-spacing: 1.6px;
        text-transform: uppercase;
        opacity: .7;
    }

    .sim-page .sim-mock-body {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 14px;
    }

    .sim-page .sim-chip {
        flex-shrink: 0;
        width: 64px;
        height: 52px;
        background: linear-gradient(145deg, #f3d57a 0%, #c9a227 42%, #e6c34f 70%, #a67c00 100%);
        border-radius: 4px;
        padding: 5px 6px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .45), 0 1px 3px rgba(0, 0, 0, .4);
    }

    .sim-page .sim-chip-pads {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3px;
        height: 100%;
    }

    .sim-page .sim-chip-pads span {
        background: linear-gradient(180deg, #edd078, #b8922a);
        border-radius: 1px;
        border: 1px solid rgba(110, 70, 0, .35);
    }

    .sim-page .sim-mock-msisdn {
        min-width: 0;
    }

    .sim-page .sim-mock-label {
        font-size: 10px;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        opacity: .65;
        margin-bottom: 2px;
    }

    .sim-page .sim-mock-number {
        font-size: 15px;
        font-weight: 600;
        letter-spacing: .3px;
        word-break: break-all;
        color: #fff;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .sim-page .sim-mock-number:hover {
        color: #86efac;
    }

    .sim-page .sim-mock-iccid {
        border-top: 1px solid rgba(255, 255, 255, .12);
        padding-top: 10px;
    }

    .sim-page .sim-mock-iccid .value {
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 1px;
        word-break: break-all;
        opacity: .95;
    }

    .sim-page .sim-info-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 11px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }

    .sim-page .sim-info-list .sim-info-row:last-child {
        border-bottom: none;
    }

    .sim-page .sim-info-row > i {
        color: #94a3b8;
        font-size: 16px;
        margin-top: 1px;
        flex-shrink: 0;
    }

    .sim-page .sim-info-label {
        color: #64748b;
        min-width: 118px;
    }

    .sim-page .sim-info-value {
        margin-left: auto;
        text-align: right;
        font-weight: 600;
        color: #1f2937;
        word-break: break-word;
    }

    .sim-page .sim-tabs .nav-link {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
    }

    .sim-page .sim-tabs .nav-link.active {
        color: #2563eb;
    }

    .sim-page .sim-table {
        font-size: 13px;
    }

    .sim-page .sim-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 11px;
        letter-spacing: .5px;
        text-transform: uppercase;
        font-weight: 600;
        white-space: nowrap;
    }

    .sim-page .sim-table td {
        vertical-align: middle;
    }

    .sim-page .sim-rider-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        text-align: left;
    }

    .sim-page .sim-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e0e7ff;
        color: #3730a3;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .sim-page .sim-avatar.is-employee {
        background: #fef3c7;
        color: #92400e;
    }

    .sim-page .sim-rider-sub {
        color: #94a3b8;
        font-size: 11px;
    }

    .sim-page .sim-empty {
        padding: 44px 16px;
        text-align: center;
        color: #64748b;
        font-size: 13px;
    }

    .sim-page .sim-note {
        max-width: 190px;
        display: inline-block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: bottom;
    }
</style>
@endpush

@section('content')
@php
    $simStatus = \App\Models\Sims::statusDisplay($sims->status);
    $activeTab = request('tab') === 'invoices' ? 'invoices' : 'assignments';
    $assignedPerson = $sims->assignedPerson();
    $assignedIsEmployee = $sims->assign_type === 'employee';
    $carrierName = $sims->telecomCompany?->name ?? 'SIM';
    $waNumber = preg_replace('/\D+/', '', (string) $sims->number);
    $emiDisplay = trim((string) $sims->emi);
    if (str_starts_with($emiDisplay, '="') && str_ends_with($emiDisplay, '"')) {
        $emiDisplay = substr($emiDisplay, 2, -1);
    }
    $initials = static function (?string $name): string {
        $name = trim((string) $name);
        if ($name === '') {
            return '—';
        }
        $parts = preg_split('/\s+/', $name);
        return strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    };
@endphp

<div class="content sim-page">
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('sims.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i> Go Back
        </a>
    </div>

    <div class="row g-3">
        <!-- Left: SIM mock, details, and actions -->
        <div class="col-lg-4 col-md-5">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="sim-mock-wrap">
                        <div class="sim-mock">
                            <div class="sim-mock-top">
                                <span class="sim-mock-carrier">{{ strtoupper($carrierName) }}</span>
                                <span class="sim-mock-tag">SIM Card</span>
                            </div>
                            <div class="sim-mock-body">
                                <div class="sim-chip" aria-hidden="true">
                                    <div class="sim-chip-pads">
                                        <span></span><span></span>
                                        <span></span><span></span>
                                        <span></span><span></span>
                                    </div>
                                </div>
                                <div class="sim-mock-msisdn">
                                    <div class="sim-mock-label">Mobile Number</div>
                                    @if($waNumber)
                                    <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="sim-mock-number">
                                        <i class="ti ti-brand-whatsapp"></i>
                                        {{ $sims->number }}
                                    </a>
                                    @else
                                    <div class="sim-mock-number">N/A</div>
                                    @endif
                                </div>
                            </div>
                            <div class="sim-mock-iccid">
                                <div class="sim-mock-label">ICCID / EMI</div>
                                <div class="value">{{ $emiDisplay !== '' ? $emiDisplay : '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="sim-info-list mt-3">
                    <div class="sim-info-row">
                        <i class="ti ti-building"></i>
                        <span class="sim-info-label">SIM Company</span>
                        <span class="sim-info-value">{{ $sims->telecomCompany?->name ?? '—' }}</span>
                    </div>
                    <div class="sim-info-row">
                        <i class="ti ti-circle-check"></i>
                        <span class="sim-info-label">Status</span>
                        <span class="sim-info-value">
                            <span class="badge {{ $simStatus['badge'] }}">{{ $simStatus['label'] }}</span>
                        </span>
                    </div>
                    <div class="sim-info-row">
                        <i class="ti ti-user"></i>
                        <span class="sim-info-label">Assigned {{ $assignedIsEmployee ? 'Employee' : 'Rider' }}</span>
                        <span class="sim-info-value">
                            @if($assignedPerson)
                                @if($assignedIsEmployee)
                                <a href="{{ route('employees.show', $assignedPerson->id) }}" target="_blank" class="text-decoration-none">
                                    {{ $assignedPerson->name }}
                                </a>
                                @else
                                <a href="{{ route('riders.show', $assignedPerson->id) }}" target="_blank" class="text-decoration-none">
                                    {{ $assignedPerson->name }}
                                </a>
                                @endif
                                @if($sims->assigneeIsAbsconded())
                                <span class="badge bg-label-danger ms-1">Absconded</span>
                                @endif
                            @else
                            —
                            @endif
                        </span>
                    </div>
                    <div class="sim-info-row">
                        <i class="ti ti-cpu"></i>
                        <span class="sim-info-label">EMI / ICCID</span>
                        <span class="sim-info-value">{{ $emiDisplay !== '' ? $emiDisplay : '—' }}</span>
                    </div>
                    <div class="sim-info-row">
                        <i class="ti ti-building-store"></i>
                        <span class="sim-info-label">Vendor</span>
                        <span class="sim-info-value">{{ $sims->vendors?->name ?? '—' }}</span>
                    </div>
                    <div class="sim-info-row">
                        <i class="ti ti-git-branch"></i>
                        <span class="sim-info-label">Branch</span>
                        <span class="sim-info-value">{{ $sims->branch?->name ?? '—' }}</span>
                    </div>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                    @canany(['sims_assign_create', 'sims_assign_edit'])
                    @if($sims->assign_to)
                    <a href="javascript:void(0);" class="btn btn-primary show-modal" data-size="lg"
                       data-title="Return SIM" data-action="{{ route('sims.return', $sims->id) }}">
                        <i class="ti ti-arrow-back-up me-1"></i> Return SIM
                    </a>
                    @elseif($sims->isAssignable())
                    <a href="javascript:void(0);" class="btn btn-primary show-modal" data-size="lg"
                       data-title="Assign SIM" data-action="{{ route('sims.assign', $sims->id) }}">
                        <i class="ti ti-user-plus me-1"></i> Assign Rider
                    </a>
                    @else
                    <button type="button" class="btn btn-primary" disabled>
                        <i class="ti ti-user-plus me-1"></i> Assign Rider
                    </button>
                    <small class="text-muted text-center">Activate this SIM before assigning it.</small>
                    @endif
                    @endcanany

                    @can('sims_sim_edit')
                    <a href="javascript:void(0);" class="btn btn-outline-primary show-modal" data-size="lg"
                       data-title="Edit SIM" data-action="{{ route('sims.edit', $sims->id) }}">
                        <i class="ti ti-edit me-1"></i> Edit SIM
                    </a>

                    @if(!$sims->assign_to)
                    @php
                        $deactivating = !$sims->isDeactivated();
                    @endphp
                    <form action="{{ route('sims.activateDeactivate') }}" method="post" class="form-ajax-submit" id="simStatusForm">
                        @csrf
                        <input type="hidden" name="mode" value="{{ $deactivating ? 'deactivate' : 'activate' }}">
                        <input type="hidden" name="sim_ids[]" value="{{ $sims->id }}">
                        <button type="submit" class="btn w-100 {{ $deactivating ? 'btn-outline-danger' : 'btn-outline-success' }}">
                            <i class="ti {{ $deactivating ? 'ti-toggle-left' : 'ti-toggle-right' }} me-1"></i>
                            {{ $deactivating ? 'Deactivate SIM' : 'Activate SIM' }}
                        </button>
                    </form>
                    @endif
                    @endcan
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: history tabs -->
        <div class="col-lg-8 col-md-7">
            <div class="card shadow-sm">
                <div class="card-header pb-0">
                    <ul class="nav nav-tabs card-header-tabs sim-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'assignments' ? 'active' : '' }}" data-bs-toggle="tab"
                                    data-bs-target="#tab-assignments" type="button" role="tab">
                                Assignment History
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'invoices' ? 'active' : '' }}" data-bs-toggle="tab"
                                    data-bs-target="#tab-invoices" type="button" role="tab">
                                Invoice History
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content">
                    <!-- Assignment history -->
                    <div class="tab-pane fade {{ $activeTab === 'assignments' ? 'show active' : '' }}" id="tab-assignments" role="tabpanel">
                        <div class="px-3 pt-3">
                            <h6 class="mb-1">Assignment History</h6>
                            <p class="text-muted mb-3" style="font-size: 12px;">
                                All rider and employee assignments and returns for this SIM.
                            </p>
                        </div>

                        @if($histories->isEmpty())
                        <div class="sim-empty">
                            <i class="ti ti-user-off d-block mb-2" style="font-size: 28px; color: #cbd5e1;"></i>
                            No assignment records for this SIM yet.
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover sim-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Assigned To</th>
                                        <th>Assign Date</th>
                                        <th>Assign By</th>
                                        <th>Return Date</th>
                                        <th>Return By</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($histories as $index => $history)
                                    @php
                                        $historyPerson = $history->employee_id ? $history->employee : $history->rider;
                                        $historyIsEmployee = (bool) $history->employee_id;
                                    @endphp
                                    <tr>
                                        <td>{{ $histories->firstItem() + $index }}</td>
                                        <td>
                                            <span class="sim-rider-cell">
                                                <span class="sim-avatar {{ $historyIsEmployee ? 'is-employee' : '' }}">{{ $initials($historyPerson?->name) }}</span>
                                                <span>
                                                    @if($historyIsEmployee && $history->employee)
                                                    <a href="{{ route('employees.show', $history->employee->id) }}"
                                                       target="_blank" class="text-decoration-none d-block">
                                                        {{ $history->employee->name }}
                                                    </a>
                                                    <span class="sim-rider-sub">{{ $history->employee->employee_id ?? 'Employee' }}</span>
                                                    @elseif($history->rider)
                                                    <a href="{{ route('riders.show', $history->rider->id) }}"
                                                       target="_blank" class="text-decoration-none d-block">
                                                        {{ $history->rider->name }}
                                                    </a>
                                                    <span class="sim-rider-sub">{{ $history->rider->rider_id }}</span>
                                                    @else
                                                    <span class="text-muted">{{ $historyIsEmployee ? 'Employee removed' : 'Rider removed' }}</span>
                                                    @endif
                                                </span>
                                            </span>
                                        </td>
                                        <td>
                                            {{ $history->note_date ? \Carbon\Carbon::parse($history->note_date)->format('d-M-Y') : '—' }}
                                        </td>
                                        <td>{{ $history->assignedBy?->name ?? '—' }}
                                            @if($history->assignedBy?->roles?->first())
                                            <div class="sim-rider-sub">{{ $history->assignedBy->roles->first()->name }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $history->return_date ? \Carbon\Carbon::parse($history->return_date)->format('d-M-Y') : '—' }}
                                        </td>
                                        <td>{{ $history->returnedBy?->name ?? '—' }}
                                            @if($history->returnedBy?->roles?->first())
                                            <div class="sim-rider-sub">{{ $history->returnedBy->roles->first()->name }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($history->return_date)
                                            <span class="badge bg-label-secondary">Returned</span>
                                            @else
                                            <span class="badge bg-success">Active</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($history->notes)
                                            <span class="sim-note" title="{{ $history->notes }}">{{ $history->notes }}</span>
                                            @elseif(!$history->return_date)
                                            <span class="text-muted">Currently assigned</span>
                                            @else
                                            —
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-3">
                            <span class="text-muted" style="font-size: 12px;">
                                Showing {{ $histories->firstItem() }} to {{ $histories->lastItem() }}
                                of {{ $histories->total() }} entries
                            </span>
                            {{ $histories->links() }}
                        </div>
                        @endif

                        @if(!$sims->isDeactivated())
                        <div class="px-3 pb-3">
                            <div class="alert alert-info d-flex gap-2 align-items-center mb-0" style="font-size: 12px;">
                                <i class="ti ti-info-circle"></i>
                                <span>You can reassign this SIM to another rider or employee once it is returned.</span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Invoice history -->
                    <div class="tab-pane fade {{ $activeTab === 'invoices' ? 'show active' : '' }}" id="tab-invoices" role="tabpanel">
                        <div class="px-3 pt-3 d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                <h6 class="mb-1">Invoice History</h6>
                                <p class="text-muted mb-3" style="font-size: 12px;">
                                    SIM invoice lines billed against this number.
                                </p>
                            </div>
                            <div class="text-end" style="font-size: 12px;">
                                <div class="text-muted">Total Billed</div>
                                <div class="fw-bold">
                                    AED {{ number_format((float) ($invoiceTotals->total ?? 0), 2) }}
                                    <span class="text-muted fw-normal">
                                        ({{ (int) ($invoiceTotals->bills ?? 0) }} bills)
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if($invoiceItems->isEmpty())
                        <div class="sim-empty">
                            <i class="ti ti-file-off d-block mb-2" style="font-size: 28px; color: #cbd5e1;"></i>
                            No invoices recorded for this SIM yet.
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover sim-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Invoice</th>
                                        <th>Inv Date</th>
                                        <th>Billing Month</th>
                                        <th>Company</th>
                                        <th class="text-end">Rental</th>
                                        <th class="text-end">Extra</th>
                                        <th class="text-end">Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoiceItems as $index => $item)
                                    @php $invoice = $item->invoice; @endphp
                                    <tr>
                                        <td>{{ $invoiceItems->firstItem() + $index }}</td>
                                        <td>
                                            @if($invoice)
                                            <a href="javascript:void(0);" class="show-modal-right text-decoration-none"
                                               data-action="{{ route('simInvoices.show', $invoice->id) }}">
                                                {{ $invoice->invoice_number ?? ('SIMI-' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT)) }}
                                            </a>
                                            @else
                                            —
                                            @endif
                                        </td>
                                        <td>
                                            {{ $invoice?->inv_date ? \Carbon\Carbon::parse($invoice->inv_date)->format('d-M-Y') : '—' }}
                                        </td>
                                        <td>
                                            {{ $invoice?->billing_month ? \Carbon\Carbon::parse($invoice->billing_month)->format('M Y') : '—' }}
                                        </td>
                                        <td>{{ $invoice?->company?->name ?? '—' }}</td>
                                        <td class="text-end">{{ number_format((float) $item->rental_amount, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) ($item->additional_charges + $item->international_usage_charges), 2) }}</td>
                                        <td class="text-end fw-semibold">{{ number_format((float) $item->total_amount, 2) }}</td>
                                        <td>
                                            @if($invoice)
                                                @if((int) $invoice->status === 1)
                                                <span class="badge bg-success">Paid</span>
                                                @elseif((int) $invoice->status === 3)
                                                <span class="badge bg-warning">Partially Paid</span>
                                                @else
                                                <span class="badge bg-danger">Unpaid</span>
                                                @endif
                                            @else
                                            —
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-3">
                            <span class="text-muted" style="font-size: 12px;">
                                Showing {{ $invoiceItems->firstItem() }} to {{ $invoiceItems->lastItem() }}
                                of {{ $invoiceItems->total() }} entries
                            </span>
                            {{ $invoiceItems->links() }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var statusForm = document.getElementById('simStatusForm');
        if (statusForm) {
            statusForm.addEventListener('submit', function(e) {
                var mode = statusForm.querySelector('input[name="mode"]').value;
                if (!confirm('Are you sure you want to ' + mode + ' this SIM?')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            }, true);
        }
    });
</script>
@endsection
