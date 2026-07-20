@extends('bike_rent_companies.view')
<style>
    .table-responsive {
        max-height: calc(100vh + 350px);
    }
</style>
@section('page_content')
    <div class="content">
        @include('flash::message')
        <div class="clearfix"></div>
        @can('bike_on_rent_invoices_view')
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
                @can('bike_on_rent_invoices_create')
                    <button class="btn btn-primary btn-sm show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Receipt" data-action="{{ route('customer_invoices.create') }}?customer_id={{ $customer->id }}">Add New</button>
                @endcan
            </div>
            @include('customer_invoices.table')
        </div>
        @else
            <div class="text-center mt-5">
                <h3>You do not have permission to view Invoices.</h3> 
            </div>
        @endcan
    </div>
@endsection
