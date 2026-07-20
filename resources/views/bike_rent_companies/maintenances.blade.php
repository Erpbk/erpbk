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
        @canany(['bike_on_rent_maintenance_view', 'garages_maintenance_view'])
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
                @canany(['bike_on_rent_maintenance_create', 'garages_maintenance_create'])
                    <button class="btn btn-primary btn-sm show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Receipt" data-action="{{ route('customer_invoices.create') }}?customer_id={{ $customer->id }}">Add New</button>
                @endcanany
            </div>
            <div class="card-body table-responsive px-2 py-0" id="table-data">
                @include('bike-maintenance.table')
            </div>
        </div>
        @else
            <div class="text-center mt-5">
                <h3>You do not have permission to view Receipts.</h3> 
            </div>
        @endcanany
    </div>
@endsection
