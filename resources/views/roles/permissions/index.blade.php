@extends($layout ?? 'layouts.app')
@section('title', ($isCreate ?? false) ? 'Create New Role' : ('Role Permissions — ' . ($role->name ?? '')))

@section('content')
@php
$isCreate = $isCreate ?? false;
$companySlug = request()->route('company_slug') ?? session('company_slug');
$usersUrl = route('settings-panel.users.index', ['company_slug' => $companySlug]);
if ($isCreate) {
    $saveUrl = route('settings-panel.roles.permissions.store', ['company_slug' => $companySlug]);
    $fieldsUrlTemplate = route('settings-panel.roles.permissions.create-module-fields', ['company_slug' => $companySlug, 'module' => '__MODULE__']);
} else {
    $saveUrl = route('settings-panel.roles.permissions.save', ['company_slug' => $companySlug, 'role' => $role->id]);
    $fieldsUrlTemplate = route('settings-panel.roles.permissions.module-fields', ['company_slug' => $companySlug, 'role' => $role->id, 'module' => '__MODULE__']);
}
@endphp

<div class="rfp-page">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1">{{ $isCreate ? 'Create New Role' : 'Permissions' }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ $usersUrl }}">Administration</a></li>
                    <li class="breadcrumb-item active">
                        {{ $isCreate ? 'New Role' : ('Permissions — ' . $role->name) }}
                    </li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ $usersUrl }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back
            </a>
            <button type="button" id="rfpSaveBtn" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>{{ $isCreate ? 'Create Role' : 'Save Changes' }}
            </button>
        </div>
    </div>

    @if ($isCreate)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <label for="rfpRoleName" class="form-label fw-semibold required">Role Name</label>
            <input type="text" id="rfpRoleName" class="form-control form-control-lg" maxlength="255"
                placeholder="e.g. Fleet Manager, Accountant, HR Officer" autocomplete="off" required>
            <div class="invalid-feedback" id="rfpRoleNameError">Please enter a role name.</div>
            <small class="text-muted">Set the role name, then configure module and field permissions below.</small>
        </div>
    </div>
    @endif

    {{-- Summary bar --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap align-items-center gap-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rfp-donut" id="rfpDonut">
                    <span id="rfpPercent">{{ $summary['percent'] }}%</span>
                </div>
                <div>
                    <div class="fw-bold fs-5"><span id="rfpEnabled">{{ $summary['enabled'] }}</span> of <span id="rfpTotal">{{ $summary['total'] }}</span></div>
                    <div class="text-muted small">Permissions Enabled</div>
                </div>
            </div>
            <div class="vr d-none d-md-block"></div>
            <div class="btn-group rfp-filters" role="group" aria-label="Quick filters">
                <button type="button" class="btn btn-sm btn-primary" data-filter="all">All Modules</button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-filter="view">View</button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-filter="create">Create</button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-filter="edit">Edit</button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-filter="delete">Delete</button>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- LEFT: Module permissions --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <h5 class="mb-0 fw-bold">Module Permissions</h5>
                            <small class="text-muted">Control access to modules and actions.</small>
                        </div>
                    </div>
                    <div class="input-group input-group-sm mt-2">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" id="rfpModuleSearch" class="form-control" placeholder="Search module...">
                    </div>
                </div>
                <div class="card-body p-0">
                    {{-- Column header --}}
                    <div class="rfp-cols-head d-flex align-items-center px-3 py-2 border-bottom">
                        <div class="rfp-col-name">Module</div>
                        <div class="rfp-col-act">View</div>
                        <div class="rfp-col-act">Create</div>
                        <div class="rfp-col-act">Edit</div>
                        <div class="rfp-col-act">Delete</div>
                    </div>

                    <div id="rfpModulesBody" class="rfp-modules-scroll">
                        @php
                        $actions = ['view', 'create', 'edit', 'delete'];
                        $flatModules = collect($moduleRows)->where('is_flat', true)->values();
                        $groupedModules = collect($moduleRows)->where('is_flat', false)->values();
                        @endphp

                        {{-- Modules without submodules: grouped together in one card --}}
                        @if ($flatModules->isNotEmpty())
                        <div class="rfp-module-block is-grouped rfp-flat-card rfp-page-item">
                            <div class="rfp-module-head d-flex align-items-center px-3 py-2">
                                <div class="rfp-col-name fw-semibold text-uppercase small text-muted">General Modules</div>
                                <div class="rfp-col-act small text-muted">View</div>
                                <div class="rfp-col-act small text-muted">Create</div>
                                <div class="rfp-col-act small text-muted">Edit</div>
                                <div class="rfp-col-act small text-muted">Delete</div>
                            </div>
                            <div class="border-top">
                                @foreach ($flatModules as $m)
                                @php $sub = $m['submodules'][0] ?? ['actions' => []]; @endphp
                                <div class="rfp-flat-row rfp-sub-row rfp-selectable rfp-counter-scope d-flex align-items-center px-3 py-2"
                                    data-module-id="{{ $m['id'] }}"
                                    data-search="{{ \Illuminate\Support\Str::lower($m['name']) }}"
                                    data-has-fields="{{ $m['has_fields'] ? '1' : '0' }}">
                                    <div class="rfp-col-name d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-link p-0 text-start text-decoration-none rfp-module-select fw-medium">
                                            {{ $m['name'] }}
                                        </button>
                                        <span class="badge bg-label-secondary rfp-mod-counter" data-total="{{ $m['leaf_total'] }}">
                                            <span class="rfp-mod-enabled">{{ $m['leaf_enabled'] }}</span>/{{ $m['leaf_total'] }}
                                        </span>
                                        @if ($m['has_fields'])
                                        <i class="ti ti-list-details text-muted" title="{{ $m['field_count'] }} fields"></i>
                                        @endif
                                    </div>
                                    @foreach ($actions as $action)
                                    @php $act = $sub['actions'][$action] ?? null; @endphp
                                    <div class="rfp-col-act">
                                        @if ($act)
                                        <div class="form-check form-switch d-inline-flex justify-content-center m-0">
                                            <input type="checkbox" role="switch" class="form-check-input rfp-perm-toggle"
                                                data-action="{{ $action }}" data-perm-id="{{ $act['id'] }}"
                                                {{ $act['enabled'] ? 'checked' : '' }}>
                                        </div>
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Modules with submodules: one card each --}}
                        @foreach ($groupedModules as $m)
                        @php
                        $subNames = collect($m['submodules'])->pluck('name')->implode(' ');
                        $search = \Illuminate\Support\Str::lower($m['name'] . ' ' . $subNames);
                        @endphp
                        <div class="rfp-module-block is-grouped rfp-selectable rfp-counter-scope rfp-page-item"
                            data-module-id="{{ $m['id'] }}"
                            data-search="{{ $search }}"
                            data-has-fields="{{ $m['has_fields'] ? '1' : '0' }}">

                            <div class="rfp-module-head d-flex align-items-center px-3 py-2">
                                <div class="rfp-col-name d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-link p-0 text-start text-decoration-none rfp-module-select fw-semibold">
                                        {{ $m['name'] }}
                                    </button>
                                    <span class="badge bg-label-secondary rfp-mod-counter" data-total="{{ $m['leaf_total'] }}">
                                        <span class="rfp-mod-enabled">{{ $m['leaf_enabled'] }}</span>/{{ $m['leaf_total'] }}
                                    </span>
                                    @if ($m['has_fields'])
                                    <i class="ti ti-list-details text-muted" title="{{ $m['field_count'] }} fields"></i>
                                    @endif
                                </div>
                                <div class="rfp-col-act"></div>
                                <div class="rfp-col-act"></div>
                                <div class="rfp-col-act"></div>
                                <div class="rfp-col-act"></div>
                            </div>

                            <div class="rfp-submodules border-top">
                                <div class="rfp-sub-head d-flex align-items-center px-3 py-1">
                                    <div class="rfp-col-name">Submodule</div>
                                    <div class="rfp-col-act">View</div>
                                    <div class="rfp-col-act">Create</div>
                                    <div class="rfp-col-act">Edit</div>
                                    <div class="rfp-col-act">Delete</div>
                                </div>
                                @foreach ($m['submodules'] as $sub)
                                <div class="rfp-sub-row d-flex align-items-center px-3 py-2 {{ $sub['has_fields'] ? 'rfp-selectable' : '' }}"
                                    @if ($sub['has_fields']) data-module-id="{{ $sub['id'] }}" @endif>
                                    <div class="rfp-col-name ps-3 fw-medium">
                                        @if ($sub['has_fields'])
                                        <button type="button" class="btn btn-link p-0 text-start text-decoration-none rfp-module-select fw-medium">
                                            {{ $sub['name'] }}
                                        </button>
                                        <i class="ti ti-list-details text-muted ms-1" title="{{ $sub['field_count'] }} fields"></i>
                                        @else
                                        {{ $sub['name'] }}
                                        @endif
                                    </div>
                                    @foreach ($actions as $action)
                                    @php $act = $sub['actions'][$action] ?? null; @endphp
                                    <div class="rfp-col-act">
                                        @if ($act)
                                        <div class="form-check form-switch d-inline-flex justify-content-center m-0">
                                            <input type="checkbox" role="switch" class="form-check-input rfp-perm-toggle"
                                                data-action="{{ $action }}" data-perm-id="{{ $act['id'] }}"
                                                {{ $act['enabled'] ? 'checked' : '' }}>
                                        </div>
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <small class="text-muted" id="rfpModuleCount"></small>
                        <small class="text-muted"><i class="ti ti-info-circle me-1"></i>Scroll to see all modules</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT: Field permissions --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold">Field Permissions</h5>
                            <small class="text-muted">Define show-in-form, edit access and required fields per role.</small>
                        </div>
                        <span class="badge bg-label-primary" id="rfpSelectedModule">No module selected</span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mt-2 small text-muted rfp-legend">
                        <span><span class="rfp-dot bg-success"></span> Show in Form &amp; Editable</span>
                        <span><span class="rfp-dot bg-primary"></span> Show in Form (Read only)</span>
                        <span><span class="rfp-dot bg-secondary"></span> Hidden</span>
                    </div>
                    <div class="input-group input-group-sm mt-2">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" id="rfpFieldSearch" class="form-control" placeholder="Search field..." disabled>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="rfpFieldPanel" class="table-responsive">
                        <div class="text-center text-muted py-5">
                            <i class="ti ti-arrow-left fs-2 d-block mb-2"></i>
                            Select a module on the left to manage its field permissions.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rfp-page .rfp-donut {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: conic-gradient(var(--bs-primary) calc(var(--rfp-pct, 0) * 1%), #e9ecef 0);
        font-weight: 700;
        font-size: .8rem;
        position: relative;
    }

    .rfp-page .rfp-donut::before {
        content: "";
        position: absolute;
        inset: 8px;
        border-radius: 50%;
        background: var(--bs-card-bg, #fff);
    }

    .rfp-page .rfp-donut span {
        position: relative;
        z-index: 1;
    }

    .rfp-page .rfp-dot {
        display: inline-block;
        width: 9px;
        height: 9px;
        border-radius: 50%;
        margin-right: 3px;
    }

    .rfp-page .rfp-fields-table th {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--bs-secondary);
    }

    /* Grouped module blocks */
    .rfp-page .rfp-col-name {
        flex: 1 1 auto;
        min-width: 0;
    }

    .rfp-page .rfp-col-act {
        flex: 0 0 84px;
        width: 84px;
        text-align: center;
    }

    .rfp-page .rfp-cols-head,
    .rfp-page .rfp-sub-head {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--bs-secondary);
        font-weight: 600;
    }

    .rfp-page .rfp-module-block {
        border-bottom: 1px solid var(--bs-border-color);
    }

    .rfp-page .rfp-module-block.is-grouped {
        margin: .5rem;
        border: 1px solid var(--bs-border-color);
        border-radius: .5rem;
        overflow: hidden;
    }

    .rfp-page .rfp-module-block.is-grouped .rfp-module-head {
        background: var(--bs-tertiary-bg, #f8f9fa);
    }

    .rfp-page .rfp-sub-row+.rfp-sub-row {
        border-top: 1px solid var(--bs-border-color);
    }

    .rfp-page .rfp-sub-row:hover {
        background: rgba(105, 108, 255, .04);
    }

    .rfp-page .rfp-flat-row {
        cursor: pointer;
        transition: background .12s ease, box-shadow .12s ease;
    }

    .rfp-page .rfp-flat-row:hover {
        background: rgba(105, 108, 255, .04);
    }

    /* Selected module highlight (clear background + accent border) */
    .rfp-page .rfp-module-block.active {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 2px rgba(105, 108, 255, .25);
    }

    .rfp-page .rfp-module-block.active .rfp-module-head {
        background: rgba(105, 108, 255, .16);
    }

    .rfp-page .rfp-module-block.active .rfp-module-select {
        color: var(--bs-primary);
        font-weight: 700;
    }

    .rfp-page .rfp-flat-row.active,
    .rfp-page .rfp-sub-row.active {
        background: rgba(105, 108, 255, .16);
        box-shadow: inset 4px 0 0 var(--bs-primary);
    }

    .rfp-page .rfp-sub-row.active .rfp-module-select {
        color: var(--bs-primary);
        font-weight: 700;
    }

    .rfp-page .rfp-flat-row.active .rfp-module-select {
        color: var(--bs-primary);
        font-weight: 700;
    }

    .rfp-page .rfp-flat-card .rfp-module-head {
        background: var(--bs-tertiary-bg, #f8f9fa);
    }

    .rfp-page .rfp-module-select {
        color: inherit;
    }

    /* Scrollable module list so a large number of modules fits without breaking layout */
    .rfp-page .rfp-modules-scroll {
        max-height: 640px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .rfp-page #rfpFieldPanel {
        max-height: 640px;
        overflow-y: auto;
    }

    .rfp-page .rfp-modules-scroll::-webkit-scrollbar,
    .rfp-page #rfpFieldPanel::-webkit-scrollbar {
        width: 8px;
    }

    .rfp-page .rfp-modules-scroll::-webkit-scrollbar-thumb,
    .rfp-page #rfpFieldPanel::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .rfp-page .rfp-modules-scroll::-webkit-scrollbar-thumb:hover,
    .rfp-page #rfpFieldPanel::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    .rfp-page .rfp-mod-counter {
        font-weight: 600;
    }

    .rfp-page .rfp-fields-table code {
        font-size: .78rem;
    }

    .rfp-page .card-header {
        background: transparent;
    }
</style>
@endsection

@section('page-script')
<script>
    (function() {
        var RFP = {
            fieldsUrl: @json($fieldsUrlTemplate),
            saveUrl: @json($saveUrl),
            csrf: @json(csrf_token()),
            fieldStats: @json($fieldStats),
            moduleTotal: {{ (int) $summary['module_total'] }},
            isCreate: @json($isCreate),
            usersUrl: @json($usersUrl),
        };

        // Persisted (unsaved) field edits: { moduleId: { fieldName: {visible, editable, required} } }
        var fieldState = {};
        // Permission leaves the admin actually changed: key = permId => { id: permId, enabled: bool }
        var permChanges = {};
        var currentModuleId = null;
        var activeFilter = 'all';

        var $units = $('.rfp-page-item');

        function num(n) {
            return isNaN(n) ? 0 : n;
        }

        function moduleEnabledCount() {
            return $('.rfp-perm-toggle:checked').length;
        }

        function fieldTotals() {
            var total = 0,
                enabled = 0;
            for (var mid in RFP.fieldStats) {
                if (!RFP.fieldStats.hasOwnProperty(mid)) continue;
                total += num(RFP.fieldStats[mid].total);
                enabled += num(RFP.fieldStats[mid].enabled);
            }
            return {
                total: total,
                enabled: enabled
            };
        }

        function updateSummary() {
            var ft = fieldTotals();
            var total = RFP.moduleTotal + ft.total;
            var enabled = moduleEnabledCount() + ft.enabled;
            var pct = total > 0 ? Math.round((enabled / total) * 100) : 0;
            $('#rfpEnabled').text(enabled);
            $('#rfpTotal').text(total);
            $('#rfpPercent').text(pct + '%');
            $('#rfpDonut').css('--rfp-pct', pct);
        }

        // ---- Left panel: search / filter / pagination ----
        function matches($el, term) {
            var hay = ($el.attr('data-search') || '').toString();
            var matchesSearch = !term || hay.indexOf(term) !== -1;
            var matchesFilter = activeFilter === 'all' ||
                $el.find('.rfp-perm-toggle[data-action="' + activeFilter + '"]:checked').length > 0;
            return matchesSearch && matchesFilter;
        }

        // Filter individual flat rows inside the "General Modules" card.
        function refreshFlatRows(term) {
            $('.rfp-flat-card .rfp-flat-row').each(function() {
                $(this).toggle(matches($(this), term));
            });
        }

        function visibleUnits(term) {
            return $units.filter(function() {
                var $u = $(this);
                if ($u.hasClass('rfp-flat-card')) {
                    return $u.find('.rfp-flat-row:visible').length > 0;
                }
                return matches($u, term);
            });
        }

        // No pagination: every matching module is rendered inside the scrollable list.
        function renderPage() {
            var term = $('#rfpModuleSearch').val().toLowerCase().trim();
            refreshFlatRows(term);
            var $vis = visibleUnits(term);
            $units.hide();
            $vis.show();

            var moduleCount = $('.rfp-flat-card .rfp-flat-row:visible').length +
                $vis.filter(function() {
                    return !$(this).hasClass('rfp-flat-card');
                }).length;
            $('#rfpModuleCount').text(moduleCount + ' module' + (moduleCount === 1 ? '' : 's'));
        }

        $('#rfpModuleSearch').on('input', function() {
            renderPage();
        });

        $('.rfp-filters button').on('click', function() {
            activeFilter = $(this).data('filter');
            $('.rfp-filters button').removeClass('btn-primary').addClass('btn-outline-primary');
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');
            renderPage();
        });

        // ---- Module permission toggles ----
        function updateModuleCounter($scope) {
            var enabled = $scope.find('.rfp-perm-toggle:checked').length;
            $scope.find('.rfp-mod-enabled').first().text(enabled);
        }

        $(document).on('change', '.rfp-perm-toggle', function() {
            var $t = $(this);
            var permId = parseInt($t.attr('data-perm-id'), 10);
            if (!isNaN(permId)) {
                permChanges[permId] = {
                    ids: [permId],
                    enabled: this.checked
                };
            }
            updateModuleCounter($t.closest('.rfp-counter-scope'));
            updateSummary();
            if (activeFilter !== 'all') renderPage();
        });

        // ---- Right panel: load module fields ----
        function loadModuleFields(moduleId, moduleName) {
            moduleId = String(moduleId);
            currentModuleId = moduleId;
            $('.rfp-selectable').removeClass('active');
            $('.rfp-selectable[data-module-id="' + moduleId + '"]').addClass('active');
            $('#rfpSelectedModule').text(moduleName);
            $('#rfpFieldPanel').html('<div class="text-center text-muted py-5"><div class="spinner-border spinner-border-sm"></div> Loading fields...</div>');

            $.getJSON(RFP.fieldsUrl.replace('__MODULE__', moduleId))
                .done(function(res) {
                    $('#rfpFieldPanel').html(res.html);
                    // Re-apply any unsaved edits for this module.
                    applyStateToDom(moduleId);
                    syncModuleFieldStats(moduleId);
                    $('#rfpFieldSearch').prop('disabled', false).val('');
                    filterFields();
                })
                .fail(function() {
                    $('#rfpFieldPanel').html('<div class="text-center text-danger py-5">Failed to load fields.</div>');
                });
        }

        $(document).on('click', '.rfp-module-select', function(e) {
            e.preventDefault();
            var $sel = $(this).closest('.rfp-selectable');
            loadModuleFields($sel.attr('data-module-id'), $(this).text().trim());
        });

        // Clicking anywhere on a module row/header selects it (except on the toggles or the name button, which handle themselves).
        $(document).on('click', '.rfp-flat-row, .rfp-module-head', function(e) {
            if ($(e.target).closest('.form-check, .rfp-module-select').length) return;
            var $sel = $(this).closest('.rfp-selectable');
            var moduleId = $sel.attr('data-module-id');
            if (!$sel.length || moduleId == null || moduleId === '') return;
            loadModuleFields(moduleId, $sel.find('.rfp-module-select').first().text().trim());
        });

        // ---- Field permission rules ----
        // Use attr() (not jQuery .data()) so field names are never type-coerced
        // ("true"/"false"/numerics) and module ids stay stable string keys.
        function rowModuleId($tr) {
            return String($tr.attr('data-module-id') || '');
        }

        function rowFieldName($tr) {
            return String($tr.attr('data-field-name') || '');
        }

        function readRow($tr) {
            return {
                visible: $tr.find('.rfp-field-visible').is(':checked'),
                editable: $tr.find('.rfp-field-editable').is(':checked'),
                required: $tr.find('.rfp-field-required').is(':checked')
            };
        }

        function enforceRow($tr) {
            var visible = $tr.find('.rfp-field-visible').is(':checked');
            var $editable = $tr.find('.rfp-field-editable');
            var $required = $tr.find('.rfp-field-required');
            if (!visible) {
                // Remember editable intent so re-showing the field does not silently
                // leave it read-only (which then gets persisted and locks the form).
                if ($editable.attr('data-prev-editable') === undefined) {
                    $editable.attr('data-prev-editable', $editable.is(':checked') ? '1' : '0');
                }
                if ($required.attr('data-prev-required') === undefined) {
                    $required.attr('data-prev-required', $required.is(':checked') ? '1' : '0');
                }
                $editable.prop('checked', false).prop('disabled', true);
                $required.prop('checked', false).prop('disabled', true);
            } else {
                $editable.prop('disabled', false);
                $required.prop('disabled', false);
                var prevEditable = $editable.attr('data-prev-editable');
                if (prevEditable !== undefined) {
                    $editable.prop('checked', prevEditable === '1');
                    $editable.removeAttr('data-prev-editable');
                }
                var prevRequired = $required.attr('data-prev-required');
                if (prevRequired !== undefined) {
                    $required.prop('checked', prevRequired === '1');
                    $required.removeAttr('data-prev-required');
                }
            }
        }

        function storeRow($tr) {
            var mid = rowModuleId($tr);
            var name = rowFieldName($tr);
            if (!mid || !name) return;
            if (!fieldState[mid]) fieldState[mid] = {};
            fieldState[mid][name] = readRow($tr);
        }

        function applyStateToDom(moduleId) {
            var state = fieldState[String(moduleId)];
            if (!state) return;
            $('#rfpFieldPanel .rfp-field-row').each(function() {
                var $tr = $(this);
                var s = state[rowFieldName($tr)];
                if (!s) return;
                $tr.find('.rfp-field-visible').prop('checked', s.visible);
                $tr.find('.rfp-field-editable').prop('checked', s.editable);
                $tr.find('.rfp-field-required').prop('checked', s.required);
                $tr.find('.rfp-field-editable, .rfp-field-required')
                    .removeAttr('data-prev-editable')
                    .removeAttr('data-prev-required');
                enforceRow($tr);
            });
        }

        function syncModuleFieldStats(moduleId) {
            var enabled = 0,
                total = 0;
            $('#rfpFieldPanel .rfp-field-row').each(function() {
                var r = readRow($(this));
                total += 3;
                enabled += (r.visible ? 1 : 0) + (r.editable ? 1 : 0) + (r.required ? 1 : 0);
            });
            var mid = String(moduleId);
            if (total > 0 || !RFP.fieldStats[mid]) {
                RFP.fieldStats[mid] = {
                    total: total,
                    enabled: enabled
                };
            }
            updateSummary();
        }

        $(document).on('change', '.rfp-field-visible', function() {
            var $tr = $(this).closest('.rfp-field-row');
            enforceRow($tr);
            storeRow($tr);
            syncModuleFieldStats(rowModuleId($tr));
        });

        $(document).on('change', '.rfp-field-editable', function() {
            var $tr = $(this).closest('.rfp-field-row');
            if ($(this).is(':checked')) {
                $tr.find('.rfp-field-visible').prop('checked', true);
                $tr.find('.rfp-field-editable').removeAttr('data-prev-editable');
                enforceRow($tr);
            }
            storeRow($tr);
            syncModuleFieldStats(rowModuleId($tr));
        });

        $(document).on('change', '.rfp-field-required', function() {
            var $tr = $(this).closest('.rfp-field-row');
            if ($(this).is(':checked')) {
                $tr.find('.rfp-field-visible').prop('checked', true);
                $tr.find('.rfp-field-required').removeAttr('data-prev-required');
                enforceRow($tr);
            }
            storeRow($tr);
            syncModuleFieldStats(rowModuleId($tr));
        });

        // ---- Field search ----
        function filterFields() {
            var term = $('#rfpFieldSearch').val().toLowerCase().trim();
            $('#rfpFieldPanel .rfp-field-row').each(function() {
                var hay = ($(this).attr('data-field-label') || '').toString();
                $(this).toggle(!term || hay.indexOf(term) !== -1);
            });
        }
        $('#rfpFieldSearch').on('input', filterFields);

        // ---- Save ----
        $('#rfpSaveBtn').on('click', function() {
            var $btn = $(this);
            var roleName = '';

            if (RFP.isCreate) {
                roleName = ($('#rfpRoleName').val() || '').toString().trim();
                if (!roleName) {
                    $('#rfpRoleName').addClass('is-invalid').focus();
                    $('#rfpRoleNameError').text('Please enter a role name.').show();
                    return;
                }
                $('#rfpRoleName').removeClass('is-invalid');
                $('#rfpRoleNameError').hide();
            }

            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span>' +
                (RFP.isCreate ? 'Creating...' : 'Saving...')
            );

            // Only persist fields the admin actually changed. Re-saving every row from the
            // panel was overwriting untouched fields (e.g. locking middle fields as
            // read-only when only a custom field permission was edited).
            if (currentModuleId !== null) {
                $('#rfpFieldPanel .rfp-field-row').each(function() {
                    var $tr = $(this);
                    var mid = rowModuleId($tr);
                    var name = rowFieldName($tr);
                    if (fieldState[mid] && fieldState[mid].hasOwnProperty(name)) {
                        storeRow($tr);
                    }
                });
            }

            var permChangesList = [];
            for (var key in permChanges) {
                if (permChanges.hasOwnProperty(key)) permChangesList.push(permChanges[key]);
            }

            // On create, also capture every currently-checked toggle.
            if (RFP.isCreate) {
                $('.rfp-perm-toggle:checked').each(function() {
                    var permId = parseInt($(this).attr('data-perm-id'), 10);
                    if (!isNaN(permId) && !permChanges[permId]) {
                        permChangesList.push({ ids: [permId], enabled: true });
                    }
                });
            }

            var fields = [];
            for (var mid in fieldState) {
                if (!fieldState.hasOwnProperty(mid)) continue;
                for (var name in fieldState[mid]) {
                    if (!fieldState[mid].hasOwnProperty(name)) continue;
                    var s = fieldState[mid][name];
                    fields.push({
                        module_id: parseInt(mid, 10),
                        field_name: name,
                        visible: s.visible ? 1 : 0,
                        editable: s.editable ? 1 : 0,
                        required: s.required ? 1 : 0
                    });
                }
            }

            var payload = {
                _token: RFP.csrf,
                perm_changes: JSON.stringify(permChangesList),
                fields: JSON.stringify(fields)
            };
            if (RFP.isCreate) {
                payload.name = roleName;
            }

            $.ajax({
                url: RFP.saveUrl,
                method: 'POST',
                data: payload,
                dataType: 'json'
            }).done(function(res) {
                permChanges = {};
                if (typeof toastr !== 'undefined') {
                    toastr.success((res && res.message) || (RFP.isCreate ? 'Role created successfully.' : 'Permissions saved successfully.'));
                }
                if (RFP.isCreate) {
                    var dest = (res && res.redirect) ? res.redirect : RFP.usersUrl;
                    setTimeout(function() { window.location.href = dest; }, 600);
                }
            }).fail(function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to save permissions.';
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errs = xhr.responseJSON.errors;
                    if (errs.name && errs.name[0]) {
                        $('#rfpRoleName').addClass('is-invalid').focus();
                        $('#rfpRoleNameError').text(errs.name[0]).show();
                        msg = errs.name[0];
                    } else {
                        var first = Object.keys(errs)[0];
                        if (first && errs[first][0]) msg = errs[first][0];
                    }
                }
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                } else {
                    alert(msg);
                }
            }).always(function() {
                $btn.prop('disabled', false).html(
                    '<i class="ti ti-device-floppy me-1"></i>' +
                    (RFP.isCreate ? 'Create Role' : 'Save Changes')
                );
            });
        });

        if (RFP.isCreate) {
            $('#rfpRoleName').on('input', function() {
                $(this).removeClass('is-invalid');
                $('#rfpRoleNameError').hide();
            });
        }

        // ---- Init ----
        updateSummary();
        renderPage();
    })();
</script>
@endsection