{{--
  Optional per-voucher-type print header + signature position overrides.
  Expects $printLayout already resolved, or $printLayoutCode / $voucherTypeCode.
  Also sets $plHeader / $plPos for use in this same include only; parents should
  resolve via vouchers.partials.print_layout_boot first.
--}}
@php
    if (!isset($printLayout)) {
        $layoutCode = $printLayoutCode
            ?? $voucherTypeCode
            ?? ($voucherType ?? null)
            ?? ($payment->voucher->voucher_type ?? null)
            ?? ($voucher->voucher_type ?? null)
            ?? null;
        if (is_object($layoutCode) && isset($layoutCode->code)) {
            $layoutCode = $layoutCode->code;
        }
        $printLayout = $layoutCode
            ? app(\App\Services\Voucher\VoucherSignatureResolver::class)->layoutFor((string) $layoutCode)
            : null;
    }
    $plHeader = is_array($printLayout['header'] ?? null)
        ? \App\Models\VoucherSignatureConfig::normalizeHeaderSettings($printLayout['header'])
        : null;
    $plPos = ($printLayout['signature_position'] ?? 'after') === 'bottom' ? 'bottom' : 'after';
@endphp
@if($plHeader)
@php
    $plAlign = $plHeader['align'];
    $plLogoMaxH = max(40, (int) round($plHeader['logo'] * 0.38));
    $plLogoMaxW = max(80, (int) round($plHeader['logo'] * 1.05));
    $plNameSize = max(12, min(28, (float) $plHeader['name'] * 0.72));
    $plDetSize = max(9, min(14, (float) $plHeader['det'] * 0.9));
    $plMinH = max(48, (int) round($plHeader['height'] * 0.45));
    $plLine = !empty($plHeader['line']);
@endphp
<style>
.rpv-doc .rpv-hdr td {
    @if($plAlign === 'center')
    text-align: center;
    @elseif($plAlign === 'right')
    text-align: right;
    @else
    text-align: left;
    @endif
    min-height: {{ $plMinH }}px;
    border-bottom: {{ $plLine ? '2px solid var(--rpv-blue)' : '0' }};
    padding-bottom: {{ $plLine ? '16px' : '8px' }};
}
.rpv-doc .rpv-hdr-logo { text-align: {{ $plAlign === 'right' ? 'right' : ($plAlign === 'center' ? 'center' : 'left') }}; }
.rpv-doc .rpv-logo {
    max-height: {{ $plLogoMaxH }}px;
    max-width: {{ $plLogoMaxW }}px;
    @if($plAlign === 'center')
    margin-left: auto; margin-right: auto;
    @elseif($plAlign === 'right')
    margin-left: auto; margin-right: 0;
    @endif
}
.rpv-doc .rpv-company { font-size: {{ $plNameSize }}px; text-align: {{ $plAlign }}; }
.rpv-doc .rpv-meta { font-size: {{ $plDetSize }}px; text-align: {{ $plAlign }}; }
.rpv-doc .rpv-sheet {
    display: flex;
    flex-direction: column;
    min-height: {{ !empty($isPdf) ? '0' : '720px' }};
}
.rpv-doc .rpv-sigs,
.rpv-doc .vsa-sig-wrap {
    @if($plPos === 'bottom')
    margin-top: auto !important;
    @else
    margin-top: 28px !important;
    @endif
}
</style>
@elseif(!empty($printLayout))
<style>
.rpv-doc .rpv-sheet {
    display: flex;
    flex-direction: column;
    min-height: {{ !empty($isPdf) ? '0' : '720px' }};
}
.rpv-doc .rpv-sigs,
.rpv-doc .vsa-sig-wrap {
    @if($plPos === 'bottom')
    margin-top: auto !important;
    @else
    margin-top: 28px !important;
    @endif
}
</style>
@endif
