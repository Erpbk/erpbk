{{--
  Shared header for screen + PDF (same markup).
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
@endphp

<table class="inv-hdr" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td class="inv-hdr-logo" width="22%" valign="middle">
            @if(!empty($logoSrc))
                <img src="{{ $logoSrc }}" alt="{{ $settings['company_name'] ?? 'Logo' }}">
            @else
                <div class="inv-logo-ph">Logo</div>
            @endif
        </td>
        <td class="inv-hdr-brand" width="46%" valign="middle" align="center">
            <div class="inv-company">{{ ucwords($settings['company_name'] ?? '') }}</div>
            <div class="inv-meta">
                @if(!empty($settings['vat_number']))
                    TRN: {{ $settings['vat_number'] }}
                @endif
                @if(!empty($settings['company_phone']))
                    @if(!empty($settings['vat_number'])) · @endif
                    Tel: {{ $settings['company_phone'] }}
                @endif
                @if(!empty($settings['company_email']))
                    {{ $settings['company_email'] }}
                @endif
                @if(!empty($settings['company_address']))
                    <br>{{ ucwords($settings['company_address']) }}
                @endif
            </div>
        </td>
        <td class="inv-hdr-stamp" width="32%" valign="top" align="right">
            <div class="inv-badge">{{ $invoiceTitle }}</div>
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
        </td>
    </tr>
</table>
