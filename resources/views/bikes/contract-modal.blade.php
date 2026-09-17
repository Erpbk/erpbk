
@isset($contract)
                    <a href="{{route('bike.contract', $contract->id)}}" data-toggle="tooltip" class="file btn btn-warning  btn-sm mr-1" data-modalID="modal-new" target="_blank"><i class="fas fa-file"></i>&nbsp; View / Print Contract</a>
@if($contract->contract)
                        <a href="{{storage_url('app/contract/'.$contract->contract)}}" data-toggle="tooltip" class="file btn btn-success  btn-sm mr-1" data-modalID="modal-new" target="_blank"><i class="fas fa-file"></i>&nbsp; Signed Contract</a>
@endif
@endisset
                <form action="{{url('bikes/contract_upload',@$contract->id)}}" method="post" enctype="multipart/form-data" id="formajax">
@csrf
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                @include('partials.universal_document_upload', [
                                  'name' => 'contract',
                                  'label' => 'Upload Signed Contract File',
                                  'required' => false,
                                  'inputClass' => 'form-control',
                                  'showHint' => true,
                                ])
                            </div>
                        </div>
                        <!--col-->
                    </div>
                    <!--row-->
                </div>
                <div class="modal-footer1 mt-3">
                    <button type="submit" class="save_rec btn btn-primary save_rec">Upload</button>
                </div>
            </form>
