@extends('layouts.app')

@section('title', 'View File')

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <h3>File Details</h3>
  </div>
</section>
<section class="content px-3">
  @yield('page_content')
</section>
@endsection
