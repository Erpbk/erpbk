@extends('layouts.app')
@section('title', 'Bank List')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="px-2">
        <div class="row mb-4">
            <div class="col-sm-6 d-flex gap-2">
            </div>
            <div class="col-sm-6">
                @include('banks.partials.actions_dropdown')
            </div>
            </div>
        </div>
</section>
@yield('page_content')
@endsection