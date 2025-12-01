@extends('layouts.app')
@section('title', 'Rider Profile')

@section('content')
<style>
.myform .required:after {
  content: " *";
    color: red;
    font-weight: 200;
}
</style>

<div class="content-wrapper" style="min-height: 1603.43px;">
    <section class="content-header">
       <div class="container-fluid">
          <div class="row mb-2">
             <div class="col-sm-6">
                <h1>User Profile</h1>
             </div>
             <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                   <li class="breadcrumb-item"><a href="{{url('rider')}}">Rider</a></li>
                   <li class="breadcrumb-item active">User Profile</li>
                </ol>
             </div>
          </div>
       </div>
    </section>

    <section class="content">
       <div class="container-fluid">
          <div class="row">
             <div class="col-md-3">
                <div class="card card-primary card-outline">
                   <div class="card-body box-profile">
                    <div class="">
                        @isset($result)
<form action="{{url('riders/picture_upload/'.$result['id'])}}" method="POST" enctype="multipart/form-data" id="formajax2">
    @endisset
    @csrf
                        @php
                        if(@$result['image_name']){
                            $image_name = Storage::url('app/profile/'.$result['image_name']);
                        }else{
                            $image_name = asset('public/uploads/default.jpg');
                        }
                    @endphp
                        <img src="{{ $image_name}}" id="output" width="400"  class="profile-user-img img-fluid" />
                        @isset($result)
                        <div class="button-wrapper">
                          <label for="upload" class="btn btn-default me-2 mb-3 mt-3" tabindex="0">
                            <span class="d-none d-sm-block">Change Photo</span>
                            <i class="ti ti-upload d-block d-sm-none"></i>
                            <input type="file" id="upload" name="image_name" class="account-file-input " hidden accept="image/png, image/jpeg" onchange="loadFile(event)" />
                          </label>
                          <input type="submit" class="btn btn-primary" value="Upload"/>
                        </div>
                        @endisset
                    </form>
                      </div>
                      <script>

                        var loadFile = function (event) {
                          var image = document.getElementById("output");
                          image.src = URL.createObjectURL(event.target.files[0]);
                        };


                        </script>
                      {{-- <div class="text-center">
                         <img class="profile-user-img img-fluid" src="https://placehold.co/400X400" alt="User profile picture">
                      </div> --}}
                      <h3 class="profile-username text-center">@isset($result){{$result['name']??'not-set'}}@endisset</h3>
                      <p class="text-muted text-center">@isset($result){{$result['designation']??'not-set'}}@endisset</p>
                      <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Rider ID</b> <span class="float-right">@isset($result){{$result['rider_id']??'not-set'}}@endisset</span>
                         </li>
                         <li class="list-group-item">
                            <b>Bike Number</b> <span class="float-right">@isset($result){{$riders->bikes->plate??'not-set'}}@endisset</span>
                         </li>
                         <li class="list-group-item">
                            <b>Date Of Joining</b> <a class="float-right">@isset($result){{App\Helpers\CommonHelper::DateFormat($result['doj'])??'not-set'}}@endisset</a>
                         </li>
                         <li class="list-group-item @if(@$result['status'] == 1) text-success @else text-danger @endif" >
                            <b>Status</b> <a class="float-right">@isset($result){{App\Helpers\CommonHelper::RiderStatus($result['status'])??'not-set'}}@endisset</a>
                         </li>

                         <li class="list-group-item @if(@$result['job_status'] == 1) text-success @else text-danger @endif" >
                            <b>Job Status</b> <span class="float-right">
                                @isset($result)<a href="javascript:void(0);" data-action="{{url('riders/job_status/'.$result['id'])}}" data-title="Change Job Status" class="btn btn-default btn-sm show-modal">Change Status</a>@endisset
                                 @isset($result['job_status']){{App\Helpers\CommonHelper::JobStatus($result['job_status'])??'not-set'}}@endisset</span>
                                @isset($rider->jobstatus)
                                <hr/>
                                <b>Reason:</b>
                                {{$rider->jobstatus->reason??'not-set'}}
                                @endisset
                         </li>
                         <li class="list-group-item">
                            <b>Balance</b> <a class="float-right">@isset($trans_acc_id){{App\Helpers\Account::show_bal(App\Helpers\Account::Monthly_ob(date('y-m-d'), $trans_acc_id))??'not-set'}}@endisset</a>
                         </li>
                      </ul>
                      {{-- <a href="#" class="btn btn-primary btn-block"><b>Update</b></a> --}}
                   </div>
                </div>
             {{--    <div class="card card-primary">
                   <div class="card-header">
                      <h3 class="card-title">About Me</h3>
                   </div>
                   <div class="card-body">
                      <strong><i class="fas fa-book mr-1"></i> Education</strong>
                      <p class="text-muted">
                         B.S. in Computer Science from the University of Tennessee at Knoxville
                      </p>
                      <hr>
                      <strong><i class="fas fa-map-marker-alt mr-1"></i> Location</strong>
                      <p class="text-muted">Malibu, California</p>
                      <hr>
                      <strong><i class="fas fa-pencil-alt mr-1"></i> Skills</strong>
                      <p class="text-muted">
                         <span class="tag tag-danger">UI Design</span>
                         <span class="tag tag-success">Coding</span>
                         <span class="tag tag-info">Javascript</span>
                         <span class="tag tag-warning">PHP</span>
                         <span class="tag tag-primary">Node.js</span>
                      </p>
                      <hr>
                      <strong><i class="far fa-file-alt mr-1"></i> Notes</strong>
                      <p class="text-muted">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam fermentum enim neque.</p>
                   </div>
                </div> --}}
             </div>
             <div class="col-md-9">
                <div class="card">
                   <div class="card-header p-2">
                      <ul class="nav nav-pills">

                         <li class="nav-item"><a class="nav-link active" href="#information" data-toggle="tab">Information</a></li>
                         @can('riders_document')
                         <li class="nav-item"><a class="nav-link" href="#documents" data-toggle="tab">Documents</a></li>
                         @endcan
                         <li class="nav-item"><a class="nav-link" href="#timeline" data-toggle="tab">Timeline</a></li>
                         @can('invoices_view')
                         <li class="nav-item"><a class="nav-link" href="#invoices" data-toggle="tab">Invoices</a></li>
                         @endcan



{{--                          <li class="nav-item"><a class="nav-link" href="#settings" data-toggle="tab">Settings</a></li>
 --}}                      </ul>
                   </div>
                   <div class="card-body">
                      <div class="tab-content">

                         <div class="active tab-pane" id="information">
                            @include('riders.fields')

                         </div>
                         <div class="tab-pane" id="documents">
                            @isset($rider)
                            @include('riders.document')
                            @endisset
                         </div>
                         <div class=" tab-pane" id="timeline">
                            @isset($rider)
                            @include('riders.timeline')
                            @endisset
                         </div>
                         <div class="tab-pane" id="invoices">
                            @include('riders.invoices')
                         </div>


                         <div class="tab-pane" id="settings">

                         </div>

                      </div>
                   </div>
                </div>
             </div>
          </div>
       </div>
    </section>
 </div>
 <script>
        $(document).ready(function() {
    // Get the initial active tab from the URL
    var activeTab = window.location.hash.replace('#', '');
    // Activate the initial tab
    $('.nav-item a[href="#' + activeTab + '"]').tab('show');
    });
</script>

 @include('riders.inc_func')

@endsection
