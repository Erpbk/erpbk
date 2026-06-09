@extends('layouts.app')

@section('title','Legal Cases')

@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
@endpush

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="mb-0">Legal Case Accounts</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createaccount">
                <i class="fa fa-plus me-1"></i> Create Legal Case Account
            </button>
        </div>
    </div>
</section>

<div class="content">
    @include('flash::message')

    @if(isset($legalCaseStatuses) && $legalCaseStatuses->isNotEmpty())
    <div class="fleet-supervisor-section mb-3">
        <div class="fleet-supervisor-accordion expanded" id="legalCaseStatusSliderAccordion">
            <div class="fleet-supervisor-slider-container legal-case-account-slider">
                <div class="slider-controls">
                    <button class="slider-btn prev-btn" id="legalCasePrevBtn" type="button" aria-label="Previous">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <div class="slider-indicators" id="legalCaseSliderIndicators"></div>
                    <button class="slider-btn next-btn" id="legalCaseNextBtn" type="button" aria-label="Next">
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
                <div class="fleet-supervisor-cards slider-track" id="legalCaseSliderTrack">
                    @php $legalCaseSlideIndex = 0; @endphp
                    @foreach($legalCaseStatuses as $vs)
                    <div class="fleet-supervisor-card @if((int)request('case_status_id') === (int)$vs->id) active filtered @endif"
                        data-slide="{{ $legalCaseSlideIndex++ }}"
                        onclick="filterByLegalCaseStatus('{{ $vs->id }}')">
                        <h3 class="fleet-supervisor-name">{{ $vs->name }}</h3>
                        @if(isset($vs->description) && trim((string) $vs->description) !== '')
                        <div class="small text-muted mb-1 text-truncate" title="{{ $vs->description }}">{{ \Illuminate\Support\Str::limit($vs->description, 42) }}</div>
                        @endif
                        <div class="fleet-supervisor-stats">
                            <div class="fleet-stat active @if((int)request('case_status_id') === (int)$vs->id && request('step_status') === 'completed') active-selected @endif"
                                onclick="event.stopPropagation(); filterByLegalCaseStatusStep('{{ $vs->id }}', 'completed')">
                                <i class="fleet-stat-icon ti ti-circle-check"></i>
                                <span class="fleet-stat-label">Completed</span>
                                <span class="fleet-stat-value">{{ $legalCaseStatusSliderCounts[$vs->id]['completed'] ?? 0 }}</span>
                            </div>
                            <div class="fleet-stat inactive @if((int)request('case_status_id') === (int)$vs->id && request('step_status') === 'pending') active-selected @endif"
                                onclick="event.stopPropagation(); filterByLegalCaseStatusStep('{{ $vs->id }}', 'pending')">
                                <i class="fleet-stat-icon ti ti-alert-circle"></i>
                                <span class="fleet-stat-label">Pending</span>
                                <span class="fleet-stat-value">{{ $legalCaseStatusSliderCounts[$vs->id]['pending'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Accounts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="searchTopbody">
                    <form id="filterForm" action="{{ route('LegalCase.index') }}" method="GET">
                        @if(request()->filled('case_status_id'))
                        <input type="hidden" name="case_status_id" value="{{ request('case_status_id') }}">
                        @endif
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="quick_search">Quick Search</label>
                                <input type="text" name="quick_search" id="quick_search" class="form-control" placeholder="Rider ID, name, person code" value="{{ request('quick_search') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="name">Account Name</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="Filter By Account Name" value="{{ request('name') }}">
                            </div>
                            <div class="form-group col-md-12">
                                <label for="step_status">Step Status</label>
                                <select class="form-control" name="step_status" id="step_status">
                                    <option value="">All</option>
                                    <option value="completed" {{ request('step_status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="pending" {{ request('step_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                </select>
                            </div>
                            <div class="col-md-12 form-group text-center">
                                <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div></div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if(request()->filled('case_status_id') || request()->filled('step_status'))
                @php
                $clearQueryHeader = request()->query();
                unset($clearQueryHeader['case_status_id'], $clearQueryHeader['step_status'], $clearQueryHeader['page']);
                @endphp
                <a href="{{ route('LegalCase.index', $clearQueryHeader) }}" class="btn btn-outline-secondary btn-sm">Clear status filters</a>
                @endif
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="fa fa-search"></i> Filter Accounts
                </button>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('legal_cases.account_table', [
            'data' => $data,
            'nextPendingByAccountId' => $nextPendingByAccountId ?? [],
            'urgentExpiryByAccountId' => $urgentExpiryByAccountId ?? [],
            ])
        </div>
    </div>
</div>

<div class="modal fade" id="createaccount" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Legal Case Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('LegalCase.accountcreate') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="person_key" class="form-label">Select Rider or Employee</label>
                            <select class="form-select" id="person_key" name="person_key" required>
                                <option value="">Select</option>
                                @if(($riders ?? collect())->isNotEmpty())
                                <optgroup label="Riders">
                                    @foreach($riders as $r)
                                    <option value="rider:{{ $r->id }}">{{ $r->rider_id }} - {{ $r->name }}</option>
                                    @endforeach
                                </optgroup>
                                @endif
                                @if(($employees ?? collect())->isNotEmpty())
                                <optgroup label="Employees">
                                    @foreach($employees as $e)
                                    <option value="employee:{{ $e->id }}">{{ $e->employee_id }} - {{ $e->name }}</option>
                                    @endforeach
                                </optgroup>
                                @endif
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('page-script')
<style>
    /* Continuous ticker uses overflow hidden on the container (riders-styles). */
    .legal-case-account-slider.fleet-supervisor-slider-container:not(.ticker-mode) {
        overflow: visible;
    }

    .legal-case-account-slider .fleet-stat-value {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    .fleet-stat.active-selected {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
        border-width: 2px;
    }

    .fleet-stat.inactilc.active-selected {
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
    }

    .fleet-supervisor-card.filtered .fleet-stat {
        background: rgba(255, 255, 255, 0.85);
        border-radius: 6px;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    function filterByLegalCaseStatus(visaStatusId) {
        var url = new URL(window.location.href);
        url.searchParams.delete('case_status_id');
        url.searchParams.delete('step_status');
        url.searchParams.set('case_status_id', visaStatusId);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function filterByLegalCaseStatusStep(visaStatusId, stepKey) {
        var url = new URL(window.location.href);
        var currentId = url.searchParams.get('case_status_id');
        var currentStep = url.searchParams.get('step_status');
        if (currentId === String(visaStatusId) && currentStep === stepKey) {
            url.searchParams.delete('step_status');
        } else {
            url.searchParams.set('case_status_id', visaStatusId);
            url.searchParams.set('step_status', stepKey);
        }
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function initVisaAccountFleetSlider() {
        var sliderTrack = document.getElementById('legalCaseSliderTrack');
        if (!sliderTrack || sliderTrack.dataset.tickerInit === '1') {
            return;
        }

        var cards = sliderTrack.querySelectorAll('.fleet-supervisor-card');
        if (!cards.length) {
            return;
        }

        var container = sliderTrack.closest('.fleet-supervisor-slider-container');
        if (container) {
            container.classList.add('ticker-mode');
        }

        sliderTrack.dataset.tickerInit = '1';

        if (cards.length < 2) {
            return;
        }

        var computedTrackStyle = window.getComputedStyle(sliderTrack);
        var gap = parseFloat(computedTrackStyle.columnGap || computedTrackStyle.gap || '16') || 16;
        var isAnimating = false;

        function slideNextCard() {
            if (isAnimating) {
                return;
            }
            var firstCard = sliderTrack.querySelector('.fleet-supervisor-card');
            if (!firstCard) {
                return;
            }
            isAnimating = true;
            var shiftAmount = firstCard.offsetWidth + gap;
            sliderTrack.style.transition = 'transform 520ms ease';
            sliderTrack.style.transform = 'translateX(-' + shiftAmount + 'px)';

            window.setTimeout(function() {
                sliderTrack.style.transition = 'none';
                sliderTrack.style.transform = 'translateX(0)';
                sliderTrack.appendChild(firstCard);
                void sliderTrack.offsetWidth;
                isAnimating = false;
            }, 540);
        }

        var intervalId = window.setInterval(slideNextCard, 2600);
        sliderTrack.dataset.tickerIntervalId = String(intervalId);
    }

    function confirmDelete(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        })
    }
    $(document).ready(function() {
        $('#person_key').select2({
            dropdownParent: $('#createaccount'),
            placeholder: "Select Rider or Employee",
            allowClear: true
        });
        $('#step_status').select2({
            dropdownParent: $('#searchModal'),
            placeholder: "Filter By Step Status",
            allowClear: true
        });
        setTimeout(initVisaAccountFleetSlider, 150);
    });

    $(document).on('click', '.js-delete-legal-case-account', function() {
        var url = $(this).data('delete-url');
        if (url) {
            confirmDelete(url);
        }
    });
</script>
@endsection