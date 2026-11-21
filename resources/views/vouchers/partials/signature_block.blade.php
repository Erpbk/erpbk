{{--
  Configurable voucher signature slots.
  Stamp is the base; signature overlays on the stamp (official combined mark).
  Screen "View Voucher" hides this block; Print/PDF shows it.
--}}
@php
    $slots = collect($signatureSlots ?? [])->values();
    $variant = $signatureVariant ?? 'rpv';
    $tableClass = $variant === 'cv' ? 'cv-sigs' : 'rpv-sigs';
    $cellClass = $variant === 'cv' ? 'cv-sig' : 'rpv-sig';
    $labelClass = $variant === 'cv' ? 'cv-sig-label' : 'rpv-sig-label';
    $nameClass = $variant === 'cv' ? 'cv-sig-name' : 'rpv-sig-name';
    $desigClass = $variant === 'cv' ? 'cv-sig-desig' : 'rpv-sig-desig';
    $perRow = max(1, min(4, (int) ($signaturePerRow ?? 4)));
    $showSigNow = !empty($signatureShowOnScreen)
        || !empty($isPdf)
        || request()->boolean('print')
        || (bool) request()->get('print');
@endphp
@if($slots->isNotEmpty())
@php
    $absUrl = static function (?string $src): string {
        $src = trim((string) $src);
        if ($src === '') {
            return '';
        }
        if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://') || str_starts_with($src, 'data:') || str_starts_with($src, 'blob:')) {
            return $src;
        }

        return url($src);
    };
@endphp
<style>
    .vsa-sig-wrap { page-break-inside: avoid; break-inside: avoid; }
    .vsa-sig-print-only { display: none !important; }
    .rpv-doc--pdf .vsa-sig-print-only { display: block !important; }

    .{{ $tableClass }} { table-layout: fixed; width: 100%; border-collapse: collapse; }
    .{{ $tableClass }} td.{{ $cellClass }} {
        vertical-align: bottom !important;
        text-align: center;
        padding: 0 12px;
    }

    /* Same 4-row grid in every column so lines + labels share one baseline. */
    .vsa-slot {
        display: grid;
        grid-template-rows: 96px 14px 36px auto;
        align-items: end;
        justify-items: center;
        width: 100%;
        text-align: center;
    }
    .vsa-combo {
        position: relative;
        width: 180px;
        height: 96px;
        margin: 0 auto;
    }
    .vsa-combo-stamp {
        position: absolute;
        left: 50%;
        top: 40%;
        transform: translate(-50%, -50%);
        max-width: 156px;
        max-height: 74px;
        width: auto;
        height: auto;
        object-fit: contain;
        z-index: 1;
        opacity: 0.9;
    }
    .vsa-combo-sign {
        position: absolute;
        left: 50%;
        top: 58%;
        transform: translate(-50%, -50%) rotate(-6deg);
        max-width: 176px;
        max-height: 68px;
        width: auto;
        height: auto;
        object-fit: contain;
        z-index: 2;
        filter: contrast(1.05);
    }
    .vsa-combo--sign-only .vsa-combo-sign {
        top: 50%;
        transform: translate(-50%, -50%) rotate(0deg);
        max-height: 68px;
        max-width: 176px;
    }
    .vsa-combo--stamp-only .vsa-combo-stamp {
        top: 50%;
        max-height: 70px;
    }
    .vsa-slot-line {
        align-self: end;
        width: 100%;
        max-width: 180px;
        border-top: 1px solid #94a3b8;
        height: 0;
        margin: 0 auto 6px;
    }
    .vsa-slot-meta {
        width: 100%;
        max-width: 160px;
        min-height: 36px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
    }
    .{{ $tableClass }} .{{ $nameClass }} {
        font-size: 11px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        line-height: 1.25;
    }
    .{{ $tableClass }} .{{ $desigClass }} {
        font-size: 10px;
        color: #64748b;
        margin-top: 2px;
        line-height: 1.25;
    }
    .{{ $tableClass }} .{{ $labelClass }} {
        margin-top: 4px;
        width: 100%;
    }
    .vsa-combo img {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    @media print {
        .vsa-sig-print-only { display: block !important; }
        .vsa-sig-wrap,
        .vsa-sig-wrap table,
        .vsa-slot,
        .vsa-combo,
        .vsa-combo img {
            visibility: visible !important;
            opacity: 1 !important;
        }
        .vsa-slot { display: grid !important; }
        .vsa-combo { display: block !important; }
    }
</style>
<div class="vsa-sig-wrap{{ $showSigNow ? '' : ' vsa-sig-print-only' }}">
@foreach($slots->chunk($perRow) as $row)
<table class="{{ $tableClass }}" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        @foreach($row as $slot)
        @php
            $widthPct = round(100 / max(1, $row->count()), 2);
            $stampSrc = $absUrl($slot['stamp_url'] ?? null);
            $signSrc = $absUrl($slot['signature_url'] ?? null);
            $hasArt = $stampSrc !== '' || $signSrc !== '';
            $comboClass = 'vsa-combo';
            if ($stampSrc !== '' && $signSrc !== '') {
                $comboClass .= ' vsa-combo--both';
            } elseif ($stampSrc !== '') {
                $comboClass .= ' vsa-combo--stamp-only';
            } elseif ($signSrc !== '') {
                $comboClass .= ' vsa-combo--sign-only';
            }
        @endphp
        <td class="{{ $cellClass }}" style="width: {{ $widthPct }}%;">
            <div class="vsa-slot">
                <div class="{{ $comboClass }}">
                    @if($stampSrc !== '')
                    <img class="vsa-combo-stamp" src="{{ $stampSrc }}" alt="">
                    @endif
                    @if($signSrc !== '')
                    <img class="vsa-combo-sign" src="{{ $signSrc }}" alt="">
                    @endif
                </div>
                <div class="vsa-slot-line"></div>
                <div class="vsa-slot-meta">
                    @if(!empty($slot['display_name']))
                    <div class="{{ $nameClass }}">{{ $slot['display_name'] }}</div>
                    @endif
                    @if(!empty($slot['designation']))
                    <div class="{{ $desigClass }}">{{ $slot['designation'] }}</div>
                    @endif
                </div>
                <div class="{{ $labelClass }}">{{ $slot['role_label'] ?? 'Signature' }}</div>
            </div>
        </td>
        @endforeach
    </tr>
</table>
@endforeach
</div>
@endif
