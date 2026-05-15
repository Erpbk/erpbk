@php
$employment = $employment ?? \App\Models\Riders::employmentStatusDisplay($employmentStatus ?? null);
$option = $option ?? \App\Models\Riders::riderOptionStatusBadge($optionText ?? null);
@endphp
<div class="d-flex flex-wrap align-items-center gap-1 justify-content-center">
  <span class="badge {{ $employment['badge'] }}" title="Employment / assignment status">{{ $employment['label'] }}</span>
  @if($option)
  <span class="badge {{ $option['badge'] }}" title="Rider option / flag">{{ $option['label'] }}</span>
  @endif
</div>