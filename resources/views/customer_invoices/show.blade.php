<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Customer Invoice #{{ $invoice->invoice_number ?? $invoice->id }} Month: {{ date('M-Y', strtotime($invoice->billing_month)) }}</title>
    @include('invoices.partials.tax_invoice_styles')
    @include('invoices.partials.tax_invoice_pdf_styles')
</head>

<body>
    @php
    $settings = company_table('settings')->pluck('value', 'name')->toArray();
    $running_total = 0;
    $subtotal_from_items = 0;
    $currency = \App\Helpers\Currency::code();
    $defaults = \App\Support\CustomerInvoiceDefaults::all();
    $invoiceTitle = $defaults['title'] ?: 'CUSTOMER INVOICE';
    $customerNote = $invoice->customer_note
    ?: ($invoice->customer->customer_note ?? null)
    ?: ($defaults['customer_notes'] ?: null);
    $termsAndConditions = $invoice->terms_and_conditions
    ?: ($invoice->customer->terms_and_conditions ?? null)
    ?: ($defaults['terms_and_conditions'] ?: null);
    $customerNoteLabel = $invoice->customer
    ? $invoice->customer->resolvedCustomerNoteLabel()
    : \App\Models\Customers::DEFAULT_CUSTOMER_NOTE_LABEL;
    $termsAndConditionsLabel = $invoice->customer
    ? $invoice->customer->resolvedTermsAndConditionsLabel()
    : \App\Models\Customers::DEFAULT_TERMS_AND_CONDITIONS_LABEL;
    $invoiceNumber = $invoice->invoice_number ?? ('CI-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT));
    $customerDisplay = $invoice->customer->company_name
    ?: ($invoice->customer->name ?? 'N/A');
    $projectName = $invoice->customer->name ?? 'N/A';
    $noteCards = collect([
    $termsAndConditions ? ['title' => $termsAndConditionsLabel, 'body' => $termsAndConditions] : null,
    $invoice->notes ? ['title' => 'Internal Notes', 'body' => $invoice->notes] : null,
    ])->filter()->values();
    $noteGridClass = match ($noteCards->count()) {
    1 => 'one',
    3 => 'three',
    default => '',
    };
    @endphp

    @if(empty($isPdf))
    <div class="controls no-print">
        @php
            $ciStatus = $invoice->status ?? null;
            $ciIsPaid = $ciStatus === 'paid' || (int) $ciStatus === 1;
            $ciIsPartial = $ciStatus === 'partially_paid' || (int) $ciStatus === 3;
            $ciPaymentId = \App\Support\InvoicePaymentLink::latestId(
                $invoice,
                $invoice->customer?->account_id ? (int) $invoice->customer->account_id : null
            );
        @endphp
        @include('invoices.partials.action_toolbar', [
            'isPaid' => $ciIsPaid,
            'isPartial' => $ciIsPartial && ! $ciIsPaid,
            'editUrl' => route('customer_invoice.edit', $invoice->id),
            'editTitle' => 'Edit Invoice',
            'editCan' => ['customers_invoices_edit', 'bike_on_rent_invoices_edit'],
            'editPaymentUrl' => $ciPaymentId ? route('payments.edit', $ciPaymentId) : null,
            'editPaymentTitle' => 'Edit Payment',
            'editPaymentCan' => ['customers_payments_edit', 'cash_&_banks_payments_edit'],
            'emailUrl' => route('customer_invoices.sendEmail', $invoice->id),
            'emailTitle' => 'Send Email',
            'downloadUrl' => route('customer_invoices.download', $invoice),
            'showPayment' => ! $ciIsPaid,
            'paymentUrl' => route('payments.create') . '?customer_id=' . ($invoice->customer_id ?? '') . '&invoice_id=' . $invoice->id,
            'paymentTitle' => 'Record Payment',
            'paymentCan' => ['customers_payments_create', 'customers_invoices_edit', 'bike_on_rent_invoices_edit'],
            'cloneUrl' => route('customer_invoice.clone', $invoice),
            'cloneTitle' => 'Clone Invoice',
            'cloneCan' => ['customers_invoices_create', 'bike_on_rent_invoices_create'],
            'deleteFormRoute' => ['customer_invoices.destroy', $invoice],
            'deleteCan' => ['customers_invoices_delete', 'bike_on_rent_invoices_delete'],
        ])
    </div>
    @endif

    <div class="invoice-box">
        <div class="band"></div>
        <div class="sheet">
            @include('invoices.partials.tax_invoice_header', [
            'settings' => $settings,
            'invoiceTitle' => $invoiceTitle,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDateLabel' => date('d M Y', strtotime($invoice->inv_date)),
            'billingLabel' => date('M Y', strtotime($invoice->billing_month)),
            ])

            <table class="parties" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="party" width="50%" valign="top">
                        <h3 class="party-title">Bill To</h3>
                        <p class="party-name">{{ $customerDisplay }}</p>
                        <div class="party-grid">
                            <div class="party-line">
                                <span class="k">Project</span>
                                <span class="v">{{ $projectName }}</span>
                            </div>
                            <div class="party-line">
                                <span class="k">TRN</span>
                                <span class="v">{{ $invoice->customer->tax_number ?? '—' }}</span>
                            </div>
                            <div class="party-line">
                                <span class="k">Contact</span>
                                <span class="v">{{ $invoice->customer->contact_number ?? '—' }}</span>
                            </div>
                            <div class="party-line">
                                <span class="k">Email</span>
                                <span class="v">{{ $invoice->customer->company_email ?? '—' }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="party alt" width="50%" valign="top">
                        <h3 class="party-title">Service Period</h3>
                        <div class="party-grid" style="margin-top: 4px;">
                            <div class="party-line">
                                <span class="k">From</span>
                                <span class="v">{{ date('d M Y', strtotime($invoice->date_from)) }}</span>
                            </div>
                            <div class="party-line">
                                <span class="k">To</span>
                                <span class="v">{{ date('d M Y', strtotime($invoice->date_to)) }}</span>
                            </div>
                            <div class="party-line">
                                <span class="k">Month</span>
                                <span class="v">{{ date('F Y', strtotime($invoice->billing_month)) }}</span>
                            </div>
                            <div class="party-line">
                                <span class="k">Currency</span>
                                <span class="v">{{ $currency }}</span>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            @if($invoice->description)
            <div class="desc">
                <span class="t">Description</span>
                <p>{{ $invoice->description }}</p>
            </div>
            @endif

            @if($invoice->items && $invoice->items->count() > 0)
            <div class="tbl-wrap">
                <table class="items">
                    <thead>
                        <tr>
                            <th class="col-sr">#</th>
                            <th class="col-desc">Description</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th>VAT %</th>
                            <th>VAT</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $key => $item)
                        @php
                        $quantity = $item->quantity ?? 1;
                        $rate = $item->rate ?? 0;
                        $vatPercent = $item->vat ?? 0;
                        $subtotal = $quantity * $rate;
                        $vatAmount = $subtotal * ($vatPercent / 100);
                        $rowTotal = $subtotal + $vatAmount;
                        $running_total += $rowTotal;
                        $subtotal_from_items += $subtotal;
                        @endphp
                        <tr>
                            <td class="col-sr">{{ $key + 1 }}</td>
                            <td class="col-desc">{{ $item->item_name ?? 'N/A' }}</td>
                            <td>{{ number_format($quantity, 2) }}</td>
                            <td>{{ number_format($rate, 2) }}</td>
                            <td>{{ number_format($subtotal, 2) }}</td>
                            <td>{{ number_format($vatPercent, 2) }}%</td>
                            <td>{{ number_format($vatAmount, 2) }}</td>
                            <td class="total-cell">{{ number_format($rowTotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('invoices.partials.tax_invoice_totals_notes', [
            'partyNote' => $customerNote,
            'partyNoteLabel' => $customerNoteLabel,
            'subtotalAmount' => $invoice->subtotal ?? $subtotal_from_items,
            'vatAmount' => $invoice->vat ?? 0,
            'totalAmount' => $invoice->total ?? $running_total,
            'currency' => $currency,
            'paidAmount' => $invoice->paid_amount ?? 0,
            'balanceAmount' => $invoice->balance ?? (($invoice->total ?? 0) - ($invoice->paid_amount ?? 0)),
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