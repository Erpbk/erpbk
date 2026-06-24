<form action="{{ route('riderInvoices.sendEmail', ['company_slug' => request()->route('company_slug'), 'id' => $invoice->id]) }}" method="POST" id="formajax">
    @csrf
    <div class="col-md-12 form-group">
        <label>Email Address</label>
        <input type="email" class="form-control form-control-sm" name="email_to" value="{{ $invoice->rider->email }}" readonly>
    </div>
    <div class="col-md-12 form-group">
        <label>Subject</label>
        <input type="text" class="form-control form-control-sm" name="email_subject" value="Invoice# {{ App\Helpers\General::inv_sch($invoice->id, $invoice->created_at) }}">
    </div>
    <div class="col-md-12 form-group">
        <label>Message</label>
        <textarea name="email_message" rows="5" class="form-control">Hi {{ $invoice->rider->name }},
Please find attached invoice with this email.

Regards,
{{ config('app.name') }}
</textarea>
    </div>
    <button type="submit" class="btn btn-primary float-right">Send Email</button>
</form>
