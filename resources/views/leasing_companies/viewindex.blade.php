@extends('layouts.app')
@section('title', 'Leasing Companies List')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="px-2">
        <div class="row mb-2">
            <div class=" pt-2">
                <div class="action-buttons d-flex justify-content-end">
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="addLeasingCompanyDropdownBtn">
                            <i class="ti ti-plus"></i>
                            <span>Add New</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="addLeasingCompanyDropdown">
                            @can('leasing_create')
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="md" data-title="Add New Leasing Company" data-action="{{ route('leasingCompanies.create') }}">
                                    <i class="ti ti-plus"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">New Leasing Company</div>
                                        <div class="action-dropdown-item-desc">Add a new Leasing Company</div>
                                    </div>
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@yield('page_content')
@endsection
