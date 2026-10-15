@php
  $isRider = (request('type') === 'rider') || (isset($files) && ($files->type ?? null) === 'rider');
  $editing = isset($files) && $files;
  $entityType = $editing ? ($files->type ?? request('type')) : request('type');
  $entityTypeId = $editing ? ($files->type_id ?? request('type_id')) : request('type_id');
  $suggest = request('suggested_name') ?? false;
  $documentTypes = $isRider
    ? \App\Models\RiderDocumentType::ordered()->get()
    : collect();

  $currentTypeId = old('document_type_id', $editing ? $files->document_type_id : request('document_type_id'));
  $currentSubtypeId = old('document_subtype_id', $editing ? $files->document_subtype_id : request('document_subtype_id'));

  // Auto-select from missing-doc key (e.g. passport_front) or document_type_key query
  $missingKey = (string) (request('document_key') ?: request('missing_key') ?: '');
  if ($isRider && !$editing && !$currentTypeId) {
    $resolved = null;
    if ($missingKey !== '') {
      $resolved = \App\Support\RiderDocumentFieldValues::resolveTypeFromMissingKey($missingKey);
    } elseif (request('document_type_key')) {
      $resolved = \App\Support\RiderDocumentFieldValues::resolveTypeFromMissingKey(
        (string) request('document_type_key') . (request('document_subtype_key') ? '_' . request('document_subtype_key') : '')
      );
      // If only type key without subtype suffix and subtypes exist, resolveType handles "main"
      if (!$resolved['document_type_id'] && request('document_type_key')) {
        $resolved = \App\Support\RiderDocumentFieldValues::resolveTypeFromMissingKey((string) request('document_type_key'));
      }
    }
    if ($resolved && !empty($resolved['document_type_id'])) {
      $currentTypeId = $resolved['document_type_id'];
      if (!$currentSubtypeId && !empty($resolved['document_subtype_id'])) {
        $currentSubtypeId = $resolved['document_subtype_id'];
      }
    }
  }

  // If subtype key passed explicitly with type already known
  if ($isRider && !$editing && $currentTypeId && !$currentSubtypeId && request('document_subtype_key')) {
    $currentSubtypeId = \App\Models\RiderDocumentTypeSubtype::query()
      ->where('document_type_id', $currentTypeId)
      ->where('key', (string) request('document_subtype_key'))
      ->where('is_active', true)
      ->value('id');
  }

  $savedFieldValues = old('field_values', ($editing && is_array($files->field_values ?? null)) ? $files->field_values : []);

  // Prefill from rider linked columns on create
  if ($isRider && !$editing && empty($savedFieldValues) && $currentSubtypeId && $entityTypeId) {
    $rider = \App\Models\Riders::find($entityTypeId);
    $savedFieldValues = \App\Support\RiderDocumentFieldValues::prefillFromRider($rider, (int) $currentSubtypeId);
  }
@endphp

<!-- Type Field -->
<input type="hidden" name="type" value="{{ $entityType }}" />
<input type="hidden" name="type_id" value="{{ $entityTypeId }}" />

<!-- File Name Field -->
<div class="col-12">
  <input type="hidden" name="name" value="0" />
</div>

@if($isRider)
<div class="col-12 mb-3">
  <label class="pl-2">Document Type</label>
  <select name="document_type_id" id="rider_document_type_id" class="form-select" style="height: 40px;">
    <option value="">— Select type —</option>
    @foreach($documentTypes as $docType)
      <option value="{{ $docType->id }}"
        data-key="{{ $docType->key }}"
        @selected((string) $currentTypeId === (string) $docType->id)>
        {{ $docType->label ?: ($docType->front_label ?: $docType->key) }}
      </option>
    @endforeach
  </select>
</div>

<div class="col-12 mb-3" id="rider_document_subtype_wrap" style="{{ $currentTypeId ? '' : 'display:none;' }}">
  <label class="pl-2">Document Subtype</label>
  <select name="document_subtype_id" id="rider_document_subtype_id" class="form-select" style="height: 40px;">
    <option value="">— Select subtype —</option>
  </select>
  <small class="text-muted">Fields below change based on the selected subtype only.</small>
</div>

<div class="col-12 mb-3" id="rider_document_dynamic_fields"></div>
@endif

@if($suggest)
<div class="col-12">
  <input type="hidden" name="suggested_name" id="suggested_name_input" value="{{ $suggest }}" />
  <label class=" pl-2">File Name Will Be stored As: <strong class="text-danger" id="suggested_name_label">{{ $suggest }}</strong></label>
</div>
@else
<div class="col-12">
  <label class=" pl-2">Suggest File Name<small class="text-muted"> (Optional)</small></label>
  <input type="text" name="suggested_name" id="suggested_name_input" class="form-control" style="height: 40px;" value="{{ old('suggested_name', $editing ? ($files->name ?? '') : '') }}" />
  <small class="text-muted mb-3">This Will be Saved Instead of File Name</small>
</div>
@endif

<div class="col-12 mb-3">
  @include('partials.universal_document_upload', [
    'name' => 'file_name',
    'label' => 'Document',
    'required' => !$editing,
    'inputClass' => 'form-control',
    'labelClass' => 'pl-2',
    'showHint' => true,
  ])
  @if($editing && !empty($files->file_name))
    <small class="text-muted">Leave empty to keep the existing file.</small>
  @endif
</div>

@if(!$isRider)
<div class="col-12">
  <label class="pl-2">Expiry Date <small class="text-muted">(Optional)</small></label>
  <input type="date" name="expiry_date" class="form-control mb-3" style="height: 40px;" value="{{ old('expiry_date', $editing && $files->expiry_date ? \Illuminate\Support\Carbon::parse($files->expiry_date)->format('Y-m-d') : '') }}" />
</div>
@else
  {{-- Rider: expiry comes from dynamic document fields (e.g. Passport Expiry) --}}
  <input type="hidden" name="expiry_date" id="rider_expiry_date_hidden" value="{{ old('expiry_date', $editing && $files->expiry_date ? \Illuminate\Support\Carbon::parse($files->expiry_date)->format('Y-m-d') : '') }}" />
@endif

@if($isRider)
@php
  $subtypesUrlTpl = route('files.rider-document-type-subtypes', [
    'company_slug' => request()->route('company_slug') ?? session('company_slug'),
    'id' => '__ID__',
  ]);
  $fieldsUrlTpl = route('files.rider-document-subtype-fields', [
    'company_slug' => request()->route('company_slug') ?? session('company_slug'),
    'id' => '__ID__',
  ]);
  $prefillUrlTpl = route('files.rider-document-subtype-prefill', [
    'company_slug' => request()->route('company_slug') ?? session('company_slug'),
    'id' => '__ID__',
  ]) . '?rider_id=' . urlencode((string) $entityTypeId);
@endphp
<script>
(function() {
  var subtypesUrlTpl = @json($subtypesUrlTpl);
  var fieldsUrlTpl = @json($fieldsUrlTpl);
  var prefillUrlTpl = @json($prefillUrlTpl);
  var initialTypeId = @json($currentTypeId ? (string) $currentTypeId : '');
  var initialSubtypeId = @json($currentSubtypeId ? (string) $currentSubtypeId : '');
  var savedValues = @json($savedFieldValues ?: new \stdClass());
  var lockedSuggest = @json($suggest ? true : false);
  var isEditing = @json($editing);

  var typeSelect = document.getElementById('rider_document_type_id');
  var subtypeSelect = document.getElementById('rider_document_subtype_id');
  var subtypeWrap = document.getElementById('rider_document_subtype_wrap');
  var fieldsWrap = document.getElementById('rider_document_dynamic_fields');
  var suggestInput = document.getElementById('suggested_name_input');
  var suggestLabel = document.getElementById('suggested_name_label');

  if (!typeSelect || !subtypeSelect || !fieldsWrap) return;

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function updateSuggestedName() {
    if (lockedSuggest) return;
    var typeOpt = typeSelect.options[typeSelect.selectedIndex];
    var subtypeOpt = subtypeSelect.options[subtypeSelect.selectedIndex];
    if (!typeOpt || !typeOpt.value) return;
    var key = typeOpt.getAttribute('data-key') || '';
    var subtypeKey = subtypeOpt && subtypeOpt.value ? (subtypeOpt.getAttribute('data-key') || '') : '';
    var name = key;
    if (subtypeKey && subtypeKey !== 'main') name = key + '_' + subtypeKey;
    if (suggestInput && suggestInput.dataset.auto !== '0') {
      suggestInput.value = name;
    }
    if (suggestLabel) suggestLabel.textContent = suggestInput ? suggestInput.value : name;
  }

  function clearFields() {
    fieldsWrap.innerHTML = '';
  }

  function renderFields(fields, values) {
    clearFields();
    if (!fields || !fields.length) {
      return;
    }
    values = values || {};
    var html = '<div class="border rounded p-3 bg-light"><h6 class="mb-3">Document fields</h6>';
    fields.forEach(function(f) {
      if (!f.is_active) return;
      var val = values[f.key] != null ? values[f.key] : '';
      var req = f.is_required ? ' required' : '';
      var reqMark = f.is_required ? ' <span class="text-danger">*</span>' : '';
      var name = 'field_values[' + f.key + ']';
      html += '<div class="mb-3">';
      html += '<label class="pl-2">' + esc(f.label) + reqMark + '</label>';
      if (f.input_type === 'textarea') {
        html += '<textarea name="' + name + '" class="form-control"' + req + ' rows="2">' + esc(val) + '</textarea>';
      } else if (f.input_type === 'select') {
        html += '<select name="' + name + '" class="form-select"' + req + '><option value="">— Select —</option>';
        (f.options || []).forEach(function(opt) {
          var ov = (typeof opt === 'object' && opt) ? (opt.value || opt.label || '') : String(opt);
          var ol = (typeof opt === 'object' && opt) ? (opt.label || opt.value || '') : String(opt);
          html += '<option value="' + esc(ov) + '"' + (String(val) === String(ov) ? ' selected' : '') + '>' + esc(ol) + '</option>';
        });
        html += '</select>';
      } else {
        var itype = f.input_type === 'number' ? 'number' : (f.input_type === 'date' ? 'date' : 'text');
        html += '<input type="' + itype + '" name="' + name + '" class="form-control" style="height:40px;" value="' + esc(val) + '"' + req + ' />';
      }
      html += '</div>';
    });
    html += '</div>';
    fieldsWrap.innerHTML = html;
  }

  function fetchPrefill(subtypeId) {
    if (isEditing || !subtypeId) return Promise.resolve({});
    return fetch(prefillUrlTpl.replace('__ID__', String(subtypeId)), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(r) { return r.json(); }).then(function(data) {
      return (data && data.values) ? data.values : {};
    }).catch(function() { return {}; });
  }

  function loadSubtypes(typeId, preferredSubtypeId, thenLoadFields) {
    subtypeSelect.innerHTML = '<option value="">— Select subtype —</option>';
    clearFields();
    if (!typeId) {
      subtypeWrap.style.display = 'none';
      return Promise.resolve();
    }
    return fetch(subtypesUrlTpl.replace('__ID__', String(typeId)), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(r) { return r.json(); }).then(function(data) {
      var list = (data.subtypes || []).filter(function(s) { return s.is_active; });
      if (!list.length) {
        subtypeWrap.style.display = 'none';
        updateSuggestedName();
        return;
      }
      subtypeWrap.style.display = '';
      list.forEach(function(s) {
        var opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.label;
        opt.setAttribute('data-key', s.key);
        if (preferredSubtypeId && String(preferredSubtypeId) === String(s.id)) {
          opt.selected = true;
        }
        subtypeSelect.appendChild(opt);
      });
      // Auto-pick single subtype or preferred
      if (!subtypeSelect.value && list.length === 1) {
        subtypeSelect.value = list[0].id;
      }
      updateSuggestedName();
      if (thenLoadFields && subtypeSelect.value) {
        return loadFields(subtypeSelect.value, savedValues);
      }
    });
  }

  function loadFields(subtypeId, values) {
    clearFields();
    if (!subtypeId) return Promise.resolve();
    var valuesPromise = (values && Object.keys(values).length)
      ? Promise.resolve(values)
      : fetchPrefill(subtypeId);
    return Promise.all([
      fetch(fieldsUrlTpl.replace('__ID__', String(subtypeId)), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      }).then(function(r) { return r.json(); }),
      valuesPromise
    ]).then(function(results) {
      var data = results[0] || {};
      var vals = results[1] || {};
      renderFields(data.fields || [], vals);
    });
  }

  typeSelect.addEventListener('change', function() {
    savedValues = {};
    if (suggestInput) suggestInput.dataset.auto = '1';
    loadSubtypes(this.value, null, true).then(function() {
      updateSuggestedName();
    });
  });

  subtypeSelect.addEventListener('change', function() {
    savedValues = {};
    if (suggestInput) suggestInput.dataset.auto = '1';
    updateSuggestedName();
    loadFields(this.value, {});
  });

  if (suggestInput && !lockedSuggest) {
    suggestInput.addEventListener('input', function() {
      this.dataset.auto = '0';
    });
  }

  if (initialTypeId) {
    typeSelect.value = initialTypeId;
    loadSubtypes(initialTypeId, initialSubtypeId, true);
  }
})();
</script>
@endif
