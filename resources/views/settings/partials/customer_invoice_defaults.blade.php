{{-- Company defaults for Customer Invoice title / notes / terms. --}}
@php
  $invoiceDefaults = \App\Support\CustomerInvoiceDefaults::all();
@endphp
<hr class="my-4">
<div class="mb-2">
  <h5 class="mb-1">Invoice defaults</h5>
  <p class="text-muted small mb-3">
    Default text for customer invoices. Title appears on the printed invoice header.
  </p>
</div>
<form method="POST" action="{{ route('settings-panel.module-settings.update-invoice-defaults', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'module' => 'customer_invoices']) }}">
  @csrf
  <div class="mb-3">
    <label class="form-label" for="customer_invoice_title">Invoice Title</label>
    <input
      type="text"
      name="invoice_title"
      id="customer_invoice_title"
      class="form-control"
      maxlength="100"
      placeholder="CUSTOMER INVOICE"
      value="{{ old('invoice_title', $invoiceDefaults['title']) }}"
    >
    <div class="form-text">Shown as the heading on the invoice (e.g. CUSTOMER INVOICE).</div>
  </div>
  <div class="mb-3">
    <label class="form-label" for="customer_invoice_terms_and_conditions">Terms &amp; Conditions</label>
    <textarea
      name="terms_and_conditions"
      id="customer_invoice_terms_and_conditions"
      class="form-control"
      rows="4"
      placeholder="Enter default terms &amp; conditions..."
    >{{ old('terms_and_conditions', $invoiceDefaults['terms_and_conditions']) }}</textarea>
  </div>
  <div class="mb-3">
    <label class="form-label" for="customer_invoice_customer_notes">Customer Notes</label>
    <textarea
      name="customer_notes"
      id="customer_invoice_customer_notes"
      class="form-control"
      rows="4"
      placeholder="Thanks for your business."
    >{{ old('customer_notes', $invoiceDefaults['customer_notes']) }}</textarea>
    <div class="form-text">Will be displayed on the invoice</div>
  </div>
  <div class="text-end">
    <button type="submit" class="btn btn-primary">Save invoice defaults</button>
  </div>
</form>
