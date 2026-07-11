@php
    $permissionModules = $permissionModules ?? [];
    $rolePermissions = $rolePermissions ?? [];
    $standardActions = ['view', 'create', 'edit', 'delete'];
    $actionColumns = array_map('ucfirst', $standardActions);

    $directModules = collect($permissionModules)->filter(fn ($m) => count($m['submodules']) === 0)->values();
    $groupedModules = collect($permissionModules)->filter(fn ($m) => count($m['submodules']) > 0)->values();

    $directModulePermTotal = $directModules->sum(fn ($m) => count($m['leaves']));
@endphp

<div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
  <div class="d-flex align-items-center gap-2">
      <span class="fw-semibold small text-secondary">Permissions</span>
      <span class="badge bg-light text-dark border rounded-pill px-2 py-0 small" id="progressText4">0%</span>
      <div class="progress" style="width: 100px; height: 3px;">
          <div class="progress-bar bg-primary" id="permissionProgress8" style="width: 0%;"></div>
      </div>
  </div>
  <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="badge bg-light text-dark border px-3 py-1 rounded-pill">
          <i class="bi bi-check2-circle text-primary me-1"></i>
          <span class="fw-semibold" id="totalPermissions13">0</span>
      </span>
      <span class="badge bg-light text-dark border px-3 py-1 rounded-pill">
          <i class="bi bi-grid-3x3-gap text-secondary me-1"></i>
          <span class="fw-semibold" id="totalModules7">0</span>
      </span>
      <label class="permission-toggle form-check form-switch m-0 d-inline-flex align-items-center gap-2" title="Select all permissions">
          <input type="checkbox" class="form-check-input" id="selectAllPermissions7" role="switch">
          <span class="small fw-semibold text-secondary">All</span>
      </label>
  </div>
</div>

<div class="permissions-modules-list">
    @if($directModules->isNotEmpty())
        <div class="card border shadow-sm mb-3 module-card module-card-grouped">
            <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark" style="font-size: 0.85rem;">Modules</span>
                <span class="badge bg-light text-dark border rounded-pill px-2 py-0 small">
                    <span id="direct-modules-checked">0</span>
                    <span class="text-muted">/{{ $directModulePermTotal }}</span>
                </span>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm table-borderless permission-matrix-table mb-0 align-middle">
                        <thead>
                            <tr class="border-bottom">
                                <th class="small text-secondary fw-semibold ps-0" style="min-width: 140px;">Module</th>
                                @foreach($actionColumns as $actionLabel)
                                    <th class="small text-center fw-semibold text-secondary" style="min-width: 72px;">{{ $actionLabel }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($directModules as $moduleData)
                                @php
                                    $moduleKey = 'module-' . \Illuminate\Support\Str::slug($moduleData['moduleName']);
                                    $totalPerms = count($moduleData['leaves']);
                                    $leavesByAction = collect($moduleData['leaves'])->keyBy(fn ($leaf) => strtolower($leaf['name']));
                                @endphp
                                <tr class="border-bottom permission-module-row" data-module-key="{{ $moduleKey }}">
                                    <td class="small ps-0 py-2">
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <span class="fw-bold text-dark">{{ $moduleData['moduleName'] }}</span>
                                            <span class="badge bg-light text-dark border rounded-pill px-2 py-0 small">
                                                <span class="module-count" data-module-key="{{ $moduleKey }}">0</span>
                                                <span class="text-muted">/{{ $totalPerms }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    @foreach($standardActions as $action)
                                        <td class="text-center py-2">
                                            @if($leavesByAction->has($action))
                                                @php $leaf = $leavesByAction->get($action); $isChecked = isset($rolePermissions[$leaf['id']]); @endphp
                                                @include('roles.partials.permission_toggle', [
                                                    'permissionId' => $leaf['id'],
                                                    'permData' => $leaf,
                                                    'moduleKey' => $moduleKey,
                                                    'isChecked' => $isChecked,
                                                ])
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @forelse($groupedModules as $moduleData)
        @php
            $moduleKey = 'module-' . \Illuminate\Support\Str::slug($moduleData['moduleName']);
            $moduleLeaves = collect($moduleData['leaves']);
            foreach ($moduleData['submodules'] as $submoduleData) {
                $moduleLeaves = $moduleLeaves->merge($submoduleData['leaves']);
            }
            $totalPerms = $moduleLeaves->count();
        @endphp
        <div class="card border shadow-sm mb-3 module-card module-card-has-submodules" data-module-key="{{ $moduleKey }}">
            <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $moduleData['moduleName'] }}</span>
                <span class="badge bg-light text-dark border rounded-pill px-2 py-0 small" id="count-{{ $moduleKey }}">
                    <span class="module-count" data-module-key="{{ $moduleKey }}">0</span>
                    <span class="text-muted">/{{ $totalPerms }}</span>
                </span>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm table-borderless permission-matrix-table mb-0 align-middle">
                        <thead>
                            <tr class="border-bottom">
                                <th class="small text-secondary fw-semibold ps-0" style="min-width: 140px;">Submodule</th>
                                @foreach($actionColumns as $actionLabel)
                                    <th class="small text-center fw-semibold text-secondary" style="min-width: 72px;">{{ $actionLabel }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($moduleData['submodules'] as $submoduleData)
                                @php
                                    $leavesByAction = collect($submoduleData['leaves'])->keyBy(fn ($leaf) => strtolower($leaf['name']));
                                @endphp
                                <tr class="border-bottom" data-module-key="{{ $moduleKey }}">
                                    <td class="small fw-bold text-dark ps-0 py-2">
                                        {{ $submoduleData['name'] }}
                                    </td>
                                    @foreach($standardActions as $action)
                                        <td class="text-center py-2">
                                            @if($leavesByAction->has($action))
                                                @php $leaf = $leavesByAction->get($action); $isChecked = isset($rolePermissions[$leaf['id']]); @endphp
                                                @include('roles.partials.permission_toggle', [
                                                    'permissionId' => $leaf['id'],
                                                    'permData' => $leaf,
                                                    'moduleKey' => $moduleKey,
                                                    'isChecked' => $isChecked,
                                                ])
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        @if($directModules->isEmpty())
            <div class="text-center text-muted py-4 small">No permissions found.</div>
        @endif
    @endforelse
</div>

<div class="d-flex align-items-center justify-content-between mt-2">
  <span class="text-muted small">
      <span class="fw-semibold text-dark" id="totalCheckedText7">0</span> selected
  </span>
  <span class="text-muted small">
      <i class="bi bi-toggles me-1"></i> Toggle to enable permission
  </span>
</div>

<script>
(function () {
  var ns = '.permissionRoleMatrix';
  var isSyncingSelectAll = false;

  function getPermissionInputs() {
      return $('.permissions-modules-list .module-permission');
  }

  function syncSelectAllToggle(total, totalChecked) {
      var $selectAll = $('#selectAllPermissions7');
      if (!$selectAll.length) {
          return;
      }

      isSyncingSelectAll = true;
      $selectAll.each(function () {
          this.indeterminate = false;
      });
      $selectAll.prop('checked', total > 0 && totalChecked === total);
      isSyncingSelectAll = false;
  }

  function updateDirectModulesCount() {
      var $directRows = $('.permission-module-row');
      if (!$directRows.length) {
          return;
      }

      var checked = 0;
      $directRows.each(function () {
          var key = $(this).data('module-key');
          checked += getPermissionInputs().filter('[data-module-key="' + key + '"]:checked').length;
      });
      $('#direct-modules-checked').text(checked);
  }

  function updateModuleCount(moduleKey) {
      var $permissions = getPermissionInputs().filter('[data-module-key="' + moduleKey + '"]');
      var $count = $('.module-count[data-module-key="' + moduleKey + '"]');

      if (!$permissions.length || !$count.length) return;

      $count.text($permissions.filter(':checked').length);
      updateDirectModulesCount();
      updateTotalPermissions();
  }

  function updateTotalPermissions() {
      var $permissions = getPermissionInputs();
      var totalChecked = $permissions.filter(':checked').length;
      var total = $permissions.length;
      $('#totalPermissions13').text(totalChecked);
      $('#totalCheckedText7').text(totalChecked);

      var progress = total > 0 ? (totalChecked / total) * 100 : 0;
      $('#permissionProgress8').css('width', progress + '%');
      $('#progressText4').text(Math.round(progress) + '%');

      $('.module-count').each(function() {
          var key = $(this).data('module-key');
          $(this).text($permissions.filter('[data-module-key="' + key + '"]:checked').length);
      });

      syncSelectAllToggle(total, totalChecked);
      updateDirectModulesCount();
  }

  function bindPermissionRoleMatrixEvents() {
      $(document)
          .off('change' + ns, '.permissions-modules-list .module-permission')
          .on('change' + ns, '.permissions-modules-list .module-permission', function () {
              updateModuleCount($(this).data('module-key'));
          });

      $(document)
          .off('change' + ns, '#selectAllPermissions7')
          .on('change' + ns, '#selectAllPermissions7', function () {
              if (isSyncingSelectAll) {
                  return;
              }

              var checked = this.checked;
              getPermissionInputs().each(function () {
                  this.checked = checked;
              });
              updateTotalPermissions();
          });
  }

  function initPermissionRoleMatrix() {
      if (!$('.permissions-modules-list').length) {
          return;
      }

      $('#totalModules7').text($('.module-count').length);
      updateTotalPermissions();
  }

  if (!window.__permissionRoleMatrixBound) {
      window.__permissionRoleMatrixBound = true;
      bindPermissionRoleMatrixEvents();
  }

  window.initPermissionRoleMatrix = initPermissionRoleMatrix;
  initPermissionRoleMatrix();
})();
</script>

<style>
:root {
  --border-color: #e9ecef;
  --permission-toggle-off: #ced4da;
}

.permissions-modules-list {
  max-height: calc(100vh - 320px);
  overflow-y: auto;
}

.permission-matrix-table th,
.permission-matrix-table td {
  vertical-align: middle;
}

.permission-toggle {
  min-height: 1.5rem;
  padding-left: 0;
}

.permission-toggle .form-check-input {
  width: 2.25rem;
  height: 1.15rem;
  margin: 0;
  cursor: pointer;
  background-color: var(--permission-toggle-off);
  border-color: var(--permission-toggle-off);
  float: none;
  transition: background-color 0.2s ease, border-color 0.2s ease, background-position 0.2s ease;
}

.permission-toggle .form-check-input:focus {
  box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.2);
  border-color: var(--bs-primary);
}

.permission-toggle .form-check-input:checked {
  background-color: var(--bs-primary);
  border-color: var(--bs-primary);
}

.module-card {
  border-color: var(--border-color) !important;
}

.module-card:last-child {
  margin-bottom: 0 !important;
}

.badge.bg-light {
  background: #f8f9fa !important;
}

.badge.border {
  border-color: var(--border-color) !important;
}

.progress {
  background: #f1f3f5;
  border-radius: 2px;
}

.progress-bar {
  transition: width 0.4s ease;
}
</style>
