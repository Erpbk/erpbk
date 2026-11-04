@extends($layout ?? 'layouts.app')

@section('title', ($moduleLabel ?? 'Voucher Signature Assignment') . ' – ' . ($voucherType->label ?? ''))

@push('third_party_stylesheets')
<style>
  .vsa-slot {
    border: 1px solid #e7e7e8;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    background: #fff;
  }
  .vsa-slot .drag-handle {
    cursor: grab;
    color: #697a8d;
  }
  .vsa-slot .drag-handle:active {
    cursor: grabbing;
  }
  .vsa-slot.sortable-ghost {
    opacity: 0.5;
  }
</style>
@endpush

@section('content')
@include('flash::message')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <a href="{{ route('settings-panel.voucher-signature-assignment.index', ['company_slug' => $company_slug]) }}"
             class="text-muted small text-decoration-none">&larr; Back to list</a>
          <h4 class="card-title mb-0 mt-1">
            {{ $voucherType->label }}
            <span class="badge bg-label-secondary ms-1">{{ $voucherType->code }}</span>
          </h4>
          <p class="text-muted small mb-0 mt-1">
            Add, remove, and reorder signature slots. Leave empty to keep the legacy signature layout for this voucher type.
          </p>
        </div>
        @if(!empty($canEdit) && $config)
        <form method="post"
              action="{{ route('settings-panel.voucher-signature-assignment.clear', ['company_slug' => $company_slug, 'code' => $voucherType->code]) }}"
              onsubmit="return confirm('Clear all signature slots for this voucher type?');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-sm">Clear configuration</button>
        </form>
        @endif
      </div>
    </div>
  </div>
</div>

<form method="post"
      action="{{ route('settings-panel.voucher-signature-assignment.save', ['company_slug' => $company_slug, 'code' => $voucherType->code]) }}"
      id="vsa-form">
  @csrf

  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">Signature slots</h5>
          @if(!empty($canEdit))
          <button type="button" class="btn btn-sm btn-primary" id="vsa-add-slot">Add slot</button>
          @endif
        </div>
        <div class="card-body">
          <div id="vsa-slots">
            @foreach($slots as $index => $slot)
              @include('settings.voucher_signature_assignment._slot_row', [
                'index' => $index,
                'slot' => $slot,
                'assigneeTypes' => $assigneeTypes,
                'departments' => $departments,
                'users' => $users,
                'employees' => $employees,
                'canEdit' => $canEdit,
              ])
            @endforeach
          </div>
          <div id="vsa-empty" class="text-muted text-center py-4 @if($slots->isNotEmpty()) d-none @endif">
            No slots configured. Click <strong>Add slot</strong> to define a signature workflow, or leave empty for the legacy layout.
          </div>
        </div>
        @if(!empty($canEdit))
        <div class="card-footer d-flex justify-content-end gap-2">
          <a href="{{ route('settings-panel.voucher-signature-assignment.index', ['company_slug' => $company_slug]) }}"
             class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Save signature assignment</button>
        </div>
        @endif
      </div>
    </div>
  </div>
</form>

<template id="vsa-slot-template">
  @include('settings.voucher_signature_assignment._slot_row', [
    'index' => '__INDEX__',
    'slot' => null,
    'assigneeTypes' => $assigneeTypes,
    'departments' => $departments,
    'users' => $users,
    'employees' => $employees,
    'canEdit' => true,
  ])
</template>
@endsection

@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
  var slotsEl = document.getElementById('vsa-slots');
  var emptyEl = document.getElementById('vsa-empty');
  var addBtn = document.getElementById('vsa-add-slot');
  var template = document.getElementById('vsa-slot-template');
  var canEdit = @json(!empty($canEdit));

  function reindex() {
    var rows = slotsEl.querySelectorAll('.vsa-slot');
    rows.forEach(function (row, i) {
      row.querySelectorAll('[name]').forEach(function (input) {
        input.name = input.name.replace(/slots\[(?:\d+|__INDEX__)\]/, 'slots[' + i + ']');
      });
      var activeCb = row.querySelector('input[type="checkbox"][name*="[is_active]"]');
      if (activeCb) {
        var newId = 'vsa-active-' + i;
        activeCb.id = newId;
        var label = row.querySelector('label[for^="vsa-active-"]');
        if (label) label.setAttribute('for', newId);
      }
      var badge = row.querySelector('.vsa-slot-index');
      if (badge) badge.textContent = '#' + (i + 1);
    });
    if (emptyEl) {
      emptyEl.classList.toggle('d-none', rows.length > 0);
    }
  }

  function toggleAssigneeFields(row) {
    var type = row.querySelector('.vsa-assignee-type');
    if (!type) return;
    var val = type.value;
    row.querySelectorAll('[data-assignee]').forEach(function (el) {
      el.classList.toggle('d-none', el.getAttribute('data-assignee') !== val);
    });
  }

  function bindRow(row) {
    var typeSelect = row.querySelector('.vsa-assignee-type');
    if (typeSelect) {
      typeSelect.addEventListener('change', function () { toggleAssigneeFields(row); });
      toggleAssigneeFields(row);
    }
    var removeBtn = row.querySelector('.vsa-remove-slot');
    if (removeBtn) {
      removeBtn.addEventListener('click', function () {
        row.remove();
        reindex();
      });
    }
  }

  slotsEl.querySelectorAll('.vsa-slot').forEach(bindRow);

  if (canEdit && addBtn && template) {
    addBtn.addEventListener('click', function () {
      var html = template.innerHTML.replace(/__INDEX__/g, String(slotsEl.querySelectorAll('.vsa-slot').length));
      var wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      var row = wrap.firstElementChild;
      slotsEl.appendChild(row);
      bindRow(row);
      reindex();
    });
  }

  if (canEdit && typeof Sortable !== 'undefined') {
    new Sortable(slotsEl, {
      handle: '.drag-handle',
      animation: 150,
      ghostClass: 'sortable-ghost',
      onEnd: reindex
    });
  }
})();
</script>
@endpush
