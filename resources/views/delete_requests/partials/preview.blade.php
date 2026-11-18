@php
    $req = $deleteRequest;
    $fullUrl = $moduleUrl ?: route('settings-panel.delete-requests.show', $req);
    $binUrl = route('settings-panel.trash.index');
    $embedModule = $embedModule ?? null;
    $embedView = $embedView ?? null;
    $embedData = $embedData ?? [];
@endphp

<div class="dr-preview"
     data-id="{{ $req->id }}"
     data-title="{{ $resourceId }}{{ $req->module_name ? ' · ' . $req->module_name : '' }}"
     data-sub="{{ $req->module_name }} · Request #{{ $req->id }} · by {{ $req->requester->name ?? '—' }}"
     data-status="{{ $req->status }}"
     data-bin="{{ $req->bin_outcome }}"
     data-approve-url="{{ route('settings-panel.delete-requests.approve', $req) }}"
     data-reject-url="{{ route('settings-panel.delete-requests.reject', $req) }}"
     data-full-url="{{ $fullUrl }}"
     data-bin-url="{{ $binUrl }}"
     data-can-review="{{ ($canReview && $req->isPending()) ? '1' : '0' }}"
     data-can-bin="{{ ($canOpenBin && $req->isInRecycleBin()) ? '1' : '0' }}">

    @if($req->wasPermanentlyDeleted())
        <div class="banner">This record was permanently deleted from the Recycle Bin — its details can no longer be shown.</div>
    @elseif(!empty($embedView))
        <div class="dr-module-embed">
            @include($embedView, $embedData)
        </div>
    @elseif($moduleUrl && $record)
        @if($req->isRejected())
            <div class="banner gry">Request rejected — the record is still in its original module.</div>
        @elseif($req->isInRecycleBin())
            <div class="banner amb">This record is in the Recycle Bin — open full page to review it there.</div>
        @elseif($req->isPending())
            <div class="banner amb">Waiting for approval — open the module record below.</div>
        @endif
        <div class="doc" style="padding:18px 20px">
            <p class="mb-3" style="color:#475569;font-size:14px">
                Preview for <strong>{{ $req->module_name }}</strong> uses the module screen.
            </p>
            <a class="btn btn-primary" href="{{ $fullUrl }}" target="_blank" rel="noopener">Open module record ↗</a>
        </div>
    @elseif($record)
        <div class="banner amb">Module preview is not available for this record type yet.</div>
        <div class="doc" style="padding:18px 20px">
            <div class="it2"><span>Module</span><b>{{ $req->module_name }}</b></div>
            <div class="it2"><span>Record</span><b>{{ $resourceId }}</b></div>
            <div class="it2"><span>Status</span><b>{{ ucfirst($req->status) }}</b></div>
        </div>
    @else
        <div class="banner amb">Record details are unavailable for this request.</div>
        <div class="doc" style="padding:18px 20px">
            <div class="it2"><span>Module</span><b>{{ $req->module_name }}</b></div>
            <div class="it2"><span>Record</span><b>{{ $resourceId }}</b></div>
            <div class="it2"><span>Status</span><b>{{ ucfirst($req->status) }}</b></div>
        </div>
    @endif

    {{-- Reason / remarks + audit trail under posted transactions / module document. --}}
    @include('delete_requests.partials.audit_trail', ['deleteRequest' => $req])
</div>
