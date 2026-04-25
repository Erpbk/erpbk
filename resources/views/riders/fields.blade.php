@php
  $riderCategories = $riderCategories ?? \App\Models\RiderCategory::orderBy('display_order')->orderBy('id')->get();
  $fieldsByCategory = $fieldsByCategory ?? \App\Models\RiderCustomField::fieldsByCategoryForForm();
  $useDynamicFields = is_array($fieldsByCategory) && count($fieldsByCategory) > 0;
@endphp

@if ($useDynamicFields)
  {{-- One card per category, stacked (no tabs) --}}
  @foreach($fieldsByCategory as $group)
    <div class="card mb-4">
      <div class="card-header">
        <b>{{ $group->category->label }}</b>
      </div>
      <div class="card-body">
        <div class="row">
          @foreach($group->fields as $item)
            @include('riders._form_field', ['item' => $item])
          @endforeach
        </div>
      </div>
    </div>
  @endforeach
@else
  {{-- Fallback: slug-based, one card per category (no tabs) --}}
  @foreach($riderCategories as $cat)
    @php
      $catCustomFields = \App\Models\RiderCustomField::where('category_id', $cat->id)->orderBy('display_order')->orderBy('id')->get();
    @endphp
    <div class="card mb-4">
      <div class="card-header">
        <b>{{ $cat->label }}</b>
      </div>
      <div class="card-body">
        @if($cat->slug === 'rider_info')
          @include('riders.fields.rider_info')
        @elseif($cat->slug === 'visa_info')
          @include('riders.fields.visa_info')
        @elseif($cat->slug === 'job_info')
          @include('riders.fields.job_info')
        @elseif($cat->slug === 'labor_info')
          @include('riders.fields.labor_info')
        @elseif($cat->slug === 'additional_info')
          @include('riders.fields.additional_info')
        @else
          @include('riders.fields.other')
        @endif
        @if($catCustomFields->isNotEmpty())
          <div class="row mt-3">
            @foreach($catCustomFields as $cf)
              @include('riders._form_field', ['item' => (object)['kind' => 'custom', 'field' => $cf]])
            @endforeach
          </div>
        @endif
      </div>
    </div>
  @endforeach
@endif

@once
  <div class="modal fade" id="addRiderDropdownOptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Dropdown Option</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="addRiderDropdownOptionForm">
          @csrf
          <div class="modal-body">
            <input type="hidden" name="field_key" id="dropdownOptionFieldKey">
            <input type="hidden" name="custom_field_id" id="dropdownOptionCustomFieldId">
            <div class="mb-2 text-muted small" id="dropdownOptionFieldLabel"></div>
            <label class="form-label">Option value</label>
            <input type="text" class="form-control" name="option_value" id="dropdownOptionValue" required maxlength="255">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    (function() {
      var modalEl = document.getElementById('addRiderDropdownOptionModal');
      var formEl = document.getElementById('addRiderDropdownOptionForm');
      if (!modalEl || !formEl) return;

      var bsModal = (typeof bootstrap !== 'undefined' && bootstrap.Modal) ? new bootstrap.Modal(modalEl) : null;
      var csrf = (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) ||
        (formEl.querySelector('input[name="_token"]') && formEl.querySelector('input[name="_token"]').value) || '';
      var activeDropdownSelect = null;

      document.addEventListener('change', function(e) {
        var sel = e.target.closest('.js-dropdown-with-add-option');
        if (!sel) return;
        if (sel.value !== '__add_option__') return;
        activeDropdownSelect = sel;
        document.getElementById('dropdownOptionFieldKey').value = sel.getAttribute('data-field-key') || '';
        document.getElementById('dropdownOptionCustomFieldId').value = sel.getAttribute('data-custom-field-id') || '';
        document.getElementById('dropdownOptionFieldLabel').textContent = (sel.getAttribute('data-label') || 'Field') + ' - add new option';
        document.getElementById('dropdownOptionValue').value = '';
        sel.value = '';
        if (bsModal) bsModal.show();
      });

      formEl.addEventListener('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(formEl);
        fetch("{{ route('riders.dropdown-options.store') }}", {
            method: 'POST',
            body: fd,
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(function(r) {
            return r.json().then(function(data) {
              return r.ok ? data : Promise.reject(data);
            });
          })
          .then(function(data) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: data.message || 'Option added.',
                showConfirmButton: false,
                timer: 1800
              });
            }
            if (bsModal) bsModal.hide();
            var newOptionValue = (document.getElementById('dropdownOptionValue').value || '').trim();
            if (activeDropdownSelect && newOptionValue) {
              var existing = Array.from(activeDropdownSelect.options).find(function(opt) {
                return (opt.value || '').toLowerCase() === newOptionValue.toLowerCase();
              });
              if (!existing) {
                var addOpt = Array.from(activeDropdownSelect.options).find(function(opt) {
                  return opt.value === '__add_option__';
                });
                var newOpt = new Option(newOptionValue, newOptionValue);
                if (addOpt) {
                  activeDropdownSelect.insertBefore(newOpt, addOpt);
                } else {
                  activeDropdownSelect.add(newOpt);
                }
              }
              activeDropdownSelect.value = newOptionValue;
              activeDropdownSelect.dispatchEvent(new Event('change', {
                bubbles: true
              }));
            } else {
              window.location.reload();
            }
          })
          .catch(function(err) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: (err && err.message) ? err.message : 'Could not add option.'
              });
            }
          });
      });
    })();
  </script>
@endonce
