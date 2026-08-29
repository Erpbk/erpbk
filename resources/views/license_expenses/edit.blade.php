
            {!! Form::model($LicenseExpenses, ['route' => ['LicenseExpense.update'], 'method' => 'post']) !!}
            <input type="hidden" name="id" value="{{ $LicenseExpenses->id }}">
            <input type="hidden"  id="reload_page" value="1">
                <div class="row">
                    @include('license_expenses.fields')
                </div>

            <div class="action-btn">
                <button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
            </div>

            {!! Form::close() !!}
