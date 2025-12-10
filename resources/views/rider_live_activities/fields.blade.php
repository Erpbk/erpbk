<!-- Rider Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('rider_id', 'Rider Id:') !!}
    {!! Form::number('rider_id', null, ['class' => 'form-control']) !!}
</div>

<!-- D Rider Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('d_rider_id', 'D Rider Id:') !!}
    {!! Form::number('d_rider_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Payout Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payout_type', 'Payout Type:') !!}
    {!! Form::text('payout_type', null, ['class' => 'form-control', 'maxlength' => 20, 'maxlength' => 20]) !!}
</div>

<!-- Delivered Orders Field -->
<div class="form-group col-sm-6">
    {!! Form::label('delivered_orders', 'Delivered Orders:') !!}
    {!! Form::number('delivered_orders', null, ['class' => 'form-control']) !!}
</div>

<!-- Ontime Orders Field -->
<div class="form-group col-sm-6">
    {!! Form::label('ontime_orders', 'Ontime Orders:') !!}
    {!! Form::number('ontime_orders', null, ['class' => 'form-control']) !!}
</div>

<!-- Ontime Orders Percentage Field -->
<div class="form-group col-sm-6">
    {!! Form::label('ontime_orders_percentage', 'Ontime Orders Percentage:') !!}
    {!! Form::number('ontime_orders_percentage', null, ['class' => 'form-control']) !!}
</div>

<!-- Avg Time Field -->
<div class="form-group col-sm-6">
    {!! Form::label('avg_time', 'Avg Time:') !!}
    {!! Form::number('avg_time', null, ['class' => 'form-control']) !!}
</div>

<!-- Rejected Orders Field -->
<div class="form-group col-sm-6">
    {!! Form::label('rejected_orders', 'Rejected Orders:') !!}
    {!! Form::number('rejected_orders', null, ['class' => 'form-control']) !!}
</div>

<!-- Rejected Orders Percentage Field -->
<div class="form-group col-sm-6">
    {!! Form::label('rejected_orders_percentage', 'Rejected Orders Percentage:') !!}
    {!! Form::number('rejected_orders_percentage', null, ['class' => 'form-control']) !!}
</div>

<!-- Login Hr Field -->
<div class="form-group col-sm-6">
    {!! Form::label('login_hr', 'Login Hr:') !!}
    {!! Form::number('login_hr', null, ['class' => 'form-control']) !!}
</div>

<!-- Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:') !!}
    {!! Form::text('date', null, ['class' => 'form-control','id'=>'date']) !!}
</div>

@push('page_scripts')
    <script type="text/javascript">
        $('#date').datepicker()
    </script>
@endpush