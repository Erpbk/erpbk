@php
  $filterChips = $filterChips ?? [];
  $keep = request()->except(['page']);
@endphp

@if(count($filterChips))
<div class="ra-chips" id="raChips">
  @foreach($filterChips as $chip)
    @php
      $params = $keep;
      unset($params[$chip['key']]);
      $href = request()->url() . (count($params) ? '?' . http_build_query($params) : '');
    @endphp
    <span class="ra-chip">
      {{ $chip['label'] }}: {{ $chip['value'] }}
      <a href="{{ $href }}" aria-label="Remove {{ $chip['label'] }} filter" style="border:0;background:#fff;color:#1e3a8a;width:18px;height:18px;border-radius:50%;display:inline-grid;place-items:center;text-decoration:none;line-height:1;">×</a>
    </span>
  @endforeach
</div>
@endif
