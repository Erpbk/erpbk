@extends('layouts.app')
@if (request()->segment(1) == 'rtaFines')
@section('title','RTA Fines')
@endif
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div>
        <div class="row mb-2">
            <div class="col-sm-6 d-flex gap">
                <h4 style="padding-left: 20px; font-weight: bold; margin-top: 10px;">RTA Fine Accounts</h4>
            </div>
            <div class="col-sm-6 text-end mb-2">
                @if(request()->segment(1) =='rtaFines')
                <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#createaccount">
                    Add New RTA Fines Account
                </a>
                @endif
            </div>
        </div>
    </div>
</section>
@yield('page_content')
@endsection