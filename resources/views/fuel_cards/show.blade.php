@extends('layouts.app')
@section('title', 'Fuel Card Details')

@push('third_party_stylesheets')
<style>
    .fc-page .fc-visual {
        background: linear-gradient(135deg, #16264a 0%, #1e3a6b 55%, #16264a 100%);
        border-radius: 14px;
        color: #fff;
        padding: 18px 20px;
    }

    .fc-page .fc-visual-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 26px;
    }

    .fc-page .fc-visual-brand {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        letter-spacing: .5px;
    }

    .fc-page .fc-visual-brand i {
        background: #e11d48;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
    }

    .fc-page .fc-visual-tag {
        font-size: 10px;
        letter-spacing: 1.4px;
        text-transform: uppercase;
        opacity: .75;
    }

    .fc-page .fc-visual-label {
        font-size: 10px;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        opacity: .7;
        margin-bottom: 2px;
    }

    .fc-page .fc-visual-number {
        font-size: 17px;
        font-weight: 600;
        letter-spacing: 1.5px;
        margin-bottom: 18px;
        word-break: break-all;
    }

    .fc-page .fc-visual-stats {
        display: flex;
        gap: 28px;
    }

    .fc-page .fc-visual-stats .value {
        font-size: 14px;
        font-weight: 600;
    }

    .fc-page .fc-info-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 11px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }

    .fc-page .fc-info-list .fc-info-row:last-child {
        border-bottom: none;
    }

    .fc-page .fc-info-row > i {
        color: #94a3b8;
        font-size: 16px;
        margin-top: 1px;
        flex-shrink: 0;
    }

    .fc-page .fc-info-label {
        color: #64748b;
        min-width: 118px;
    }

    .fc-page .fc-info-value {
        margin-left: auto;
        text-align: right;
        font-weight: 600;
        color: #1f2937;
        word-break: break-word;
    }

    .fc-page .fc-danger-panel {
        border: 1px solid #fecaca;
        background: #fef2f2;
        border-radius: 12px;
        padding: 14px;
        margin-top: 16px;
    }

    .fc-page .fc-danger-panel .title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #b91c1c;
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 6px;
    }

    .fc-page .fc-danger-panel .desc {
        color: #7f1d1d;
        font-size: 12px;
        line-height: 1.5;
        margin-bottom: 12px;
    }

    .fc-page .fc-tabs .nav-link {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
    }

    .fc-page .fc-tabs .nav-link.active {
        color: #2563eb;
    }

    .fc-page .fc-table {
        font-size: 13px;
    }

    .fc-page .fc-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 11px;
        letter-spacing: .5px;
        text-transform: uppercase;
        font-weight: 600;
        white-space: nowrap;
    }

    .fc-page .fc-table td {
        vertical-align: middle;
    }

    .fc-page .fc-rider-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        text-align: left;
    }

    .fc-page .fc-avatar {
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

    .fc-page .fc-rider-sub {
        color: #94a3b8;
        font-size: 11px;
    }

    .fc-page .fc-empty {
        padding: 44px 16px;
        text-align: center;
        color: #64748b;
        font-size: 13px;
    }

    .fc-page .fc-note {
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
    $cardStatus = \App\Models\FuelCards::statusDisplay($card->status);
    $activeTab = request('tab') === 'transactions' ? 'transactions' : 'assignments';
    $chargeableRider = $chargeableRider ?? $card->chargeableRider();
    $initials = static function (?string $name): string {
        $name = trim((string) $name);
        if ($name === '') {
            return '—';
        }
        $parts = preg_split('/\s+/', $name);
        return strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    };
@endphp

<div class="content fc-page">
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('fuelCards.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i> Go Back
        </a>
    </div>

    <div class="row g-3">
        <!-- Left: card summary, actions, and lost-card charge -->
        <div class="col-lg-4 col-md-5">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="fc-visual mb-3">
                        <div class="fc-visual-top">
                            <div class="fc-visual-brand">
                                <i class="ti ti-gas-station"></i>
                                <span>{{ strtoupper($card->fuelCompany?->name ?? 'FUEL') }}</span>
                            </div>
                            <span class="fc-visual-tag">Fuel Card</span>
                        </div>
                        <div class="fc-visual-label">Card Number</div>
                        <div class="fc-visual-number">{{ $card->card_number ?? 'N/A' }}</div>
                        <div class="fc-visual-stats">
                            <div>
                                <div class="fc-visual-label">Service Charges</div>
                                <div class="value">
                                    {{ $card->service_charges !== null ? number_format((float) $card->service_charges, 2) : '—' }}
                                </div>
                            </div>
                            <div>
                                <div class="fc-visual-label">Issue Date</div>
                                <div class="value">
                                    {{ $card->card_issue_date ? \Carbon\Carbon::parse($card->card_issue_date)->format('d-M-Y') : '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="fc-info-list">
                    <div class="fc-info-row">
                        <i class="ti ti-building"></i>
                        <span class="fc-info-label">Fuel Company</span>
                        <span class="fc-info-value">{{ $card->fuelCompany?->name ?? '—' }}</span>
                    </div>
                    <div class="fc-info-row">
                        <i class="ti ti-circle-check"></i>
                        <span class="fc-info-label">Status</span>
                        <span class="fc-info-value">
                            <span class="badge {{ $cardStatus['badge'] }}">{{ $cardStatus['label'] }}</span>
                        </span>
                    </div>
                    <div class="fc-info-row">
                        <i class="ti ti-user"></i>
                        <span class="fc-info-label">Assigned Rider</span>
                        <span class="fc-info-value">
                            @if($card->rider)
                            <a href="{{ route('riders.show', $card->rider->rider_id) }}" target="_blank" class="text-decoration-none">
                                {{ $card->rider->name }}
                            </a>
                            @else
                            —
                            @endif
                        </span>
                    </div>
                    <div class="fc-info-row">
                        <i class="ti ti-calendar"></i>
                        <span class="fc-info-label">Issue Date</span>
                        <span class="fc-info-value">
                            {{ $card->card_issue_date ? \Carbon\Carbon::parse($card->card_issue_date)->format('d-M-Y') : '—' }}
                        </span>
                    </div>
                    <div class="fc-info-row">
                        <i class="ti ti-receipt-2"></i>
                        <span class="fc-info-label">Service Charges (Monthly)</span>
                        <span class="fc-info-value">
                            {{ $card->service_charges !== null ? 'AED ' . number_format((float) $card->service_charges, 2) : '—' }}
                        </span>
                    </div>
                    <div class="fc-info-row">
                        <i class="ti ti-git-branch"></i>
                        <span class="fc-info-label">Branch</span>
                        <span class="fc-info-value">{{ $card->branch?->name ?? '—' }}</span>
                    </div>
                    <div class="fc-info-row">
                        <i class="ti ti-note"></i>
                        <span class="fc-info-label">Remarks</span>
                        <span class="fc-info-value">{{ $card->remarks ?: '—' }}</span>
                    </div>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                    @canany(['fuel_cards_assign_create', 'fuel_cards_assign_edit'])
                    @if($card->assigned_to)
                    <a href="javascript:void(0);" class="btn btn-primary show-modal" data-size="lg"
                       data-title="Return Fuel Card" data-action="{{ route('fuelCards.return', $card->id) }}">
                        <i class="ti ti-arrow-back-up me-1"></i> Return Card
                    </a>
                    @elseif($card->isAssignable())
                    <a href="javascript:void(0);" class="btn btn-primary show-modal" data-size="lg"
                       data-title="Assign Fuel Card" data-action="{{ route('fuelCards.assign', $card->id) }}">
                        <i class="ti ti-user-plus me-1"></i> Assign Rider
                    </a>
                    @else
                    <button type="button" class="btn btn-primary" disabled>
                        <i class="ti ti-user-plus me-1"></i> Assign Rider
                    </button>
                    <small class="text-muted text-center">
                        {{ $card->isLost() ? 'A lost card cannot be assigned.' : 'Activate this card before assigning it.' }}
                    </small>
                    @endif
                    @endcanany

                    @can('fuel_cards_card_edit')
                    <a href="javascript:void(0);" class="btn btn-outline-primary show-modal" data-size="lg"
                       data-title="Edit Fuel Card" data-action="{{ route('fuelCards.edit', $card->id) }}">
                        <i class="ti ti-edit me-1"></i> Edit Card
                    </a>

                    @if(!$card->isLost() && !$card->assigned_to)
                    @php
                        $deactivating = !$card->isDeactivated();
                    @endphp
                    <form action="{{ route('fuelCards.activateDeactivate') }}" method="post" class="form-ajax-submit" id="cardStatusForm">
                        @csrf
                        <input type="hidden" name="mode" value="{{ $deactivating ? 'deactivate' : 'activate' }}">
                        <input type="hidden" name="card_ids[]" value="{{ $card->id }}">
                        <button type="submit" class="btn w-100 {{ $deactivating ? 'btn-outline-danger' : 'btn-outline-success' }}">
                            <i class="ti {{ $deactivating ? 'ti-toggle-left' : 'ti-toggle-right' }} me-1"></i>
                            {{ $deactivating ? 'Deactivate Card' : 'Activate Card' }}
                        </button>
                    </form>
                    @endif
                    @endcan
                    </div>

                    @can('fuel_cards_card_edit')
                    <div class="fc-danger-panel">
                        @if($card->isLost())
                        <div class="title"><i class="ti ti-alert-triangle"></i> Card Lost / Not Returned</div>
                        <div class="desc mb-2">
                            Charged to
                            @if($card->lostRider)
                            <strong>{{ $card->lostRider->name }}</strong>
                            @if($card->lostRider->rider_id)
                            ({{ $card->lostRider->rider_id }})
                            @endif
                            @else
                            <strong>rider</strong>
                            @endif
                            on {{ $card->lost_date ? \Carbon\Carbon::parse($card->lost_date)->format('d-M-Y') : '—' }}.
                            @if($card->lost_remarks)
                            <br>{{ $card->lost_remarks }}
                            @endif
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted" style="font-size: 11px;">Charged Amount</div>
                                <div class="fw-bold text-danger" style="font-size: 18px;">
                                    AED {{ number_format((float) $card->lost_amount, 2) }}
                                </div>
                            </div>
                            @if($card->lostVoucherLabel())
                            <a href="javascript:void(0);" class="btn btn-sm btn-outline-danger show-modal" data-size="xl"
                               data-title="Voucher # {{ $card->lostVoucherLabel() }}"
                               data-action="{{ route('vouchers.show', $card->lost_voucher_id) }}">
                                <i class="ti ti-file-invoice me-1"></i>
                                {{ $card->lostVoucherLabel() }}
                            </a>
                            @endif
                        </div>
                        @else
                        <div class="title"><i class="ti ti-alert-triangle"></i> Card Lost / Not Returned</div>
                        <div class="desc">
                            Mark this card as lost or not returned and charge the rider. Enter the
                            amount in the form; an Inventory Loss (IL) voucher is generated automatically.
                        </div>
                        <a href="javascript:void(0);" class="btn btn-danger btn-sm w-100 show-modal" data-size="xl"
                           data-title="Charge Rider For Lost Card" data-action="{{ route('fuelCards.chargeLost', $card->id) }}">
                            <i class="ti ti-cash-off me-1"></i> Charge Rider (Lost Card)
                        </a>
                        @endif
                    </div>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Right: history tabs -->
        <div class="col-lg-8 col-md-7">
            <div class="card shadow-sm">
                <div class="card-header pb-0">
                    <ul class="nav nav-tabs card-header-tabs fc-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'assignments' ? 'active' : '' }}" data-bs-toggle="tab"
                                    data-bs-target="#tab-assignments" type="button" role="tab">
                                Assignment History
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'transactions' ? 'active' : '' }}" data-bs-toggle="tab"
                                    data-bs-target="#tab-transactions" type="button" role="tab">
                                Transaction History
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
                                All rider assignments and returns for this fuel card.
                            </p>
                        </div>

                        @if($histories->isEmpty())
                        <div class="fc-empty">
                            <i class="ti ti-user-off d-block mb-2" style="font-size: 28px; color: #cbd5e1;"></i>
                            No assignment records for this card yet.
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover fc-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Rider / Company</th>
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
                                    <tr>
                                        <td>{{ $histories->firstItem() + $index }}</td>
                                        <td>
                                            <span class="fc-rider-cell">
                                                <span class="fc-avatar">{{ $initials($history->rider?->name) }}</span>
                                                <span>
                                                    @if($history->rider)
                                                    <a href="{{ route('riders.show', $history->rider->rider_id) }}"
                                                       target="_blank" class="text-decoration-none d-block">
                                                        {{ $history->rider->name }}
                                                    </a>
                                                    <span class="fc-rider-sub">{{ $history->rider->rider_id }}</span>
                                                    @else
                                                    <span class="text-muted">Rider removed</span>
                                                    @endif
                                                </span>
                                            </span>
                                        </td>
                                        <td>
                                            {{ $history->assign_date ? \Carbon\Carbon::parse($history->assign_date)->format('d-M-Y') : '—' }}
                                        </td>
                                        <td>{{ $history->assignedBy?->name ?? '—' }}
                                            @if($history->assignedBy?->roles?->first())
                                            <div class="fc-rider-sub">{{ $history->assignedBy->roles->first()->name }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $history->return_date ? \Carbon\Carbon::parse($history->return_date)->format('d-M-Y') : '—' }}
                                        </td>
                                        <td>{{ $history->returnedBy?->name ?? '—' }}
                                            @if($history->returnedBy?->roles?->first())
                                            <div class="fc-rider-sub">{{ $history->returnedBy->roles->first()->name }}</div>
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
                                            @if($history->note)
                                            <span class="fc-note" title="{{ $history->note }}">{{ $history->note }}</span>
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

                        @if(!$card->isLost())
                        <div class="px-3 pb-3">
                            <div class="alert alert-info d-flex gap-2 align-items-center mb-0" style="font-size: 12px;">
                                <i class="ti ti-info-circle"></i>
                                <span>You can reassign this fuel card to another rider once it is returned.</span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Transaction history -->
                    <div class="tab-pane fade {{ $activeTab === 'transactions' ? 'show active' : '' }}" id="tab-transactions" role="tabpanel">
                        <div class="px-3 pt-3 d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                <h6 class="mb-1">Transaction History</h6>
                                <p class="text-muted mb-3" style="font-size: 12px;">
                                    Fuel transactions recorded against this card number.
                                </p>
                            </div>
                            <div class="text-end" style="font-size: 12px;">
                                <div class="text-muted">Total Fuel Value</div>
                                <div class="fw-bold">
                                    AED {{ number_format((float) ($transactionTotals->total ?? 0), 2) }}
                                    <span class="text-muted fw-normal">
                                        ({{ (int) ($transactionTotals->trips ?? 0) }} trips,
                                        {{ number_format((float) ($transactionTotals->qty ?? 0), 2) }} ltr)
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if($transactions->isEmpty())
                        <div class="fc-empty">
                            <i class="ti ti-gas-station-off d-block mb-2" style="font-size: 28px; color: #cbd5e1;"></i>
                            No fuel transactions recorded for this card yet.
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover fc-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Trans Date</th>
                                        <th>Trans No</th>
                                        <th>Rider</th>
                                        <th>Site</th>
                                        <th>Product</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $index => $txn)
                                    <tr>
                                        <td>{{ $transactions->firstItem() + $index }}</td>
                                        <td>
                                            {{ $txn->trans_date ? \Carbon\Carbon::parse($txn->trans_date)->format('d-M-Y') : '—' }}
                                        </td>
                                        <td>{{ $txn->trans_no ?: '—' }}</td>
                                        <td>
                                            @if($txn->rider)
                                            <a href="{{ route('riders.show', $txn->rider->rider_id) }}"
                                               target="_blank" class="text-decoration-none">
                                                {{ $txn->rider->name }}
                                            </a>
                                            @else
                                            —
                                            @endif
                                        </td>
                                        <td>{{ $txn->site ?: '—' }}</td>
                                        <td>{{ $txn->product ?: '—' }}</td>
                                        <td class="text-end">{{ number_format((float) $txn->qty, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $txn->price, 2) }}</td>
                                        <td class="text-end fw-semibold">{{ number_format((float) $txn->total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-3">
                            <span class="text-muted" style="font-size: 12px;">
                                Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }}
                                of {{ $transactions->total() }} entries
                            </span>
                            {{ $transactions->links() }}
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
        var statusForm = document.getElementById('cardStatusForm');
        if (statusForm) {
            statusForm.addEventListener('submit', function(e) {
                var mode = statusForm.querySelector('input[name="mode"]').value;
                if (!confirm('Are you sure you want to ' + mode + ' this fuel card?')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            }, true);
        }
    });
</script>
@endsection
