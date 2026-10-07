{!! Form::open(['route' => 'riderInvoices.store', 'id' => 'formajax']) !!}

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3 px-1">
    <div>
        <h4 class="mb-1 fw-bold" style="color: {{ ($companyBrand['primary_color'] ?? '#1e3a5f') }};">Create Rider Invoice</h4>
        <p class="text-muted mb-0 small">Enter rider details and invoice items</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
        {!! Form::submit('Create Invoice', ['class' => 'btn btn-primary']) !!}
    </div>
</div>

@include('rider_invoices.fields')

<div class="d-flex justify-content-end gap-2 mt-3 px-1 pb-1">
    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Create Invoice', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}

<script>
(function () {
    var $header = $('#modalTop .modal-header');
    $header.addClass('d-none');
    $('#modalTop').one('hidden.bs.modal', function () {
        $header.removeClass('d-none');
    });
})();
</script>
