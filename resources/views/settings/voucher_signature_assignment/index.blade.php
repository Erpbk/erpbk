@extends($layout ?? 'layouts.app')

@section('title', ($moduleLabel ?? 'Voucher Signature Assignment') . ' – Site Settings')

@push('third_party_stylesheets')
<style>
:root{
  --vsa-ink:#1B1D2A;--vsa-ink2:#3A3D52;--vsa-mute:#5A5E75;--vsa-mute2:#6A6E85;
  --vsa-line:#E4E6F0;--vsa-line2:#ECEDF4;--vsa-bg:#F4F5FA;--vsa-soft:#FAFAFD;
  --vsa-brand:#2F2FD8;--vsa-brand-ink:#2525B8;--vsa-brand-soft:#ECECFD;
  --vsa-ok:#145234;--vsa-ok-bg:#F1FAF5;--vsa-ok-line:#CFE8DA;
}
.vsa-wrap{display:flex;flex-direction:column;gap:18px;color:var(--vsa-ink);font-size:14px}
.vsa-card{background:#fff;border:1px solid var(--vsa-line);border-radius:14px}
.vsa-head{padding:24px 28px;display:flex;flex-wrap:wrap;gap:24px;justify-content:space-between;align-items:flex-start}
.vsa-head .ic{width:48px;height:48px;border-radius:12px;background:var(--vsa-brand-soft);color:var(--vsa-brand);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.vsa-head h1{margin:0;font-size:22px;font-weight:700}
.vsa-head p{margin:0;font-size:14px;line-height:1.5;color:var(--vsa-mute);max-width:620px}
.vsa-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;flex:0 1 400px;min-width:280px}
.vsa-stat{border:1px solid var(--vsa-line);border-radius:10px;padding:12px 14px}
.vsa-stat small{font-size:12px;color:var(--vsa-mute)}.vsa-stat b{display:block;font-size:24px;margin-top:2px}
.vsa-stat.ok{border-color:var(--vsa-ok-line);background:var(--vsa-ok-bg)}.vsa-stat.ok small{color:#1F6B45}.vsa-stat.ok b{color:var(--vsa-ok)}
.vsa-stat.lg{background:#F8F8FB}
.vsa-iconbtn{border:0;background:transparent;color:var(--vsa-mute2);width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center}
.vsa-iconbtn:hover{background:#F1F2F8;color:var(--vsa-ink)}
.vsa-btnp{height:38px;padding:0 16px;border-radius:9px;border:0;background:var(--vsa-brand);color:#fff;font-size:14px;font-weight:600}
.vsa-btns{height:38px;padding:0 14px;border-radius:9px;border:1px solid #D5D7E5;background:#fff;color:var(--vsa-ink2);font-size:14px}
.vsa-inp{height:36px;padding:0 10px;border:1px solid #D5D7E5;border-radius:8px;font-size:13.5px;background:#fff;width:100%}
.vsa-toolbar{padding:16px 20px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;border-bottom:1px solid var(--vsa-line2)}
.vsa-pills{display:flex;gap:4px;background:#F1F2F8;padding:4px;border-radius:10px;flex-wrap:wrap}
.vsa-pills button{height:34px;padding:0 14px;border-radius:8px;border:0;font-size:13px;font-weight:500;background:transparent;color:var(--vsa-mute)}
.vsa-pills button.on{background:#fff;color:var(--vsa-ink);font-weight:600;box-shadow:0 1px 2px rgba(20,20,40,.12)}
.vsa-pills button span{color:var(--vsa-mute2);font-weight:500;margin-left:3px}
.vsa-search{position:relative;flex:0 1 300px;min-width:200px}
.vsa-search svg{position:absolute;left:12px;top:10px}
.vsa-search .vsa-inp{padding-left:36px;border-radius:10px}
.vsa-code{display:inline-block;min-width:42px;text-align:center;font-family:ui-monospace,Menlo,monospace;font-size:12px;font-weight:600;padding:4px 8px;border-radius:6px;background:var(--vsa-brand-soft);color:var(--vsa-brand-ink)}
.vsa-kind{font-size:11.5px;font-weight:600;padding:3px 8px;border-radius:999px;background:var(--vsa-brand-soft);color:var(--vsa-brand-ink)}
.vsa-modchip{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--vsa-ink2);padding:3px 8px;border-radius:6px;background:#F4F5F9;border:1px solid var(--vsa-line);white-space:nowrap}
.vsa-modchip i{width:7px;height:7px;border-radius:2px;background:var(--vsa-mute2);display:inline-block}
.vsa-legacy{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--vsa-mute);padding:4px 10px;border-radius:999px;background:#F1F2F8}
.vsa-legacy i{width:6px;height:6px;border-radius:999px;background:#9A9DB2;display:inline-block}
.vsa-flow{display:flex;flex-wrap:nowrap;gap:3px;align-items:center}
.vsa-flow svg{width:11px;height:11px;flex-shrink:0}
.vsa-chip{display:inline-flex;align-items:center;padding:3px 7px;border-radius:999px;background:var(--vsa-ok-bg);border:1px solid var(--vsa-ok-line);font-size:11.5px;color:var(--vsa-ok);white-space:nowrap}
.vsa-act,.vsa-act:link,.vsa-act:visited{height:34px;padding:0 14px;border-radius:8px;font-size:13px;font-weight:600;border:1px solid var(--vsa-brand);background:var(--vsa-brand);color:#fff !important;text-decoration:none;display:inline-flex;align-items:center}
.vsa-act.edit,.vsa-act.edit:link,.vsa-act.edit:visited{border-color:#D5D7E5;background:#fff;color:var(--vsa-ink) !important}
.vsa-act:hover,.vsa-act:focus{color:#fff !important;opacity:.92}
.vsa-act.edit:hover,.vsa-act.edit:focus{color:var(--vsa-ink) !important;opacity:1;background:#F8F8FD}
.vsa-grip{width:26px;height:30px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;color:#A3A6B8;cursor:grab}
.vsa-tblwrap{overflow-x:auto}
.vsa-tbl{width:100%;border-collapse:collapse}
.vsa-tbl th{padding:12px;font-size:12px;font-weight:600;color:var(--vsa-mute);text-transform:uppercase;letter-spacing:.04em;text-align:left;background:var(--vsa-soft);white-space:nowrap}
.vsa-tbl td{padding:12px;border-top:1px solid var(--vsa-line2);vertical-align:middle;white-space:nowrap}
.vsa-tbl tr.r:hover td{background:#F8F8FD}
.vsa-tbl tr.r.dragging{opacity:.35}
.vsa-tbl tr.r.over-top td{box-shadow:inset 0 3px 0 var(--vsa-brand)}
.vsa-tbl tr.r.over-bot td{box-shadow:inset 0 -3px 0 var(--vsa-brand)}
.vsa-toast{position:fixed;left:50%;bottom:28px;transform:translateX(-50%);background:var(--vsa-ink);color:#fff;padding:10px 16px;border-radius:10px;font-size:13px;opacity:0;transition:opacity .2s;pointer-events:none;z-index:50}
.vsa-toast.show{opacity:1}
</style>
@endpush

@section('content')
@include('flash::message')
@php
  $docs = collect($documents ?? []);
  $configuredCount = $docs->where('configured', true)->count();
  $legacyCount = $docs->count() - $configuredCount;
  $canEdit = !empty($canEdit);
@endphp
<div class="vsa-wrap" id="vsa-root"
     data-can-edit="{{ $canEdit ? '1' : '0' }}"
     data-reorder-url="{{ route('settings-panel.voucher-signature-assignment.reorder', ['company_slug' => $company_slug]) }}"
     data-label-url="{{ route('settings-panel.voucher-signature-assignment.store-module-label', ['company_slug' => $company_slug]) }}">

  <div class="vsa-card vsa-head">
    <div style="display:flex;gap:16px;flex:1 1 420px;min-width:0">
      <div class="ic">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17c3-1 4-6 6-6s1 5 3 5 3-3 4-3 2 2 5 2"/><path d="M3 21h18"/></svg>
      </div>
      <div style="display:flex;flex-direction:column;gap:6px;min-width:0">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap" id="vsa-name-row">
          <h1 id="vsa-name-display">{{ $moduleLabel }}</h1>
          @if($canEdit)
          <button type="button" class="vsa-iconbtn" id="vsa-edit-name" aria-label="Rename module">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
          </button>
          @endif
        </div>
        <form id="vsa-name-form" method="post" action="{{ route('settings-panel.voucher-signature-assignment.store-module-label', ['company_slug' => $company_slug]) }}" class="d-none" style="display:none;align-items:center;gap:8px;flex-wrap:wrap">
          @csrf
          <input type="text" name="module_label" id="vsa-name-inp" class="vsa-inp" style="width:320px;max-width:100%;font-size:16px;font-weight:600" value="{{ $moduleLabel }}" maxlength="100" required>
          <button type="submit" class="vsa-btnp">Save</button>
          <button type="button" class="vsa-btns" id="vsa-cancel-name">Cancel</button>
        </form>
        <p>Set the header and signatures for every voucher. They print on the view, print and PDF. Documents without signature slots keep the legacy layout. Drag the dots to change the order.</p>
      </div>
    </div>
    <div class="vsa-stats">
      <div class="vsa-stat"><small>Documents</small><b id="vsa-stat-docs">{{ $docs->count() }}</b></div>
      <div class="vsa-stat ok"><small>Configured</small><b id="vsa-stat-cfg">{{ $configuredCount }}</b></div>
      <div class="vsa-stat lg"><small>Legacy layout</small><b id="vsa-stat-leg">{{ $legacyCount }}</b></div>
    </div>
  </div>

  <div class="vsa-card" style="overflow:hidden">
    <div class="vsa-toolbar">
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <div class="vsa-pills" id="vsa-status-pills">
          <button type="button" data-status="all" class="on">Any status</button>
          <button type="button" data-status="configured">Configured<span>{{ $configuredCount }}</span></button>
          <button type="button" data-status="legacy">Legacy<span>{{ $legacyCount }}</span></button>
        </div>
      </div>
      <div class="vsa-search">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6A6E85" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input id="vsa-q" class="vsa-inp" placeholder="Search code, name or module…" autocomplete="off">
      </div>
    </div>
    <div class="vsa-tblwrap">
      <table class="vsa-tbl">
        <thead>
          <tr>
            <th style="padding:0;width:34px"></th>
            <th>Code</th>
            <th>Document</th>
            <th>Type</th>
            <th>Module</th>
            <th>Signature flow</th>
            <th style="text-align:right;padding-right:20px">Action</th>
          </tr>
        </thead>
        <tbody id="vsa-doc-rows">
          @forelse($docs as $doc)
            @php $on = !empty($doc['configured']); @endphp
            <tr class="r"
                @if($canEdit) draggable="true" @endif
                data-code="{{ $doc['code'] }}"
                data-configured="{{ $on ? '1' : '0' }}"
                data-search="{{ strtolower($doc['code'].' '.$doc['label'].' '.$doc['module']) }}">
              <td style="padding-left:8px;padding-right:0">
                @if($canEdit)
                <span class="vsa-grip" title="Drag to reorder">
                  <svg width="14" height="18" viewBox="0 0 14 18" fill="currentColor"><circle cx="4" cy="3" r="1.6"/><circle cx="10" cy="3" r="1.6"/><circle cx="4" cy="9" r="1.6"/><circle cx="10" cy="9" r="1.6"/><circle cx="4" cy="15" r="1.6"/><circle cx="10" cy="15" r="1.6"/></svg>
                </span>
                @endif
              </td>
              <td><span class="vsa-code">{{ $doc['code'] }}</span></td>
              <td style="font-weight:500">{{ $doc['label'] }}</td>
              <td><span class="vsa-kind">{{ $doc['kind_label'] }}</span></td>
              <td><span class="vsa-modchip"><i></i>{{ $doc['module'] }}</span></td>
              <td>
                @if($on && !empty($doc['flow']))
                  <div class="vsa-flow">
                    @foreach($doc['flow'] as $i => $step)
                      <span class="vsa-chip">{{ $step['label'] ?: 'Untitled' }}</span>
                      @if($i < count($doc['flow']) - 1)
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8A8EA5" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                      @endif
                    @endforeach
                  </div>
                @else
                  <span class="vsa-legacy"><i></i>Legacy layout</span>
                @endif
              </td>
              <td style="text-align:right;padding-right:20px">
                <a class="vsa-act {{ $on ? 'edit' : '' }}"
                   href="{{ route('settings-panel.voucher-signature-assignment.configure', ['company_slug' => $company_slug, 'code' => $doc['code']]) }}">
                  {{ $on ? 'Edit' : 'Set up' }}
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No active voucher types found.</td></tr>
          @endforelse
        </tbody>
      </table>
      <div id="vsa-empty" class="text-muted text-center py-5 d-none">Nothing matches your search.</div>
    </div>
  </div>
</div>
<div class="vsa-toast" id="vsa-toast" role="status"></div>
@endsection

@push('third_party_scripts')
<script>
(function () {
  var root = document.getElementById('vsa-root');
  if (!root) return;
  var canEdit = root.getAttribute('data-can-edit') === '1';
  var status = 'all';
  var tbody = document.getElementById('vsa-doc-rows');
  var empty = document.getElementById('vsa-empty');
  var q = document.getElementById('vsa-q');
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || document.querySelector('input[name="_token"]')?.value
    || '';

  function toast(msg) {
    var t = document.getElementById('vsa-toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(t._h);
    t._h = setTimeout(function () { t.classList.remove('show'); }, 1800);
  }

  function filterRows() {
    var term = (q.value || '').trim().toLowerCase();
    var rows = tbody.querySelectorAll('tr.r');
    var shown = 0;
    rows.forEach(function (row) {
      var on = row.getAttribute('data-configured') === '1';
      var okStatus = status === 'all' || (status === 'configured' && on) || (status === 'legacy' && !on);
      var okSearch = !term || (row.getAttribute('data-search') || '').indexOf(term) !== -1;
      var show = okStatus && okSearch;
      row.style.display = show ? '' : 'none';
      if (show) shown++;
    });
    if (empty) empty.classList.toggle('d-none', shown > 0 || rows.length === 0);
  }

  document.getElementById('vsa-status-pills')?.addEventListener('click', function (e) {
    var btn = e.target.closest('button[data-status]');
    if (!btn) return;
    status = btn.getAttribute('data-status');
    this.querySelectorAll('button').forEach(function (b) { b.classList.toggle('on', b === btn); });
    filterRows();
  });
  q?.addEventListener('input', filterRows);

  var editBtn = document.getElementById('vsa-edit-name');
  var nameForm = document.getElementById('vsa-name-form');
  var nameDisplay = document.getElementById('vsa-name-row');
  editBtn?.addEventListener('click', function () {
    nameDisplay.querySelector('h1')?.parentElement && (nameDisplay.style.display = 'none');
    nameForm.style.display = 'flex';
    nameForm.classList.remove('d-none');
    document.getElementById('vsa-name-inp')?.focus();
  });
  document.getElementById('vsa-cancel-name')?.addEventListener('click', function () {
    nameForm.style.display = 'none';
    nameForm.classList.add('d-none');
    nameDisplay.style.display = 'flex';
  });

  if (!canEdit || !tbody) return;

  var drag = null;
  tbody.addEventListener('dragstart', function (e) {
    var r = e.target.closest('tr.r');
    if (!r) return;
    drag = r;
    r.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', r.getAttribute('data-code') || '');
  });
  tbody.addEventListener('dragover', function (e) {
    if (!drag) return;
    var r = e.target.closest('tr.r');
    if (!r || r === drag) return;
    e.preventDefault();
    tbody.querySelectorAll('.over-top,.over-bot').forEach(function (x) {
      x.classList.remove('over-top', 'over-bot');
    });
    var box = r.getBoundingClientRect();
    r.classList.add(e.clientY < box.top + box.height / 2 ? 'over-top' : 'over-bot');
  });
  tbody.addEventListener('drop', function (e) {
    if (!drag) return;
    var r = e.target.closest('tr.r');
    if (!r || r === drag) return;
    e.preventDefault();
    var after = r.classList.contains('over-bot');
    if (after) r.after(drag); else r.before(drag);
    persistOrder();
  });
  tbody.addEventListener('dragend', function () {
    tbody.querySelectorAll('.dragging,.over-top,.over-bot').forEach(function (x) {
      x.classList.remove('dragging', 'over-top', 'over-bot');
    });
    drag = null;
  });

  function persistOrder() {
    var order = Array.from(tbody.querySelectorAll('tr.r')).map(function (r) {
      return r.getAttribute('data-code');
    }).filter(Boolean);
    fetch(root.getAttribute('data-reorder-url'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ order: order })
    }).then(function (res) {
      if (res.ok) toast('Order saved');
    }).catch(function () {});
  }
})();
</script>
@endpush
