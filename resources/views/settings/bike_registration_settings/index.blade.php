@extends('layouts.settingsPanelLayout')

@section('title', $pageTitle ?? 'Bike Registration Settings')

@section('content')
@include('flash::message')

@php
$companySlug = request()->route('company_slug') ?? session('company_slug');
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h4 class="card-title mb-0">{{ $settingsHeading ?? ($moduleLabel . ' Settings') }}</h4>
      </div>
    </div>
  </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-br-general" type="button">General</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-br-statuses" type="button">Registration statuses</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bike-registration-top" type="button" id="tab-bike-registration-top-btn">Top bar</button>
  </li>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="tab-br-general">
    <form action="{{ route('settings-panel.module-settings.store-module-label', ['company_slug' => $companySlug, 'module' => 'bike_registration']) }}" method="POST" class="row g-3 align-items-end">
      @csrf
      <div class="col-md-6">
        <label class="form-label">Name in menu</label>
        <input type="text" name="module_label" class="form-control" value="{{ old('module_label', $moduleLabel ?? '') }}" maxlength="100" required>
      </div>
      <div class="col-md-6 text-end">
        <button class="btn btn-primary" type="submit">Save name</button>
      </div>
    </form>
  </div>

  <div class="tab-pane fade" id="tab-br-statuses">
    <p class="text-muted">Create registration statuses, default fees, and active flags (same pattern as Visa Expense statuses).</p>
    <a href="{{ route('settings-panel.bike-registration-statuses.index', ['company_slug' => $companySlug]) }}" class="btn btn-primary btn-sm mb-3">
      <i class="ti ti-list-details me-1"></i> Open registration status manager
    </a>
    <div class="table-responsive border rounded p-2 bg-light">
      @include('bike_registration_statuses.table', [
      'bikeRegistrationStatuses' => $bikeRegistrationStatusesForSettings ?? collect(),
      'bikeRegistrationRoute' => 'settings-panel.bike-registration-statuses',
      ])
    </div>
  </div>

  <div class="tab-pane fade" id="tab-bike-registration-top" role="tabpanel">
    <div class="card border-0 shadow-none">
      <div class="card-body px-0">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <p class="text-muted small mb-0">Default category is <strong>Bike Registration Top Status</strong>. Add only the options you want on Bike Registration account listing top cards.</p>
        </div>
        <form id="bikeRegistrationTopAjaxForm" method="POST" action="{{ route('settings-panel.module-settings.update-bike-registration-top', ['company_slug' => $companySlug, 'module' => 'bike_registration']) }}">
          @csrf
          <div class="accordion" id="bikeRegistrationTopAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="bikeRegistrationTopHeading">
                <div class="d-flex align-items-center gap-2 p-2">
                  <button class="accordion-button py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#bikeRegistrationTopCollapse" aria-expanded="true" aria-controls="bikeRegistrationTopCollapse">
                    <span>Bike Registration Top Status</span>
                    <span class="badge bg-label-primary ms-2" id="bikeRegistrationTopSelectedCount">{{ count((array)($selectedBikeRegistrationTopStatusIds ?? [])) }}</span>
                  </button>
                  <div class="form-check form-switch mb-0" style="display: inline-flex; align-items: center; gap: 0.4rem;padding: 0.35rem 0.6rem;">
                    <input style="width: 2rem; height: 1.1rem; margin: 0; cursor: pointer;" class="form-check-input rider-top-visibility-toggle" type="checkbox" id="bikeRegistrationTopEnabled" data-field="show_in_top_bar" {{ (!empty($bikeRegistrationTopEnabled) ? 'checked' : '') }}>
                    <label style="font-size: 0.78rem; font-weight: 500; color: #5f6b7a; margin-top: 0; cursor: pointer;" class="form-check-label text-nowrap" for="bikeRegistrationTopEnabled">Top Bar</label>
                  </div>
                </div>
              </h2>
              <div id="bikeRegistrationTopCollapse" class="accordion-collapse collapse show" aria-labelledby="bikeRegistrationTopHeading" data-bs-parent="#bikeRegistrationTopAccordion">
                <div class="accordion-body">
                  <div class="d-flex justify-content-end mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-add-bike-registration-top-option" data-bs-toggle="modal" data-bs-target="#addBikeRegistrationTopOptionModal">
                      <i class="ti ti-plus me-1"></i> Add Status
                    </button>
                  </div>
                  @php
                  $selectedBrTopIds = collect((array)($selectedBikeRegistrationTopStatusIds ?? []))->map(fn ($id) => (int) $id)->all();
                  $selectedBikeRegistrationTopStatuses = collect($bikeRegistrationStatusesForSettings ?? collect())
                  ->filter(fn ($s) => in_array((int) $s->id, $selectedBrTopIds, true))
                  ->sortBy(fn ($s) => array_search((int) $s->id, $selectedBrTopIds, true))
                  ->values();
                  @endphp
                  <ul class="list-group list-group-flush" id="bikeRegistrationTopSelectedList">
                    @forelse($selectedBikeRegistrationTopStatuses as $status)
                    <li class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between" data-selected-id="{{ (int) $status->id }}">
                      <div class="d-flex align-items-center">
                        <span class="bike-registration-top-drag-handle me-2 text-muted" title="Drag to sort" style="cursor: grab;">
                          <i class="ti ti-grip-vertical"></i>
                        </span>
                        <i class="ti ti-point-filled me-1 text-muted"></i>
                        <span>{{ $status->name }}</span>
                        <input type="hidden" name="status_ids[]" value="{{ (int) $status->id }}">
                      </div>
                      <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-xs btn-outline-danger js-remove-bike-registration-top-option" data-remove-id="{{ (int) $status->id }}" title="Remove option">
                          <i class="ti ti-trash"></i>
                        </button>
                      </div>
                    </li>
                    @empty
                    <li class="list-group-item px-0 py-2 text-muted" id="bikeRegistrationTopNoOptions">No options added yet.</li>
                    @endforelse
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="addBikeRegistrationTopOptionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Registration Status Option</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Registration status</label>
          {{-- Do not use class "select2": custom.js inits all .select2 without dropdownParent, which breaks dropdowns inside Bootstrap modals. --}}
          <select id="bikeRegistrationTopStatusSelect" class="form-select br-registration-top-modal-select" name="bike_registration_top_status_pick">
            <option value="">Select</option>
            @foreach(($bikeRegistrationStatusesForSettings ?? collect()) as $status)
            <option value="{{ (int) $status->id }}" data-name="{{ $status->name }}">{{ $status->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnAddBikeRegistrationTopOption">Add Option</button>
      </div>
    </div>
  </div>
</div>

@push('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<style>
  /* Ensure Select2 dropdown stacks above modal backdrop (BS5 modal ~1055) */
  .select2-container--open.br-registration-top-select2-wrap {
    z-index: 1060;
  }
</style>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var targetHash = window.location.hash;
    if (targetHash === '#tab-bike-registration-top') {
      var brTabBtn = document.querySelector('[data-bs-target="' + targetHash + '"]');
      if (brTabBtn && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
        bootstrap.Tab.getOrCreateInstance(brTabBtn).show();
      } else if (brTabBtn) {
        brTabBtn.click();
      }
    }

    var topForm = document.getElementById('bikeRegistrationTopAjaxForm');
    var topCount = document.getElementById('bikeRegistrationTopSelectedCount');
    var topToggle = document.getElementById('bikeRegistrationTopEnabled');
    var topList = document.getElementById('bikeRegistrationTopSelectedList');
    var topModalSelect = document.getElementById('bikeRegistrationTopStatusSelect');
    var topAddBtn = document.getElementById('btnAddBikeRegistrationTopOption');

    function refreshBikeRegistrationTopCount() {
      if (!topList || !topCount) return;
      var count = topList.querySelectorAll('li[data-selected-id]').length;
      topCount.textContent = String(count);
      var emptyRow = document.getElementById('bikeRegistrationTopNoOptions');
      if (count === 0) {
        if (!emptyRow) {
          var li = document.createElement('li');
          li.className = 'list-group-item px-0 py-2 text-muted';
          li.id = 'bikeRegistrationTopNoOptions';
          li.textContent = 'No options added yet.';
          topList.appendChild(li);
        }
      } else if (emptyRow) {
        emptyRow.remove();
      }
    }

    function saveBikeRegistrationTopAjax() {
      if (!topForm) return;
      var fd = new FormData(topForm);
      var isChecked = document.getElementById('bikeRegistrationTopEnabled')?.checked;
      fd.set('show_in_top_bar', isChecked ? '1' : '0');
      if (topToggle) topToggle.disabled = true;
      if (topModalSelect) topModalSelect.disabled = true;
      if (topAddBtn) topAddBtn.disabled = true;

      fetch(topForm.action, {
          method: 'POST',
          body: fd,
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        })
        .then(function(r) {
          return r.json().then(function(d) {
            return {
              ok: r.ok,
              data: d
            };
          });
        })
        .then(function(result) {
          if (topToggle) topToggle.disabled = false;
          if (topModalSelect) topModalSelect.disabled = false;
          if (topAddBtn) topAddBtn.disabled = false;
          if (!result.ok || !result.data || result.data.success !== true) {
            throw new Error((result.data && result.data.message) ? result.data.message : 'Could not save.');
          }
          refreshBikeRegistrationTopCount();
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Saved',
              text: result.data.message || 'Updated successfully.',
              timer: 1400,
              showConfirmButton: false
            });
          }
        })
        .catch(function(err) {
          if (topToggle) topToggle.disabled = false;
          if (topModalSelect) topModalSelect.disabled = false;
          if (topAddBtn) topAddBtn.disabled = false;
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: (err && err.message) ? err.message : 'Could not save.'
            });
          }
        });
    }

    if (topToggle) {
      topToggle.addEventListener('change', function() {
        saveBikeRegistrationTopAjax();
      });
    }
    if (topList) {
      if (typeof Sortable !== 'undefined') {
        new Sortable(topList, {
          handle: '.bike-registration-top-drag-handle',
          draggable: 'li[data-selected-id]',
          animation: 150,
          ghostClass: 'table-warning',
          onEnd: function() {
            refreshBikeRegistrationTopCount();
            saveBikeRegistrationTopAjax();
          }
        });
      }
      topList.addEventListener('click', function(e) {
        var removeBtn = e.target.closest('.js-remove-bike-registration-top-option');
        if (!removeBtn) return;
        var row = removeBtn.closest('li[data-selected-id]');
        if (row) {
          row.remove();
          refreshBikeRegistrationTopCount();
          saveBikeRegistrationTopAjax();
        }
      });
    }

    function initBikeRegistrationTopModalSelect2() {
      if (!(typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2) || !topModalSelect) return;
      var $modal = jQuery('#addBikeRegistrationTopOptionModal');
      var $select = jQuery(topModalSelect);
      if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
      }
      $select.select2({
        dropdownParent: $modal,
        placeholder: 'Select registration status',
        allowClear: true,
        width: '100%',
        dropdownCssClass: 'br-registration-top-select2-dropdown'
      });
      var $container = $select.next('.select2');
      if ($container.length) {
        $container.addClass('br-registration-top-select2-wrap');
      }
    }
    var addBikeRegistrationTopModal = document.getElementById('addBikeRegistrationTopOptionModal');
    if (addBikeRegistrationTopModal) {
      addBikeRegistrationTopModal.addEventListener('shown.bs.modal', function() {
        initBikeRegistrationTopModalSelect2();
        requestAnimationFrame(function() {
          if (typeof jQuery !== 'undefined' && topModalSelect && jQuery.fn.select2 && jQuery(topModalSelect).data('select2')) {
            jQuery(topModalSelect).select2('open');
          }
        });
      });
      addBikeRegistrationTopModal.addEventListener('hidden.bs.modal', function() {
        if (!(typeof jQuery !== 'undefined' && topModalSelect)) return;
        var $s = jQuery(topModalSelect);
        if ($s.hasClass('select2-hidden-accessible')) {
          $s.select2('close');
        }
      });
    }
    if (topAddBtn && topModalSelect && topList) {
      topAddBtn.addEventListener('click', function() {
        var rawVal = (typeof jQuery !== 'undefined' && jQuery(topModalSelect).length && jQuery(topModalSelect).hasClass('select2-hidden-accessible')) ?
          jQuery(topModalSelect).val() :
          topModalSelect.value;
        var selectedId = parseInt(rawVal || '0', 10);
        if (!selectedId) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'warning',
              title: 'Select status',
              text: 'Please select a registration status first.'
            });
          }
          return;
        }
        if (topList.querySelector('li[data-selected-id="' + selectedId + '"]')) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'info',
              title: 'Already added',
              text: 'This status is already added.'
            });
          }
          return;
        }
        var selectedOption = topModalSelect.options[topModalSelect.selectedIndex];
        var name = selectedOption ? (selectedOption.getAttribute('data-name') || selectedOption.text || 'Status') : 'Status';
        var li = document.createElement('li');
        li.className = 'list-group-item px-0 py-2 d-flex align-items-center justify-content-between';
        li.setAttribute('data-selected-id', String(selectedId));
        li.innerHTML =
          '<div class="d-flex align-items-center">' +
          '<span class="bike-registration-top-drag-handle me-2 text-muted" title="Drag to sort" style="cursor: grab;"><i class="ti ti-grip-vertical"></i></span>' +
          '<i class="ti ti-point-filled me-1 text-muted"></i>' +
          '<span>' + name + '</span>' +
          '<input type="hidden" name="status_ids[]" value="' + selectedId + '">' +
          '</div>' +
          '<div class="d-flex align-items-center gap-1">' +
          '<button type="button" class="btn btn-xs btn-outline-danger js-remove-bike-registration-top-option" data-remove-id="' + selectedId + '" title="Remove option"><i class="ti ti-trash"></i></button>' +
          '</div>';
        topList.appendChild(li);
        refreshBikeRegistrationTopCount();
        if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2) {
          jQuery(topModalSelect).val('').trigger('change');
        } else {
          topModalSelect.value = '';
        }
        var modalEl = document.getElementById('addBikeRegistrationTopOptionModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          var modal = bootstrap.Modal.getInstance(modalEl);
          if (modal) modal.hide();
        }
        saveBikeRegistrationTopAjax();
      });
    }
    refreshBikeRegistrationTopCount();
  });
</script>
@endpush

@endsection