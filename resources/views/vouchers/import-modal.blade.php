<div class="modal fade" id="excel-modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="upload-excel" method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Import Voucher
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-8">
                            <label for="exampleInputEmail1">Voucher Type</label>
                            {!! Form::select('voucher_type',App\Helpers\General::ImportVoucherType(),null ,['class' => 'form-control form-control-sm select2 ','id'=>'payment_type']) !!}
                            <b>Note: </b>Select voucher type carefully.
                        </div>
                        <div class="form-group col-md-8">
                            <label>Upload File</label>
                            <input name="file" class="form-control form-control-sm" type="file">
                        </div>

                        <!--col-->
                    </div>
                    <!--row-->
                </div>
                <div class="form-group col-md-12">
                    <b>Download Voucher Samples</b><br />
                    <a style="font-size: 12px;" href="{{ URL::asset('public/excel_samples/sample-vendor-voucher.xlsx') }}" download>Vendor Sample</a>,&nbsp;
                    <a style="font-size: 12px;" href="{{ URL::asset('public/excel_samples/sample-rta-voucher.xlsx') }}" download>RTA Sample</a>,&nbsp;
                    <a style="font-size: 12px;" href="{{ URL::asset('public/excel_samples/sample-fuel-voucher.xlsx') }}" download>Fuel Sample</a>,&nbsp;
                    <a style="font-size: 12px;" href="{{ URL::asset('public/excel_samples/bike-maintenance-voucher.xlsx') }}" download>Maintenance Sample</a>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="save_rec btn btn-primary save_rec">Upload</button>
                    <button class="btn btn-primary btn-sm loader" type="button" disabled style="display: none">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Uploading...
                    </button>
                    <button type="submit" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>