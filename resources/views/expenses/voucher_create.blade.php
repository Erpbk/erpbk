{!! Form::open(['route' => 'expenses.voucher.store', 'id'=>'formajax', 'class' => 'form-with-fixed-footer']) !!}
<input type="hidden" id="reload_page" value="1">

<div class="card-body card-body-with-footer">
    @include('expenses.voucher_fields')
</div>

<div class="card-footer fixed-footer" style="z-index: 1 !important; text-align: right;">
    {!! Form::submit('Save', ['class' => 'btn btn-primary', 'onclick' => 'getExpenseTotal();']) !!}
</div>

{!! Form::close() !!}
