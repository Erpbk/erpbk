@php
  $filterAction = $filterAction ?? route('attendance.index');
  $typeName = $typeName ?? 'ref_type';
  $userName = $userName ?? 'ref_id';
  $selectedType = strtolower((string) ($selectedType ?? request($typeName) ?? request('user_type') ?? request('ref_type') ?: 'employee'));
  $selectedUser = $selectedUser ?? request($userName);
  $selectedStatus = $selectedStatus ?? request('status');
  $selectedDate = $selectedDate ?? request('date');
  $fromDate = $fromDate ?? request('from_date');
  $toDate = $toDate ?? request('to_date');
  $dateFieldName = $dateFieldName ?? 'date';
  $projects = $projects ?? collect();
  $projectId = $projectId ?? request('project_id');
  $fleetSupervisors = $fleetSupervisors ?? collect();
  $fleetSupervisor = $fleetSupervisor ?? request('fleet_supervisor');
  $hiddenFields = $hiddenFields ?? [];
  $showMonth = $showMonth ?? false;
  $monthValue = $monthValue ?? '';
  $exportUrl = $exportUrl ?? null;
  $exportLabel = $exportLabel ?? 'Export';
  $resetUrl = $resetUrl ?? $filterAction;
  if (is_string($selectedDate) && preg_match('/^\d{4}-\d{2}$/', $selectedDate)) {
      $selectedDate = '';
  }
@endphp
<style>
  .attendance-filter-bar {
    position: relative;
    overflow: visible;
  }
  .attendance-filter-bar .select2-container {
    width: 100% !important;
  }
  .attendance-filter-bar .select2-container .select2-selection--single {
    height: 38px;
  }
  .attendance-filter-bar .select2-container .select2-selection__rendered {
    line-height: 36px;
    padding-left: 12px;
  }
  .attendance-filter-bar .select2-container .select2-selection__arrow {
    height: 36px;
  }
  .select2-container--open {
    z-index: 9999;
  }
  .select2-dropdown.attendance-filter-select2-dropdown {
    z-index: 10000;
  }
</style>
<div class="attendance-filter-bar">
    <form id="filterForm" action="{{ $filterAction }}" method="GET" data-rfp-skip-lock="1">
        @foreach($hiddenFields as $hiddenName => $hiddenValue)
        @if($hiddenName !== 'date' || !$showMonth)
        <input type="hidden" name="{{ $hiddenName }}" value="{{ $hiddenValue }}">
        @endif
        @endforeach
        <input type="hidden" id="filter_user_type" name="{{ $typeName }}" value="{{ $selectedType }}">
        <div class="row g-2 align-items-end">
            @if($showMonth)
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <label class="form-label fw-semibold mb-1" for="filter_month">Month</label>
                <input type="month" id="filter_month" name="date" class="form-control" value="{{ $monthValue }}">
            </div>
            @endif
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <label class="form-label fw-semibold mb-1" for="filter_date">Date</label>
                <input type="date" id="filter_date" name="{{ $dateFieldName }}" class="form-control" value="{{ $selectedDate }}">
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <label class="form-label fw-semibold mb-1" for="filter_user_id">User</label>
                <select class="form-select attendance-filter-select" id="filter_user_id" name="{{ $userName }}" data-placeholder="All Users">
                    <option value="">All Users</option>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <label class="form-label fw-semibold mb-1" for="filter_status">Status</label>
                <select class="form-select attendance-filter-select" id="filter_status" name="status" data-placeholder="All Status">
                    <option value="">All Status</option>
                    <option value="present" {{ $selectedStatus == 'present' ? 'selected' : '' }}>Present</option>
                    <option value="absent" {{ $selectedStatus == 'absent' ? 'selected' : '' }}>Absent</option>
                    <option value="late" {{ $selectedStatus == 'late' ? 'selected' : '' }}>Late</option>
                    <option value="half day" {{ in_array($selectedStatus, ['half day', 'half-day'], true) ? 'selected' : '' }}>Half Day</option>
                    <option value="weekend" {{ $selectedStatus == 'weekend' ? 'selected' : '' }}>Weekend</option>
                    <option value="on leave" {{ $selectedStatus == 'on leave' ? 'selected' : '' }}>On Leave</option>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <label class="form-label fw-semibold mb-1" for="filter_project_id">Project</label>
                <select class="form-select attendance-filter-select" id="filter_project_id" name="project_id" data-placeholder="All Projects">
                    <option value="">All Projects</option>
                    @foreach($projects as $project)
                    <option value="{{ $project->id }}" {{ (string) $projectId === (string) $project->id ? 'selected' : '' }}>
                        {{ $project->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <label class="form-label fw-semibold mb-1" for="filter_fleet_supervisor">Fleet Supervisor</label>
                <select class="form-select attendance-filter-select" id="filter_fleet_supervisor" name="fleet_supervisor" data-placeholder="All Supervisors">
                    <option value="">All Supervisors</option>
                    @foreach($fleetSupervisors as $supervisor)
                    <option value="{{ $supervisor }}" {{ (string) $fleetSupervisor === (string) $supervisor ? 'selected' : '' }}>
                        {{ $supervisor }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <label class="form-label fw-semibold mb-1" for="filter_from_date">From Date</label>
                <input type="date" id="filter_from_date" name="from_date" class="form-control" value="{{ $fromDate }}">
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <label class="form-label fw-semibold mb-1" for="filter_to_date">To Date</label>
                <input type="date" id="filter_to_date" name="to_date" class="form-control" value="{{ $toDate }}">
            </div>
            <div class="col-12 col-lg-auto">
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-filter me-1"></i> Filter
                    </button>
                    <a href="{{ $resetUrl }}" class="btn btn-outline-secondary">Reset</a>
                    @if($exportUrl)
                    <a href="{{ $exportUrl }}" class="btn btn-success" target="_blank">
                        <i class="fas fa-file-excel me-1"></i>{{ $exportLabel }}
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>
@once
@push('page-scripts')
<script>
    (function() {
        var usersUrl = @json(route('attendance.users', ['refType' => '__TYPE__']));
        var selectedUserId = @json((string) ($selectedUser ?? ''));
        var userType = @json((string) $selectedType);

        function initFilterSelect2($selects) {
            $selects.each(function() {
                var $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                $el.next('.select2-container').remove();
                $el.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $el.data('placeholder') || 'Select',
                    dropdownParent: $(document.body),
                    dropdownCssClass: 'attendance-filter-select2-dropdown',
                    minimumResultsForSearch: $el.is('#filter_status') ? 20 : 0,
                    language: {
                        noResults: function() {
                            if ($el.is('#filter_project_id')) {
                                return 'No projects found';
                            }
                            if ($el.is('#filter_fleet_supervisor')) {
                                return 'No supervisors found';
                            }
                            return 'No results found';
                        }
                    }
                });
            });
        }

        function loadFilterUsers(refType, selectedId) {
            var $user = $('#filter_user_id');
            if ($user.hasClass('select2-hidden-accessible')) {
                $user.select2('destroy');
            }
            $user.next('.select2-container').remove();

            if (!refType) {
                $user.html('<option value="">All Users</option>');
                initFilterSelect2($user);
                return;
            }

            $user.html('<option value="">All Users</option>');
            $.get(usersUrl.replace('__TYPE__', encodeURIComponent(refType)))
                .done(function(users) {
                    $user.html('<option value="">All Users</option>');
                    if (selectedId === 'all') {
                        selectedId = '';
                    }
                    $.each(users || [], function(_, user) {
                        var selected = String(selectedId) === String(user.id) ? ' selected' : '';
                        $user.append('<option value="' + user.id + '"' + selected + '>' + user.name + '</option>');
                    });
                    initFilterSelect2($user);
                    if (selectedId) {
                        $user.val(String(selectedId)).trigger('change');
                    }
                })
                .fail(function() {
                    $user.html('<option value="">All Users</option>');
                    initFilterSelect2($user);
                });
        }

        $(function() {
            var $form = $('#filterForm');
            initFilterSelect2($form.find('select.attendance-filter-select').not('#filter_user_id'));
            loadFilterUsers(userType, selectedUserId);

            $form.on('submit', function() {
                $(this).find('input[type="date"], select').each(function() {
                    if ($(this).attr('name') && !$(this).val()) {
                        $(this).prop('disabled', true);
                    }
                });
            });
        });
    })();
</script>
@endpush
@endonce
