<!-- Name Field -->
<div class="form-group mb-3">
  <div class="row g-2 align-items-center">
      <div class="col-md-4">
          {!! Form::label('name', 'Role Name', ['class' => 'form-label fw-semibold text-muted small text-uppercase mb-1 tracking-wide']) !!}
          @isset($roles)
              {!! Form::text('name', null, ['class' => 'form-control form-control-sm border-0 border-bottom border-2 border-secondary rounded-0 px-0 shadow-none', 'required', 'readonly', 'maxlength' => 255, 'placeholder' => 'Enter role name...']) !!}
          @else
              {!! Form::text('name', null, ['class' => 'form-control form-control-sm border-0 border-bottom border-2 border-secondary rounded-0 px-0 shadow-none', 'required', 'maxlength' => 255, 'placeholder' => 'Enter role name...']) !!}
          @endisset
      </div>
      <div class="col-md-8">
          <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap">
              <span class="badge bg-light text-dark border px-3 py-1 rounded-pill">
                  <i class="bi bi-check2-circle text-primary me-1"></i>
                  <span class="fw-semibold" id="totalPermissions13">0</span>
              </span>
              <span class="badge bg-light text-dark border px-3 py-1 rounded-pill">
                  <i class="bi bi-grid-3x3-gap text-secondary me-1"></i>
                  <span class="fw-semibold" id="totalModules7">0</span>
              </span>
              <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-light btn-sm border px-2" id="selectAllPermissions7">
                      <i class="bi bi-check-all text-primary"></i>
                  </button>
                  <button type="button" class="btn btn-light btn-sm border px-2" id="deselectAllPermissions7">
                      <i class="bi bi-x-circle text-secondary"></i>
                  </button>
              </div>
          </div>
      </div>
  </div>
</div>

<!-- Permissions Table Section -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="card-header bg-white py-2 px-3 border-bottom">
      <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
              <i class="bi bi-table text-secondary fs-6"></i>
              <span class="fw-semibold small text-secondary">Permissions</span>
              <span class="badge bg-light text-dark border rounded-pill px-2 py-0 small" id="progressText4">0%</span>
          </div>
          <div class="d-flex align-items-center gap-2">
              <div class="progress" style="width: 100px; height: 3px;">
                  <div class="progress-bar bg-secondary" id="permissionProgress8" style="width: 0%;"></div>
              </div>
              <span class="text-muted small">
                  <i class="bi bi-arrow-left-right"></i>
              </span>
          </div>
      </div>
  </div>
  
  <div class="card-body p-0">
      <div class="table-responsive permissions-table-scroll scrollbar">
          <table class="table table-sm table-borderless mb-0 permissions-matrix-table">
              <tbody>
                  @php
                      use Spatie\Permission\Models\Permission;
                      
                      $hasParentId = \Illuminate\Support\Facades\Schema::hasColumn('permissions', 'parent_id');
                      $permissionMatrix = [];
                      
                      if ($hasParentId) {
                          $modules = Permission::query()
                              ->where(function ($q) {
                                  $q->whereNull('parent_id')->orWhere('parent_id', 0);
                              })
                              ->get();
                          
                          foreach ($modules as $module) {
                              $moduleName = $module->name;
                              $permissions = Permission::where('parent_id', $module->id)->get();
                              
                              if ($permissions->count() > 0) {
                                  $matrix = [];
                                  foreach ($permissions as $perm) {
                                      $parts = explode('_', $perm->name, 2);
                                      if (count($parts) === 2) {
                                          $displayName = ucwords(str_replace('_', ' ', $parts[1]));
                                      } else {
                                          $displayName = ucwords(str_replace('_', ' ', $perm->name));
                                      }
                                      $matrix[$perm->id] = [
                                          'name' => $displayName,
                                          'full_name' => $perm->name
                                      ];
                                  }
                                  $permissionMatrix[$moduleName] = $matrix;
                              }
                          }
                      } else {
                          $allPerms = Permission::orderBy('name')->get();
                          $grouped = [];
                          foreach ($allPerms as $perm) {
                              $parts = explode('_', $perm->name, 2);
                              if (count($parts) === 2) {
                                  $module = ucwords(str_replace('_', ' ', $parts[0]));
                                  $action = ucwords(str_replace('_', ' ', $parts[1]));
                              } else {
                                  $module = 'General';
                                  $action = ucwords(str_replace('_', ' ', $perm->name));
                              }
                              if (!isset($grouped[$module])) {
                                  $grouped[$module] = [];
                              }
                              $grouped[$module][$perm->id] = [
                                  'name' => $action,
                                  'full_name' => $perm->name
                              ];
                          }
                          
                          foreach ($grouped as $module => $perms) {
                              if (count($perms) > 0) {
                                  $permissionMatrix[$module] = $perms;
                              }
                          }
                      }
                      
                      ksort($permissionMatrix);

                      $rolePermissions = $rolePermissions ?? [];
                  @endphp
                  
                  @foreach ($permissionMatrix as $moduleName => $permissions)
                      @php
                          $moduleKey = 'module-' . \Illuminate\Support\Str::slug($moduleName);
                          $totalPerms = count($permissions);
                          $colors = ['#4A90D9', '#50C878', '#FF6B6B', '#FFA94D', '#845EC2', '#FF6F91', '#00C9A7', '#FF9671'];
                          $colorIndex = $loop->index % count($colors);
                          $moduleColor = $colors[$colorIndex];
                      @endphp
                      <tr class="module-row" data-module-color="{{ $moduleColor }}">
                          <td class="module-column py-2 px-3">
                              <div class="d-flex align-items-center gap-2">
                                  <div class="module-badge" style="width: 3px; height: 20px; background: {{ $moduleColor }}; border-radius: 2px; flex-shrink: 0;"></div>
                                  <span class="fw-bold text-dark" style="font-size: 0.8rem; letter-spacing: 0.02em;">{{ $moduleName }}</span>
                                  <span class="badge bg-light text-dark border rounded-pill px-1 py-0 small ms-1" id="count-{{ $moduleKey }}">
                                      <span class="module-count" data-module-key="{{ $moduleKey }}">0</span>
                                      <span class="text-muted">/{{ $totalPerms }}</span>
                                  </span>
                              </div>
                          </td>
                          <td class="permissions-cell py-2 px-2">
                              <div class="d-flex flex-nowrap gap-1 permissions-row" data-module-key="{{ $moduleKey }}">
                                  @foreach ($permissions as $permissionId => $permData)
                                      @php
                                          $isChecked = isset($rolePermissions[$permissionId]);
                                      @endphp
                                      <div class="permission-pill {{ $isChecked ? 'pill-selected' : 'pill-unselected' }}" 
                                           data-permission-id="{{ $permissionId }}"
                                           data-module-key="{{ $moduleKey }}"
                                           style="cursor: pointer; transition: all 0.15s ease; display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 12px; border: 1.5px solid {{ $isChecked ? $moduleColor : '#dee2e6' }}; background: {{ $isChecked ? $moduleColor . '15' : 'transparent' }};">
                                          <div class="form-check">
                                              <input class="form-check-input module-permission d-none" 
                                                     name="permission[]" 
                                                     id="perm-{{ $permissionId }}" 
                                                     value="{{ $permissionId }}" 
                                                     type="checkbox" 
                                                     data-module-key="{{ $moduleKey }}"
                                                     @if($isChecked) checked @endif>
                                          </div>
                                          <div class="d-flex align-items-center gap-1">
                                              @if($isChecked)
                                                  <i class="bi bi-check-circle-fill" style="font-size: 0.5rem; color: {{ $moduleColor }};"></i>
                                              @else
                                                  <i class="bi bi-circle" style="font-size: 0.5rem; opacity: 0.2;"></i>
                                              @endif
                                              <span class="small permission-label" style="font-size: 0.65rem; font-weight: 500; letter-spacing: 0.01em; color: {{ $isChecked ? '#212529' : '#6c757d' }};">
                                                  {{ $permData['name'] }}
                                              </span>
                                          </div>
                                      </div>
                                  @endforeach
                              </div>
                          </td>
                      </tr>
                  @endforeach
              </tbody>
          </table>
      </div>
  </div>
  
  <div class="card-footer bg-white py-1 px-3 border-top">
      <div class="d-flex align-items-center justify-content-between">
          <span class="text-muted small">
              <span class="fw-semibold text-dark" id="totalCheckedText7">0</span> selected
          </span>
          <span class="text-muted small">
              <i class="bi bi-hand-index-thumb me-1"></i> Click pill to toggle
          </span>
      </div>
  </div>
</div>

<script>
(function () {
  function moduleColorWithAlpha($moduleRow, alphaHex) {
      var color = $moduleRow.data('module-color');
      if (!color) {
          return 'transparent';
      }
      return String(color) + (alphaHex || '15');
  }

  function applyPillSelectedStyle($pill, $moduleRow) {
      var moduleColor = $moduleRow.data('module-color');
      $pill.removeClass('pill-unselected').addClass('pill-selected');
      $pill.css({
          'border-color': moduleColor,
          'background': moduleColorWithAlpha($moduleRow)
      });
      $pill.find('.bi-circle').removeClass('bi-circle').addClass('bi-check-circle-fill').css({ color: moduleColor, opacity: 1 });
      $pill.find('.permission-label').css('color', '#212529');
  }

  function applyPillUnselectedStyle($pill) {
      $pill.removeClass('pill-selected').addClass('pill-unselected');
      $pill.css({
          'border-color': '#dee2e6',
          'background': 'transparent'
      });
      $pill.find('.bi-check-circle-fill').removeClass('bi-check-circle-fill').addClass('bi-circle').css({ color: '', opacity: 0.2 });
      $pill.find('.permission-label').css('color', '#6c757d');
  }

  function updateModuleCount(moduleKey) {
      var $permissions = $('.module-permission[data-module-key="' + moduleKey + '"]');
      var $count = $('.module-count[data-module-key="' + moduleKey + '"]');
      
      if (!$permissions.length || !$count.length) return;
      
      var checkedCount = $permissions.filter(':checked').length;
      $count.text(checkedCount);
      updateTotalPermissions();
  }
  
  function updateTotalPermissions() {
      var totalChecked = $('.module-permission:checked').length;
      var total = $('.module-permission').length;
      $('#totalPermissions13').text(totalChecked);
      $('#totalCheckedText7').text(totalChecked);
      
      var progress = total > 0 ? (totalChecked / total) * 100 : 0;
      $('#permissionProgress8').css('width', progress + '%');
      $('#progressText4').text(Math.round(progress) + '%');
      
      // Update module counts
      $('.module-count').each(function() {
          var key = $(this).data('module-key');
          var $perms = $('.module-permission[data-module-key="' + key + '"]');
          var checked = $perms.filter(':checked').length;
          $(this).text(checked);
      });
  }
  
  // Toggle permission on pill click
  $(document).on('click', '.permission-pill', function (e) {
      if ($(e.target).closest('.form-check').length) return;
      
      var $pill = $(this);
      var $checkbox = $pill.find('.module-permission');
      var isChecked = $checkbox.prop('checked');
      var moduleKey = $pill.data('module-key');
      var $moduleRow = $pill.closest('.module-row');

      $checkbox.prop('checked', !isChecked).trigger('change');

      if (!isChecked) {
          applyPillSelectedStyle($pill, $moduleRow);
      } else {
          applyPillUnselectedStyle($pill);
      }
      
      // Update module count
      updateModuleCount(moduleKey);
  });
  
  // Handle checkbox change events
  $(document).on('change', '.module-permission', function () {
      var moduleKey = $(this).data('module-key');
      updateModuleCount(moduleKey);
  });
  
  // Select all permissions
  $('#selectAllPermissions7').on('click', function() {
      $('.module-permission').prop('checked', true).trigger('change');
      $('.permission-pill').each(function() {
          applyPillSelectedStyle($(this), $(this).closest('.module-row'));
      });
      updateTotalPermissions();
  });

  $('#deselectAllPermissions7').on('click', function() {
      $('.module-permission').prop('checked', false).trigger('change');
      $('.permission-pill').each(function() {
          applyPillUnselectedStyle($(this));
      });
      updateTotalPermissions();
  });
  
  // Initialize
  $(function () {
      var moduleCount = $('.module-row').length;
      $('#totalModules7').text(moduleCount);
      
      // Initialize module counts
      $('.module-count').each(function() {
          var key = $(this).data('module-key');
          var $perms = $('.module-permission[data-module-key="' + key + '"]');
          var checked = $perms.filter(':checked').length;
          $(this).text(checked);
      });
      
      // Initialize pill styling
      $('.permission-pill').each(function() {
          var $pill = $(this);
          var $moduleRow = $pill.closest('.module-row');
          if ($pill.find('.module-permission').prop('checked')) {
              applyPillSelectedStyle($pill, $moduleRow);
          } else {
              applyPillUnselectedStyle($pill);
          }
      });
      
      updateTotalPermissions();
  });
})();
</script>

<style>
/* Clean, horizontal layout */
:root {
  --border-color: #e9ecef;
}

.permissions-table-scroll {
  overflow-x: auto;
  overflow-y: visible;
  -webkit-overflow-scrolling: touch;
  max-width: 100%;
}

.permissions-matrix-table {
  width: max-content;
  min-width: 100%;
  font-size: 0.8rem;
  margin-bottom: 0;
}

/* Module column - sticky while table scrolls horizontally */
.module-column {
  min-width: 180px;
  max-width: 180px;
  background: white !important;
  border-right: 1px solid var(--border-color) !important;
  position: sticky;
  left: 0;
  z-index: 5;
}

/* Permissions cell */
.permissions-cell {
  padding: 6px 8px !important;
  white-space: nowrap;
}

.permissions-row {
  display: inline-flex;
  flex-wrap: nowrap;
  gap: 6px;
  padding: 4px 0;
  min-height: 36px;
}

/* Permission pills - compact and inline */
.permission-pill {
  display: inline-flex !important;
  align-items: center;
  padding: 2px 10px !important;
  border-radius: 12px !important;
  border: 1.5px solid #dee2e6;
  background: transparent;
  transition: all 0.15s ease;
  user-select: none;
  flex-shrink: 0;
  cursor: pointer;
  height: 26px;
}

.permission-pill:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.permission-pill.pill-unselected:hover {
  border-color: #adb5bd;
  background: #f8f9fa;
}

.permission-pill.pill-selected:hover {
  background: rgba(13, 110, 253, 0.12);
}

/* Module row divider */
.module-row {
  border-bottom: 1px solid var(--border-color);
}

.module-row:last-child {
  border-bottom: none;
}

/* Module badge - colored accent */
.module-badge {
  flex-shrink: 0;
}

/* Scrollbar - minimal */
.scrollbar::-webkit-scrollbar {
  height: 4px;
}

.scrollbar::-webkit-scrollbar-track {
  background: #f1f3f5;
}

.scrollbar::-webkit-scrollbar-thumb {
  background: #ced4da;
  border-radius: 2px;
}

.scrollbar::-webkit-scrollbar-thumb:hover {
  background: #adb5bd;
}

/* Badge - clean */
.badge.bg-light {
  background: #f8f9fa !important;
}

.badge.border {
  border-color: var(--border-color) !important;
}

/* Progress bar - minimal */
.progress {
  background: #f1f3f5;
  border-radius: 2px;
}

.progress-bar {
  background: #6c757d !important;
  transition: width 0.4s ease;
}

/* Card - clean */
.card {
  border-color: var(--border-color);
}

.card-header {
  background: white !important;
}

.card-footer {
  background: white !important;
}

/* Form controls - minimal */
.form-control-sm {
  font-size: 0.8rem;
  padding: 0.2rem 0;
}

.form-control:focus {
  border-color: #6c757d;
  box-shadow: none;
}

/* Buttons - minimal */
.btn-light {
  background: white;
  border-color: var(--border-color);
  color: #495057;
  transition: all 0.15s ease;
}

.btn-light:hover {
  background: #f8f9fa;
  border-color: #ced4da;
}

/* Tracking */
.tracking-wide {
  letter-spacing: 0.05em;
}

/* Counter animation */
@keyframes fadeUpdate {
  0% { opacity: 0.7; }
  50% { opacity: 1; }
  100% { opacity: 0.7; }
}

#totalPermissions13:not(:empty) {
  animation: fadeUpdate 0.2s ease;
}

/* Responsive */
@media (max-width: 768px) {
  .module-column {
      min-width: 140px !important;
      max-width: 140px !important;
  }
  
  .permission-pill {
      padding: 1px 8px !important;
      height: 22px;
      font-size: 0.6rem;
  }
  
  .permission-pill .permission-label {
      font-size: 0.55rem !important;
  }
  
  .permissions-row {
      gap: 4px;
      min-height: 30px;
  }
}

/* Tooltip on hover */
.permission-pill {
  position: relative;
}

.permission-pill:hover::after {
  content: 'Click to toggle';
  position: absolute;
  bottom: -20px;
  left: 50%;
  transform: translateX(-50%);
  background: #212529;
  color: white;
  padding: 1px 6px;
  border-radius: 3px;
  font-size: 0.45rem;
  white-space: nowrap;
  opacity: 0;
  animation: tooltipShow 0.3s ease forwards;
  pointer-events: none;
  z-index: 10;
}

@keyframes tooltipShow {
  from { opacity: 0; transform: translateX(-50%) translateY(4px); }
  to { opacity: 1; transform: translateX(-50%) translateY(0); }
}

/* Remove tooltip on mobile */
@media (max-width: 768px) {
  .permission-pill:hover::after {
      display: none;
  }
}

/* Color-based pill backgrounds */
.pill-selected {
  transition: all 0.15s ease;
}
</style>