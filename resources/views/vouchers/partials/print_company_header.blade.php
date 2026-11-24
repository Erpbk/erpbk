{{--
  Company header matching Voucher Signature Assignment preview.
  Expects branding vars + optional $plHeader from layoutPartsFor.
  When $plHeader is null, uses defaultHeaderSettings() so live docs match the Set up preview.
--}}
@php
    $hdr = is_array($plHeader ?? null) && ($plHeader ?? []) !== []
        ? \App\Models\VoucherSignatureConfig::normalizeHeaderSettings($plHeader)
        : \App\Models\VoucherSignatureConfig::defaultHeaderSettings();

    $align = in_array(($hdr['align'] ?? ''), ['left', 'center', 'right'], true)
        ? $hdr['align']
        : 'left';
    $on = $hdr['on'] ?? [];

    $companyName = trim((string) ($companyName ?? ''));
    $logoSrc = $logoSrc ?? null;
    $phone = trim((string) ($companyPhone ?? ''));
    $email = trim((string) ($companyEmail ?? ''));
    $addr = trim((string) ($companyAddress ?? ''));
    $trn = trim((string) ($companyTrn ?? ''));
    $web = trim((string) ($companyWebsite ?? (\App\Helpers\Common::getSetting('company_website') ?? '')));
    $po = trim((string) ($companyPoBox ?? (\App\Helpers\Common::getSetting('po_box') ?? '')));
    $vatPct = trim((string) ($companyVatPercent ?? (\App\Helpers\Common::getSetting('vat_percent') ?? \App\Helpers\Common::getSetting('vat') ?? '')));

    $detailMap = [
        'tel' => $phone !== '' ? ('Tel: ' . $phone) : '',
        'email' => $email,
        'web' => $web,
        'addr' => $addr,
        'trn' => $trn !== '' ? ('TRN: ' . $trn) : '',
        'vat' => $vatPct !== '' ? ('VAT: ' . $vatPct) : '',
        'po' => $po !== '' ? ('PO Box: ' . $po) : '',
    ];

    $line1Keys = ['tel', 'email', 'web'];
    $line2Keys = ['addr', 'trn', 'vat', 'po'];
    $line1 = collect($line1Keys)
        ->filter(fn ($k) => !empty($on[$k]) && ($detailMap[$k] ?? '') !== '')
        ->map(fn ($k) => $detailMap[$k])
        ->implode(' · ');
    $line2 = collect($line2Keys)
        ->filter(fn ($k) => !empty($on[$k]) && ($detailMap[$k] ?? '') !== '')
        ->map(fn ($k) => $detailMap[$k])
        ->implode(' · ');

    $logoMaxH = max(40, (int) round(((float) $hdr['logo']) * 0.38));
    $logoMaxW = max(80, (int) round(((float) $hdr['logo']) * 1.05));
    $nameSize = max(12, min(28, (float) $hdr['name'] * 0.72));
    $detSize = max(9, min(14, (float) $hdr['det'] * 0.9));
    $minH = max(48, (int) round(((float) $hdr['height']) * 0.45));
    $showLine = !empty($hdr['line']);
    $withBorder = !array_key_exists('headerWithBorder', get_defined_vars())
        || $headerWithBorder;
@endphp
<style>
.rpv-print-hdr {
    display: flex;
    align-items: center;
    gap: 18px;
    width: 100%;
    min-height: {{ $minH }}px;
    margin: 0 0 {{ $withBorder ? '14px' : '0' }};
    padding: 0 0 {{ $withBorder && $showLine ? '14px' : ($withBorder ? '8px' : '0') }};
    border-bottom: {{ $withBorder && $showLine ? '2px solid var(--rpv-blue)' : '0' }};
    box-sizing: border-box;
}
.rpv-doc--pdf .rpv-print-hdr {
    margin-bottom: {{ $withBorder ? '10px' : '0' }};
    padding-bottom: {{ $withBorder && $showLine ? '10px' : ($withBorder ? '6px' : '0') }};
    gap: 14px;
}
.rpv-print-hdr--left { flex-direction: row; }
.rpv-print-hdr--right { flex-direction: row-reverse; }
.rpv-print-hdr--center { flex-direction: column; text-align: center; }
.rpv-print-hdr-logo {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
}
.rpv-print-hdr--left .rpv-print-hdr-logo { justify-content: flex-start; }
.rpv-print-hdr--right .rpv-print-hdr-logo { justify-content: flex-end; }
.rpv-print-hdr-logo img {
    display: block;
    max-height: {{ $logoMaxH }}px;
    max-width: {{ $logoMaxW }}px;
    width: auto;
    height: auto;
    object-fit: contain;
}
.rpv-print-hdr-text {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
    /* Match assignment preview: brand text stays centered in the remaining space */
    text-align: center;
}
.rpv-print-hdr--center .rpv-print-hdr-text {
    width: 100%;
    align-items: center;
}
.rpv-print-hdr .rpv-company {
    font-size: {{ $nameSize }}px;
    font-weight: 800;
    color: var(--rpv-blue);
    line-height: 1.15;
    margin: 0;
    text-align: inherit;
}
.rpv-print-hdr .rpv-meta {
    display: block;
    margin: 0;
    font-size: {{ $detSize }}px;
    font-weight: 400;
    color: #666;
    line-height: 1.5;
    text-align: inherit;
}
</style>
<div class="rpv-print-hdr rpv-print-hdr--{{ $align }}">
    <div class="rpv-print-hdr-logo">
        @if(!empty($logoSrc))
        <img src="{{ $logoSrc }}" alt="{{ $companyName }}">
        @endif
    </div>
    <div class="rpv-print-hdr-text">
        @if($companyName !== '')
        <div class="rpv-company">{{ $companyName }}</div>
        @endif
        @if($line1 !== '')
        <div class="rpv-meta">{{ $line1 }}</div>
        @endif
        @if($line2 !== '')
        <div class="rpv-meta">{{ $line2 }}</div>
        @endif
    </div>
</div>
