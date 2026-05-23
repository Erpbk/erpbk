@php
$documentExpiryAlerts = $documentExpiryAlerts ?? ['expiring' => [], 'expired' => []];
$expiring = $documentExpiryAlerts['expiring'] ?? [];
$expired = $documentExpiryAlerts['expired'] ?? [];
@endphp

<div class="row mb-4 g-3">
  <div class="col-md-6">
  <div class="card h-100 dashboard-doc-alert dashboard-doc-alert-expiring {{ ($expiring['total'] ?? 0) > 0 ? 'dashboard-doc-alert-active' : '' }}">
    <div class="card-body">
      <div class="d-flex align-items-start justify-content-between mb-2">
        <div>
          <h5 class="mb-1 fw-semibold text-warning">
            <i class="ti ti-alert-triangle me-1"></i>{{ __('Expiring Soon Documents') }}
          </h5>
          <p class="text-muted small mb-0">{{ __('Within :days days — modules you can access', ['days' => 10]) }}</p>
        </div>
        <span class="badge bg-label-warning fs-5 px-3 py-2">{{ number_format($expiring['total'] ?? 0) }}</span>
      </div>

      @if(!empty($expiring['by_module']))
      <ul class="list-unstyled small mb-3 dashboard-doc-module-list">
        @foreach($expiring['by_module'] as $row)
        <li class="d-flex justify-content-between py-1 border-bottom border-light">
          <span>{{ $row['label'] }}</span>
          <span class="fw-semibold text-warning">{{ number_format($row['count']) }}</span>
        </li>
        @endforeach
      </ul>
      @endif

      @if(!empty($expiring['items']))
      <div class="dashboard-doc-items small mb-3">
        @foreach($expiring['items'] as $item)
        <div class="d-flex justify-content-between align-items-start py-1 border-bottom border-light">
          <div class="pe-2">
            <span class="badge bg-label-secondary me-1">{{ $item['module_label'] }}</span>
            @if(!empty($item['url']))
            <a href="{{ $item['url'] }}" class="text-body fw-medium">{{ $item['title'] }}</a>
            @else
            <span class="fw-medium">{{ $item['title'] }}</span>
            @endif
            <div class="text-muted">{{ $item['expiry_date'] }} · {{ $item['days_left'] }} {{ __('days left') }}</div>
          </div>
        </div>
        @endforeach
      </div>
      @elseif(($expiring['total'] ?? 0) === 0)
      <p class="text-muted small mb-3">{{ __('No documents expiring soon.') }}</p>
      @endif

      @if(($expiring['total'] ?? 0) > 0 && !empty($expiring['list_url']))
      <a href="{{ $expiring['list_url'] }}" class="btn btn-sm btn-warning w-100">{{ __('View all expiring') }}</a>
      @endif
    </div>
  </div>
  </div>

  <div class="col-md-6">
  <div class="card h-100 dashboard-doc-alert dashboard-doc-alert-expired {{ ($expired['total'] ?? 0) > 0 ? 'dashboard-doc-alert-active' : '' }}">
    <div class="card-body">
      <div class="d-flex align-items-start justify-content-between mb-2">
        <div>
          <h5 class="mb-1 fw-semibold text-danger">
            <i class="ti ti-file-alert me-1"></i>{{ __('Expired Documents') }}
          </h5>
          <p class="text-muted small mb-0">{{ __('Past expiry date — modules you can access') }}</p>
        </div>
        <span class="badge bg-label-danger fs-5 px-3 py-2">{{ number_format($expired['total'] ?? 0) }}</span>
      </div>

      @if(!empty($expired['by_module']))
      <ul class="list-unstyled small mb-3 dashboard-doc-module-list">
        @foreach($expired['by_module'] as $row)
        <li class="d-flex justify-content-between py-1 border-bottom border-light">
          <span>{{ $row['label'] }}</span>
          <span class="fw-semibold text-danger">{{ number_format($row['count']) }}</span>
        </li>
        @endforeach
      </ul>
      @endif

      @if(!empty($expired['items']))
      <div class="dashboard-doc-items small mb-3">
        @foreach($expired['items'] as $item)
        <div class="d-flex justify-content-between align-items-start py-1 border-bottom border-light">
          <div class="pe-2">
            <span class="badge bg-label-secondary me-1">{{ $item['module_label'] }}</span>
            @if(!empty($item['url']))
            <a href="{{ $item['url'] }}" class="text-body fw-medium">{{ $item['title'] }}</a>
            @else
            <span class="fw-medium">{{ $item['title'] }}</span>
            @endif
            <div class="text-muted">{{ $item['expiry_date'] }}
              @if(($item['days_left'] ?? 0) < 0)
              · {{ abs($item['days_left']) }} {{ __('days ago') }}
              @endif
            </div>
          </div>
        </div>
        @endforeach
      </div>
      @elseif(($expired['total'] ?? 0) === 0)
      <p class="text-muted small mb-3">{{ __('No expired documents.') }}</p>
      @endif

      @if(($expired['total'] ?? 0) > 0 && !empty($expired['list_url']))
      <a href="{{ $expired['list_url'] }}" class="btn btn-sm btn-danger w-100">{{ __('View all expired') }}</a>
      @endif
    </div>
  </div>
  </div>
</div>
