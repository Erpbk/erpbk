{{--
  Shared header for screen + PDF (same markup).
  Uses Voucher Signature Assignment company header; keeps invoice stamp on the right.
  Expected: $settings, $invoiceTitle, $invoiceNumber, $invoiceDateLabel, $billingLabel
--}}
@php
$invoiceDateLabel = $invoiceDateLabel ?? '';
$billingLabel = $billingLabel ?? '';
$accent = ($brand['primary_color'] ?? null) ?: '#004aad';
$accentSoft = ($brand['primary_soft'] ?? null) ?: '#eef4fc';
$accentLine = ($brand['border_color'] ?? null) ?: '#c5d8f0';

$logoSrc = null;
if (!empty($brand['logo_src']) && !empty($isPdf)) {
    $logoSrc = $brand['logo_src'];
} elseif (!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo'])) {
    if (!empty($isPdf)) {
        $absolute = Storage::disk('public')->path($settings['company_logo']);
        if (is_string($absolute) && is_file($absolute)) {
            $mime = @mime_content_type($absolute) ?: 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($absolute));
        }
    } else {
        $logoSrc = storage_url($settings['company_logo']);
    }
}

[, $plHeader] = app(\App\Services\Voucher\VoucherSignatureResolver::class)
    ->layoutPartsForCompany(\App\Support\CompanyContext::id());

$invPdf = $invPdf ?? [];
@endphp

<style>
.inv-hdr-vsa {
    display: flex;
    align-items: flex-end;
    gap: 16px;
    width: 100%;
    margin: 0 0 14px;
    padding: 0 0 12px;
    border-bottom: 2px solid {{ $accent }};
    box-sizing: border-box;
}
.inv-hdr-vsa-main { flex: 1 1 auto; min-width: 0; }
.inv-hdr-vsa-main .rpv-print-hdr {
    margin: 0 !important;
    padding: 0 !important;
    border-bottom: 0 !important;
}
.inv-hdr-vsa-stamp { flex: 0 0 auto; text-align: right; max-width: 42%; }
.inv-hdr-vsa .inv-badge {
    display: inline-block;
    background: {{ $accentSoft }};
    color: {{ $accent }};
    border: 1px solid {{ $accentLine }};
    border-radius: 6px;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: .04em;
    text-transform: uppercase;
    padding: 5px 10px;
    margin-bottom: 8px;
}
.inv-hdr-vsa .inv-kv { border-collapse: collapse; margin-left: auto; font-size: 9.5px; }
.inv-hdr-vsa .inv-kv .k { color: #64748b; font-weight: 500; text-align: left; padding: 2px 0 2px 12px; white-space: nowrap; }
.inv-hdr-vsa .inv-kv .v { color: #0f172a; font-weight: 700; text-align: right; padding: 2px 0 2px 12px; white-space: nowrap; }
</style>

<div class="inv-hdr-vsa"@if(!empty($invPdf['hdr'])) style="{{ $invPdf['hdr'] }}"@endif>
    <div class="inv-hdr-vsa-main">
        @include('vouchers.partials.print_company_header', [
            'plHeader' => $plHeader,
            'companyName' => ucwords((string) ($settings['company_name'] ?? '')),
            'logoSrc' => $logoSrc,
            'companyPhone' => $settings['company_phone'] ?? '',
            'companyEmail' => $settings['company_email'] ?? '',
            'companyAddress' => isset($settings['company_address']) ? ucwords((string) $settings['company_address']) : '',
            'companyTrn' => $settings['vat_number'] ?? '',
            'headerWithBorder' => false,
        ])
    </div>
    <div class="inv-hdr-vsa-stamp">
        <div class="inv-badge"@if(!empty($invPdf['badge'])) style="{{ $invPdf['badge'] }}"@endif>{{ $invoiceTitle }}</div>
        <table class="inv-kv" cellpadding="0" cellspacing="0" align="right">
            <tr>
                <td class="k">Invoice No</td>
                <td class="v">{{ $invoiceNumber }}</td>
            </tr>
            @if($invoiceDateLabel !== '')
            <tr>
                <td class="k">Date</td>
                <td class="v">{{ $invoiceDateLabel }}</td>
            </tr>
            @endif
            @if($billingLabel !== '')
            <tr>
                <td class="k">Billing</td>
                <td class="v">{{ $billingLabel }}</td>
            </tr>
            @endif
        </table>
    </div>
</div>
