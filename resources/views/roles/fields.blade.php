
<!-- Name Field -->
<div class="form-group col-sm-8">
    {!! Form::label('name', 'Name:') !!}
    @isset($roles)
    {!! Form::text('name', null, ['class' => 'form-control', 'required','readonly', 'maxlength' => 255, 'maxlength' => 255]) !!}

    @else
    {!! Form::text('name', null, ['class' => 'form-control', 'required', 'maxlength' => 255, 'maxlength' => 255]) !!}
    @endisset
</div>
<br>
<h5>Role Permissions</h5>
<div class="table-responsive scrollbar" >
    <table class="table table-flush-spacing">
      <tbody>
        @php
            use Spatie\Permission\Models\Permission;
            $hasParentId = \Illuminate\Support\Facades\Schema::hasColumn('permissions', 'parent_id');
            if (! isset($modules) && $hasParentId) {
              $modules = Permission::query()
                ->where(function ($q) {
                  $q->whereNull('parent_id')->orWhere('parent_id', 0);
                })
                ->get();
            }
        @endphp
        @if ($hasParentId)
        @foreach ($modules as $module)
        @php
            $permissions = Permission::where('parent_id', $module->id)->get();
            $moduleKey = 'module-' . $module->id;
        @endphp
        <tr>
          <td class="text-nowrap fw-medium align-top">
            <div>{{ $module->name }}</div>
            <div class="form-check mt-2">
              <input class="form-check-input module-select-all" type="checkbox" id="{{ $moduleKey }}-all" data-module-key="{{ $moduleKey }}">
              <label class="form-check-label small text-muted" for="{{ $moduleKey }}-all">Select All</label>
            </div>
          </td>
          <td>
            <div class="d-flex flex-wrap module-permissions" data-module-key="{{ $moduleKey }}">
          @foreach ($permissions as $item)

                <div class="form-check me-3 me-lg-5 mb-2">
                    <input class="form-check-input module-permission" name="permission[]" id="{{ $item->id }}" value="{{ $item->id }}" type="checkbox" data-module-key="{{ $moduleKey }}"
                    @isset($rolePermissions[$item->id]) checked @endisset >
                    @php
                         $customPermissionLabels = [
                             'visaexpense_show_in_menu' => 'Show in Menu',
                         ];
                         $name = explode('_',$item->name,2);
                        $name = $customPermissionLabels[$item->name] ?? ucwords(str_replace("_"," ",$name[1] ?? $item->name));
                    @endphp
                    <label class="form-check-label" for="{{ $item->id }}">{{ $name }}</label>
                </div>

            @endforeach
        </div>
          </td>
        </tr>

        @endforeach
        @else
        @php
          $groupedPermissions = Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
              $parts = explode('_', $permission->name, 2);
              if (count($parts) === 2) {
                return ucwords(str_replace('_', ' ', $parts[0]));
              }
              return ucwords(str_replace('_', ' ', $permission->name));
            });
        @endphp
        @foreach ($groupedPermissions as $moduleName => $permissions)
        @php
            $moduleKey = 'module-' . \Illuminate\Support\Str::slug($moduleName);
        @endphp
        <tr>
          <td class="text-nowrap fw-medium align-top">
            <div>{{ $moduleName }}</div>
            <div class="form-check mt-2">
              <input class="form-check-input module-select-all" type="checkbox" id="{{ $moduleKey }}-all" data-module-key="{{ $moduleKey }}">
              <label class="form-check-label small text-muted" for="{{ $moduleKey }}-all">Select All</label>
            </div>
          </td>
          <td>
            <div class="d-flex flex-wrap module-permissions" data-module-key="{{ $moduleKey }}">
              @foreach ($permissions as $item)
              <div class="form-check me-3 me-lg-5 mb-2">
                <input class="form-check-input module-permission" name="permission[]" id="{{ $item->id }}" value="{{ $item->id }}" type="checkbox" data-module-key="{{ $moduleKey }}"
                  @isset($rolePermissions[$item->id]) checked @endisset>
                @php
                  $parts = explode('_', $item->name, 2);
                  $label = count($parts) === 2 ? ucwords(str_replace('_', ' ', $parts[1])) : ucwords(str_replace('_', ' ', $item->name));
                @endphp
                <label class="form-check-label" for="{{ $item->id }}">{{ $label }}</label>
              </div>
              @endforeach
            </div>
          </td>
        </tr>
        @endforeach
        @endif

      </tbody>
    </table>
  </div>

  <script>
(function () {
  function syncModuleSelectAll(moduleKey) {
    var $permissions = $('.module-permission[data-module-key="' + moduleKey + '"]');
    var $selectAll = $('.module-select-all[data-module-key="' + moduleKey + '"]');
    if (!$permissions.length || !$selectAll.length) {
      return;
    }

    var checkedCount = $permissions.filter(':checked').length;
    $selectAll.prop('checked', checkedCount === $permissions.length);
    $selectAll.prop('indeterminate', checkedCount > 0 && checkedCount < $permissions.length);
  }

  $(document).on('change', '.module-select-all', function () {
    var moduleKey = $(this).data('module-key');
    var checked = this.checked;
    $('.module-permission[data-module-key="' + moduleKey + '"]').prop('checked', checked);
    $(this).prop('indeterminate', false);
  });

  $(document).on('change', '.module-permission', function () {
    syncModuleSelectAll($(this).data('module-key'));
  });

  $(function () {
    $('.module-select-all').each(function () {
      syncModuleSelectAll($(this).data('module-key'));
    });
  });
})();
</script>
