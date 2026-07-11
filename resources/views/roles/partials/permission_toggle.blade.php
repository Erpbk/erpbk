<label class="permission-toggle form-check form-switch m-0 d-inline-flex justify-content-center"
       title="{{ $permData['full_name'] }}">
    <input class="form-check-input module-permission"
           name="permission[]"
           id="perm-{{ $permissionId }}"
           value="{{ $permissionId }}"
           type="checkbox"
           role="switch"
           data-module-key="{{ $moduleKey }}"
           @if($isChecked) checked @endif>
</label>
