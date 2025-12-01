<!-- Rider Id Field -->
<div class="col-sm-12">
    {!! Form::label('rider_id', 'Rider ID:') !!}
    <p>{{ $riderEmails->rider->rider_id }}</p>
</div>

<!-- Mail To Field -->
<div class="col-sm-12">
    {!! Form::label('mail_to', 'Mail To:') !!}
    <p>{{ $riderEmails->mail_to }}</p>
</div>

<!-- Subject Field -->
<div class="col-sm-12">
    {!! Form::label('subject', 'Subject:') !!}
    <p>{{ $riderEmails->subject }}</p>
</div>

<!-- Message Field -->
<div class="col-sm-12">
    {!! Form::label('message', 'Message:') !!}
    <p>{{ $riderEmails->message }}</p>
</div>

<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p style="text-transform: uppercase;">{{ $riderEmails->status }}</p>
</div>

