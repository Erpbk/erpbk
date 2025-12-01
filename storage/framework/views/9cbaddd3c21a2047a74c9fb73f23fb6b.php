<?php $__env->startSection('title','Riders'); ?>

<?php $__env->startPush('third_party_stylesheets'); ?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<link rel="stylesheet" href="<?php echo e(asset('css/riders-styles.css')); ?>">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="container-fluid">
        <!-- Enhanced Fleet Supervisor Accordion Section -->
        <div class="filter-tabs-section mb-4" id="filter-tabs-section">
            <div class="container-fluid d-flex justify-content-between">
                <?php
                $activeFiltersCount = count(request('rider_status', [])) + (request('balance_filter') ? 1 : 0);

                // Helper function to toggle rider status in URL
                function toggleRiderStatus($status) {
                $currentStatuses = request('rider_status', []);
                $newStatuses = $currentStatuses;

                if (in_array($status, $currentStatuses)) {
                // Remove the status
                $newStatuses = array_diff($currentStatuses, [$status]);
                } else {
                // Add the status
                $newStatuses[] = $status;
                }

                $queryParams = request()->query();
                $queryParams['rider_status'] = array_values($newStatuses);

                return request()->fullUrlWithQuery($queryParams);
                }

                // Helper function to toggle balance filter in URL
                function toggleBalanceFilter() {
                $queryParams = request()->query();

                if (request('balance_filter') == 'greater_than_zero') {
                unset($queryParams['balance_filter']);
                } else {
                $queryParams['balance_filter'] = 'greater_than_zero';
                }

                return request()->fullUrlWithQuery($queryParams);
                }
                ?>


                <div class="filter-tabs">
                    <?php if($activeFiltersCount > 0): ?>
                    <div class="filter-status">
                        <div class="filter-info">
                            <i class="ti ti-filter"></i>
                            <span><?php echo e($activeFiltersCount); ?> filter<?php echo e($activeFiltersCount > 1 ? 's' : ''); ?> applied</span>
                            <a href="<?php echo e(route('riders.index')); ?>" class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="ti ti-x"></i>
                                Clear All
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <a href="<?php echo e(route('riders.index')); ?>" class="filter-tab <?php echo e(!request('rider_status') && !request('balance_filter') ? 'active' : ''); ?>">
                        <i class="ti ti-users"></i>
                        All Riders
                    </a>
                </div>
                <div class="fleet-supervisor-header-right d-flex align-items-center">
                    <div class="action-buttons">
                        <div class="action-dropdown-container">
                            <button class="action-dropdown-btn" id="addRiderDropdownBtn">
                                <i class="ti ti-plus"></i>
                                <span>Add Rider</span>
                                <i class="ti ti-chevron-down"></i>
                            </button>
                            <div class="action-dropdown-menu" id="addRiderDropdown">
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rider_create')): ?>
                                <a class="action-dropdown-item" href="<?php echo e(route('riders.create')); ?>">
                                    <i class="ti ti-user-plus"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Create New Rider</div>
                                        <div class="action-dropdown-item-desc">Add a new rider to the system</div>
                                    </div>
                                </a>
                                <?php endif; ?>
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="sm" data-title="Import Today Attendance" data-action="<?php echo e(route('rider.attendance_import')); ?>">
                                    <i class="ti ti-calendar-check"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Import Today Attendance</div>
                                        <div class="action-dropdown-item-desc">Import attendance data for today</div>
                                    </div>
                                </a>
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="sm" data-title="Import Rider Activities" data-action="<?php echo e(route('rider.activities_import')); ?>">
                                    <i class="ti ti-activity"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Import Noon Activities</div>
                                        <div class="action-dropdown-item-desc">Import Noon rider activity data</div>
                                    </div>
                                </a>
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="sm" data-title="Import Keeta Rider Activities" data-action="<?php echo e(route('rider.keeta_activities_import')); ?>">
                                    <i class="ti ti-activity"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Import Keeta Activities</div>
                                        <div class="action-dropdown-item-desc">Import Keeta rider activity data</div>
                                    </div>
                                </a>
                                <a class="action-dropdown-item" href="<?php echo e(route('rider.exportRiders')); ?>">
                                    <i class="ti ti-file-export"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Export Riders</div>
                                        <div class="action-dropdown-item-desc">Export rider data to Excel</div>
                                    </div>
                                </a>
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="sm" data-title="Import Rider Vouchers" data-action="<?php echo e(route('riders.import_rider_vouchers', ['modal' => 1])); ?>">
                                    <i class="ti ti-file-spreadsheet"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Import Rider Vouchers (Page)</div>
                                        <div class="action-dropdown-item-desc">Open import modal</div>
                                    </div>
                                </a>
                                <a class="action-dropdown-item openColumnControlSidebar" href="javascript:void(0);" data-size="sm" data-title="Column Control">
                                    <i class="ti ti-columns"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Column Control</div>
                                        <div class="action-dropdown-item-desc">Open column control modal</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="fleet-supervisor-section">
            <div class="fleet-supervisor-accordion expanded" id="fleetSupervisorAccordion">
                <div class="fleet-supervisor-slider-container">
                    <div class="slider-controls">
                        <button class="slider-btn prev-btn" id="prevBtn" type="button">
                            <i class="ti ti-chevron-left"></i>
                        </button>
                        <div class="slider-indicators" id="sliderIndicators"></div>
                        <button class="slider-btn next-btn" id="nextBtn" type="button">
                            <i class="ti ti-chevron-right"></i>
                        </button>
                    </div>
                    <div class="fleet-supervisor-cards slider-track" id="sliderTrack">
                        <?php
                        $dropdown = DB::table('dropdowns')->where('label', 'Fleet Supervisor')->first();
                        $fleetSupervisors = $dropdown && $dropdown->values ? json_decode($dropdown->values, true) : [];
                        ?>

                        <?php $__currentLoopData = $fleetSupervisors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $fleet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="fleet-supervisor-card <?php if($fleet == request('fleet_supervisor')): ?> active filtered <?php endif; ?>" data-slide="<?php echo e($index); ?>" onclick="filterByFleetSupervisor('<?php echo e($fleet); ?>')">
                            <h3 class="fleet-supervisor-name"><?php echo e($fleet); ?></h3>
                            <div class="fleet-supervisor-stats">
                                <div class="fleet-stat active <?php if($fleet == request('fleet_supervisor') && in_array('active', request('rider_status', []))): ?> active-selected <?php endif; ?>" onclick="event.stopPropagation(); filterByStatus('<?php echo e($fleet); ?>', 'active')">
                                    <i class="fleet-stat-icon ti ti-user-check"></i>
                                    <span class="fleet-stat-label">Active</span>
                                    <span class="fleet-stat-value"><?php echo e(\App\Models\Riders::where('fleet_supervisor', $fleet)->where('status', 1)->whereHas('bikes', function($q) { $q->where('warehouse', 'Active'); })->count()); ?></span>
                                </div>
                                <div class="fleet-stat inactive <?php if($fleet == request('fleet_supervisor') && in_array('inactive', request('rider_status', []))): ?> active-selected <?php endif; ?>" onclick="event.stopPropagation(); filterByStatus('<?php echo e($fleet); ?>', 'inactive')">
                                    <i class="fleet-stat-icon ti ti-user-x"></i>
                                    <span class="fleet-stat-label">Inactive</span>
                                    <span class="fleet-stat-value"><?php echo e(\App\Models\Riders::where('fleet_supervisor', $fleet)->where(function($q) { $q->where('status', 3)->orWhereDoesntHave('bikes', function($bikeQuery) { $bikeQuery->where('warehouse', 'Active'); }); })->count()); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        <?php
                        // Absconder Counts
                        $absActiveCountSlider = \App\Models\Riders::where('absconder', 1)
                        ->where('status', 1)
                        ->whereHas('bikes', function($q) { $q->where('warehouse', 'Active'); })
                        ->count();
                        $absInactiveCountSlider = \App\Models\Riders::where('absconder', 1)
                        ->where(function($q){
                        $q->where('status', 3)
                        ->orWhereDoesntHave('bikes', function($b){ $b->where('warehouse','Active'); });
                        })
                        ->count();
                        $absFilterActive = !empty(request('absconder')) && in_array('1', (array) request('absconder'), true);
                        $absActiveSelectedSlider = $absFilterActive && in_array('active', request('rider_status', []));
                        $absInactiveSelectedSlider = $absFilterActive && in_array('inactive', request('rider_status', []));

                        // Learning License Counts
                        $llActiveCountSlider = \App\Models\Riders::where('l_license', 1)
                        ->where('status', 1)
                        ->whereHas('bikes', function($q) { $q->where('warehouse', 'Active'); })
                        ->count();
                        $llInactiveCountSlider = \App\Models\Riders::where('l_license', 1)
                        ->where(function($q){
                        $q->where('status', 3)
                        ->orWhereDoesntHave('bikes', function($b){ $b->where('warehouse','Active'); });
                        })
                        ->count();
                        $llActiveSelectedSlider = in_array('llicense', request('rider_status', [])) && in_array('active', request('rider_status', []));
                        $llInactiveSelectedSlider = in_array('llicense', request('rider_status', [])) && in_array('inactive', request('rider_status', []));

                        // Follow Up Counts
                        $fuActiveCountSlider = \App\Models\Riders::where('flowup', 1)
                        ->where('status', 1)
                        ->whereHas('bikes', function($q) { $q->where('warehouse', 'Active'); })
                        ->count();
                        $fuInactiveCountSlider = \App\Models\Riders::where('flowup', 1)
                        ->where(function($q){
                        $q->where('status', 3)
                        ->orWhereDoesntHave('bikes', function($b){ $b->where('warehouse','Active'); });
                        })
                        ->count();
                        $fuActiveSelectedSlider = in_array('followup', request('rider_status', [])) && in_array('active', request('rider_status', []));
                        $fuInactiveSelectedSlider = in_array('followup', request('rider_status', [])) && in_array('inactive', request('rider_status', []));

                        // Recovery Counts (balance > 0)
                        $recoveryActiveCountSlider = \App\Models\Riders::whereHas('account', function($q) {
                        $q->whereRaw('(SELECT COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) FROM transactions WHERE account_id = accounts.id) > 0');
                        })
                        ->where('status', 1)
                        ->whereHas('bikes', function($q) { $q->where('warehouse', 'Active'); })
                        ->count();
                        $recoveryInactiveCountSlider = \App\Models\Riders::whereHas('account', function($q) {
                        $q->whereRaw('(SELECT COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) FROM transactions WHERE account_id = accounts.id) > 0');
                        })
                        ->where(function($q){
                        $q->where('status', 3)
                        ->orWhereDoesntHave('bikes', function($b){ $b->where('warehouse','Active'); });
                        })
                        ->count();
                        $recoveryActiveSelectedSlider = request('balance_filter') === 'greater_than_zero' && in_array('active', request('rider_status', []));
                        $recoveryInactiveSelectedSlider = request('balance_filter') === 'greater_than_zero' && in_array('inactive', request('rider_status', []));
                        ?>

                        <div class="fleet-supervisor-card <?php echo e((!empty(request('absconder')) && in_array('1', (array) request('absconder'), true)) ? 'active filtered' : ''); ?>" onclick="filterAbsconderBoth()">
                            <h3 class="fleet-supervisor-name"><i class="ti ti-user-x"></i> Absconder</h3>
                            <div class="fleet-supervisor-stats">
                                <div class="fleet-stat active <?php echo e($absActiveSelectedSlider ? 'active-selected' : ''); ?>" onclick="event.stopPropagation(); filterAbsconderStatus('active')">
                                    <i class="fleet-stat-icon ti ti-user-check"></i>
                                    <span class="fleet-stat-label">Active</span>
                                    <span class="fleet-stat-value"><?php echo e($absActiveCountSlider); ?></span>
                                </div>
                                <div class="fleet-stat inactive <?php echo e($absInactiveSelectedSlider ? 'active-selected' : ''); ?>" onclick="event.stopPropagation(); filterAbsconderStatus('inactive')">
                                    <i class="fleet-stat-icon ti ti-user-x"></i>
                                    <span class="fleet-stat-label">Inactive</span>
                                    <span class="fleet-stat-value"><?php echo e($absInactiveCountSlider); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="fleet-supervisor-card <?php echo e((!empty(request('llicense')) && in_array('1', (array) request('llicense'), true)) ? 'active filtered' : (in_array('llicense', request('rider_status', [])) ? 'active filtered' : '')); ?>" onclick="filterLLicenseBoth()">
                            <h3 class="fleet-supervisor-name"><i class="ti ti-license"></i> Learning License</h3>
                            <div class="fleet-supervisor-stats">
                                <div class="fleet-stat active <?php echo e($llActiveSelectedSlider ? 'active-selected' : ''); ?>" onclick="event.stopPropagation(); filterLLicenseStatus('active')">
                                    <i class="fleet-stat-icon ti ti-user-check"></i>
                                    <span class="fleet-stat-label">Active</span>
                                    <span class="fleet-stat-value"><?php echo e($llActiveCountSlider); ?></span>
                                </div>
                                <div class="fleet-stat inactive <?php echo e($llInactiveSelectedSlider ? 'active-selected' : ''); ?>" onclick="event.stopPropagation(); filterLLicenseStatus('inactive')">
                                    <i class="fleet-stat-icon ti ti-user-x"></i>
                                    <span class="fleet-stat-label">Inactive</span>
                                    <span class="fleet-stat-value"><?php echo e($llInactiveCountSlider); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="fleet-supervisor-card <?php echo e((!empty(request('followup')) && in_array('1', (array) request('followup'), true)) ? 'active filtered' : (in_array('followup', request('rider_status', [])) ? 'active filtered' : '')); ?>" onclick="filterFollowUpBoth()">
                            <h3 class="fleet-supervisor-name"><i class="ti ti-phone-call"></i> Follow Up</h3>
                            <div class="fleet-supervisor-stats">
                                <div class="fleet-stat active <?php echo e($fuActiveSelectedSlider ? 'active-selected' : ''); ?>" onclick="event.stopPropagation(); filterFollowUpStatus('active')">
                                    <i class="fleet-stat-icon ti ti-user-check"></i>
                                    <span class="fleet-stat-label">Active</span>
                                    <span class="fleet-stat-value"><?php echo e($fuActiveCountSlider); ?></span>
                                </div>
                                <div class="fleet-stat inactive <?php echo e($fuInactiveSelectedSlider ? 'active-selected' : ''); ?>" onclick="event.stopPropagation(); filterFollowUpStatus('inactive')">
                                    <i class="fleet-stat-icon ti ti-user-x"></i>
                                    <span class="fleet-stat-label">Inactive</span>
                                    <span class="fleet-stat-value"><?php echo e($fuInactiveCountSlider); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="fleet-supervisor-card <?php echo e(request('balance_filter') === 'greater_than_zero' ? 'active filtered' : ''); ?>" onclick="filterRecoveryBoth()">
                            <h3 class="fleet-supervisor-name"><i class="ti ti-cash"></i> Recovery</h3>
                            <div class="fleet-supervisor-stats">
                                <div class="fleet-stat active <?php echo e($recoveryActiveSelectedSlider ? 'active-selected' : ''); ?>" onclick="event.stopPropagation(); filterRecoveryStatus('active')">
                                    <i class="fleet-stat-icon ti ti-user-check"></i>
                                    <span class="fleet-stat-label">Active</span>
                                    <span class="fleet-stat-value"><?php echo e($recoveryActiveCountSlider); ?></span>
                                </div>
                                <div class="fleet-stat inactive <?php echo e($recoveryInactiveSelectedSlider ? 'active-selected' : ''); ?>" onclick="event.stopPropagation(); filterRecoveryStatus('inactive')">
                                    <i class="fleet-stat-icon ti ti-user-x"></i>
                                    <span class="fleet-stat-label">Inactive</span>
                                    <span class="fleet-stat-value"><?php echo e($recoveryInactiveCountSlider); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fleet Supervisor Slider Script -->
        <script>
            // Fleet Supervisor Slider with boundary checking
            setTimeout(function() {
                console.log('Initializing fleet supervisor slider...');
                const sliderTrack = document.getElementById('sliderTrack');
                const prevBtn = document.getElementById('prevBtn');
                const nextBtn = document.getElementById('nextBtn');

                if (sliderTrack && prevBtn && nextBtn) {
                    console.log('All elements found, initializing slider...');

                    const cards = sliderTrack.querySelectorAll('.fleet-supervisor-card');
                    const totalCards = cards.length;
                    console.log('Found', totalCards, 'cards');

                    if (totalCards === 0) {
                        console.log('No cards found');
                        return;
                    }

                    let currentIndex = 0;
                    const cardWidth = 296; // 280px card width + 16px gap
                    const maxIndex = totalCards - 1;

                    // Update button states based on current position
                    function updateButtonStates() {
                        // Disable prev button if at first card
                        if (currentIndex === 0) {
                            prevBtn.style.opacity = '0.5';
                            prevBtn.style.pointerEvents = 'none';
                            prevBtn.disabled = true;
                        } else {
                            prevBtn.style.opacity = '1';
                            prevBtn.style.pointerEvents = 'auto';
                            prevBtn.disabled = false;
                        }

                        // Disable next button if at last card
                        if (currentIndex >= maxIndex) {
                            nextBtn.style.opacity = '0.5';
                            nextBtn.style.pointerEvents = 'none';
                            nextBtn.disabled = true;
                        } else {
                            nextBtn.style.opacity = '1';
                            nextBtn.style.pointerEvents = 'auto';
                            nextBtn.disabled = false;
                        }

                        console.log('Current index:', currentIndex, 'Max index:', maxIndex);
                    }

                    // Update slider position
                    function updateSlider() {
                        const translateX = -currentIndex * cardWidth;
                        sliderTrack.style.transform = `translateX(${translateX}px)`;
                        console.log('Moving to card', currentIndex, 'translateX:', translateX);
                        updateButtonStates();
                    }

                    // Next slide - move to next card
                    function nextSlide() {
                        if (currentIndex < maxIndex) {
                            currentIndex++;
                            updateSlider();
                        } else {
                            console.log('Already at last card, cannot go forward');
                        }
                    }

                    // Previous slide - move to previous card
                    function prevSlide() {
                        if (currentIndex > 0) {
                            currentIndex--;
                            updateSlider();
                        } else {
                            console.log('Already at first card, cannot go backward');
                        }
                    }

                    // Add click handlers with boundary checking
                    nextBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('Next button clicked, current index:', currentIndex);
                        nextSlide();
                    });

                    prevBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('Prev button clicked, current index:', currentIndex);
                        prevSlide();
                    });

                    // Initialize slider
                    updateSlider();

                    console.log('Fleet supervisor slider initialized successfully!');
                    console.log('Total cards:', totalCards, 'Max index:', maxIndex);

                } else {
                    console.log('Missing elements:', {
                        sliderTrack: !!sliderTrack,
                        prevBtn: !!prevBtn,
                        nextBtn: !!nextBtn
                    });
                }
            }, 500);
        </script>
        <!-- Filter Tabs Section -->

        <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
            <div class="filter-header">
                <h5>Filter Riders</h5>
                <button type="button" class="btn-close" id="closeSidebar"></button>
            </div>
            <div class="filter-body" id="searchTopbody">
                <form id="filterForm" action="<?php echo e(route('riders.index')); ?>" method="GET">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="id">Rider Id</label>
                            <input type="number" name="rider_id" class="form-control" placeholder="Filter By Rider ID" value="<?php echo e(request('rider_id')); ?>">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="courier_id">Courier ID</label>
                            <input type="number" name="courier_id" class="form-control" placeholder="Filter By Courier ID" value="<?php echo e(request('courier_id')); ?>">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="name">Rider Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Filter By Name" value="<?php echo e(request('name')); ?>">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="fleet_supervisor">Filter by Fleet SuperVisor</label>
                            <select class="form-control " id="fleet_supervisor" name="fleet_supervisor">
                                <?php
                                $supervisorRow = DB::table('dropdowns')
                                ->where('label', 'Fleet Supervisor')
                                ->whereNotNull('values')
                                ->first();
                                $fleetSupervisors = [];
                                if ($supervisorRow && $supervisorRow->values) {
                                $fleetSupervisors = json_decode($supervisorRow->values, true);
                                }
                                ?>
                                <option value="" selected>Select</option>
                                <?php $__currentLoopData = $fleetSupervisors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($supervisor); ?>" <?php echo e(request('fleet_supervisor') == $supervisor ? 'selected' : ''); ?>><?php echo e($supervisor); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="hub">Filter by HUB</label>
                            <select class="form-control " id="hub" name="hub">
                                <?php
                                $emirateHubs = DB::table('riders')
                                ->whereNotNull('designation')
                                ->where('designation', '!=', '')
                                ->select('designation')
                                ->distinct()
                                ->pluck('designation');
                                ?>
                                <option value="" selected>Select</option>
                                <?php $__currentLoopData = $emirateHubs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($hub); ?>" <?php echo e(request('hub') == $hub ? 'selected' : ''); ?>><?php echo e($hub); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="customer_id">Filter by Customer</label>
                            <select class="form-control " id="customer_id" name="customer_id">
                                <?php
                                $customerIds = DB::table('riders')
                                ->whereNotNull('customer_id')
                                ->where('customer_id', '!=', '')
                                ->pluck('customer_id')
                                ->unique();

                                $customers = DB::table('customers')
                                ->whereIn('id', $customerIds)
                                ->select('id', 'name')
                                ->get();
                                ?>
                                <option value="" selected>Select</option>
                                <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($customer->id); ?>" <?php echo e(request('customer_id') == $customer->id ? 'selected' : ''); ?>><?php echo e($customer->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="bike">Bike Number</label>
                            <input type="text" name="branded_plate_no" value="<?php echo e(request('bike')); ?>" class="form-control" placeholder="Filter By Bike Number">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="designation">Filter by Designation</label>
                            <select class="form-control " id="designation" name="designation">
                                <?php
                                $emiratedesignation = DB::table('riders')
                                ->whereNotNull('designation')
                                ->where('designation', '!=', '')
                                ->select('designation')
                                ->distinct()
                                ->pluck('designation');
                                ?>
                                <option value="" selected>Select</option>
                                <?php $__currentLoopData = $emiratedesignation; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $des): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($des); ?>" <?php echo e(request('designation') == $des ? 'selected' : ''); ?>><?php echo e($des); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="attandence">Filter by Attandence</label>
                            <select class="form-control " id="attendance" name="attendance">
                                <?php
                                $attandence = DB::table('riders')
                                ->whereNotNull('attendance')
                                ->where('attendance', '!=', '')
                                ->select('attendance')
                                ->distinct()
                                ->pluck('attendance');
                                ?>
                                <option value="" selected>Select</option>
                                <?php $__currentLoopData = $attandence; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($att); ?>" <?php echo e(request('attandence') == $att ? 'selected' : ''); ?>><?php echo e($att); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="bike_assignment_status">Filter by Bike Assignment</label>
                            <select class="form-control " id="bike_assignment_status" name="bike_assignment_status">
                                <option value="" selected>Select</option>
                                <option value="Active" <?php echo e(request('bike_assignment_status') == 'Active' ? 'selected' : ''); ?>>Active</option>
                                <option value="Inactive" <?php echo e(request('bike_assignment_status') == 'Inactive' ? 'selected' : ''); ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12 form-group text-center">
                            <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Filter Overlay -->
        <div id="filterOverlay" class="filter-overlay"></div>
</section>

<?php
use Illuminate\Support\Facades\Schema;
// Get all columns from riders table
$filteredColumns = Schema::getColumnListing('riders');

// Columns to exclude
$exclude = ['id', 'email', 'NFDID', 'cdm_deposit_id', 'DEPT', 'job_status', 'attach_documents', 'other_details', 'TAID', 'dob', 'mashreq_id', 'PID', 'branded_plate_no', 'vaccine_status', 'created_at', 'updated_at', 'VID', 'noon_no', 'contract', 'image_name', 'rider_reference', 'vat', 'attendance_date'];

// Final filtered columns
$dbColumns = array_diff($filteredColumns, $exclude);
$preferredOrder = [
'rider_id',
'name',
'company_contact',
'fleet_supervisor',
'emirate_hub',
'customer_id',
'designation',
'shift',
'attendance',
'status',
];

$columns = [];
$added = [];
$makeTitle = function ($key) {
$customTitles = [
'doj' => 'Date of Joining',
'recruiter_id' => 'Recruiter',
'l_license' => 'Learning',
];
return $customTitles[$key] ?? ucwords(str_replace('_', ' ', $key));
};

// If Absconder filter is active, make sure 'absconder' column is prioritized
if (in_array('absconder', (array) request('rider_status', []))) {
array_unshift($preferredOrder, 'absconder');
}

// Add preferred DB columns first
foreach ($preferredOrder as $key) {
if (in_array($key, $dbColumns)) {
$columns[] = ['data' => $key, 'title' => $makeTitle($key)];
$added[$key] = true;
}
}

// Add remaining DB columns
foreach ($dbColumns as $key) {
if (empty($added[$key])) {
$columns[] = ['data' => $key, 'title' => $makeTitle($key)];
}
}

// 3) Append special/computed columns used in UI
$columns = array_merge($columns, [
['data' => 'bike', 'title' => 'Bike'],
['data' => 'orders_sum', 'title' => 'Orders'],
['data' => 'days', 'title' => 'Days'],
['data' => 'balance', 'title' => 'Balance'],
['data' => 'action', 'title' => 'Actions'],
// Keep last two fixed utility columns for search and control icons
['data' => 'search', 'title' => 'Search'],
['data' => 'control', 'title' => 'Control'],
]);

$tableColumns = $columns;
?>
<?php echo $__env->make('components.column-control-panel', [
'tableColumns' => $tableColumns,
'exportRoute' => route('rider.exportCustomizableRiders'),
'tableIdentifier' => 'riders_table'
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<div class="content container-fluid">
    <?php echo $__env->make('flash::message', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="card-title">
                <!-- <h3>Riders</h3> -->
            </div>
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="<?php echo e(request('quick_search')); ?>">
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0">
            <div class="riders-table-container">
                <?php echo $__env->make('riders.table', ['data' => $data, 'tableColumns' => $tableColumns], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
            <div class="filter-loading-overlay" style="display: none;">
                <div class="filter-loading-content">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Applying filters...</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-script'); ?>
<script type="text/javascript">
    // Make filter tabs section sticky on scroll
    $(document).ready(function() {
        const filterTabsSection = document.getElementById('filter-tabs-section');
        const originalWidth = filterTabsSection.offsetWidth;
        const originalPosition = filterTabsSection.getBoundingClientRect();
        const parentElement = filterTabsSection.parentElement;
        const parentPadding = parseInt(window.getComputedStyle(parentElement).paddingLeft) || 0;

        window.addEventListener('scroll', function() {
            const parentRect = parentElement.getBoundingClientRect();
            if (parentRect.top < 0) {
                filterTabsSection.classList.add('filter-tabs-fixed');
                filterTabsSection.style.width = originalWidth + 'px';
                filterTabsSection.style.left = (parentRect.left + parentPadding) + 'px';
            } else {
                filterTabsSection.classList.remove('filter-tabs-fixed');
                filterTabsSection.style.width = '';
                filterTabsSection.style.left = '';
            }
        });
    });
    $(document).ready(function() {
        $('#fleet_supervisor').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Fleet SuperVisor",
            allowClear: true, // ✅ cross icon enable
        });
        $('#hub').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By HUB",
            allowClear: true, // ✅ cross icon enable
        });
        $('#customer_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Customer",
            allowClear: true, // ✅ cross icon enable
        });
        $('#designation').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Designation",
            allowClear: true, // ✅ cross icon enable
        });
        $('#bike_assignment_status').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true, // ✅ cross icon enable
            placeholder: "Filter By status",
        });
        $('#attendance').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Attandence",
            allowClear: true, // ✅ cross icon enable
        });
    });
</script>
<script type="text/javascript">
    $(document).ready(function() {
        // Filter sidebar functionality - open on hover
        $(document).on('mouseenter', '#openFilterSidebar, .openFilterSidebar', function(e) {
            e.preventDefault();
            console.log('Filter button hovered!'); // Debug line
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
            return false;
        });

        // Keep the original click handler for mobile devices
        $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
            e.preventDefault();
            console.log('Filter button clicked!'); // Debug line
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
            return false;
        });

        $('#closeSidebar, #filterOverlay').on('click', function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });
        $('#filterForm').on('submit', function(e) {
            // Let the form submit naturally - no need to prevent default
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });
        // Quick search input (main) - redirect to URL with search parameter
        $('#quickSearch').on('keyup', function(e) {
            if (e.keyCode === 13 || $(this).val().length === 0) {
                const searchValue = $(this).val();
                const url = new URL(window.location);

                if (searchValue) {
                    url.searchParams.set('quick_search', searchValue);
                } else {
                    url.searchParams.delete('quick_search');
                }

                window.location.href = url.toString();
            }
        });

        // Quick search input (sidebar) - redirect to URL with search parameter
        $('#quickSearchSidebar').on('keyup', function(e) {
            if (e.keyCode === 13 || $(this).val().length === 0) {
                const searchValue = $(this).val();
                const url = new URL(window.location);

                if (searchValue) {
                    url.searchParams.set('quick_search', searchValue);
                } else {
                    url.searchParams.delete('quick_search');
                }

                window.location.href = url.toString();
            }
        });
    });
</script>
<script>
    $(document).ready(function() {
        // Fleet Supervisor Accordion Toggle
        $('#fleetSupervisorToggle').on('click', function() {
            const accordion = $('#fleetSupervisorAccordion');
            const toggle = $(this);

            if (accordion.hasClass('expanded')) {
                accordion.removeClass('expanded').addClass('collapsed');
                toggle.addClass('collapsed');
            } else {
                accordion.removeClass('collapsed').addClass('expanded');
                toggle.removeClass('collapsed');
            }
        });

        // Add Rider Dropdown Toggle
        $('#addRiderDropdownBtn').on('click', function(e) {
            e.stopPropagation();
            const dropdown = $('#addRiderDropdown');
            const btn = $(this);

            if (dropdown.hasClass('show')) {
                dropdown.removeClass('show');
                btn.removeClass('open');
                // Reset inline styles when closing
                dropdown.css({
                    position: '',
                    top: '',
                    left: '',
                    right: '',
                    width: ''
                });
            } else {
                // Close other dropdowns
                $('.action-dropdown-menu').removeClass('show');
                $('.action-dropdown-btn').removeClass('open');
                // Show this dropdown
                dropdown.addClass('show');
                btn.addClass('open');

                // Reposition menu to be outside containers (viewport-level)
                const rect = btn[0].getBoundingClientRect();
                const menuWidth = Math.max(260, dropdown.outerWidth());
                const viewportWidth = window.innerWidth;
                const left = Math.min(rect.right - menuWidth, viewportWidth - menuWidth - 12);
                const top = rect.bottom + 8;

                // Use fixed positioning to escape any overflow:hidden ancestors
                dropdown.css({
                    position: 'fixed',
                    top: `${top}px`,
                    left: `${Math.max(12, left)}px`,
                    right: '',
                    width: `${menuWidth}px`,
                    'z-index': 3000
                });
            }
        });

        // Close dropdowns when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.action-dropdown-container').length) {
                $('.action-dropdown-menu').removeClass('show');
                $('.action-dropdown-btn').removeClass('open');
            }
        });

        // Close on scroll/resize to avoid misaligned fixed menu
        $(window).on('scroll resize', function() {
            const dropdown = $('#addRiderDropdown');
            if (dropdown.hasClass('show')) {
                dropdown.removeClass('show').css({
                    position: '',
                    top: '',
                    left: '',
                    right: '',
                    width: ''
                });
                $('#addRiderDropdownBtn').removeClass('open');
            }
        });

        // Fleet supervisor and balance filter cards now use direct links - no JavaScript needed
    });

    // Fleet supervisor filtering function - shows both active and inactive
    function filterByFleetSupervisor(fleetSupervisor) {
        const url = new URL(window.location);

        // Clear existing filters
        url.searchParams.delete('fleet_supervisor');
        url.searchParams.delete('rider_status');
        url.searchParams.delete('rider_status[]');
        url.searchParams.delete('absconder[]');

        // Set fleet supervisor filter
        url.searchParams.set('fleet_supervisor', fleetSupervisor);

        // Set both active and inactive status
        url.searchParams.append('rider_status[]', 'active');
        url.searchParams.append('rider_status[]', 'inactive');

        // Redirect to filtered URL
        window.location.href = url.toString();
    }

    // Fleet supervisor status filtering function - toggle specific status
    function filterByStatus(fleetSupervisor, status) {
        const url = new URL(window.location);
        const currentFleetSupervisor = url.searchParams.get('fleet_supervisor');
        const currentStatuses = url.searchParams.getAll('rider_status[]');

        // If clicking the same fleet supervisor and same status, toggle it off
        if (currentFleetSupervisor === fleetSupervisor && currentStatuses.includes(status)) {
            // Remove this specific status
            const newStatuses = currentStatuses.filter(s => s !== status);
            url.searchParams.delete('rider_status[]');
            newStatuses.forEach(s => url.searchParams.append('rider_status[]', s));

            // If no statuses left, remove fleet supervisor filter entirely
            if (newStatuses.length === 0) {
                url.searchParams.delete('fleet_supervisor');
            }
        } else {
            // Set fleet supervisor and specific status
            url.searchParams.set('fleet_supervisor', fleetSupervisor);
            url.searchParams.delete('rider_status[]');
            url.searchParams.set('rider_status[]', status);
        }

        // Redirect to filtered URL
        window.location.href = url.toString();
    }

    // Absconder filtering: clicking the card sets absconder + both statuses
    function filterAbsconderBoth() {
        const url = new URL(window.location);
        // Clear existing status params
        url.searchParams.delete('rider_status');
        url.searchParams.delete('rider_status[]');

        // Set absconder + both active/inactive using explicit param
        url.searchParams.append('absconder[]', '1');
        url.searchParams.append('rider_status[]', 'active');
        url.searchParams.append('rider_status[]', 'inactive');

        // Remove fleet supervisor filter to avoid conflict
        url.searchParams.delete('fleet_supervisor');

        window.location.href = url.toString();
    }

    // Toggle specific status under Absconder (enforce AND semantics with absconder)
    function filterAbsconderStatus(status) {
        const url = new URL(window.location);
        const currentStatuses = url.searchParams.getAll('rider_status[]');

        // Ensure absconder explicit param is present
        if (!url.searchParams.getAll('absconder[]').includes('1')) {
            // Reset to only absconder + clicked status
            url.searchParams.delete('rider_status');
            url.searchParams.delete('rider_status[]');
            url.searchParams.append('absconder[]', '1');
            url.searchParams.append('rider_status[]', status);
        } else {
            // Toggle the clicked status while keeping absconder
            const hasStatus = currentStatuses.includes(status);
            url.searchParams.delete('rider_status[]');
            // Always ensure absconder[] stays
            url.searchParams.append('absconder[]', '1');
            // Add the other status if present
            const other = status === 'active' ? 'inactive' : 'active';
            if (hasStatus) {
                // If toggling off and no other status present, leave only absconder
                if (currentStatuses.includes(other)) {
                    url.searchParams.append('rider_status[]', other);
                }
            } else {
                // Add clicked status
                url.searchParams.append('rider_status[]', status);
                // Preserve other if it existed
                if (currentStatuses.includes(other)) {
                    url.searchParams.append('rider_status[]', other);
                }
            }
        }

        // Remove fleet supervisor filter to avoid mixing contexts
        url.searchParams.delete('fleet_supervisor');

        window.location.href = url.toString();
    }

    // Learning License filtering
    function filterLLicenseBoth() {
        const url = new URL(window.location);
        url.searchParams.delete('rider_status');
        url.searchParams.delete('rider_status[]');
        url.searchParams.delete('absconder[]');
        url.searchParams.delete('followup[]');
        url.searchParams.append('llicense[]', '1');
        url.searchParams.append('rider_status[]', 'active');
        url.searchParams.append('rider_status[]', 'inactive');
        url.searchParams.delete('fleet_supervisor');
        window.location.href = url.toString();
    }

    function filterLLicenseStatus(status) {
        const url = new URL(window.location);
        const currentStatuses = url.searchParams.getAll('rider_status[]');
        if (!url.searchParams.getAll('llicense[]').includes('1')) {
            url.searchParams.delete('rider_status');
            url.searchParams.delete('rider_status[]');
            url.searchParams.delete('absconder[]');
            url.searchParams.delete('followup[]');
            url.searchParams.append('llicense[]', '1');
            url.searchParams.append('rider_status[]', status);
        } else {
            const hasStatus = currentStatuses.includes(status);
            const other = status === 'active' ? 'inactive' : 'active';
            url.searchParams.delete('rider_status[]');
            url.searchParams.append('llicense[]', '1');
            if (!hasStatus) url.searchParams.append('rider_status[]', status);
            if (currentStatuses.includes(other)) url.searchParams.append('rider_status[]', other);
        }
        url.searchParams.delete('fleet_supervisor');
        window.location.href = url.toString();
    }

    // Follow Up filtering
    function filterFollowUpBoth() {
        const url = new URL(window.location);
        url.searchParams.delete('rider_status');
        url.searchParams.delete('rider_status[]');
        url.searchParams.delete('absconder[]');
        url.searchParams.delete('llicense[]');
        url.searchParams.append('followup[]', '1');
        url.searchParams.append('rider_status[]', 'active');
        url.searchParams.append('rider_status[]', 'inactive');
        url.searchParams.delete('fleet_supervisor');
        window.location.href = url.toString();
    }

    function filterFollowUpStatus(status) {
        const url = new URL(window.location);
        const currentStatuses = url.searchParams.getAll('rider_status[]');
        if (!url.searchParams.getAll('followup[]').includes('1')) {
            url.searchParams.delete('rider_status');
            url.searchParams.delete('rider_status[]');
            url.searchParams.delete('absconder[]');
            url.searchParams.delete('llicense[]');
            url.searchParams.append('followup[]', '1');
            url.searchParams.append('rider_status[]', status);
        } else {
            const hasStatus = currentStatuses.includes(status);
            const other = status === 'active' ? 'inactive' : 'active';
            url.searchParams.delete('rider_status[]');
            url.searchParams.append('followup[]', '1');
            if (!hasStatus) url.searchParams.append('rider_status[]', status);
            if (currentStatuses.includes(other)) url.searchParams.append('rider_status[]', other);
        }
        url.searchParams.delete('fleet_supervisor');
        window.location.href = url.toString();
    }

    // Recovery filtering (balance_filter + active/inactive)
    function filterRecoveryBoth() {
        const url = new URL(window.location);
        url.searchParams.set('balance_filter', 'greater_than_zero');
        url.searchParams.delete('rider_status');
        url.searchParams.delete('rider_status[]');
        url.searchParams.append('rider_status[]', 'active');
        url.searchParams.append('rider_status[]', 'inactive');
        url.searchParams.delete('fleet_supervisor');
        window.location.href = url.toString();
    }

    function filterRecoveryStatus(status) {
        const url = new URL(window.location);
        url.searchParams.set('balance_filter', 'greater_than_zero');
        const currentStatuses = url.searchParams.getAll('rider_status[]');
        const hasStatus = currentStatuses.includes(status);
        const other = status === 'active' ? 'inactive' : 'active';
        url.searchParams.delete('rider_status[]');
        if (!hasStatus) url.searchParams.append('rider_status[]', status);
        if (currentStatuses.includes(other)) url.searchParams.append('rider_status[]', other);
        url.searchParams.delete('fleet_supervisor');
        window.location.href = url.toString();
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.querySelector('#dataTableBuilder');
        const headers = table.querySelectorAll('th.sorting');
        const tbody = table.querySelector('tbody');

        headers.forEach((header, colIndex) => {
            header.addEventListener('click', () => {
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const isAsc = header.classList.contains('sorted-asc');

                // Clear previous sort classes
                headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));

                // Add new sort direction
                header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');

                // Sort logic
                rows.sort((a, b) => {
                    let aText = a.children[colIndex]?.textContent.trim().toLowerCase();
                    let bText = b.children[colIndex]?.textContent.trim().toLowerCase();

                    const aVal = isNaN(aText) ? aText : parseFloat(aText);
                    const bVal = isNaN(bText) ? bText : parseFloat(bText);

                    if (aVal < bVal) return isAsc ? 1 : -1;
                    if (aVal > bVal) return isAsc ? -1 : 1;
                    return 0;
                });

                // Re-append sorted rows
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    });
    document.querySelector('.copy-text').addEventListener('click', function() {
        const value = this.querySelector('.copy-value').textContent.trim();
        const icon = this.querySelector('i');

        navigator.clipboard.writeText(value).then(() => {
            // Icon ko tick mark me change karo
            icon.classList.remove('fa-copy');
            icon.classList.add('fa-check');

            // 1.5 sec baad wapas copy icon me badal do
            setTimeout(() => {
                icon.classList.remove('fa-check');
                icon.classList.add('fa-copy');
            }, 1500);
        });
    });

    // Status filter functionality is now handled by direct URL links

    // Add CSS for enhanced interactions
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .ripple-effect {
                animation: ripple 0.6s ease-out;
            }
            
            @keyframes ripple {
                0% { transform: scale(1); }
                50% { transform: scale(1.02); }
                100% { transform: scale(1); }
            }
            
            .hover-effect {
                animation: hover-pulse 0.3s ease-out;
            }
            
            @keyframes hover-pulse {
                0% { transform: translateY(-2px); }
                100% { transform: translateY(-2px); }
            }
            
            .click-effect {
                animation: click-bounce 0.2s ease-out;
            }
            
            @keyframes click-bounce {
                0% { transform: scale(1); }
                50% { transform: scale(0.98); }
                100% { transform: scale(1); }
            }
            
            .select2-result-item {
                display: flex;
                align-items: center;
                padding: 8px 0;
            }
            
            .option-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .option-icon {
                font-size: 18px;
                filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
            }
            
            .option-text {
                font-weight: 500;
                color: #374151;
            }
            
            .select2-results__option--highlighted .option-text {
                color: white;
            }
            
            .select2-results__option[aria-selected=true] .option-text {
                color: white;
            }
            
            .custom-scrollbar {
                scrollbar-width: thin;
                scrollbar-color: #3b82f6 #f1f5f9;
            }
            
            /* Fleet supervisor active/inactive button highlighting */
            .fleet-stat.active-selected {
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                transform: scale(1.05);
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
                border: 2px solid #10b981;
            }
            
            .fleet-stat.active-selected .fleet-stat-icon {
                color: white;
            }
            
            .fleet-stat.active-selected .fleet-stat-label {
                color: white;
                font-weight: 600;
            }
            
            .fleet-stat.active-selected .fleet-stat-value {
                color: white;
                font-weight: 600;
            }
            
            /* Default Active button styling */
            .fleet-stat.active {
                background: linear-gradient(135deg, #d1fae5, #a7f3d0);
                border: 1px solid #10b981;
                color: #065f46;
            }
            
            .fleet-stat.active .fleet-stat-icon {
                color: #10b981;
            }
            
            .fleet-stat.active .fleet-stat-label {
                color: #065f46;
                font-weight: 500;
            }
            
            .fleet-stat.active .fleet-stat-value {
                color: #065f46;
                font-weight: 600;
            }
            
            /* Default Inactive button styling */
            .fleet-stat.inactive {
                background: linear-gradient(135deg, #fee2e2, #fecaca);
                border: 1px solid #ef4444;
                color: #991b1b;
            }
            
            .fleet-stat.inactive .fleet-stat-icon {
                color: #ef4444;
            }
            
            .fleet-stat.inactive .fleet-stat-label {
                color: #991b1b;
                font-weight: 500;
            }
            
            .fleet-stat.inactive .fleet-stat-value {
                color: #991b1b;
                font-weight: 600;
            }
            
            .fleet-stat:hover {
                background: rgba(16, 185, 129, 0.1);
                transform: translateY(-2px);
                transition: all 0.3s ease;
            }
            
            .fleet-stat {
                cursor: pointer;
                transition: all 0.3s ease;
                border-radius: 8px;
                padding: 8px 12px;
                margin: 4px 0;
            }
            
            /* Fleet supervisor card filtered state */
            .fleet-supervisor-card.filtered {
                background: linear-gradient(135deg, #e0f2fe, #b3e5fc);
                border: 2px solid #29b6f6;
                box-shadow: 0 4px 15px rgba(41, 182, 246, 0.2);
                transform: scale(1.02);
            }
            
            .fleet-supervisor-card.filtered .fleet-supervisor-name {
                color: #0277bd;
                font-weight: 600;
            }
            
            .fleet-supervisor-card.filtered .fleet-stat {
                background: rgba(255, 255, 255, 0.8);
                border-radius: 6px;
            }

            /* Fix Add Rider dropdown positioning in header */
            .fleet-supervisor-header-right { position: relative; overflow: visible; }
            .action-dropdown-container { position: static; }
            .action-dropdown-btn { display: inline-flex; align-items: center; gap: 8px; }
            .action-dropdown-menu {
                position: fixed; /* switched to fixed in JS when opened */
                min-width: 260px;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.12);
                padding: 8px 0;
                z-index: 3000;
                display: none;
            }
            .action-dropdown-menu.show { display: block; }
            .action-dropdown-item { display: flex; align-items: flex-start; gap: 12px; padding: 10px 14px; color: #111827; text-decoration: none; }
            .action-dropdown-item:hover { background: #f3f4f6; }
            .action-dropdown-item i { color: #2563eb; margin-top: 2px; }
            .action-dropdown-item-text { font-weight: 600; }
            .action-dropdown-item-desc { font-size: 12px; color: #6b7280; }
        `)
        .appendTo('head');

    function initFleetSupervisorSlider() {
        console.log('Initializing slider...');

        const sliderTrack = document.getElementById('sliderTrack');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const indicatorsContainer = document.getElementById('sliderIndicators');

        if (!sliderTrack) {
            console.log('sliderTrack not found');
            return;
        }

        if (!prevBtn) {
            console.log('prevBtn not found');
            return;
        }

        if (!nextBtn) {
            console.log('nextBtn not found');
            return;
        }

        if (!indicatorsContainer) {
            console.log('indicatorsContainer not found');
            return;
        }

        const cards = sliderTrack.querySelectorAll('.fleet-supervisor-card');
        console.log('Found cards:', cards.length);

        if (cards.length === 0) {
            console.log('No cards found');
            return;
        }

        let currentIndex = 0;

        // Simple next function
        function nextSlide() {
            if (currentIndex < cards.length - 1) {
                currentIndex++;
                updateSlider();
            }
        }

        // Simple prev function
        function prevSlide() {
            if (currentIndex > 0) {
                currentIndex--;
                updateSlider();
            }
        }

        // Update slider
        function updateSlider() {
            const translateX = -currentIndex * 300; // 300px per slide
            sliderTrack.style.transform = `translateX(${translateX}px)`;

            // Update buttons
            prevBtn.style.opacity = currentIndex === 0 ? '0.5' : '1';
            nextBtn.style.opacity = currentIndex >= cards.length - 1 ? '0.5' : '1';
        }

        // Event listeners
        nextBtn.onclick = function() {
            nextSlide();
        };

        prevBtn.onclick = function() {
            prevSlide();
        };

        // Initialize
        updateSlider();
    }

    // Initialize slider when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing slider...');
        setTimeout(initFleetSupervisorSlider, 100);
    });

    // Also try to initialize when window loads
    window.addEventListener('load', function() {
        console.log('Window loaded, initializing slider...');
        setTimeout(initFleetSupervisorSlider, 100);
    });

    // Enhanced notification function
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="ti ti-${type === 'success' ? 'check' : type === 'error' ? 'x' : 'info'}"></i>
                <span>${message}</span>
            </div>
        `;

        const colors = {
            success: '#10b981',
            error: '#ef4444',
            info: '#3b82f6'
        };

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${colors[type] || '#3b82f6'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease;
            max-width: 300px;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/riders/index.blade.php ENDPATH**/ ?>