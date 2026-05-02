@extends('bikes.view')

@section('page_content')

<div class="card card-action mb-1">
  <div class="card-header align-items-center bg-primary">
    <h5 class="card-action-title mb-0 text-white">Bike Detail</h5>
  </div>

  {!! Form::model($bikes, ['route' => ['bikes.update', $bikes->id], 'method' => 'patch', 'id' => 'formajax']) !!}
  <input type="hidden" name="updated_by" value="{{ Auth::user()->id }}">

  <div class="card-body">
    {{-- Dynamic fields (fixed + custom) --}}
    @include('bikes.fields')

    <div class="form-group col-sm-12 mt-3 text-end">
      <button class="btn btn-primary" type="submit">Save</button>
    </div>
  </div>

  {!! Form::close() !!}
</div>
@endsection