<div class="modal-load-error text-center p-5 text-danger">
    <i class="fas fa-exclamation-circle fa-3x"></i>
    <p class="mt-3 mb-1 fw-semibold">{{ $message ?? 'Unable to load content.' }}</p>
    @if(!empty($status))
    <p class="text-muted small mb-3">HTTP {{ $status }}</p>
    @endif
    <button type="button" class="btn btn-primary btn-sm" onclick="location.reload()">Refresh page</button>
</div>
