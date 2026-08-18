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
  .rider-doc-expiry-badge.is-expired {
    color: #dc3545;
    background: #fde8ea;
    border-color: #dc3545;
    animation: rider-doc-badge-blink 0.8s ease-in-out infinite;
  }
  .rider-doc-expiry-badge.is-expiring {
    color: #c27a00;
    background: #fff4d6;
    border-color: #e6a817;
    animation: rider-doc-badge-blink 1.15s ease-in-out infinite;
  }
  .rider-doc-expiry-badge.is-valid {
    color: #198754;
    background: #e8f6ee;
    border-color: #198754;
    animation: rider-doc-badge-pulse 1.8s ease-in-out infinite;
  }
  .rider-doc-expiry-badge.is-none {
    color: #6c757d;
    background: #f1f3f5;
    border-color: #adb5bd;
  }
</style>
@endonce
@if ($url)
  <a href="{{ $url }}"
    target="_blank"
    rel="noopener noreferrer"
    class="rider-doc-expiry-badge is-{{ $status }}"
    title="{{ $title }} — click to open document">{{ $text }}</a>
@else
  <span class="rider-doc-expiry-badge is-{{ $status }}"
    title="{{ $title }}">{{ $text }}</span>
@endif
