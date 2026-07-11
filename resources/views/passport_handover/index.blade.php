@extends('layouts.app')

@section('title', 'Passport Handover')

@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
@endpush

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="mb-0">Passport Handover</h3>
            @can('passport_handover_create')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPassportHandover">
                <i class="ti ti-e-passport me-1"></i> New Passport Handover
            </button>
            @endcan
        </div>
    </div>
</section>

<div class="content mt-3">
    @include('flash::message')

    @if($topEnabled)
    <div class="fleet-supervisor-section mb-3">
        <div class="fleet-supervisor-accordion expanded" id="passportHandoverSliderAccordion">
            <div class="fleet-supervisor-slider-container">
                <div class="fleet-supervisor-cards slider-track d-flex gap-3 flex-wrap">
                    <div class="fleet-supervisor-card @if($statusFilter === '') active filtered @endif"
                        onclick="filterByPassportStatus('')">
                        <h3 class="fleet-supervisor-name">All</h3>
                        <div class="fleet-supervisor-stats">
                            <div class="fleet-stat active">
                                <i class="fleet-stat-icon ti ti-users"></i>
                                <span class="fleet-stat-label">Persons</span>
                                <span class="fleet-stat-value">{{ $persons->total() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="fleet-supervisor-card @if($statusFilter === 'issued') active filtered @endif"
                        onclick="filterByPassportStatus('issued')">
                        <h3 class="fleet-supervisor-name">Currently Issued</h3>
                        <div class="fleet-supervisor-stats">
                            <div class="fleet-stat active">
                                <i class="fleet-stat-icon ti ti-e-passport"></i>
                                <span class="fleet-stat-label">Issued</span>
                                <span class="fleet-stat-value">{{ $issuedCount }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="fleet-supervisor-card @if($statusFilter === 'returned') active filtered @endif"
                        onclick="filterByPassportStatus('returned')">
                        <h3 class="fleet-supervisor-name">Returned</h3>
                        <div class="fleet-supervisor-stats">
                            <div class="fleet-stat inactive">
                                <i class="fleet-stat-icon ti ti-circle-check"></i>
                                <span class="fleet-stat-label">Returned</span>
                                <span class="fleet-stat-value">{{ $returnedCount }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Persons</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="searchTopbody">
                    <form id="filterForm" action="{{ route('passportHandover.index') }}" method="GET">
                        @if(request()->filled('status_filter'))
                        <input type="hidden" name="status_filter" value="{{ request('status_filter') }}">
                        @endif
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="quick_search">Quick Search</label>
                                <input type="text" name="quick_search" id="quick_search" class="form-control"
                                    placeholder="Name, ID, person code, passport number" value="{{ request('quick_search') }}">
                            </div>
                            <div class="col-md-12 form-group text-center">
                                <button type="submit" class="btn btn-primary pull-right mt-3">
                                    <i class="fa fa-filter mx-2"></i> Filter Data
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <small class="text-muted">Select a Rider or Employee to view passport handover history</small>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if(request()->filled('status_filter') || request()->filled('quick_search'))
                @php
                $clearQuery = request()->query();
                unset($clearQuery['status_filter'], $clearQuery['quick_search'], $clearQuery['page']);
                @endphp
                <a href="{{ route('passportHandover.index', $clearQuery) }}" class="btn btn-outline-secondary btn-sm">Clear filters</a>
                @endif
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="fa fa-search"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('passport_handover.person_table', ['persons' => $persons])
        </div>
        <div class="card-footer" id="pagination-links">
            {{ $persons->links('components.global-pagination') }}
        </div>
    </div>
</div>

@can('passport_handover_create')
<div class="modal fade" id="createPassportHandover" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Passport Handover</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Select a Rider or Employee to issue a passport.</p>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="person_key" class="form-label">Rider / Employee <span class="text-danger">*</span></label>
                        <select class="form-select" id="person_key" required>
                            <option value="">Select Rider or Employee</option>
                            @if(($riders ?? collect())->isNotEmpty())
                            <optgroup label="Riders">
                                @foreach($riders as $r)
                                <option value="rider:{{ $r->id }}">{{ $r->rider_id }} - {{ $r->name }}@if($r->passport) ({{ $r->passport }})@endif</option>
                                @endforeach
                            </optgroup>
                            @endif
                            @if(($employees ?? collect())->isNotEmpty())
                            <optgroup label="Employees">
                                @foreach($employees as $e)
                                <option value="employee:{{ $e->id }}">{{ $e->employee_id }} - {{ $e->name }}@if($e->passport) ({{ $e->passport }})@endif</option>
                                @endforeach
                            </optgroup>
                            @endif
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-primary" id="startPassportHandoverBtn">
                            <i class="ti ti-e-passport me-1"></i> Continue to Issue
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

@section('page-script')
<script>
function filterByPassportStatus(status) {
    const url = new URL(window.location.href);
    if (status) {
        url.searchParams.set('status_filter', status);
    } else {
        url.searchParams.delete('status_filter');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

$(document).ready(function() {
    $('#startPassportHandoverBtn').on('click', function() {
        const personKey = $('#person_key').val();
        if (!personKey) {
            toastr.error('Please select a Rider or Employee.');
            return;
        }

        const parts = personKey.split(':');
        const type = parts[0];
        const id = parts[1];
        const issueUrl = @json(route('passportHandover.issueForm', ['type' => '__TYPE__', 'id' => '__ID__']))
            .replace('__TYPE__', type)
            .replace('__ID__', id);

        const pickerModal = bootstrap.Modal.getInstance(document.getElementById('createPassportHandover'));
        if (pickerModal) {
            pickerModal.hide();
        }

        $('.modal-dialog').removeClass('modal-sm modal-md modal-lg modal-xl').addClass('modal-lg');
        $('#modalTopTitle').text('Issue Passport');
        $('#modalTopbody').load(issueUrl, function(response, status) {
            if (status === 'error') {
                toastr.error('Unable to open issue form. This person may already have an open passport issue.');
                return;
            }
            toggleModalTop('show');
        });
    });

    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        $('#loading-overlay').show();
        $('#searchModal').modal('hide');

        const loaderStartTime = Date.now();
        let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
        let formData = $.param(filteredFields);

        $.ajax({
            url: $(this).attr('action'),
            type: 'GET',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                const elapsed = Date.now() - loaderStartTime;
                const remaining = Math.max(0, 300 - elapsed);
                setTimeout(function() {
                    $('#table-data').html(response.tableData);
                    $('#pagination-links').html(response.paginationLinks);
                    $('#loading-overlay').hide();
                }, remaining);
            },
            error: function() {
                $('#loading-overlay').hide();
            }
        });
    });
});
</script>
@endsection
