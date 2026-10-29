@props(['voucher'])

@php
    use Illuminate\Support\Facades\Gate;

    $canApprove = Gate::allows('voucher.approve', $voucher);
    $canReject = Gate::allows('voucher.reject', $voucher);
    $canForce = Gate::allows('voucher.forceApprove', $voucher);
    $canReopen = Gate::allows('voucher.reopen', $voucher);
    $canResubmit = Gate::allows('voucher.resubmit', $voucher);

    $__companySlug = \App\Support\CompanyRouteContext::slug();
    $routeParams = function (string $name) use ($voucher, $__companySlug) {
        $params = ['id' => $voucher->id];
        if ($__companySlug) {
            $params['company_slug'] = $__companySlug;
        }
        return route($name, $params);
    };

    $uid = (int) $voucher->id;
    $events = $voucher->relationLoaded('approvalEvents')
        ? $voucher->approvalEvents
        : $voucher->approvalEvents()->latest('id')->limit(50)->get();

    $showApprove = $voucher->isPendingApproval() && $canApprove;
    $showReject = $voucher->isPendingApproval() && $canReject;
    $showResubmit = $voucher->isApprovalRejected() && $canResubmit;
    $showForce = $canForce && ! $voucher->isApprovalApproved();
    $showReopen = $canReopen && ($voucher->isApprovalApproved() || $voucher->isApprovalRejected());
    $showHistory = $events->isNotEmpty() || ($voucher->isApprovalRejected() && $voucher->rejection_reason);
    $hasAny = $showApprove || $showReject || $showResubmit || $showForce || $showReopen || $showHistory;
@endphp

@if($hasAny)
{{-- Inline toolbar buttons (place next to Edit) — no card --}}
@if($showApprove)
<button type="button" class="btn btn-sm btn-success"
    data-bs-toggle="modal" data-bs-target="#voucherApproveModal{{ $uid }}">
    <i class="ti ti-check me-1"></i> Approve
</button>
@endif

@if($showReject)
<button type="button" class="btn btn-sm btn-danger"
    data-bs-toggle="modal" data-bs-target="#voucherRejectModal{{ $uid }}">
    <i class="ti ti-x me-1"></i> Reject
</button>
@endif

@if($showResubmit)
<button type="button" class="btn btn-sm btn-primary"
    data-bs-toggle="modal" data-bs-target="#voucherResubmitModal{{ $uid }}">
    <i class="ti ti-refresh me-1"></i> Resubmit
</button>
@endif

@if($showForce)
<button type="button" class="btn btn-sm btn-outline-warning"
    data-bs-toggle="modal" data-bs-target="#voucherForceModal{{ $uid }}">
    Force Approve
</button>
@endif

@if($showReopen)
<button type="button" class="btn btn-sm btn-outline-secondary"
    data-bs-toggle="modal" data-bs-target="#voucherReopenModal{{ $uid }}">
    Reopen
</button>
@endif

@if($showHistory)
<button type="button" class="btn btn-sm btn-outline-primary"
    data-bs-toggle="modal" data-bs-target="#voucherHistoryModal{{ $uid }}">
    <i class="ti ti-history me-1"></i> History
</button>
@endif

{{-- Modals (fixed; safe inside toolbar flex) --}}
@if($showApprove)
<div class="modal fade" id="voucherApproveModal{{ $uid }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ $routeParams('voucher-approvals.approve') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Approve voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Approve <strong>{{ $voucher->formatted_id }}</strong> and release it to the ledger?</p>
                <label class="form-label">Remark <span class="text-muted">(optional)</span></label>
                <textarea name="remark" class="form-control" rows="2" maxlength="2000" placeholder="Optional note"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Approve Voucher</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($showReject)
<div class="modal fade" id="voucherRejectModal{{ $uid }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ $routeParams('voucher-approvals.reject') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Reject voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Reject <strong>{{ $voucher->formatted_id }}</strong>? It will be hidden from active lists.</p>
                <label class="form-label">Rejection reason <span class="text-danger">*</span></label>
                <textarea name="reason" class="form-control" rows="3" minlength="10" maxlength="5000" required
                    placeholder="Minimum 10 characters"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject Voucher</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($showResubmit)
<div class="modal fade" id="voucherResubmitModal{{ $uid }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ $routeParams('voucher-approvals.resubmit') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Resubmit voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Resubmit <strong>{{ $voucher->formatted_id }}</strong> for approval? Previous rejection stays in history.</p>
                <label class="form-label">Remark <span class="text-muted">(optional)</span></label>
                <textarea name="remark" class="form-control" rows="2" maxlength="2000"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Resubmit</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($showForce)
<div class="modal fade" id="voucherForceModal{{ $uid }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ $routeParams('voucher-approvals.force-approve') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Force approve</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Force-approve <strong>{{ $voucher->formatted_id }}</strong>? This override is logged.</p>
                <label class="form-label">Remark <span class="text-danger">*</span></label>
                <textarea name="remark" class="form-control" rows="3" minlength="10" maxlength="5000" required
                    placeholder="Minimum 10 characters"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Force Approve</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($showReopen)
<div class="modal fade" id="voucherReopenModal{{ $uid }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ $routeParams('voucher-approvals.reopen') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Reopen voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Reopen <strong>{{ $voucher->formatted_id }}</strong> as pending approval? Ledger lines will be held again.</p>
                <label class="form-label">Remark <span class="text-danger">*</span></label>
                <textarea name="remark" class="form-control" rows="3" minlength="10" maxlength="5000" required
                    placeholder="Minimum 10 characters"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-secondary">Confirm Reopen</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($showHistory)
<div class="modal fade" id="voucherHistoryModal{{ $uid }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approval history — {{ $voucher->formatted_id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($voucher->isApprovalRejected() && $voucher->rejection_reason)
                    <div class="alert alert-danger py-2">
                        <strong>Current rejection reason:</strong> {{ $voucher->rejection_reason }}
                    </div>
                @endif
                @if($events->isNotEmpty())
                    <ul class="list-unstyled mb-0">
                        @foreach($events->sortByDesc('id') as $event)
                            <li class="border-bottom py-2">
                                <span class="badge bg-label-secondary">{{ str_replace('_', ' ', $event->action) }}</span>
                                <strong>{{ $event->user_name ?? 'System' }}</strong>
                                <span class="text-muted">{{ optional($event->created_at)->format('d M Y H:i') }}</span>
                                @if($event->remark)
                                    <div class="small mt-1">{{ $event->remark }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">No history entries.</p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif
@endif
