@php
    $pendingBannerModel = null;
    $pendingBannerRequestId = null;

    if (\App\Services\DeleteRequestService::enabled() && ! request()->filled('delete_request') && request()->route()) {
        $routeName = (string) request()->route()->getName();
        $routeId = request()->route('id')
            ?? request()->route('rider')
            ?? request()->route('employee')
            ?? request()->route('bike');

        if (is_object($routeId) && method_exists($routeId, 'getKey')) {
            $routeId = $routeId->getKey();
        }

        if ($routeId && str_ends_with($routeName, '.show')) {
            foreach (config('delete_approval.modules', []) as $meta) {
                if (($meta['show_route'] ?? null) === $routeName && ! empty($meta['model']) && class_exists($meta['model'])) {
                    $candidate = $meta['model']::query()->find($routeId);
                    if ($candidate && method_exists($candidate, 'isPendingDeletion') && $candidate->isPendingDeletion()) {
                        $pendingBannerModel = $candidate;
                        $pendingBannerRequestId = $candidate->pendingDeleteRequestId();
                    }
                    break;
                }
            }
        }
    }
@endphp

@if($pendingBannerModel)
<div class="alert alert-warning mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="badge bg-warning text-dark me-2">Pending Deletion</span>
            This record is locked while a delete request
            @if($pendingBannerRequestId)#{{ $pendingBannerRequestId }}@endif
            awaits administrator approval. Editing and further actions are disabled.
        </div>
        @if(auth()->user()?->isAdmin() || user_can('settings_delete_requests_view'))
            <a href="{{ route('settings-panel.delete-requests.show', $pendingBannerRequestId) }}" class="btn btn-sm btn-outline-warning">
                Review Delete Request
            </a>
        @endif
    </div>
</div>
@endif
