@extends('bikes.view')

@section('page_content')
@php
@endphp
    <div>
        @include('bike_histories.table2', ['bikeHistory' => $bikeHistory])
    </div>
@endsection
