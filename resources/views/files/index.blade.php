@extends('bikes.view')

@section('page_content')

@php
    $authorized = false;
    if(request('type') == 'bike' && auth()->user()->can('bike_document')){
      $authorized = true;
    }

@endphp
@if($authorized)
@include('flash::message')
<div class="card">
    @can('bike_document')
    <div class="card-header">
        <a class="btn btn-primary show-modal action-btn"
          href="javascript:void(0);" data-action="{{ route('files.create',['type_id'=>request('type_id')??1,'type'=>request('type')??1]) }}" data-size="sm" data-title="Upload File">
           Upload File
        </a>
    </div>
    @endcan
    @include('files.table')
</div>
@else
<div class="alert alert-warning  text-center m-3"><i class="fa fa-warning"></i> You don't have permission. &nbsp;<a href="{{url()->previous() }}"> Go Back</a></div>
@endif
@endsection
