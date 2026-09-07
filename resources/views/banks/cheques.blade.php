@extends('banks.view')
@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 100px);
    }
</style>
@endpush
@section('page_content')
    <div class="content">
        @include('flash::message')
        <div class="clearfix"></div>
        @can('cash_&_banks_cheques_view')
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
                @can('cash_&_banks_cheques_create')
                    <button class="btn btn-primary btn-sm show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Cheque" data-action="{{ route('cheques.create') }}?id={{ request()->segment(3) }}">Add New</button>
                @endcan
            </div>
            <div class="card-body table-responsive py-0" id="table-data">
                @include('cheques.table')
            </div>
        </div>
        @endcan
        @cannot('cash_&_banks_cheques_view')
            <div class="text-center mt-5">
                <h3>You do not have permission to view Cheques.</h3> 
            </div>
        @endcannot
    </div>
@endsection

@section('page-script')
@include('delete_requests._confirm_delete_script', [
    'entityName' => 'Cheque',
    'confirmText' => 'This will submit a delete request or move the cheque to the Recycle Bin.',
])
<script>
    $(document).ready(function(){
        $(document).on('click', '.delete-cheque', function(e) {
            e.preventDefault();
            confirmDelete($(this).data('url'));
        });
    })
</script>
@endsection