
            {!! Form::model($legalCases, ['route' => ['LegalCase.update'], 'method' => 'post']) !!}
            <input type="hidden" name="id" value="{{ $legalCases->id }}">
            <input type="hidden"  id="reload_page" value="1">
                <div class="row">
                    @include('legal_cases.fields')
                </div>

            <div class="action-btn">
                <button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
            </div>

            {!! Form::close() !!}
