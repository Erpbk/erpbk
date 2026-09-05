@extends('layouts.app')

@section('title','Visa Expenses')

@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
@endpush

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="mb-0">Visa Expense Accounts</h3>
            @can('visa_expense_create')
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createaccount">
                <i class="fa fa-plus me-1"></i> Create Expense Account
            </button>
            @endcan
        </div>
    </div>
</section>

<div class="content">
    @include('flash::message')

    @if(isset($visaHeadAccountConfigured) && ! $visaHeadAccountConfigured)
    <div class="alert alert-warning">
        Visa Expense chart account is not configured in Global Accounts. You can view accounts, but creating expenses or payments may fail until an administrator sets up <strong>VISA_EXPENSE_ACCOUNT</strong>.
    </div>
    @endif

    @if(isset($visaStatuses) && $visaStatuses->isNotEmpty())
    <div class="fleet-supervisor-section mb-3">
        <div class="fleet-supervisor-accordion expanded" id="visaStatusSliderAccordion">
            <div class="fleet-supervisor-slider-container visa-account-slider">
                <div class="slider-controls">
                    <button class="slider-btn prev-btn" id="visaPrevBtn" type="button" aria-label="Previous">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <div class="slider-indicators" id="visaSliderIndicators"></div>
                    <button class="slider-btn next-btn" id="visaNextBtn" type="button" aria-label="Next">
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
                <div class="fleet-supervisor-cards slider-track" id="visaSliderTrack">
                    @php $visaSlideIndex = 0; @endphp
                    @foreach($visaStatuses as $vs)
                    <div class="fleet-supervisor-card @if((int)request('visa_status_id') === (int)$vs->id) active filtered @endif"
                        data-slide="{{ $visaSlideIndex++ }}"
                        onclick="filterByVisaStatus('{{ $vs->id }}')">
                        <h3 class="fleet-supervisor-name">{{ $vs->name }}</h3>
                        @if(optional($vs->renewalCategory)->name)
                        <div class="small text-muted mb-1 text-truncate" title="{{ $vs->renewalCategory->name }}">{{ $vs->renewalCategory->name }}</div>
                        @endif
                        @if(isset($vs->description) && trim((string) $vs->description) !== '')
                        <div class="small text-muted mb-1 text-truncate" title="{{ $vs->description }}">{{ \Illuminate\Support\Str::limit($vs->description, 42) }}</div>
                        @endif
                        <div class="fleet-supervisor-stats">
                            <div class="fleet-stat active @if((int)request('visa_status_id') === (int)$vs->id && request('payment_status') === 'paid') active-selected @endif"
                                onclick="event.stopPropagation(); filterByVisaStatusPayment('{{ $vs->id }}', 'paid')">
                                <i class="fleet-stat-icon ti ti-circle-check"></i>
                                <span class="fleet-stat-label">Paid</span>
                                <span class="fleet-stat-value">{{ $visaStatusSliderCounts[$vs->id]['paid'] ?? 0 }}</span>
                            </div>
                            <div class="fleet-stat inactive @if((int)request('visa_status_id') === (int)$vs->id && request('payment_status') === 'unpaid') active-selected @endif"
                                onclick="event.stopPropagation(); filterByVisaStatusPayment('{{ $vs->id }}', 'unpaid')">
                                <i class="fleet-stat-icon ti ti-alert-circle"></i>
                                <span class="fleet-stat-label">Unpaid</span>
                                <span class="fleet-stat-value">{{ $visaStatusSliderCounts[$vs->id]['unpaid'] ?? 0 }}</span>
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
                    <form id="filterForm" action="{{ route('VisaExpense.index') }}" method="GET">
                        @if(request()->filled('visa_status_id'))
                        <input type="hidden" name="visa_status_id" value="{{ request('visa_status_id') }}">
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

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div></div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if(request()->filled('visa_status_id') || request()->filled('payment_status'))
                @php
                $clearQueryHeader = request()->query();
                unset($clearQueryHeader['visa_status_id'], $clearQueryHeader['payment_status'], $clearQueryHeader['page']);
                @endphp
                <a href="{{ route('VisaExpense.index', $clearQueryHeader) }}" class="btn btn-outline-secondary btn-sm">Clear status filters</a>
                @endif
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="fa fa-search"></i> Filter Accounts
                </button>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('visa_expenses.account_table', [
            'data' => $data,
            'nextUnpaidVisaByAccountId' => $nextUnpaidVisaByAccountId ?? [],
            'urgentVisaExpiryByAccountId' => $urgentVisaExpiryByAccountId ?? [],
            'riders' => $riders ?? collect(),
            'employees' => $employees ?? collect(),
            'personTargets' => $personTargets ?? \App\Support\CompanyModuleVisibility::simAssignTargets(),
            'allowPersonTypeSelection' => $allowPersonTypeSelection ?? false,
            'defaultPersonType' => $defaultPersonType ?? 'rider',
            ])
        </div>
    </div>
</div>

<div class="modal fade" id="createaccount" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Visa Expense Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @php
                    $personTargets = $personTargets ?? \App\Support\CompanyModuleVisibility::simAssignTargets();
                    $allowPersonTypeSelection = $allowPersonTypeSelection ?? (count($personTargets) >= 2);
                    $defaultPersonType = $defaultPersonType ?? (count($personTargets) === 1 ? $personTargets[0] : 'rider');
                    $riderLabel = \App\Support\CompanyModuleVisibility::customizedMenuLabel('riders') ?? 'Rider';
                    $employeeLabel = \App\Support\CompanyModuleVisibility::customizedMenuLabel('employees') ?? 'Employee';
                @endphp
                <form action="{{ route('VisaExpense.accountcreate') }}" method="POST" id="visaCreateAccountForm">
                    @csrf
                    <div class="row g-3">
                        @if(empty($personTargets))
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">No person modules are enabled for this company. Enable Riders and/or Employees in company settings.</div>
                        </div>
                        @else
                        @if($allowPersonTypeSelection)
                        <div class="col-12">
                            <label class="form-label d-block mb-2">Type</label>
                            <div class="btn-group w-100" role="group" aria-label="Person type">
                                @if(in_array('rider', $personTargets, true))
                                <input type="radio" class="btn-check" name="person_type" id="visa_person_type_rider" value="rider"
                                    {{ $defaultPersonType === 'rider' ? 'checked' : '' }} autocomplete="off">
                                <label class="btn btn-outline-primary" for="visa_person_type_rider">{{ $riderLabel }}</label>
                                @endif
                                @if(in_array('employee', $personTargets, true))
                                <input type="radio" class="btn-check" name="person_type" id="visa_person_type_employee" value="employee"
                                    {{ $defaultPersonType === 'employee' ? 'checked' : '' }} autocomplete="off">
                                <label class="btn btn-outline-primary" for="visa_person_type_employee">{{ $employeeLabel }}</label>
                                @endif
                            </div>
                        </div>
                        @elseif($defaultPersonType !== '')
                        <input type="hidden" name="person_type" id="visa_person_type_hidden" value="{{ $defaultPersonType }}">
                        @endif

                        @if(in_array('rider', $personTargets, true))
                        <div class="col-12 visa-person-field visa-person-field-rider{{ $defaultPersonType === 'employee' ? ' d-none' : '' }}">
                            <label for="visa_rider_id" class="form-label">Select {{ $riderLabel }}</label>
                            <select class="form-select visa-person-select" id="visa_rider_id" data-person-type="rider"
                                {{ $defaultPersonType === 'rider' ? 'required' : 'disabled' }}>
                                <option value="">Select</option>
                                @foreach($riders ?? [] as $r)
                                <option value="rider:{{ $r->id }}">{{ $r->rider_id }} - {{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if(in_array('employee', $personTargets, true))
                        <div class="col-12 visa-person-field visa-person-field-employee{{ $defaultPersonType === 'rider' ? ' d-none' : '' }}">
                            <label for="visa_employee_id" class="form-label">Select {{ $employeeLabel }}</label>
                            <select class="form-select visa-person-select" id="visa_employee_id" data-person-type="employee"
                                {{ $defaultPersonType === 'employee' ? 'required' : 'disabled' }}>
                                <option value="">Select</option>
                                @foreach($employees ?? [] as $e)
                                <option value="employee:{{ $e->id }}">{{ $e->employee_id }} - {{ $e->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <input type="hidden" name="person_key" id="person_key" value="">

                        <div class="col-12">
                            <label for="renewal_category_id" class="form-label">Visa Category</label>
                            <select class="form-select" id="renewal_category_id" name="renewal_category_id" required>
                                <option value="">Select person first</option>
                                @foreach($renewalCategories ?? [] as $cat)
                                <option value="{{ $cat->id }}" disabled>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" id="renewal_category_help">
                                Tickets are generated only from statuses in the selected visa category. Accounts must be created in category order.
                            </div>
                        </div>
                        @endif
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary" @if(empty($personTargets)) disabled @endif>Create</button>
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
    function filterByVisaStatus(visaStatusId) {
        var url = new URL(window.location.href);
        url.searchParams.delete('visa_status_id');
        url.searchParams.delete('payment_status');
        url.searchParams.set('visa_status_id', visaStatusId);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function filterByVisaStatusPayment(visaStatusId, paymentKey) {
        var url = new URL(window.location.href);
        var currentId = url.searchParams.get('visa_status_id');
        var currentPayment = url.searchParams.get('payment_status');
        if (currentId === String(visaStatusId) && currentPayment === paymentKey) {
            url.searchParams.delete('payment_status');
        } else {
            url.searchParams.set('visa_status_id', visaStatusId);
            url.searchParams.set('payment_status', paymentKey);
        }
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function initVisaAccountFleetSlider() {
        var sliderTrack = document.getElementById('visaSliderTrack');
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
        var $createModal = $('#createaccount');
        var $categorySelect = $('#renewal_category_id');
        var $categoryHelp = $('#renewal_category_help');
        var $personKey = $('#person_key');
        var $form = $('#visaCreateAccountForm');
        var eligibleCategoriesUrlTemplate = @json(route('VisaExpense.eligibleRenewalCategories', ['personType' => '__TYPE__', 'personId' => '__ID__']));
        var defaultPersonType = @json($defaultPersonType ?? 'rider');

        function currentPersonType() {
            var checked = $createModal.find('input[name="person_type"]:checked').val();
            if (checked) {
                return checked;
            }
            return $createModal.find('input[name="person_type"]').filter(':hidden').val() || defaultPersonType;
        }

        function activePersonSelect() {
            return $createModal.find('.visa-person-select[data-person-type="' + currentPersonType() + '"]');
        }

        function syncPersonKey() {
            var $select = activePersonSelect();
            var value = $select.length && !$select.prop('disabled') ? ($select.val() || '') : '';
            $personKey.val(value);
            return value;
        }

        function initPersonSelect2($select) {
            if (!$select.length) {
                return;
            }
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                dropdownParent: $createModal,
                placeholder: "Select",
                allowClear: true
            });
        }

        function applyPersonType(type) {
            $createModal.find('.visa-person-field').addClass('d-none');
            $createModal.find('.visa-person-select').each(function() {
                var $sel = $(this);
                var isActive = $sel.data('person-type') === type;
                $sel.prop('disabled', !isActive);
                $sel.prop('required', isActive);
                if (!isActive) {
                    $sel.val('').trigger('change');
                }
            });
            $createModal.find('.visa-person-field-' + type).removeClass('d-none');
            initPersonSelect2(activePersonSelect());
            syncPersonKey();
            loadEligibleCategories(syncPersonKey());
        }

        function initCategorySelect2() {
            if ($categorySelect.hasClass('select2-hidden-accessible')) {
                $categorySelect.select2('destroy');
            }
            $categorySelect.select2({
                dropdownParent: $createModal,
                placeholder: "Visa category",
                allowClear: true
            });
        }

        function resetCategorySelect() {
            $categorySelect.find('option').each(function() {
                var $opt = $(this);
                if ($opt.val() === '') {
                    $opt.prop('disabled', false).text('Select person first');
                } else {
                    $opt.prop('disabled', true);
                }
            });
            $categorySelect.val('').trigger('change');
            initCategorySelect2();
            $categoryHelp.text('Accounts must be created in category order. Tickets are generated only for statuses in the selected visa category.');
        }

        function applyEligibleCategories(categories) {
            $categorySelect.find('option').each(function() {
                var $opt = $(this);
                if ($opt.val() === '') {
                    $opt.prop('disabled', false).text(categories.length ? 'Select' : 'No category available');
                    return;
                }
                $opt.prop('disabled', true);
            });

            categories.forEach(function(cat) {
                $categorySelect.find('option[value="' + cat.id + '"]').prop('disabled', false);
            });

            if (categories.length === 0) {
                $categorySelect.val('').trigger('change');
                initCategorySelect2();
                $categoryHelp.text('This person cannot create a new account yet. Complete all unpaid entries in the current renewal category first, or all renewal categories already have accounts.');
                return;
            }

            if (categories.length === 1) {
                $categorySelect.val(String(categories[0].id));
                $categoryHelp.text('Next allowed category: ' + categories[0].name + '.');
            } else {
                $categorySelect.val('');
                $categoryHelp.text('Select the renewal category for this new expense account.');
            }

            initCategorySelect2();
            $categorySelect.trigger('change');
        }

        function loadEligibleCategories(personKey) {
            if (!personKey || personKey.indexOf(':') === -1) {
                resetCategorySelect();
                return;
            }

            var parts = personKey.split(':');
            var personType = parts[0];
            var personId = parts[1];
            $categoryHelp.text('Loading allowed categories…');

            $.ajax({
                url: eligibleCategoriesUrlTemplate
                    .replace('__TYPE__', encodeURIComponent(personType))
                    .replace('__ID__', encodeURIComponent(personId)),
                method: 'GET',
                dataType: 'json',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function(response) {
                applyEligibleCategories(response.categories || []);
            }).fail(function() {
                resetCategorySelect();
                $categoryHelp.text('Unable to load renewal categories. Please refresh the page and try again.');
            });
        }

        $createModal.on('change', 'input[name="person_type"]', function() {
            applyPersonType($(this).val());
        });

        $createModal.on('change select2:select select2:clear', '.visa-person-select', function() {
            loadEligibleCategories(syncPersonKey());
        });

        $form.on('submit', function(e) {
            var personKey = syncPersonKey();
            if (!personKey) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Select a person', text: 'Please select a rider or employee first.' });
                }
                return false;
            }
        });

        $createModal.on('shown.bs.modal', function() {
            applyPersonType(currentPersonType());
        });

        $createModal.on('hidden.bs.modal', function() {
            $createModal.find('.visa-person-select').val('').trigger('change');
            $personKey.val('');
            resetCategorySelect();
            if ($createModal.find('input[name="person_type"][type="radio"]').length) {
                $createModal.find('input[name="person_type"][value="' + defaultPersonType + '"]').prop('checked', true);
            }
        });

        applyPersonType(currentPersonType());
        resetCategorySelect();

        $('#payment_status').select2({
            dropdownParent: $('#searchModal'),
            placeholder: "Filter By Payment Status",
            allowClear: true
        });
        setTimeout(initVisaAccountFleetSlider, 150);
    });

    $(document).on('change', '.visa-edit-person-type', function() {
        var $modal = $(this).closest('.modal');
        var type = $(this).val();
        $modal.find('.visa-edit-person-field').addClass('d-none');
        $modal.find('.visa-edit-person-select').each(function() {
            var $sel = $(this);
            var isActive = $sel.data('person-type') === type;
            $sel.prop('disabled', !isActive);
            $sel.prop('required', isActive);
            if (!isActive) {
                $sel.val('');
            }
        });
        $modal.find('.visa-edit-person-field-' + type).removeClass('d-none');
        var $active = $modal.find('.visa-edit-person-select[data-person-type="' + type + '"]');
        $modal.find('.visa-edit-person-key').val($active.val() || '');
    });

    $(document).on('change', '.visa-edit-person-select', function() {
        var $modal = $(this).closest('.modal');
        if ($(this).prop('disabled')) {
            return;
        }
        $modal.find('.visa-edit-person-key').val($(this).val() || '');
    });

    $(document).on('submit', '.visa-edit-account-form', function(e) {
        var $form = $(this);
        var $active = $form.find('.visa-edit-person-select:not(:disabled)');
        var value = $active.val() || '';
        $form.find('.visa-edit-person-key').val(value);
        if (!value) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Select a person', text: 'Please select a rider or employee first.' });
            }
            return false;
        }
    });

    $(document).on('click', '.js-delete-expense-account', function() {
        var url = $(this).data('delete-url');
        if (url) {
            confirmDelete(url);
        }
    });
</script>
@endsection