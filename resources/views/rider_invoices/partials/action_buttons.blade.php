@php
$companySlug = request()->route('company_slug');
$isFullyPaid = (int) $riderInvoice->status === 1;
$invoiceIsPaid = method_exists($riderInvoice, 'isPaid')
    ? $riderInvoice->isPaid()
    : in_array((int) $riderInvoice->status, [1, 3], true);
$deleteUrl = route('riderInvoices.delete', array_filter([
    'company_slug' => $companySlug,
    'id' => $riderInvoice->id,
]));
$paymentUrl = route('payments.create', array_filter(['company_slug' => $companySlug]))
    . '?invoice_type=rider&invoice_id=' . $riderInvoice->id;
@endphp
<span class="invoice-pay-status {{ $invoiceIsPaid ? 'is-paid' : 'is-unpaid' }}">
    {{ $invoiceIsPaid ? 'Paid' : 'Unpaid' }}
</span>
@canany(['riders_invoice_edit', 'riders_invoices_edit'])
<a href="javascript:void(0);" class="action-btn show-modal" data-size="xl" data-title="Edit Rider Invoice" data-close-right-modal="1" data-action="{{ route('riderInvoices.edit', ['company_slug' => $companySlug, 'riderInvoice' => $riderInvoice->id]) }}">
    <i class="ti ti-edit"></i><span>Edit</span>
</a>
@endcanany
@can('email_create')
@if(Route::has('riderInvoices.sendEmail'))
<a href="javascript:void(0);" class="action-btn show-modal" data-size="md" data-title="Send Rider Invoice" data-action="{{ route('riderInvoices.sendEmail', ['company_slug' => $companySlug, 'id' => $riderInvoice->id]) }}">
    <i class="ti ti-mail"></i><span>Send Email</span>
</a>
@endif
@endcan
@if(Route::has('riderInvoices.download'))
<a href="{{ route('riderInvoices.download', ['company_slug' => $companySlug, 'id' => $riderInvoice->id]) }}" class="action-btn" target="_blank" rel="noopener">
    <i class="ti ti-download"></i><span>Download</span>
</a>
@endif
<button type="button" class="action-btn js-print-modal-content">
    <i class="ti ti-printer"></i><span>Print</span>
</button>
@if(! $isFullyPaid)
@canany(['riders_payments_create', 'riders_invoice_edit', 'riders_invoices_edit'])
<a href="javascript:void(0);" class="action-btn show-modal" data-size="xl" data-title="Record Rider Payment" data-close-right-modal="1" data-action="{{ $paymentUrl }}">
    <i class="ti ti-cash"></i><span>Payment</span>
</a>
@endcanany
@canany(['riders_invoices_edit', 'riders_invoice_edit'])
<a href="javascript:void(0);" class="action-btn show-modal" data-size="lg" data-title="Mark Settled (no payment)" data-action="{{ route('riderInvoices.markAsSettled', ['company_slug' => $companySlug, 'id' => $riderInvoice->id]) }}">
    <i class="ti ti-checks"></i><span>Mark Settled</span>
</a>
@endcanany
@endif
@can('riders_invoices_delete')
<a href="javascript:void(0);" class="action-btn" onclick="typeof confirmDelete === 'function' ? confirmDelete(@json($deleteUrl)) : alert('Delete is unavailable on this page.');">
    <i class="ti ti-trash"></i><span>Delete</span>
</a>
@endcan
