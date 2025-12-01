

            {!! Form::model($accounts, ['route' => ['accounts.update', $accounts->id], 'method' => 'patch','id'=>'formajax']) !!}
            <input type="hidden" id="reload_page" value="1"/>
            <div class="card-body">
                <div class="row">
                    @include('accounts.fields')
                </div>
            </div>

            <div class="action-btn">
              <button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
            </div>

            {!! Form::close() !!}
