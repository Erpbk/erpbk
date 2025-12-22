
            {!! Form::model($sims, ['url' => route('sims.return', $sims->id), 'method' => 'post','id'=>'formajax']) !!}

            <div class="card-body">
                <div class="row">
                    <!-- Number Field -->
                    <div class="form-group col-sm-6">
                        {!! Form::label('number', 'Number:') !!}
                        {!! Form::text('number', old('number', $sims->number ?? ''), ['class' => 'form-control', 'readonly' => true]) !!}
                    </div>

                    <div class="form-group col-sm-6">
                        {!! Form::label('assign_to', 'Assigned To:') !!}
                        {!! Form::text('assign_to', $rider_name, ['class' => 'form-control', 'readonly' => true]) !!}
                    </div>

                    <div class="form-group col-md-3">
                        <label for="return_date">Return Date</label>
                        <input type="date" name="return_date" class="form-control">
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
                {!! Form::submit('Return', ['class' => 'btn btn-primary']) !!}

            </div>

            {!! Form::close() !!}



