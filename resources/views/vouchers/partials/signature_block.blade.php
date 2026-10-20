{{--
  Configurable voucher signature slots.
  Expects $signatureSlots: collection/array of {role_label, display_name?, designation?}
  Optional $signatureVariant: 'cv' (classic) or 'rpv' (payment/rider document). Default: rpv
--}}
@php
    $slots = collect($signatureSlots ?? [])->values();
    $variant = $signatureVariant ?? 'rpv';
    $tableClass = $variant === 'cv' ? 'cv-sigs' : 'rpv-sigs';
    $cellClass = $variant === 'cv' ? 'cv-sig' : 'rpv-sig';
    $lineClass = $variant === 'cv' ? 'cv-sig-line' : 'rpv-sig-line';
    $labelClass = $variant === 'cv' ? 'cv-sig-label' : 'rpv-sig-label';
    $nameClass = $variant === 'cv' ? 'cv-sig-name' : 'rpv-sig-name';
    $desigClass = $variant === 'cv' ? 'cv-sig-desig' : 'rpv-sig-desig';
    $perRow = max(1, min(4, (int) ($signaturePerRow ?? 4)));
@endphp
@if($slots->isNotEmpty())
<style>
    .{{ $tableClass }} .{{ $nameClass }} {
        font-size: 11px;
        font-weight: 600;
        color: #334155;
        margin-top: 4px;
        line-height: 1.3;
    }
    .{{ $tableClass }} .{{ $desigClass }} {
        font-size: 10px;
        color: #64748b;
        margin-top: 2px;
        line-height: 1.25;
    }
</style>
@foreach($slots->chunk($perRow) as $row)
<table class="{{ $tableClass }}" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        @foreach($row as $slot)
        @php
            $widthPct = round(100 / max(1, $row->count()), 2);
        @endphp
        <td class="{{ $cellClass }}" style="width: {{ $widthPct }}%;">
            <div class="{{ $lineClass }}"></div>
            @if(!empty($slot['display_name']))
            <div class="{{ $nameClass }}">{{ $slot['display_name'] }}</div>
            @endif
            @if(!empty($slot['designation']))
            <div class="{{ $desigClass }}">{{ $slot['designation'] }}</div>
            @endif
            <div class="{{ $labelClass }}">{{ $slot['role_label'] ?? 'Signature' }}</div>
        </td>
        @endforeach
    </tr>
</table>
@endforeach
@endif
