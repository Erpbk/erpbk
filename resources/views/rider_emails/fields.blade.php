<!-- Rider Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('rider_id', 'Rider Id:') !!}
    {!! Form::number('rider_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Mail To Field -->
<div class="form-group col-sm-6">
    {!! Form::label('mail_to', 'Mail To:') !!}
    {!! Form::text('mail_to', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>

<!-- Subject Field -->
<div class="form-group col-sm-6">
    {!! Form::label('subject', 'Subject:') !!}
    {!! Form::text('subject', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>

<!-- Message Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('message', 'Message:') !!}
    {!! Form::textarea('message', null, ['class' => 'form-control', 'maxlength' => 65535, 'maxlength' => 65535]) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}
    {!! Form::text('status', null, ['class' => 'form-control', 'maxlength' => 20, 'maxlength' => 20]) !!}
</div>