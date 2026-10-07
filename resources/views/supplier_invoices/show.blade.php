<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Supplier Invoice #{{ $supplierInvoice->inv_id }}</title>
    @include('invoices.partials.tax_invoice_styles')
</head>

<body>
    @php
        $settings = company_table('settings')->pluck('value', 'name')->toArray();
        $currency = \App\Helpers\Currency::code();
        $defaults = \App\Support\InvoiceModuleDefaults::all('supplier_invoices');
        $party = $supplierInvoice->supplier;
        $invoiceTitle = $defaults['title'] ?: 'SUPPLIER INVOICE';
        $partyNote = $supplierInvoice->customer_note
            ?: ($party->invoice_note ?? null)
            ?: ($defaults['notes'] ?: null);
        $termsAndConditions = $supplierInvoice->terms_and_conditions
            ?: ($party->terms_and_conditions ?? null)
            ?: ($defaults['terms_and_conditions'] ?: null);
        $partyNoteLabel = $party
            ? $party->resolvedInvoiceNoteLabel()
            : 'Invoice Note';
        $termsAndConditionsLabel = $party
            ? $party->resolvedTermsAndConditionsLabel()
            : 'Terms & Conditions';
        $invoiceNumber = $supplierInvoice->inv_id ?? ('SUP-' . str_pad($supplierInvoice->id, 6, '0', STR_PAD_LEFT));
        $itemsTotal = $supplierInvoice->items ? $supplierInvoice->items->sum('total_amount') : 0;
        $itemsVat = $supplierInvoice->items ? $supplierInvoice->items->sum('tax_amount') : 0;
        $subtotalAmount = $supplierInvoice->subtotal ?? max(0, $itemsTotal - $itemsVat);
        $vatAmt = $supplierInvoice->vat ?? $itemsVat;
        $totalAmt = $supplierInvoice->total_amount ?? $itemsTotal;
        $noteCards = collect([
            $termsAndConditions ? ['title' => $termsAndConditionsLabel, 'body' => $termsAndConditions] : null,
            $supplierInvoice->notes ? ['title' => 'Internal Notes', 'body' => $supplierInvoice->notes] : null,
        ])->filter()->values();
        $noteGridClass = match ($noteCards->count()) {
            1 => 'one',
            3 => 'three',
            default => '',
        };
        $invoiceDateLabel = $supplierInvoice->inv_date
            ? $supplierInvoice->inv_date->format('d M Y')
            : '';
        $billingLabel = $supplierInvoice->billing_month
            ? date('M Y', strtotime($supplierInvoice->billing_month))
            : '';
    @endphp

    @if(empty($isPdf))
    <div class="controls no-print">
        @php
            $siStatus = $supplierInvoice->status ?? null;
            $siIsPaid = $siStatus === 'paid' || (int) $siStatus === 1;
            $siIsPartial = $siStatus === 'partially_paid' || (int) $siStatus === 3
                || (! $siIsPaid && (float) ($supplierInvoice->paid_amount ?? 0) > 0);
            $siPaymentId = \App\Support\InvoicePaymentLink::latestId(
                $supplierInvoice,
                $supplierInvoice->supplier?->account_id ? (int) $supplierInvoice->supplier->account_id : null
            );
        @endphp
        @include('invoices.partials.action_toolbar', [
            'isPaid' => $siIsPaid,
            'isPartial' => $siIsPartial && ! $siIsPaid,
            'editUrl' => route('supplierInvoices.edit', $supplierInvoice->id),
            'editTitle' => 'Edit Supplier Invoice',
            'editCan' => ['suppliers_invoices_edit', 'suppliers_purchase_order_edit'],
            'editPaymentUrl' => $siPaymentId ? route('payments.edit', $siPaymentId) : null,
            'editPaymentTitle' => 'Edit Payment',
            'editPaymentCan' => ['suppliers_payments_edit', 'cash_&_banks_payments_edit'],
            'emailUrl' => Route::has('supplier_invoices.send_email')
                ? route('supplier_invoices.send_email', $supplierInvoice->id)
                : null,
            'emailTitle' => 'Send Email',
            'downloadUrl' => route('supplierInvoices.show', $supplierInvoice->id),
            'showPayment' => ! $siIsPaid,
            'paymentUrl' => route('payments.create')
                . '?supplier_payment=1&supplier_id=' . ($supplierInvoice->supplier_id ?? '')
                . '&invoice_id=' . $supplierInvoice->id,
            'paymentTitle' => 'Record Payment',
            'paymentCan' => ['suppliers_payments_create', 'suppliers_invoices_edit'],
            'deleteUrl' => route('supplierInvoices.delete', $supplierInvoice->id),
            'deleteCan' => ['suppliers_invoices_delete', 'suppliers_purchase_order_delete'],
        ])
    </div>
    @include('delete_requests._confirm_delete_script', [
        'entityName' => 'Supplier Invoice',
        'confirmText' => 'This will submit a delete request or move the invoice to the Recycle Bin.',
        'method' => 'GET',
    ])
    @endif

    <div class="invoice-box">
        <div class="band"></div>
        <div class="sheet">
            @include('invoices.partials.tax_invoice_header', [
                'settings' => $settings,
                'invoiceTitle' => $invoiceTitle,
                'invoiceNumber' => $invoiceNumber,
                'invoiceDateLabel' => $invoiceDateLabel,
                'billingLabel' => $billingLabel,
            ])

            <div class="parties">
                <div class="party">
                    <h3 class="party-title">Bill From</h3>
                    <p class="party-name">{{ $party->name ?? 'N/A' }}</p>
                    <div class="party-grid">
                        <div class="party-line">
                            <span class="k">Company</span>
                            <span class="v">{{ $party->company_name ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Contact</span>
                            <span class="v">{{ $party->phone ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Email</span>
                            <span class="v">{{ $party->email ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Garage</span>
                            <span class="v">{{ $supplierInvoice->garage?->name ?? '—' }}</span>
                        </div>
                    </div>
                </div>
                <div class="party alt">
                    <h3 class="party-title">Invoice Info</h3>
                    <div class="party-grid" style="margin-top: 4px;">
                        <div class="party-line">
                            <span class="k">Created By</span>
                            <span class="v">{{ $supplierInvoice->updatedBy?->name ?? $supplierInvoice->createdBy?->name ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Month</span>
                            <span class="v">{{ $billingLabel !== '' ? $billingLabel : '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Currency</span>
                            <span class="v">{{ $currency }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($supplierInvoice->descriptions)
            <div class="desc">
                <span class="t">Description</span>
                <p>{{ $supplierInvoice->descriptions }}</p>
            </div>
            @endif

            @if($supplierInvoice->items && $supplierInvoice->items->count() > 0)
            <div class="tbl-wrap">
                <table class="items">
                    <thead>
                        <tr>
                            <th class="col-sr">#</th>
                            <th class="col-desc">Description</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>VAT</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($supplierInvoice->items as $key => $item)
                        <tr>
                            <td class="col-sr">{{ $key + 1 }}</td>
                            <td class="col-desc">{{ $item->item_des ?? '—' }}</td>
                            <td>{{ number_format($item->qty ?? 0, 2) }}</td>
                            <td>{{ number_format($item->rate ?? 0, 2) }}</td>
                            <td>{{ number_format($item->tax_amount ?? 0, 2) }}</td>
                            <td class="total-cell">{{ number_format($item->total_amount ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('invoices.partials.tax_invoice_totals_notes', [
                'partyNote' => $partyNote,
                'partyNoteLabel' => $partyNoteLabel,
                'subtotalAmount' => $subtotalAmount,
                'vatAmount' => $vatAmt,
                'totalAmount' => $totalAmt,
                'currency' => $currency,
                'paidAmount' => $supplierInvoice->paid_amount ?? 0,
                'balanceAmount' => $supplierInvoice->balance ?? (($totalAmt ?? 0) - ($supplierInvoice->paid_amount ?? 0)),
            ])
            @else
            <div class="empty">No line items on this invoice.</div>
            @endif

            @include('invoices.partials.tax_invoice_footnotes', [
                'noteCards' => $noteCards,
                'noteGridClass' => $noteGridClass,
            ])

            @include('invoices.partials.tax_invoice_footer', ['settings' => $settings])
        </div>
    </div>

    @include('invoices.partials.tax_invoice_print_script', ['isPdf' => $isPdf ?? null])
</body>

</html>
