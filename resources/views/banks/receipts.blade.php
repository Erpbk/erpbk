@extends('banks.view')
@section('page_content')
    <div class="content">
        @include('flash::message')
        <div class="clearfix"></div>

        <div class="card">
            <div class="card-body table-responsive py-0" id="table-data">
                @include('receipts.table')
            </div>
        </div>
    </div>
@endsection