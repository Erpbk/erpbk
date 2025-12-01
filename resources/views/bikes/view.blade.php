@extends('layouts.app')

@section('title','Bikes')
@section('content')
<div class="row" style="">
  <div class="col-xl-3 col-md-3 col-lg-5 order-1 order-md-0">
    <div class="card mb-6">
      <div class="card-body pt-12">
        <div class="user-avatar-section">
          <div class=" d-flex align-items-center flex-column">
            <div class="user-info text-center">
              <h6>{{$bikes->model_type}}</h6>
              <span class="badge bg-label-primary">{{$bikes->emirates}}</span>
            </div>
          </div>
        </div>
        <h5 class="pb-4 border-bottom mb-4"></h5>
        <div class="info-container">
          <ul class="list-unstyled mb-6">
            <ul class="p-0 mb-3">
              <li class="list-group-item pb-1">
                <b>Plate Number:</b> <span class="float-right">{{$bikes->plate}}</span>
              </li>

              <li class="list-group-item pb-1">
                <b>Color & Model:</b> <span class="float-right">{{$bikes->color}} - {{$bikes->model}}</span>
              </li>
              <li class="list-group-item pb-1">
                <b>Chassis Number:</b> <span class="float-right">{{$bikes->chassis_number}}</span>
              </li>
              <li class="list-group-item pb-1">
                <b>Engine Number:</b> <span class="float-right">{{$bikes->engine}}</span>
              </li>
              <li class="list-group-item pb-1">
                <b>Company:</b> <span class="float-right">{{DB::table('leasing_companies')->where('id' , $bikes->company)->first()->name }}</span>
              </li>
              <li class="list-group-item pb-1">
                <b>Status:</b> <span class="float-right">
                  @php
                  if ($bikes->status == 1) {
                  echo '<span class="badge  bg-success">Active</span>';
                  } else {
                  echo '<span class="badge  bg-danger">Inactive</span>';
                  }
                  @endphp</span>
              </li>
            </ul>
          </ul>
          <div class="d-flex justify-content-center">
            @can('item_edit')
            <a href="{{route('bikes.edit', $bikes->id)}}" class="btn  @if(request()->segment(3) =='edit') btn-primary @else btn-outline-primary @endif  btn-sm waves-effect waves-light btn-block me-1 show-modal"><i class="fa fa-edit"></i>&nbsp;Edit</a>
            @endcan
            @if($bikes->rider_id)
            <a href="javascript:void(0);" data-size="xl" data-title="Edit" data-action="{{route('bikes.assignrider', $bikes->id)}}" class="btn btn-outline-primary btn-sm waves-effect waves-light btn-block me-1 show-modal"><i class="fa fa-biking"></i>&nbsp;Assigned Rider</a>
            @else
            <a href="javascript:void(0);" data-size="xl" data-title="Assign Rider to Bike # {{$bikes->plate}}" data-action="{{ route('bikes.assign_rider', $bikes->id) }}" class="btn btn-outline-primary btn-sm waves-effect waves-light btn-block me-1 show-modal"><i class="fa fa-biking"></i>&nbsp;Assign Rider</a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-9 col-md-9 col-lg-7 order-0 order-md-1">
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row flex-wrap mb-3 row-gap-2">
        <li class="nav-item"><a class="nav-link @if(request()->segment(1) =='bikes') active @endif " href="{{route('bikes.show',$bikes->id)}}"><i class="ti ti-motorbike ti-sm me-1_5 mx-2"></i> Bike</a></li>
        <li class="nav-item">
          <a href="{{route('bikeHistories.index', ['bike_id'=>$bikes->id])}}" class="nav-link @if(request()->segment(1) =='bikeHistories') active @endif"><i class="fa fa-list-check"></i>&nbsp;History</a>
        </li>
        <li class="nav-item">
          <a href="{{ route('files.index',['type_id'=>$bikes->id,'type'=>'bike']) }}" class="nav-link @if(request()->segment(1) =='files') active @endif"><i class="fa fa-file-lines"></i>&nbsp;Files</a>
        </li>
      </ul>
    </div>
    <div class="card mb-5" id="cardBody" style="height:660px !important;overflow: auto;">
      @yield('page_content')
    </div>
  </div>
</div>
@endsection