{{-- Notification badges for expired / expiring documents (float on top of tab) --}}
@if(($expiredCount ?? 0) > 0 || ($expiringCount ?? 0) > 0)
<span class="rider-tab-badges">
  @if(($expiredCount ?? 0) > 0)
  <span class="rider-doc-status-bubble expired-bubble"
    title="{{ $expiredCount }} expired document{{ $expiredCount === 1 ? '' : 's' }}">
    {{ $expiredCount }}
  </span>
  @endif

  @if(($expiringCount ?? 0) > 0)
  <span class="rider-doc-status-bubble expiring-bubble"
    title="{{ $expiringCount }} document{{ $expiringCount === 1 ? '' : 's' }} expiring within 30 days">
    {{ $expiringCount }}
  </span>
  @endif
</span>
@endif

@once
<style>
  .rider-doc-status-bubble {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.1rem;
    padding: 0.12rem 0.4rem;
    border-radius: 999px;
    font-size: 0.62rem;
    font-weight: 700;
    line-height: 1.15;
    margin: 0;
    white-space: nowrap;
    box-shadow: 0 0 0 2px #fff, 0 2px 6px rgba(0, 0, 0, 0.18);
    box-sizing: border-box;
  }

  .expired-bubble {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    color: #fff;
  }

  .expiring-bubble {
    background: linear-gradient(135deg, #ffd93d 0%, #ffb800 100%);
    color: #fff;
  }
</style>
@endonce