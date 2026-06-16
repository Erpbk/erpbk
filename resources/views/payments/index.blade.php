@extends('banks.viewindex')
@section('page_content')
<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('payments.table')
        </div>
    </div>
</div>
@endsection
