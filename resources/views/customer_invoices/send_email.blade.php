@php
    $invoiceNumber = $invoice->invoice_number
        ?? ('CI-' . str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT));
    $customerName = $invoice->customer->company_name
        ?: ($invoice->customer->name ?? 'Customer');
    $customerEmail = $invoice->customer->company_email ?? '';
@endphp
<form action="{{ route('customer_invoices.sendEmail', ['company_slug' => request()->route('company_slug'), 'id' => $invoice->id]) }}" method="POST" id="formajax">
    @csrf
    <div class="col-md-12 form-group">
        <label>Email Address</label>
        <input type="email" class="form-control form-control-sm" name="email_to" value="{{ $customerEmail }}" @if($customerEmail) readonly @endif required>
    </div>
    <div class="col-md-12 form-group">
        <label>Subject</label>
        <input type="text" class="form-control form-control-sm" name="email_subject" value="Invoice# {{ $invoiceNumber }}">
    </div>
    <div class="col-md-12 form-group">
        <label>Message</label>
        <textarea name="email_message" rows="5" class="form-control">Hi {{ $customerName }},
Please find attached invoice with this email.

Regards,
{{ config('app.name') }}
</textarea>
    </div>
    <button type="submit" class="btn btn-primary float-right">Send Email</button>
</form>
