@extends('bikes.view')

@section('page_content')

<div class="card card-action p-0">


  {!! Form::model($bikes, ['route' => ['bikes.update', $bikes->id], 'method' => 'patch', 'id' => 'formajax', 'data-rfp-entity' => 'bike']) !!}
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