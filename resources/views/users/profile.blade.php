@extends('layouts.app')
@section('title','Profile')
@section('content')
<div class="card mb-4">
    <h5 class="card-header">Profile Details</h5>
    <!-- Account -->
    {!! Form::model($user, ['route' => ['profile', $user->id], 'method' => 'patch','id'=>'formajax','enctype'=>'multipart/form-data']) !!}
  <input type="hidden" id="reload_page" value="1" />
    <div class="card-body">
      <div class="d-flex align-items-start align-items-sm-center gap-4">
        @php
        if(auth()->user()->image_name){
            $image_name = auth()->user()->image_name;
        }else{
            $image_name = 'default.png';
        }
    @endphp
        <img src="{{ asset('uploads/'.$image_name)}}" id="output" width="200"  class="d-block w-px-100 h-px-100 rounded" />
        <div class="button-wrapper">
          <label for="upload" class="btn btn-primary me-2 mb-3" tabindex="0">
            <span class="d-none d-sm-block">Upload new photo</span>
            <i class="ti ti-upload d-block d-sm-none"></i>
            <input type="file" id="upload" name="image_name" class="account-file-input" hidden accept="image/png, image/jpeg" onchange="loadFile(event)" />
          </label>
          

          <div class="text-muted">Allowed JPG, GIF or PNG. Max size of 800K</div>
        </div>
      </div>
    </div>
    <hr class="my-0">
    <div class="card-body">
        <div class="row">
        @include('users.fields')
    </div>
        <div class="mt-2">
          <button type="submit" class="btn btn-primary me-2">Save changes</button>
          <button type="reset" class="btn btn-label-secondary">Cancel</button>
        </div>
      
    </div>
    <!-- /Account -->
</form>
  </div>
  <script>

    var loadFile = function (event) {
      var image = document.getElementById("output");
      image.src = URL.createObjectURL(event.target.files[0]);
    };
    
    
    </script>
@endsection