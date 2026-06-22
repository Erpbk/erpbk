@extends('layouts.app')

@section('title', 'Installments')

@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
@endpush

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="mb-0">Installment Accounts</h3>
        </div>
    </div>
</section>

<div class="content">
    @include('flash::message')

    <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Accounts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="searchTopbody">
                    <form id="filterForm" action="{{ route('Installments.index') }}" method="GET">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="name">Account Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ request('name') }}">
                            </div>
                            <div class="col-md-12 form-group text-center">
                                <button type="submit" class="btn btn-primary mt-3">Apply Filters</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('installments.account_table', ['data' => $data])
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script type="text/javascript">
    $(document).ready(function() {
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            $('#loading-overlay').show();
            $('#searchModal').modal('hide');

            const loaderStartTime = Date.now();
            let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
            let formData = $.param(filteredFields);

            $.ajax({
                url: "{{ route('Installments.index') }}",
                type: "GET",
                data: formData,
                success: function(data) {
                    $('#table-data').html(data.tableData);
                    let newUrl = "{{ route('Installments.index') }}" + (formData ? '?' + formData : '');
                    history.pushState(null, '', newUrl);
                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 1000 - elapsed;
                    setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                },
                error: function() {
                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 1000 - elapsed;
                    setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                }
            });
        });
    });
</script>
@endsection
