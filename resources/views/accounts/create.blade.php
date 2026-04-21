@php $__companySlug = \App\Support\CompanyRouteContext::slug(); @endphp

            {!! Form::open(['route' => ['accounts.store', ['company_slug' => $__companySlug]], 'id'=>'formajax']) !!}
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
