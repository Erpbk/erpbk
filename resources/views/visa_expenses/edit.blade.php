
            {!! Form::model($visaExpenses, ['route' => ['VisaExpense.update'], 'method' => 'patch']) !!}
            <input type="hidden" name="id" value="{{ $visaExpenses->id }}">
            <input type="hidden"  id="reload_page" value="1">
                <div class="row">
                    @include('visa_expenses.fields')
                </div>

            <div class="action-btn">
                <button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
            </div>

            {!! Form::close() !!}
