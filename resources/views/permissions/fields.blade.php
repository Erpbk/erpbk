@php
    $submodules = $submodules ?? [];
    $hasSubmodules = !empty($submodules);
@endphp

<!-- Module Name -->
<div class="form-group col-sm-12">
    {!! Form::label('name', 'Module Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'required', 'maxlength' => 255, 'placeholder' => 'e.g. Expenses']) !!}
    <small class="text-muted d-block mt-1">
        Without submodules, <span class="text-primary">view, create, edit, delete</span> are created as direct children.
        With submodules, each submodule gets its own <span class="text-primary">view, create, edit, delete</span> permissions.
    </small>
</div>

<!-- Submodules -->
<div class="form-group col-sm-12 mt-3">
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" name="use_submodules" value="1" id="use-submodules-toggle" @if($hasSubmodules) checked @endif>
        <label class="form-check-label fw-semibold" for="use-submodules-toggle">Add submodules</label>
    </div>

    <div id="submodules-section" class="{{ $hasSubmodules ? '' : 'd-none' }}">
        <label class="form-label">Submodules</label>
        <div id="submodules-container" class="row g-2">
            @forelse($submodules as $index => $submoduleName)
                <div class="col-md-6 submodule-row">
                    <div class="input-group">
                        <input type="text" name="submodules[]" class="form-control" value="{{ $submoduleName }}" placeholder="e.g. Document" required>
                        <button type="button" class="btn btn-outline-danger remove-submodule" title="Remove submodule">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-md-6 submodule-row">
                    <div class="input-group">
                        <input type="text" name="submodules[]" class="form-control" placeholder="e.g. Document">
                        <button type="button" class="btn btn-outline-danger remove-submodule" title="Remove submodule">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>
            @endforelse
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-submodule">
            <i class="ti ti-plus"></i> Add Submodule
        </button>
    </div>
</div>

<!-- Extra Permissions (only when no submodules) -->
<div id="custom-permissions-section" class="{{ $hasSubmodules ? 'd-none' : '' }}">
    <h5 class="mt-4">Custom Permissions</h5>
    <small class="text-muted d-block mb-2">Optional extra leaf permissions for this module (without submodules only).</small>
    <div class="row" id="extra-permissions-container">
        @if(isset($customPermissions))
            @foreach($customPermissions as $custom)
                <div class="col-md-4 mb-2 extra-permission-row">
                    <div class="input-group">
                        <input type="text" name="extra[]" class="form-control" value="{{ $custom }}" required>
                        <button type="button" class="btn btn-outline-danger remove-extra-permission">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <button type="button" class="btn btn-sm btn-success mt-2" id="add-permission">
        <i class="ti ti-plus"></i> Add Custom Permission
    </button>
</div>

<script>
(function () {
    const useSubmodulesToggle = document.getElementById('use-submodules-toggle');
    const submodulesSection = document.getElementById('submodules-section');
    const customPermissionsSection = document.getElementById('custom-permissions-section');
    const submodulesContainer = document.getElementById('submodules-container');
    const extraContainer = document.getElementById('extra-permissions-container');
    let submoduleCounter = 0;
    let extraCounter = 0;

    function toggleSubmoduleMode(enabled) {
        submodulesSection.classList.toggle('d-none', !enabled);
        customPermissionsSection.classList.toggle('d-none', enabled);

        submodulesSection.querySelectorAll('input[name="submodules[]"]').forEach(function (input) {
            input.disabled = !enabled;
            if (enabled) {
                input.setAttribute('required', 'required');
            } else {
                input.removeAttribute('required');
            }
        });
    }

    if (useSubmodulesToggle) {
        useSubmodulesToggle.addEventListener('change', function () {
            toggleSubmoduleMode(this.checked);
        });
        toggleSubmoduleMode(useSubmodulesToggle.checked);
    }

    document.getElementById('add-submodule')?.addEventListener('click', function () {
        submoduleCounter++;
        const div = document.createElement('div');
        div.className = 'col-md-6 submodule-row';
        div.innerHTML = `
            <div class="input-group">
                <input type="text" name="submodules[]" class="form-control" placeholder="e.g. Voucher" required>
                <button type="button" class="btn btn-outline-danger remove-submodule" title="Remove submodule">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        `;
        submodulesContainer.appendChild(div);
    });

    document.addEventListener('click', function (event) {
        const removeSubmodule = event.target.closest('.remove-submodule');
        if (removeSubmodule) {
            const row = removeSubmodule.closest('.submodule-row');
            if (row && submodulesContainer.querySelectorAll('.submodule-row').length > 1) {
                row.remove();
            }
        }

        const removeExtra = event.target.closest('.remove-extra-permission');
        if (removeExtra) {
            removeExtra.closest('.extra-permission-row')?.remove();
        }
    });

    document.getElementById('add-permission')?.addEventListener('click', function () {
        extraCounter++;
        const div = document.createElement('div');
        div.className = 'col-md-4 mb-2 extra-permission-row';
        div.id = 'perm-' + extraCounter;
        div.innerHTML = `
            <div class="input-group">
                <input type="text" name="extra[]" class="form-control" placeholder="Permission name" required>
                <button type="button" class="btn btn-outline-danger remove-extra-permission">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        `;
        extraContainer.appendChild(div);
    });
})();
</script>
