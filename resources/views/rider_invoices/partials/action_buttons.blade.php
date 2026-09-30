@php
$companySlug = request()->route('company_slug');
$statusInt = (int) ($riderInvoice->status ?? 0);
$isFullyPaid = $statusInt === 1;
$isPartial = $statusInt === 3 || (! $isFullyPaid && (float) ($riderInvoice->paid_amount ?? 0) > 0);
@endphp
@include('invoices.partials.action_toolbar', [
    'isPaid' => $isFullyPaid,
    'isPartial' => $isPartial && ! $isFullyPaid,
    'editUrl' => route('riderInvoices.edit', ['company_slug' => $companySlug, 'riderInvoice' => $riderInvoice->id]),
    'editTitle' => 'Edit Rider Invoice',
    'editCan' => ['riders_invoice_edit', 'riders_invoices_edit'],
    'emailUrl' => Route::has('riderInvoices.sendEmail')
        ? route('riderInvoices.sendEmail', ['company_slug' => $companySlug, 'id' => $riderInvoice->id])
        : null,
    'emailTitle' => 'Send Rider Invoice',
    'downloadUrl' => Route::has('riderInvoices.download')
        ? route('riderInvoices.download', ['company_slug' => $companySlug, 'id' => $riderInvoice->id])
        : null,
    'showPayment' => ! $isFullyPaid,
    'paymentUrl' => route('payments.create', array_filter(['company_slug' => $companySlug]))
        . '?invoice_type=rider&invoice_id=' . $riderInvoice->id,
    'paymentTitle' => 'Record Rider Payment',
    'paymentCan' => ['riders_payments_create', 'riders_invoice_edit', 'riders_invoices_edit'],
    'markSettledUrl' => route('riderInvoices.markAsSettled', ['company_slug' => $companySlug, 'id' => $riderInvoice->id]),
    'markSettledCan' => ['riders_invoices_edit', 'riders_invoice_edit'],
    'deleteUrl' => route('riderInvoices.delete', array_filter([
        'company_slug' => $companySlug,
        'id' => $riderInvoice->id,
    ])),
    'deleteCan' => 'riders_invoices_delete',
])
