@php
$employment = $employment ?? \App\Models\Riders::employmentStatusDisplay($employmentStatus ?? null);
$option = $option ?? \App\Models\Riders::riderOptionStatusBadge($optionText ?? null);
$statusDays = $statusDays ?? null;
$statusChangedAt = $statusChangedAt ?? null;
$daysTitle = $statusChangedAt
  ? 'Status changed on ' . \Carbon\Carbon::parse($statusChangedAt)->format('d M Y')
  : 'Days in current status';
@endphp
<div class="d-flex flex-wrap align-items-center gap-1 justify-content-center">
  <span class="badge {{ $employment['badge'] }}" title="Employment / assignment status">{{ $employment['label'] }}</span>
  @if($option)
  <span class="badge {{ $option['badge'] }}" title="Rider option / flag">{{ $option['label'] }}</span>
  @endif
  @if($statusDays !== null && $statusDays !== '')
  <span class="badge bg-label-secondary" title="{{ $daysTitle }}">{{ (int) $statusDays }} {{ (int) $statusDays === 1 ? 'day' : 'days' }}</span>
  @endif
</div>