@php
  $badge = $badge ?? [];
  $status = $badge['status'] ?? 'none';
  $url = $badge['url'] ?? null;
  $text = $badge['text']
    ?? (! empty($badge['expiry']) ? \App\Helpers\General::DateFormat($badge['expiry']) : ($badge['label'] ?? 'Document'));
  $title = trim(($badge['name'] ?? 'Document') . ($badge['expiry'] ? ' · expires ' . $badge['expiry'] : ''));
  $statusLabel = $badge['label'] ?? '';
  if ($statusLabel !== '') {
    $title = trim($title . ' · ' . $statusLabel);
  }
  
  // Calculate days until expiry for precise color coding
  $daysUntilExpiry = null;
  $colorStatus = $status;
  if (!empty($badge['expiry'])) {
    try {
      $expiryDate = \Carbon\Carbon::parse($badge['expiry']);
      $daysUntilExpiry = now()->startOfDay()->diffInDays($expiryDate, false);
      
      // Override status based on days until expiry
      if ($daysUntilExpiry < 0) {
        $colorStatus = 'expired'; // Red - Already expired
      } elseif ($daysUntilExpiry <= 7) {
        $colorStatus = 'critical'; // Dark Red - Critical (within 7 days)
      } elseif ($daysUntilExpiry <= 30) {
        $colorStatus = 'expiring'; // Orange - Expiring soon (within 30 days)
      } elseif ($daysUntilExpiry <= 60) {
        $colorStatus = 'warning'; // Yellow - Warning (within 60 days)
      } else {
        $colorStatus = 'valid'; // Green - Valid (60+ days)
      }
    } catch (\Exception $e) {
      $colorStatus = $status;
    }
  }
@endphp
@once
<style>
  @keyframes rider-doc-badge-blink {
    0%, 100% {
      opacity: 1;
      box-shadow: 0 0 0 0 currentColor;
    }
    50% {
      opacity: 0.42;
      box-shadow: 0 0 10px 1px currentColor;
    }
  }
  @keyframes rider-doc-badge-pulse {
    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 currentColor; }
    50% { opacity: 0.75; box-shadow: 0 0 8px 0 currentColor; }
  }
  .rider-doc-expiry-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.18rem 0.55rem;
    border: 1px solid;
    border-radius: 0.4rem;
    font-weight: 700;
    font-size: 0.8125rem;
    line-height: 1.3;
    letter-spacing: 0.01em;
    white-space: nowrap;
    text-decoration: none !important;
    cursor: default;
    vertical-align: middle;
  }
  a.rider-doc-expiry-badge {
    cursor: pointer;
  }
  a.rider-doc-expiry-badge:hover {
    filter: brightness(0.96);
  }
  /* Red - Already Expired */
  .rider-doc-expiry-badge.is-expired {
    color: #fff;
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    border-color: #991b1b;
    animation: rider-doc-badge-blink 0.8s ease-in-out infinite;
  }
  
  /* Dark Red - Critical (Expiring within 7 days) */
  .rider-doc-expiry-badge.is-critical {
    color: #fff;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border-color: #dc2626;
    animation: rider-doc-badge-blink 1s ease-in-out infinite;
  }
  
  /* Orange - Expiring Soon (8-30 days) */
  .rider-doc-expiry-badge.is-expiring {
    color: #fff;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    border-color: #ea580c;
    animation: rider-doc-badge-pulse 1.5s ease-in-out infinite;
  }
  
  /* Yellow - Warning (31-60 days) */
  .rider-doc-expiry-badge.is-warning {
    color: #fff;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    border-color: #f59e0b;
  }
  
  /* Green - Valid (60+ days) */
  .rider-doc-expiry-badge.is-valid {
    color: #fff;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-color: #16a34a;
  }
  
  /* Gray - No Date */
  .rider-doc-expiry-badge.is-none {
    color: #fff;
    background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
    border-color: #6b7280;
  }
</style>
@endonce
@if ($url)
  <a href="{{ $url }}"
    target="_blank"
    rel="noopener noreferrer"
    class="rider-doc-expiry-badge is-{{ $colorStatus }}"
    title="{{ $title }} — click to open document">{{ $text }}</a>
@else
  <span class="rider-doc-expiry-badge is-{{ $colorStatus }}"
    title="{{ $title }}">{{ $text }}</span>
@endif
