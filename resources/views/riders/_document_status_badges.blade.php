{{-- Badge bubble for expired documents --}}
@if(($expiredCount ?? 0) > 0)
  <span class="rider-doc-status-bubble expired-bubble"
    title="{{ $expiredCount }} expired document{{ $expiredCount === 1 ? '' : 's' }}">
    {{ $expiredCount }} Document{{ $expiredCount === 1 ? '' : 's' }} Expired
  </span>
@endif

{{-- Badge bubble for expiring soon documents --}}
@if(($expiringCount ?? 0) > 0)
  <span class="rider-doc-status-bubble expiring-bubble"
    title="{{ $expiringCount }} document{{ $expiringCount === 1 ? '' : 's' }} expiring within 30 days">
    {{ $expiringCount }} Expiring Soon
  </span>
@endif

<style>
  .rider-doc-status-bubble {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 0.5rem;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    animation: pulse-subtle 2s ease-in-out infinite;
  }

  .expired-bubble {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    color: #fff;
  }

  .expiring-bubble {
    background: linear-gradient(135deg, #ffd93d 0%, #ffb800 100%);
    color: #fff;
  }

  @keyframes pulse-subtle {
    0%, 100% {
      transform: scale(1);
      opacity: 1;
    }
    50% {
      transform: scale(1.02);
      opacity: 0.95;
    }
  }

  /* Hover effect */
  .rider-doc-status-bubble:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    animation: none;
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .rider-doc-status-bubble {
      font-size: 0.65rem;
      padding: 0.3rem 0.6rem;
      margin-left: 0.35rem;
    }
  }

  /* Small screen - show count only */
  @media (max-width: 576px) {
    .rider-doc-status-bubble {
      font-size: 0.65rem;
      padding: 0.25rem 0.5rem;
    }
  }
</style>
