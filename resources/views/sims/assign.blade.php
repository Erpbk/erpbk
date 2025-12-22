
            {!! Form::model($sims, ['url' => route('sims.assign', $sims->id), 'method' => 'post','id'=>'formajax']) !!}

            <div class="card-body">
                <div class="row">
                    <!-- Number Field -->
                    <div class="form-group col-sm-6">
                        {!! Form::label('number', 'Number:') !!}
                        {!! Form::text('number', old('number', $sims->number ?? ''), ['class' => 'form-control', 'readonly' => true]) !!}
                    </div>

                    <!-- Rider Field -->
                    <div class="form-group col-sm-6">
                        {!! Form::label('assign_to', 'Assign To:') !!}
                        {!! Form::select('assign_to', \App\Models\Riders::dropdown(), old('assign_to', $sims->assign_to ?? ''), ['class' => 'form-select select2']) !!}
                    </div>
                    
                    <div class="form-group col-md-3">
                        <label for="note_date">Assign Date</label>
                        <input type="date" name="note_date" class="form-control">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-8">
                        <textarea class="form-control" placeholder="Note....." name="notes"></textarea>
                    </div>
                </div>
            </div>

            <div class="action-btn pt-3">
              <button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}

            </div>

            {!! Form::close() !!}



