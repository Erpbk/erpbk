{{-- Items-only template body; header/parties/totals come from tax-invoice shell --}}
@if($riderInvoice->items && $riderInvoice->items->count() > 0)
    @include('rider_invoices.partials.invoice_items_classic')
@else
    <div class="empty">No invoice items found for this period.</div>
@endif
