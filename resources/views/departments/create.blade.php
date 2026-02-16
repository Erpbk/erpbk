

            @php $deptRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.departments' : 'departments'; @endphp
            {!! Form::open(['route' => $deptRoute . '.store','id'=>'formajax']) !!}



                <div class="row">
                    @include('departments.fields')
                </div>



            <div class="action-btn">
              <button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
            </div>

            {!! Form::close() !!}
