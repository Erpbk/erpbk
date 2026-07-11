@extends('layouts.app')
@section('title', 'Bank Loans')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="px-2">
        <div class="row mb-2">
            <div class="pt-2">
                <div class="action-buttons d-flex justify-content-end">
                    @hasSection('page_actions')
                        @yield('page_actions')
                    @else
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="addLoanDropdownBtn">
                            <i class="ti ti-plus"></i>
                            <span>Add New</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="addLoanDropdown">
                            @can('loans_create')
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add Bank Loan" data-action="{{ route('loans.create') }}">
                                <i class="ti ti-plus"></i>
                                <div>
                                    <div class="action-dropdown-item-text">New Bank Loan</div>
                                    <div class="action-dropdown-item-desc">Register a term loan from a bank</div>
                                </div>
                            </a>
                            @endcan
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@yield('page_content')
@endsection
