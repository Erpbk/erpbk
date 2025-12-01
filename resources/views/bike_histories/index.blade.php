@extends('bikes.view')

@section('page_content')
@php
@endphp
    <div class="card table-responsive px-2 py-0" >
        @include('bike_histories.table', ['bikeHistory' => $bikeHistory])
    </div>
@endsection
