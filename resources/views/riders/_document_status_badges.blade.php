{{-- Badge bubbles for expired / expiring documents --}}
@if(($expiredCount ?? 0) > 0 || ($expiringCount ?? 0) > 0)
  <span class="rider-tab-badges">
    @if(($expiredCount ?? 0) > 0)
      <span class="rider-doc-status-bubble expired-bubble"
        title="{{ $expiredCount }} expired document{{ $expiredCount === 1 ? '' : 's' }}">
        {{ $expiredCount }} Document{{ $expiredCount === 1 ? '' : 's' }} Expired
      </span>
    @endif

    @if(($expiringCount ?? 0) > 0)
      <span class="rider-doc-status-bubble expiring-bubble"
        title="{{ $expiringCount }} document{{ $expiringCount === 1 ? '' : 's' }} expiring within 30 days">
        {{ $expiringCount }} Expiring Soon
      </span>
    @endif
  </span>
@endif

@once
<style>
  .rider-doc-status-bubble {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.65rem;
    font-weight: 600;
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
      font-size: 0.6rem;
      padding: 0.16rem 0.4rem;
    }
  }
</style>
@endonce
