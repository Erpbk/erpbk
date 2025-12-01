<form action="{{ route('supplier.invoice_import') }}" method="POST" enctype="multipart/form-data" id="formajax">
    <div class="row">
        <div class="col-12">
            <a href="{{ url('sample/supplier_invoice_sample.xlsx') }}" class="text-success w-100" download="Supplier Invoice Sample">
                <i class="fa fa-file-download text-success"></i> &nbsp; Download Sample File
            </a>
        </div>
        <div class="col-12 mt-3 mb-3">
            <label class="mb-3 pl-2">Select file</label>
            <input type="file" name="file" class="form-control mb-3" style="height: 40px;" />
        </div>
    </div>
    <button type="submit" name="submit" class="btn btn-primary" style="width: 100%;">Start Import</button>
</form>
