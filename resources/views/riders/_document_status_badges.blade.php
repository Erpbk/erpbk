{{-- Badge bubbles for expired / expiring documents (count only) --}}
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
    min-width: 1.15rem;
    padding: 0.15rem 0.4rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    line-height: 1.2;
    margin: 0;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.16);
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

  @media (max-width: 768px) {
    .rider-doc-status-bubble {
      font-size: 0.65rem;
      padding: 0.12rem 0.32rem;
      min-width: 1rem;
    }
  }
</style>
@endonce
