{{-- resources/views/branches/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Branch Management')

@section('content')
    @include('flash::message')
    
    <h3 class="px-3 mb-3">Company Branches</h3>
    <!-- Branches Table Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            @can('branches_create')
                <a href="javascript:void(0)" class="btn btn-primary btn-sm show-modal" data-action="{{ route('branches.create') }}" data-size="lg" data-title="Add new branch">
                    <i class="icon-base ti ti-plus me-1"></i> Add New Branch
                </a>
            @endcan
        </div>
        <div class="card-body table-responsive" id="tableData">
            @include('branches.table')
        </div>
    </div>
@endsection

