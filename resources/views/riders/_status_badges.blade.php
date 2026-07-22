@php
$employment = $employment ?? \App\Models\Riders::employmentStatusDisplay($employmentStatus ?? null);
$option = $option ?? \App\Models\Riders::riderOptionStatusBadge($optionText ?? null);
$statusDays = $statusDays ?? null;
$statusChangedAt = $statusChangedAt ?? null;
if (($statusDays === null || $statusDays === '') && !empty($rider)) {
  $resolvedDays = \App\Models\Riders::resolveEmploymentStatusDays($rider);
  $statusDays = $resolvedDays['days'];
  $statusChangedAt = $statusChangedAt ?? $resolvedDays['changed_at'];
}
$daysTitle = $statusChangedAt
  ? 'Status changed on ' . \Carbon\Carbon::parse($statusChangedAt)->format('d M Y')
  : 'Days in current status';
@endphp
<div class="d-inline-flex flex-column align-items-center gap-1 {{ $wrapperClass ?? '' }}">
  <div class="d-flex flex-wrap align-items-center gap-1 justify-content-center">
    <span class="badge {{ $employment['badge'] }}" title="Employment / assignment status">{{ $employment['label'] }}</span>
    @if($option)
    <span class="badge {{ $option['badge'] }}" title="Rider option / flag">{{ $option['label'] }}</span>
    @endif
  </div>
  @if($statusDays !== null && $statusDays !== '')
  <small class="text-muted lh-1" title="{{ $daysTitle }}">{{ (int) $statusDays }} {{ (int) $statusDays === 1 ? 'day' : 'days' }}</small>
  @endif
</div>