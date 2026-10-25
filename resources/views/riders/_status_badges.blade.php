@php
$current = $current ?? \App\Models\Riders::currentStatusDisplay($employmentStatus ?? null, $optionText ?? null);
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
@php
$rlLabel = strtolower(trim((string) ($current['label'] ?? '')));
$rlBadgeClass = match (true) {
  str_contains($rlLabel, 'active') && !str_contains($rlLabel, 'inactive') => 'rl-b-active',
  str_contains($rlLabel, 'abscond') => 'rl-b-absconded',
  str_contains($rlLabel, 'cancel') => 'rl-b-cancel',
  default => 'rl-b-inactive',
};
$useRlBadge = !empty($wrapperClass) && str_contains((string) $wrapperClass, 'rl-status');
@endphp
<div class="d-inline-flex flex-column align-items-center gap-1 {{ $wrapperClass ?? '' }}">
  <div class="d-flex flex-wrap align-items-center gap-1 justify-content-center">
    <span class="{{ $useRlBadge ? 'rl-badge '.$rlBadgeClass : 'badge '.$current['badge'] }}" title="Rider Status">{{ $current['label'] }}</span>
  </div>
  @if($statusDays !== null && $statusDays !== '')
  <small class="{{ $useRlBadge ? 'rl-status-days' : 'text-muted lh-1' }}" title="{{ $daysTitle }}">{{ (int) $statusDays }} {{ (int) $statusDays === 1 ? 'day' : 'days' }}</small>
  @endif
</div>
