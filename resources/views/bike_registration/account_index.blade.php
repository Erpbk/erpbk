@extends('layouts.app')

@section('title','Bike Registration Accounts')

@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
@endpush

@section('content')
<section class="content-header">
    @can('bikes_registration_create')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="mb-0">Bike Registration Accounts</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createaccount">
                <i class="fa fa-plus me-1"></i> Create Expense Account
            </button>
        </div>
    </div>
    @endcan
</section>

<div class="content">
    @include('flash::message')

    @if(isset($registrationStatuses) && $registrationStatuses->isNotEmpty())
    <div class="fleet-supervisor-section mb-3">
        <div class="fleet-supervisor-accordion expanded" id="bikeRegistrationStatusSliderAccordion">
            <div class="fleet-supervisor-slider-container visa-account-slider">
                <div class="slider-controls">
                    <button class="slider-btn prev-btn" id="bikePrevBtn" type="button" aria-label="Previous">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <div class="slider-indicators" id="bikeSliderIndicators"></div>
                    <button class="slider-btn next-btn" id="bikeNextBtn" type="button" aria-label="Next">
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
                <div class="fleet-supervisor-cards slider-track" id="bikeSliderTrack">
                    @php $bikeSlideIndex = 0; @endphp
                    @foreach($registrationStatuses as $vs)
                    <div class="fleet-supervisor-card @if((int)request('registration_status_id') === (int)$vs->id) active filtered @endif"
                        data-slide="{{ $bikeSlideIndex++ }}"
                        onclick="filterByRegistrationStatus('{{ $vs->id }}')">
                        <h3 class="fleet-supervisor-name">{{ $vs->name }}</h3>
                        @if(isset($vs->description) && trim((string) $vs->description) !== '')
                        <div class="small text-muted mb-1 text-truncate" title="{{ $vs->description }}">{{ \Illuminate\Support\Str::limit($vs->description, 42) }}</div>
                        @endif
                        <div class="fleet-supervisor-stats">
                            <div class="fleet-stat active @if((int)request('registration_status_id') === (int)$vs->id && request('payment_status') === 'paid') active-selected @endif"
                                onclick="event.stopPropagation(); filterByRegistrationStatusPayment('{{ $vs->id }}', 'paid')">
                                <i class="fleet-stat-icon ti ti-circle-check"></i>
                                <span class="fleet-stat-label">Paid</span>
                                <span class="fleet-stat-value">{{ $registrationStatusSliderCounts[$vs->id]['paid'] ?? 0 }}</span>
                            </div>
                            <div class="fleet-stat inactive @if((int)request('registration_status_id') === (int)$vs->id && request('payment_status') === 'unpaid') active-selected @endif"
                                onclick="event.stopPropagation(); filterByRegistrationStatusPayment('{{ $vs->id }}', 'unpaid')">
                                <i class="fleet-stat-icon ti ti-alert-circle"></i>
                                <span class="fleet-stat-label">Unpaid</span>
                                <span class="fleet-stat-value">{{ $registrationStatusSliderCounts[$vs->id]['unpaid'] ?? 0 }}</span>
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
                    <form id="filterForm" action="{{ route('BikeRegistration.index') }}" method="GET">
                        @if(request()->filled('registration_status_id'))
                        <input type="hidden" name="registration_status_id" value="{{ request('registration_status_id') }}">
                        @endif
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="quick_search">Quick Search</label>
                                <input type="text" name="quick_search" id="quick_search" class="form-control" placeholder="Plate, bike code, model, chassis" value="{{ request('quick_search') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="name">Account Name</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="Filter By Account Name" value="{{ request('name') }}">
                            </div>
                            <div class="form-group col-md-12">
                                <label for="payment_status">Payment Status</label>
                                <select class="form-control" name="payment_status" id="payment_status">
                                    <option value="">All</option>
                                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
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

    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div></div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if(request()->filled('registration_status_id') || request()->filled('payment_status'))
                @php
                $clearQueryHeader = request()->query();
                unset($clearQueryHeader['registration_status_id'], $clearQueryHeader['payment_status'], $clearQueryHeader['page']);
                @endphp
                <a href="{{ route('BikeRegistration.index', $clearQueryHeader) }}" class="btn btn-outline-secondary btn-sm">Clear status filters</a>
                @endif
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="fa fa-search"></i> Filter Accounts
                </button>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('bike_registration.account_table', [
            'data' => $data,
            'nextUnpaidByAccountId' => $nextUnpaidByAccountId ?? [],
            'urgentExpiryByAccountId' => $urgentExpiryByAccountId ?? [],
            ])
        </div>
    </div>
</div>

<div class="modal fade" id="bikeRegistrationExpensesModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bike registration expenses</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2 p-md-3" id="bike-registration-expenses-modal-body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createaccount" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Bike Registration Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('BikeRegistration.accountcreate') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="bike_id" class="form-label">Select Bike</label>
                            <select class="form-select" id="bike_id" name="bike_id" required>
                                <option value="">Select</option>
                                @foreach($bikes as $bike)
                                <option value="{{ $bike->id }}">{{ $bike->plate }}@if(!empty($bike->model)) — {{ $bike->model }}@endif @if($bike->rider) ({{ $bike->rider->rider_id }} {{ $bike->rider->name }}) @else (no rider) @endif</option>
                                @endforeach
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
@include('bike_registration.scripts.table_inline_edit')
<style>
    /* Continuous ticker uses overflow hidden on the container (riders-styles). */
    .visa-account-slider.fleet-supervisor-slider-container:not(.ticker-mode) {
        overflow: visible;
    }

    .visa-account-slider .fleet-stat-value {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    .fleet-stat.active-selected {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
        border-width: 2px;
    }

    .fleet-stat.inactive.active-selected {
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
    }

    .fleet-supervisor-card.filtered .fleet-stat {
        background: rgba(255, 255, 255, 0.85);
        border-radius: 6px;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    var brCurrencySymbol = '{{ \App\Helpers\Currency::symbol() }}';

    function brUpdateExpenseTotalsFromAjax($modalBody, totals) {
        if (!totals || !$modalBody || !$modalBody.length) return;
        $modalBody.find('#br-total-unpaid').text(brCurrencySymbol + ' ' + parseFloat(totals.totalUnpaid || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
        $modalBody.find('#br-total-paid').text(brCurrencySymbol + ' ' + parseFloat(totals.totalPaid || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
        $modalBody.find('#br-count-unpaid').text(totals.unpaidCount ?? 0);
        $modalBody.find('#br-count-paid').text(totals.paidCount ?? 0);
    }

    function brShowBikeRegistrationExpensesModal() {
        var modalEl = document.getElementById('bikeRegistrationExpensesModal');
        if (!modalEl) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#bikeRegistrationExpensesModal').modal('show');
        }
    }

    $(document).on('click', '.js-br-expenses-modal', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var url = $(this).data('url');
        if (!url) return;
        $('#bike-registration-expenses-modal-body').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');
        brShowBikeRegistrationExpensesModal();
        $.ajax({
            url: url,
            type: 'GET',
            data: {
                modal: 1
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(res) {
                if (res.panelHtml) {
                    $('#bike-registration-expenses-modal-body').html(res.panelHtml);
                } else if (res.tableData) {
                    $('#bike-registration-expenses-modal-body').html('<div class="card-body p-0" id="table-data">' + res.tableData + '</div>');
                } else {
                    $('#bike-registration-expenses-modal-body').html('<div class="alert alert-warning m-2">No data returned.</div>');
                }
            },
            error: function() {
                $('#bike-registration-expenses-modal-body').html('<div class="alert alert-danger m-3">Could not load bike registration expenses.</div>');
            }
        });
    });

    $(document).on('click', '#bikeRegistrationExpensesModal .pagination a.page-link', function(e) {
        var href = $(this).attr('href');
        if (!href || href === '#' || href.indexOf('javascript') === 0) return;
        e.preventDefault();
        var $body = $('#bike-registration-expenses-modal-body');
        $.ajax({
            url: href,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(res) {
                if (res.tableData) {
                    $body.find('#table-data').html(res.tableData);
                }
                if (res.expenseTotals) {
                    brUpdateExpenseTotalsFromAjax($body, res.expenseTotals);
                }
            }
        });
    });

    function filterByRegistrationStatus(registrationStatusId) {
        var url = new URL(window.location.href);
        url.searchParams.delete('registration_status_id');
        url.searchParams.delete('payment_status');
        url.searchParams.set('registration_status_id', registrationStatusId);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function filterByRegistrationStatusPayment(registrationStatusId, paymentKey) {
        var url = new URL(window.location.href);
        var currentId = url.searchParams.get('registration_status_id');
        var currentPayment = url.searchParams.get('payment_status');
        if (currentId === String(registrationStatusId) && currentPayment === paymentKey) {
            url.searchParams.delete('payment_status');
        } else {
            url.searchParams.set('registration_status_id', registrationStatusId);
            url.searchParams.set('payment_status', paymentKey);
        }
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function initBikeRegistrationAccountFleetSlider() {
        var sliderTrack = document.getElementById('bikeSliderTrack');
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
        $('#bike_id').select2({
            dropdownParent: $('#createaccount'),
            placeholder: "Bike",
            allowClear: true
        });
        $('#payment_status').select2({
            dropdownParent: $('#searchModal'),
            placeholder: "Filter By Payment Status",
            allowClear: true
        });
        setTimeout(initBikeRegistrationAccountFleetSlider, 150);
    });

    $(document).on('click', '.js-delete-expense-account', function() {
        var url = $(this).data('delete-url');
        if (url) {
            confirmDelete(url);
        }
    });
</script>
@endsection