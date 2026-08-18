@if(($riderExpiredDocumentCount ?? 0) > 0)
  <span class="rider-expired-count-dot"
    title="{{ $riderExpiredDocumentCount }} expired document{{ $riderExpiredDocumentCount === 1 ? '' : 's' }}">{{ $riderExpiredDocumentCount }}</span>
  @if(!empty($showExpiredBubble))
    <span class="rider-expired-docs-bubble">{{ $riderExpiredDocumentCount }} Document{{ $riderExpiredDocumentCount === 1 ? '' : 's' }} Expired</span>
  @endif
@endif
