<form action="{{ route('employee.sendemail', ['company_slug' => request()->route('company_slug'), 'id' => $employee->id]) }}" method="POST" id="formajax">
    @csrf
    <div class="col-md-12 form-group">
        <label>Email Address</label>
        <input type="email" class="form-control" name="email_to" value="{{ $employee->company_email }}" readonly>
    </div>
    <div class="col-md-12 form-group">
        <label>Subject</label>
        <input type="text" class="form-control" name="email_subject" value="Message for Employee ID {{ $employee->employee_id }}">
    </div>
    <div class="col-md-12 form-group">
        <label>Message</label>
        <textarea name="email_message" rows="8" class="form-control">Hi {{ $employee->name }},

Employee ID: {{ $employee->employee_id }}

Please review the message below.

Best regards,
{{ config('app.name') }}
</textarea>
    </div>
    <button type="submit" class="btn btn-primary pull-right mt-3">Send Email</button>
</form>
