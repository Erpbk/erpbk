{{-- DomPDF: same shell as screen; brand resolved + isPdf flag drives compact spacing. --}}
@if(!empty($isPdf))
@php
    if (empty($brand) && empty($companyBrand) && class_exists(\App\Services\Agreements\AgreementPdfBranding::class)) {
        $brand = app(\App\Services\Agreements\AgreementPdfBranding::class)
            ->forCompany(\App\Support\CompanyContext::id());
    }
@endphp
{{-- Shell already included via tax_invoice_styles; re-include so PDF gets isPdf-tuned rules if styles ran without brand. --}}
@include('invoices.partials.tax_invoice_shell_styles')
@endif
