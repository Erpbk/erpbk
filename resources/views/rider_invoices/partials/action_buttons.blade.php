@php
$companySlug = request()->route('company_slug');
$isPaid = (int) $riderInvoice->status === 1;
@endphp
<div class="invoice-toolbar no-print">
    <div class="invoice-toolbar-inner">
        @can('riders_invoice_edit')
        <a href="javascript:void(0);" class="toolbar-btn show-modal" data-size="xl" data-title="Edit Rider Invoice" data-close-right-modal="1" data-action="{{ route('riderInvoices.edit', ['company_slug' => $companySlug, 'riderInvoice' => $riderInvoice->id]) }}">
            <i class="ti ti-edit"></i><span>Edit</span>
        </a>
        @endcan
        @can('email_create')
        @if(Route::has('riderInvoices.sendEmail'))
        <a href="javascript:void(0);" class="toolbar-btn show-modal" data-size="md" data-title="Send Rider Invoice" data-action="{{ route('riderInvoices.sendEmail', ['company_slug' => $companySlug, 'id' => $riderInvoice->id]) }}">
            <i class="ti ti-mail"></i><span>Send Email</span>
        </a>
        @endif
        @endcan
        @if(Route::has('riderInvoices.download'))
        <a href="{{ route('riderInvoices.download', ['company_slug' => $companySlug, 'id' => $riderInvoice->id]) }}" class="toolbar-btn" target="_blank" rel="noopener">
            <i class="ti ti-download"></i><span>Download</span>
        </a>
        @endif
        <button type="button" class="toolbar-btn" onclick="printModalContent()">
            <i class="ti ti-printer"></i><span>Print</span>
        </button>
        @if(! $isPaid)
        @can('riders_invoice_edit')
        <a href="javascript:void(0);" class="toolbar-btn show-modal" data-size="xl" data-title="Record Rider Payment" data-action="{{ route('payments.create', ['company_slug' => $companySlug]) }}?invoice_type=rider&invoice_id={{ $riderInvoice->id }}">
            <i class="ti ti-currency-dollar"></i><span>Make Payment</span>
        </a>
        @endcan
        @endif
    </div>
</div>

@if(isset($templates) && $templates->count() > 0 && Route::has('riderInvoices.updateTemplate'))
<div class="template-selector no-print">
    <form method="POST" action="{{ route('riderInvoices.updateTemplate', ['company_slug' => $companySlug, 'id' => $riderInvoice->id]) }}" id="riderInvoiceTemplateForm" class="d-flex align-items-center gap-2 flex-wrap">
        @csrf
        <label for="template_id" class="mb-0 text-muted small fw-semibold">Invoice Template:</label>
        <select name="template_id" id="template_id" class="form-select form-select-sm" style="width: auto; min-width: 200px;">
            @foreach($templates as $tpl)
            <option value="{{ $tpl->id }}" @selected((int) ($riderInvoice->template_id ?? ($activeTemplate->id ?? 0)) === (int) $tpl->id)>
                {{ $tpl->template_name }}@if($tpl->is_default) (Default)@endif
            </option>
            @endforeach
        </select>
    </form>
</div>
@endif
