{{--
  Expected: $settings (array), $invoiceTitle, $invoiceNumber, $invoiceDateLabel, $billingLabel (optional)
--}}
@php
    $invoiceDateLabel = $invoiceDateLabel ?? '';
    $billingLabel = $billingLabel ?? '';
@endphp
<header class="hdr">
    <div class="brand">
        <div class="brand-logo {{ empty($settings['company_logo']) ? 'placeholder' : '' }}">
            @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
            <img src="{{ storage_url($settings['company_logo']) }}" alt="{{ $settings['company_name'] ?? 'Logo' }}">
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
