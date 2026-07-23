@php
    $reviewDeleteRequest = null;
    $reviewRecord = null;
    $canReviewDelete = auth()->check() && (auth()->user()->isAdmin() || user_can('settings_delete_requests_edit'));

    if ($canReviewDelete && request()->filled('delete_request') && \App\Services\DeleteRequestService::enabled()) {
        $reviewDeleteRequest = \App\Models\DeleteRequest::with(['requester'])
            ->pending()
            ->find(request('delete_request'));
        if ($reviewDeleteRequest) {
            $reviewRecord = \App\Services\DeleteRequestService::resolveDeletable($reviewDeleteRequest);
        }
    }
@endphp

@if($reviewDeleteRequest)
<div class="alert alert-warning border-warning shadow-sm sticky-top mb-3" style="z-index: 1020; top: 4.5rem;">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <div class="fw-semibold mb-1">
                <i class="ti ti-trash-x me-1"></i>
                Delete Request #{{ $reviewDeleteRequest->id }} — Pending Approval
            </div>
            <div class="small mb-0">
                <strong>{{ $reviewDeleteRequest->module_name }}:</strong>
                {{ $reviewDeleteRequest->record_label ?: ('#' . $reviewDeleteRequest->deletable_id) }}
                &middot; Requested by {{ $reviewDeleteRequest->requester->name ?? '—' }}
                on {{ $reviewDeleteRequest->created_at?->format('d-m-Y H:i') }}
                @if($reviewDeleteRequest->reason)
                    &middot; Reason: {{ $reviewDeleteRequest->reason }}
                @endif
            </div>
            @if(!$reviewRecord)
                <div class="text-danger small mt-1">Warning: the primary record could not be loaded.</div>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="{{ route('settings-panel.delete-requests.approve', $reviewDeleteRequest) }}" class="d-inline">
                @csrf
                <input type="hidden" name="admin_remarks" value="">
                <button type="submit" class="btn btn-success btn-sm"
                    onclick="return confirm('Approve this request? The record will be moved to the Recycle Bin (not permanently deleted).')">
                    Approve → Recycle Bin
                </button>
            </form>
            <form method="POST" action="{{ route('settings-panel.delete-requests.reject', $reviewDeleteRequest) }}" class="d-inline">
                @csrf
                <input type="hidden" name="admin_remarks" value="">
                <button type="submit" class="btn btn-outline-secondary btn-sm"
                    onclick="return confirm('Reject this delete request? The record will remain active.')">
                    Reject Request
                </button>
            </form>
            <a href="{{ route('settings-panel.delete-requests.index') }}" class="btn btn-outline-dark btn-sm">All Requests</a>
        </div>
    </div>
</div>
@endif
