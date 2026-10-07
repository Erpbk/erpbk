{{--
  Expected: $settings (array), $invoiceTitle, $invoiceNumber, $invoiceDateLabel, $billingLabel (optional)
--}}
@php
    $invoiceDateLabel = $invoiceDateLabel ?? '';
    $billingLabel = $billingLabel ?? '';
    $logoSrc = null;
    if (!empty($isPdf) && !empty($brand['logo_src'])) {
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

@if(!empty($isPdf))
<table class="pdf-hdr" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; margin:0 0 12px 0; border-bottom:2px solid {{ $brand['primary_color'] ?? '#004aad' }};">
    <tr>
        <td width="22%" valign="middle" style="width:22%; vertical-align:middle; padding:0 8px 10px 0;">
            @if(!empty($logoSrc))
                <img src="{{ $logoSrc }}" alt="Logo" style="max-width:110px; max-height:56px; display:block;">
            @else
                <div style="border:1px dashed {{ $brand['border_color'] ?? '#c5d8f0' }}; background:{{ $brand['primary_soft'] ?? '#eef4fc' }}; color:{{ $brand['primary_color'] ?? '#004aad' }}; font-size:9px; font-weight:700; text-align:center; padding:14px 6px;">LOGO</div>
            @endif
        </td>
        <td width="45%" valign="middle" align="center" style="width:45%; vertical-align:middle; text-align:center; padding:0 8px 10px;">
            <div style="font-size:14px; font-weight:700; color:#0f172a; margin:0 0 4px;">{{ ucwords($settings['company_name'] ?? '') }}</div>
            <div style="font-size:9px; color:#64748b; line-height:1.45;">
                @if(!empty($settings['vat_number']))
                    TRN: {{ $settings['vat_number'] }}<br>
                @endif
                @if(!empty($settings['company_phone']) || !empty($settings['company_email']))
                    @if(!empty($settings['company_phone'])) Tel: {{ $settings['company_phone'] }} @endif
                    @if(!empty($settings['company_email'])) {{ $settings['company_email'] }} @endif
                    <br>
                @endif
                @if(!empty($settings['company_address']))
                    {{ ucwords($settings['company_address']) }}
                @endif
            </div>
        </td>
        <td width="33%" valign="top" align="right" style="width:33%; vertical-align:top; text-align:right; padding:0 0 10px 8px;">
            <div style="display:inline-block; background:{{ $brand['primary_color'] ?? '#004aad' }}; color:#fff; font-size:10px; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; padding:6px 12px; margin:0 0 8px;">{{ $invoiceTitle }}</div>
            <table cellpadding="0" cellspacing="0" align="right" style="margin-left:auto; border-collapse:collapse; font-size:10px;">
                <tr>
                    <td style="color:#64748b; padding:2px 10px 2px 0; text-align:left;">Invoice No</td>
                    <td style="color:#0f172a; font-weight:700; padding:2px 0; text-align:right;">{{ $invoiceNumber }}</td>
                </tr>
                @if($invoiceDateLabel !== '')
                <tr>
                    <td style="color:#64748b; padding:2px 10px 2px 0; text-align:left;">Date</td>
                    <td style="color:#0f172a; font-weight:700; padding:2px 0; text-align:right;">{{ $invoiceDateLabel }}</td>
                </tr>
                @endif
                @if($billingLabel !== '')
                <tr>
                    <td style="color:#64748b; padding:2px 10px 2px 0; text-align:left;">Billing</td>
                    <td style="color:#0f172a; font-weight:700; padding:2px 0; text-align:right;">{{ $billingLabel }}</td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
@else
<header class="hdr">
    <div class="brand">
        <div class="brand-logo {{ empty($logoSrc) ? 'placeholder' : '' }}">
            @if(!empty($logoSrc))
            <img src="{{ $logoSrc }}" alt="{{ $settings['company_name'] ?? 'Logo' }}">
            @else
            Logo
            @endif
        </div>
        <div class="brand-text">
            <h1>{{ ucwords($settings['company_name'] ?? '') }}</h1>
            <p class="meta">
                @if(!empty($settings['vat_number']))
                TRN: {{ $settings['vat_number'] }}
                @endif
                @if(!empty($settings['company_phone']) && !empty($settings['vat_number']))
                &nbsp;·&nbsp;
                @endif
                <br>
                @if(!empty($settings['company_phone']))
                Tel: {{ $settings['company_phone'] }}
                @endif
                @if(!empty($settings['company_email']))
                {{ $settings['company_email'] }}
                @endif
                <br>
                @if(!empty($settings['company_address']))
                {{ ucwords($settings['company_address']) }}
                @endif
            </p>
        </div>
    </div>
    <div class="doc-stamp">
        <div class="label">{{ $invoiceTitle }}</div>
        <div class="kv">
            <span>Invoice No</span><span>{{ $invoiceNumber }}</span>
            @if($invoiceDateLabel !== '')
            <span>Date</span><span>{{ $invoiceDateLabel }}</span>
            @endif
            @if($billingLabel !== '')
            <span>Billing</span><span>{{ $billingLabel }}</span>
            @endif
        </div>
    </div>
</header>
@endif
