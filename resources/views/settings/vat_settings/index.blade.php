@extends($layout ?? 'layouts.app')
@section('title', 'VAT Settings')

@section('content')
@include('flash::message')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h4 class="card-title mb-0">VAT Settings</h4>
        <small class="text-muted">Configure VAT (Value Added Tax) for the application</small>
      </div>
      <div class="card-body">
        <p class="text-muted mb-0">
          Set your company VAT registration number and default VAT percentage. These values are used across invoices, receipts, and other modules when calculating tax.
        </p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <ul class="nav nav-tabs mb-3" id="vatSettingsMainTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">General</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-configuration-btn" data-bs-toggle="tab" data-bs-target="#tab-configuration" type="button" role="tab">Configuration</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-quarters-btn" data-bs-toggle="tab" data-bs-target="#tab-quarters" type="button" role="tab">VAT Quarters</button>
          </li>
        </ul>

        <div class="tab-content" id="vatSettingsTabContent">
          {{-- Tab 1: General (name in menu) --}}
          <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <p class="text-muted small mb-3">This name appears in the settings panel sidebar.</p>
            <form action="{{ route('settings-panel.vat-settings.store-module-label') }}" method="POST" class="row g-3 align-items-end">
              @csrf
              <div class="col-md-6">
                <label class="form-label">Name in menu</label>
                <input type="text" name="module_label" class="form-control" value="{{ old('module_label', $moduleLabel ?? 'VAT Settings') }}" placeholder="VAT Settings" maxlength="100" required>
              </div>
              <div class="col-md-6">
                <button type="submit" class="btn btn-primary">Save name</button>
              </div>
            </form>
          </div>

          {{-- Tab 2: Configuration (VAT number, percentage, enabled) --}}
          <div class="tab-pane fade" id="tab-configuration" role="tabpanel">
            <p class="text-muted small mb-3">VAT number is shown on documents; default percentage is used when a rate is not specified.</p>
            <form action="{{ route('settings-panel.vat-settings.store') }}" method="post" id="vat-settings-form">
              @csrf
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">VAT registration number</label>
                  <input type="text" class="form-control" name="vat_number" value="{{ old('vat_number', $vat['vat_number'] ?? '') }}" placeholder="e.g. 100000000000003" maxlength="100" />
                  <small class="text-muted">Company VAT number shown on invoices and official documents.</small>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Default VAT percentage</label>
                  <div class="input-group">
                    <input type="number" class="form-control" name="vat_percentage" value="{{ old('vat_percentage', $vat['vat_percentage'] ?? '') }}" placeholder="5" step="0.01" min="0" max="100" />
                    <span class="input-group-text">%</span>
                  </div>
                  <small class="text-muted">Default rate used when a specific rate is not set (e.g. 5 for 5%).</small>
                </div>
                <div class="col-12 mb-3">
                  <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="vat_enabled" id="vat_enabled" value="1" {{ old('vat_enabled', $vat['vat_enabled'] ?? '1') === '1' ? 'checked' : '' }} />
                    <label class="form-check-label" for="vat_enabled">VAT enabled</label>
                  </div>
                  <small class="text-muted">When enabled, VAT is applied according to module logic (e.g. rider invoices, leasing).</small>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">VAT Input Account</label>
                  <select class="form-select" name="vat_input_account_id" id="vat_input_account_id">
                    <option value="">— Use default VAT account —</option>
                    @foreach(\App\Models\Accounts::orderBy('account_code')->get() as $acc)
                      <option value="{{ $acc->id }}" {{ old('vat_input_account_id', $vat['vat_input_account_id'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} – {{ $acc->name }}</option>
                    @endforeach
                  </select>
                  <small class="text-muted">Account for VAT on purchases (input). Leave empty to use default.</small>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">VAT Output Account</label>
                  <select class="form-select" name="vat_output_account_id" id="vat_output_account_id">
                    <option value="">— Use default VAT account —</option>
                    @foreach(\App\Models\Accounts::orderBy('account_code')->get() as $acc)
                      <option value="{{ $acc->id }}" {{ old('vat_output_account_id', $vat['vat_output_account_id'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} – {{ $acc->name }}</option>
                    @endforeach
                  </select>
                  <small class="text-muted">Account for VAT on sales (output). Leave empty to use default. VAT module shows combined entries of both.</small>
                </div>
              </div>
              <hr />
              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Save VAT settings</button>
              </div>
            </form>
          </div>

          {{-- Tab 3: VAT Quarters — add via modal with month checkboxes --}}
          <div class="tab-pane fade" id="tab-quarters" role="tabpanel">
            <p class="text-muted small mb-3">Add up to 4 VAT quarters. Each quarter has 3 consecutive months. Click "Add quarter", select a start month (the next two months are included automatically), then save. The quarter name is generated from the months (e.g. "January – March") or you can set a custom name.</p>

            <div class="mb-3">
              @if (count(array_filter($quarterStarts ?? [])) < 4)
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addQuarterModal">
                <i class="ti ti-plus me-1"></i> Add quarter
                </button>
                @else
                <span class="text-muted small">Maximum 4 quarters. Remove one to add another.</span>
                @endif
            </div>

            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead class="table-light">
                  <tr>
                    <th style="width: 80px;">#</th>
                    <th>Quarter name</th>
                    <th>Months</th>
                    <th style="width: 100px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @php
                  $quarterStarts = $quarterStarts ?? [];
                  $quarterNames = $quarterNames ?? [];
                  $displayIndex = 0;
                  @endphp
                  @for ($q = 0; $q < 4; $q++)
                    @if (!empty($quarterStarts[$q]))
                    @php
                    $displayIndex++;
                    $start = (int) $quarterStarts[$q];
                    $monthsInQ = \App\Http\Controllers\VatSettingsController::quarterMonthsForStart($start);
                    $autoName = implode(' – ', array_map(function ($m) use ($monthNames) { return $monthNames[$m] ?? ''; }, $monthsInQ));
                    $name = !empty($quarterNames[$q]) ? $quarterNames[$q] : $autoName;
                    $monthsLabel = implode(', ', array_map(function ($m) use ($monthNames) { return $monthNames[$m] ?? ''; }, $monthsInQ));
                    @endphp
                    <tr>
                    <td>{{ $displayIndex }}</td>
                    <td><strong>{{ $name }}</strong></td>
                    <td>{{ $monthsLabel }}</td>
                    <td>
                      <form action="{{ route('settings-panel.vat-settings.delete-quarter', $q + 1) }}" method="post" class="d-inline" onsubmit="return confirm('Remove this quarter?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                      </form>
                    </td>
                    </tr>
                    @endif
                    @endfor
                    @if ($displayIndex === 0)
                    <tr>
                      <td colspan="4" class="text-muted text-center py-4">No quarters defined. Click "Add quarter" to create one.</td>
                    </tr>
                    @endif
                </tbody>
              </table>
            </div>

            {{-- Modal: Add quarter — 12 month checkboxes --}}
            <div class="modal fade" id="addQuarterModal" tabindex="-1" aria-labelledby="addQuarterModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form action="{{ route('settings-panel.vat-settings.store-quarter') }}" method="post" id="add-quarter-form">
                    @csrf
                    <div class="modal-header">
                      <h5 class="modal-title" id="addQuarterModalLabel">Add VAT quarter</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      @error('start_month')
                      <p class="text-danger small">{{ $message }}</p>
                      @enderror
                      <p class="small text-muted mb-2">Select any month; the next two months are selected automatically (e.g. November → November, December, January). Months already used in another quarter are disabled.</p>
                      <div class="row g-2 mb-3" id="quarter-month-checkboxes">
                        @php
                        $usedMonths = [];
                        foreach ($quarterStarts ?? [] as $s) {
                          if ($s !== null) {
                            $usedMonths = array_merge($usedMonths, \App\Http\Controllers\VatSettingsController::quarterMonthsForStart($s));
                          }
                        }
                        $usedMonths = array_unique($usedMonths);
                        @endphp
                        @foreach ($monthNames ?? [] as $num => $label)
                        @php $isUsed = in_array($num, $usedMonths); @endphp
                        <div class="col-6 col-md-4">
                          <div class="form-check">
                            <input class="form-check-input vat-quarter-month-cb" type="checkbox" name="months[]" value="{{ $num }}" id="qmonth_{{ $num }}"
                              {{ $isUsed ? 'disabled' : '' }} data-month-num="{{ $num }}">
                            <label class="form-check-label {{ $isUsed ? 'text-muted' : '' }}" for="qmonth_{{ $num }}">{{ $label }}</label>
                          </div>
                        </div>
                        @endforeach
                      </div>
                      <input type="hidden" name="start_month" id="quarter-start-month" value="">
                      <div class="mb-2">
                        <label class="form-label small mb-0">Quarter name <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control form-control-sm" name="quarter_name" id="quarter-name-input" placeholder="e.g. Q1 or January – March" maxlength="100">
                        <small class="text-muted">Auto-filled from selected months; you can change it to a custom name.</small>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-primary" id="add-quarter-submit">Create quarter</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
@section('page-script')
<script>
  (function() {
    var monthNames = @json($monthNames ?? []);
    var modal = document.getElementById('addQuarterModal');
    var checkboxes = document.querySelectorAll('.vat-quarter-month-cb');
    var startInput = document.getElementById('quarter-start-month');
    var nameInput = document.getElementById('quarter-name-input');
    var form = document.getElementById('add-quarter-form');
    var submitBtn = document.getElementById('add-quarter-submit');

    function getUsedMonths() {
      var used = [];
      checkboxes.forEach(function(cb) {
        if (cb.disabled) {
          used.push(parseInt(cb.getAttribute('data-month-num'), 10));
        }
      });
      return used;
    }

    function monthsForStart(start) {
      if (start >= 1 && start <= 10) return [start, start + 1, start + 2];
      if (start === 11) return [11, 12, 1];
      if (start === 12) return [12, 1, 2];
      return [];
    }

    function setQuarterFromStart(start) {
      if (start < 1 || start > 12) return;
      var months = monthsForStart(start);
      checkboxes.forEach(function(cb) {
        if (cb.disabled) return;
        var num = parseInt(cb.getAttribute('data-month-num'), 10);
        cb.checked = months.indexOf(num) !== -1;
      });
      startInput.value = start;
      nameInput.value = months.map(function(m) { return monthNames[m] || 'Month ' + m; }).join(' – ');
    }

    function clearQuarterSelection() {
      checkboxes.forEach(function(cb) {
        if (!cb.disabled) cb.checked = false;
      });
      startInput.value = '';
      nameInput.value = '';
    }

    checkboxes.forEach(function(cb) {
      cb.addEventListener('change', function() {
        var num = parseInt(this.getAttribute('data-month-num'), 10);
        if (this.checked) {
          setQuarterFromStart(num);
        } else {
          var start = parseInt(startInput.value, 10);
          var months = monthsForStart(start);
          if (months.indexOf(num) !== -1) {
            clearQuarterSelection();
          }
        }
      });
    });

    if (modal) {
      modal.addEventListener('show.bs.modal', clearQuarterSelection);
      modal.addEventListener('hidden.bs.modal', clearQuarterSelection);
    }

    if (form) {
      form.addEventListener('submit', function() {
        if (!startInput.value) {
          return false;
        }
      });
    }

    if (window.location.search.indexOf('tab=quarters') !== -1) {
      var quartersTab = document.getElementById('tab-quarters-btn');
      if (quartersTab) quartersTab.click();
    }
  })();
</script>
@endsection