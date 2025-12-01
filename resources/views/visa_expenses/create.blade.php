{!! Form::open(['route' => 'VisaExpense.store','id'=>'formajax']) !!}


<input type="hidden" id="reload_page" value="1">
<input type="hidden" name="rider_id" value="{{ $data->id }}">
<div class="row">
    @include('visa_expenses.fields')
</div>



<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}