{{-- DomPDF: same shell as screen; brand resolved + isPdf flag drives compact spacing. --}}
@if(!empty($isPdf))
@php
    if (empty($brand) && empty($companyBrand) && class_exists(\App\Services\Agreements\AgreementPdfBranding::class)) {
        $brand = app(\App\Services\Agreements\AgreementPdfBranding::class)
            ->forCompany(\App\Support\CompanyContext::id());
    }
    $pdfBrand = $brand ?? ($companyBrand ?? []);
    $pdfSoft = $pdfBrand['primary_soft'] ?? ($pdfBrand['primary_light'] ?? '#eef4fc');
    $pdfLine = $pdfBrand['border_color'] ?? '#c5d8f0';
    $pdfBlue = $pdfBrand['primary_color'] ?? '#004aad';
@endphp
{{-- Shell already included via tax_invoice_styles; re-include so PDF gets isPdf-tuned rules if styles ran without brand. --}}
@include('invoices.partials.tax_invoice_shell_styles')
{{-- DomPDF does not support CSS variables / calc() / flex; force column layout with table-cell --}}
<style>
    .invoice-box .parties {
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        margin: 0 0 10px 0 !important;
    }

    .invoice-box .parties > .party,
    .invoice-box .parties > .party.alt {
        display: table-cell !important;
        width: 50% !important;
        background-color: {{ $pdfSoft }} !important;
        border: 1px solid {{ $pdfLine }} !important;
        vertical-align: top !important;
    }

    .invoice-box .parties > .party:first-child {
        border-right: 10px solid #ffffff !important;
    }

    .invoice-box .inv-totals-area {
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }

    .invoice-box .inv-totals-notes,
    .invoice-box .inv-totals {
        display: table-cell !important;
        vertical-align: top !important;
    }

    .invoice-box .inv-totals-notes {
        width: 55% !important;
        padding-right: 12px !important;
    }

    .invoice-box .inv-totals {
        width: 45% !important;
    }

    .invoice-box table.items-table th,
    .invoice-box .secondary-header,
    .invoice-box .primary-header,
    .invoice-box .accent-total,
    .invoice-box .light-header,
    .invoice-box .success-highlight,
    .invoice-box .amount-highlight,
    .invoice-box.invoice-layout-modern table.items-table th,
    .invoice-box.invoice-layout-modern .secondary-header,
    .invoice-box.invoice-layout-modern .primary-header,
    .invoice-box.invoice-layout-modern .accent-total,
    .invoice-box.invoice-layout-modern .light-header {
        background-color: {{ $pdfSoft }} !important;
        background: {{ $pdfSoft }} !important;
        color: #0f172a !important;
    }

    .invoice-box .inv-company {
        color: {{ $pdfBlue }} !important;
    }

    .invoice-box .inv-note-box {
        background-color: {{ $pdfSoft }} !important;
        text-align: left !important;
        height: 100% !important;
    }

    .invoice-box .foot {
        display: none !important;
    }
</style>
@endif
