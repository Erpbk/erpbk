@extends('customers.view')
<style>
    .table-responsive {
        max-height: calc(100vh + 350px);
    }
</style>
@section('page_content')
    <div class="content">
        @include('flash::message')
        <div class="clearfix"></div>
        @can('customer_payments')
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
                <button class="btn btn-primary btn-sm show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Payment" data-action="{{ route('payments.create') }}?customer_id={{ $customer->id }}">Add New</button>
                
            </div>
            <div class="card-body table-responsive py-0" id="table-data">
                @include('payments.table')
            </div>
        </div>
        @endcan
        @cannot('customer_payments')
            <div class="text-center mt-5">
                <h3>You do not have permission to view Payments.</h3> 
            </div>
        @endcannot
    </div>
@endsection
