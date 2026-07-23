@extends($layout ?? 'layouts.app')
@section('title', 'Delete Request #' . $deleteRequest->id)
@section('content')
@php
    $statusColors = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'secondary',
    ];
    $outcomeLabels = [
        'in_recycle_bin' => 'In Recycle Bin',
        'restored' => 'Restored to module',
        'permanently_deleted' => 'Permanently deleted',
    ];
    $canReview = auth()->user()?->isAdmin() || user_can('settings_delete_requests_edit');
    $moduleUrl = \App\Services\DeleteRequestService::moduleShowUrl($deleteRequest);
    $canOpenBin = auth()->user()?->can('settings_recycle_bin_view');
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Delete Request #{{ $deleteRequest->id }}</h4>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-label-{{ $statusColors[$deleteRequest->status] ?? 'secondary' }}">
                            {{ ucfirst($deleteRequest->status) }}
                        </span>
                        @if($deleteRequest->bin_outcome)
                            <span class="badge bg-label-info">
                                {{ $outcomeLabels[$deleteRequest->bin_outcome] ?? ucfirst(str_replace('_', ' ', $deleteRequest->bin_outcome)) }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Module</dt>
                        <dd class="col-sm-8">{{ $deleteRequest->module_name }}</dd>

                        <dt class="col-sm-4">Record</dt>
                        <dd class="col-sm-8">
                            {{ $deleteRequest->record_label ?: '—' }}
                            <span class="text-muted">#{{ $deleteRequest->deletable_id }}</span>
                            @if($moduleUrl && $deleteRequest->isPending())
                                <div class="mt-2">
                                    <a href="{{ $moduleUrl }}" class="btn btn-sm btn-primary">Open Full Record</a>
                                </div>
                            @endif
                            @if($deleteRequest->isInRecycleBin() && $canOpenBin)
                                <div class="mt-2">
                                    <a href="{{ route('settings-panel.trash.index') }}" class="btn btn-sm btn-outline-warning">
                                        Open Recycle Bin
                                    </a>
                                </div>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Reason</dt>
                        <dd class="col-sm-8">{{ $deleteRequest->reason ?: '—' }}</dd>

                        <dt class="col-sm-4">Admin Remarks</dt>
                        <dd class="col-sm-8">{{ $deleteRequest->admin_remarks ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Audit trail</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 timeline-audit">
                        <li class="mb-3 pb-3 border-bottom">
                            <div class="fw-semibold">Deletion requested</div>
                            <div class="small text-muted">
                                By {{ $deleteRequest->requester->name ?? '—' }}
                                on {{ $deleteRequest->created_at?->format('d-m-Y H:i:s') ?? '—' }}
                            </div>
                        </li>

                        @if($deleteRequest->isRejected())
                            <li class="mb-3 pb-3 border-bottom">
                                <div class="fw-semibold text-secondary">Request rejected</div>
                                <div class="small text-muted">
                                    By {{ $deleteRequest->reviewer->name ?? '—' }}
                                    on {{ $deleteRequest->reviewed_at?->format('d-m-Y H:i:s') ?? '—' }}
                                </div>
                            </li>
                        @endif

                        @if($deleteRequest->isApproved())
                            <li class="mb-3 pb-3 border-bottom">
                                <div class="fw-semibold text-success">Approved — moved to Recycle Bin</div>
                                <div class="small text-muted">
                                    Approved by {{ $deleteRequest->reviewer->name ?? '—' }}
                                    on {{ ($deleteRequest->moved_to_bin_at ?? $deleteRequest->reviewed_at)?->format('d-m-Y H:i:s') ?? '—' }}
                                </div>
                                <div class="small text-muted mt-1">
                                    Record left the original module and is recoverable from the Recycle Bin until permanently deleted.
                                </div>
                            </li>
                        @endif

                        @if($deleteRequest->wasRestoredFromBin())
                            <li class="mb-3 pb-3 border-bottom">
                                <div class="fw-semibold text-primary">Restored to original module</div>
                                <div class="small text-muted">
                                    By {{ $deleteRequest->restoredByUser->name ?? '—' }}
                                    on {{ $deleteRequest->restored_at?->format('d-m-Y H:i:s') ?? '—' }}
                                </div>
                            </li>
                        @endif

                        @if($deleteRequest->wasPermanentlyDeleted())
                            <li class="mb-0">
                                <div class="fw-semibold text-danger">Permanently deleted from Recycle Bin</div>
                                <div class="small text-muted">
                                    By {{ $deleteRequest->permanentlyDeletedByUser->name ?? '—' }}
                                    on {{ $deleteRequest->permanently_deleted_at?->format('d-m-Y H:i:s') ?? '—' }}
                                </div>
                            </li>
                        @elseif($deleteRequest->isInRecycleBin())
                            <li class="mb-0">
                                <div class="fw-semibold text-warning">Currently in Recycle Bin</div>
                                <div class="small text-muted">
                                    Waiting for restore or permanent deletion by an authorized administrator.
                                </div>
                            </li>
                        @elseif($deleteRequest->isPending())
                            <li class="mb-0">
                                <div class="fw-semibold text-warning">Awaiting administrator review</div>
                                <div class="small text-muted">
                                    Record remains in the original module and is locked until approved or rejected.
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @if($deleteRequest->isPending() && $canReview)
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Review</h5></div>
                <div class="card-body">
                    @if($moduleUrl)
                        <a href="{{ $moduleUrl }}" class="btn btn-outline-primary w-100 mb-3">Open Record in Module</a>
                    @endif
                    <form method="POST" action="{{ route('settings-panel.delete-requests.approve', $deleteRequest) }}" class="mb-3">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Remarks (optional)</label>
                            <textarea name="admin_remarks" class="form-control" rows="2">{{ old('admin_remarks') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100"
                            onclick="return confirm('Approve this request? The record will be moved to the Recycle Bin (not permanently deleted).')">
                            Approve → Recycle Bin
                        </button>
                    </form>
                    <form method="POST" action="{{ route('settings-panel.delete-requests.reject', $deleteRequest) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Remarks (optional)</label>
                            <textarea name="admin_remarks" class="form-control" rows="2">{{ old('admin_remarks') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Reject this delete request?')">Reject Request</button>
                    </form>
                </div>
            </div>
            @endif
            <div class="mt-3 d-flex flex-wrap gap-2">
                <a href="{{ route('settings-panel.delete-requests.index') }}" class="btn btn-outline-secondary">Back</a>
                @if($canOpenBin)
                    <a href="{{ route('settings-panel.trash.index') }}" class="btn btn-outline-warning">Recycle Bin</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
