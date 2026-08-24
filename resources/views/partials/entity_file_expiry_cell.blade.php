@php
  $file = $file ?? null;
  $label = $label ?? ($file->name ?? 'Document');
@endphp
<td class="text-start">
  @if($file && !empty($file->expiry_date))
    @include('riders._document_expiry_badge', ['badge' => \App\Support\EntityExpiry::badgeForDate($file->expiry_date, $label)])
  @else
    <span class="text-muted">—</span>
  @endif
</td>
