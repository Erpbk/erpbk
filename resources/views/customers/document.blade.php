@extends('customers.view')

@section('page_content')
{{--     <!-- Main content -->
    <section class="content">
        <div class="container-fluid mt-3"> --}}


          <div class=" card-action mb-0">
            @can('customer_document')
            <div class="card-header align-items-center">
              <h5 class="card-action-title mb-0"><i class="ti ti-file-upload ti-lg text-body me-2"></i>Files</h5>
              <a class="btn btn-primary show-modal action-btn"
                       href="javascript:void(0);" data-action="{{ route('files.create',['type_id'=>request()->segment(3),'type'=>'customer']) }}" data-size="sm" data-title="Upload File">
                        Upload File
                    </a>
            </div>
            <div class="card-body pt-0 px-2">
              @push('third_party_stylesheets')
              @include('layouts.datatables_css')
          @endpush

          <div class="card-body px-0 pt-0" >
              {!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped dataTable']) !!}
          </div>

          @push('third_party_scripts')
              @include('layouts.datatables_js')
              {!! $dataTable->scripts() !!}
          @endpush
            </div>

             @else
            <div class="alert alert-warning  text-center m-3"><i class="fa fa-warning"></i> You don't have permission.</div>
            @endcan
          </div>



{{--           <iframe src="{{url("laravel-filemanager?id=".$rider->id)}}" style="width: 100%; height: 500px; overflow: hidden; border: none;"></iframe>
 --}}
{{--
                    <form action="{{route('rider.document',$rider->id)}}" method="post" enctype="multipart/form-data" id="formajax">
                        @csrf
                        @php
                        $existing = [];
                        @endphp
                @foreach($files as $file)
                @php
                    array_push($existing,$file->type);
                @endphp
                <div class=" p-2 mb-2">
                    <h6>{{App\Helpers\General::file_types($file->type)}}</h6>
                    <div class="row">
                            <input type="hidden" name="documents[{{$file->type}}][type]" value="{{$file->type}}" />
                            <div class="col-3">
                                <label>Expiry Date</label>
                                <input type="date" name="documents[{{$file->type}}][expiry_date]" value="{{$file->expiry_date}}" class="form-control form-control-sm" />
                            </div>
                            <div class="col-3">
                                <label>Document Upload</label>
                                <input type="file" name="documents[{{$file->type}}][file_name]"  />
                            </div>
                            <div class="col-3">
                                <a href="{{ url('storage2/rider/'.$file->file_name)}}" class="btn btn-default" target="_blank">

                                @if($file->file_type == 'pdf')
                                    <i class="fa fa-file-pdf text-danger"></i>
                                @elseif(in_array($file->file_type,['jpeg','jpg','png']))
                                    <i class="fa fa-file-image text-primary"></i>
                                    @else
                                    <i class="fa fa-file text-info"></i>
                                    @endif

                                &nbsp;
                               View Document
                                </a>

                            </div>

                    </div>
                </div>
                @endforeach
                @foreach(App\Helpers\General::file_types() as $key=>$value)
                @if(!in_array($key,$existing))
                <div class=" p-2 mb-2">
                    <h6>{{$value}}</h6>
                    <div class="row">
                            <input type="hidden" name="documents[{{$key}}][type]" value="{{$key}}" />
                            <div class="col-3">
                                <label>Expiry Date</label>
                                <input type="date" name="documents[{{$key}}][expiry_date]" class="form-control form-control-sm" />
                            </div>
                            <div class="col-3">
                                <label>Document Upload</label>
                                <input type="file" name="documents[{{$key}}][file_name]"  />
                            </div>
                            <div class="col-3"></div>

                    </div>
                </div>
                @endif
                @endforeach
                <input type="hidden" id="reload_page" value="1"/>
                <button type="submit" class="btn btn-primary mb-3 mt-3">Save Documents</button>
                    </form> --}}

{{--
    </div>
</div>
 --}}

@endsection
