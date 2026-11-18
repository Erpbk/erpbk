@extends($layout ?? 'layouts.app')
@section('title', 'Delete Requests')

@php
    $statusTab = $statusTab ?? request('status', 'pending');
    $counts = $counts ?? ['pending' => $pendingCount ?? 0, 'approved' => 0, 'rejected' => 0, 'all' => 0];
    $canReview = $canReview ?? (auth()->user()?->isAdmin() || user_can('settings_delete_requests_edit'));
    $canOpenBin = $canOpenBin ?? auth()->user()?->can('settings_recycle_bin_view');
    $activeFilterCount = $activeFilterCount ?? 0;
    $moduleColors = [
        'Vouchers' => ['#eef2ff', '#4338ca'],
        'Visa Installment Plans' => ['#f3e8ff', '#7e22ce'],
        'License Installment Plans' => ['#faf5ff', '#6b21a8'],
        'Riders' => ['#e0f2fe', '#0369a1'],
        'Bikes' => ['#ecfeff', '#0e7490'],
        'Employees' => ['#fce7f3', '#be185d'],
        'Visa Expenses' => ['#f5f3ff', '#6d28d9'],
        'License Expenses' => ['#fdf4ff', '#a21caf'],
        'Accounts' => ['#ecfdf5', '#047857'],
        'Rider Invoices' => ['#f1f5f9', '#334155'],
        'Supplier Invoices' => ['#fff7ed', '#c2410c'],
        'Banks' => ['#eff6ff', '#1d4ed8'],
    ];
    $avatarColors = ['#2563eb', '#7c3aed', '#0891b2', '#db2777', '#ea580c', '#16a34a', '#4f46e5'];
    $queryBase = request()->except(['page', 'status']);
    $tabUrl = function (string $tab) use ($queryBase) {
        return route('settings-panel.delete-requests.index', array_merge($queryBase, ['status' => $tab]));
    };
    $paginator = $deleteRequests;
    $total = method_exists($paginator, 'total') ? (int) $paginator->total() : $paginator->count();
    $perPage = method_exists($paginator, 'perPage') ? (int) $paginator->perPage() : max($total, 1);
    $current = method_exists($paginator, 'currentPage') ? (int) $paginator->currentPage() : 1;
    $last = method_exists($paginator, 'lastPage') ? max(1, (int) $paginator->lastPage()) : 1;
    $from = $total === 0 ? 0 : (($current - 1) * $perPage) + 1;
    $to = $total === 0 ? 0 : min($current * $perPage, $total);
    $appended = method_exists($paginator, 'appends') ? $paginator->appends(request()->query()) : null;
    $pageNums = [];
    for ($p = 1; $p <= $last; $p++) {
        if ($p === 1 || $p === $last || abs($p - $current) <= 1) {
            $pageNums[] = $p;
        } elseif (end($pageNums) !== '…') {
            $pageNums[] = '…';
        }
    }
@endphp

@push('third_party_stylesheets')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/delete-requests-demo.css') }}?v=20261118f">
@endpush

@section('content')
<div class="dr-demo {{ $statusTab !== 'pending' ? 'nobulk' : '' }}"
     id="drRoot"
     data-preview-base="{{ url()->route('settings-panel.delete-requests.index') }}"
     data-can-review="{{ $canReview ? '1' : '0' }}"
     data-csrf="{{ csrf_token() }}">
  <div class="dr-wrap">
    @include('flash::message')

    <div class="dr-hcard">
      <div>
        <h4>Delete Requests</h4>
        <small>{{ $counts['pending'] }} pending approval</small>
      </div>
      <div class="dr-bar" id="bar">
        @foreach([
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'all' => 'All',
        ] as $key => $label)
          <a class="dr-btn {{ $statusTab === $key ? '' : 'ghost' }}" href="{{ $tabUrl($key) }}">
            {{ $label }} <b>{{ number_format((int) ($counts[$key] ?? 0)) }}</b>
          </a>
        @endforeach
        <span class="dr-sp"></span>
        <form method="GET" action="{{ route('settings-panel.delete-requests.index') }}" id="drQuickForm" class="d-inline">
          @foreach(request()->except(['search', 'page', 'voucher']) as $qk => $qv)
            @if(is_array($qv))
              @foreach($qv as $qvv)
                <input type="hidden" name="{{ $qk }}[]" value="{{ $qvv }}">
              @endforeach
            @else
              <input type="hidden" name="{{ $qk }}" value="{{ $qv }}">
            @endif
          @endforeach
          <input class="dr-q" id="q" name="search" type="search" placeholder="Quick Search..." value="{{ request('search') }}" aria-label="Quick search">
        </form>
        <button type="button" class="dr-btn" id="fbtn">
          Filter
          @if($activeFilterCount > 0)
            <b style="background:#fff;color:var(--dr-b)">{{ $activeFilterCount }}</b>
          @endif
        </button>
      </div>
    </div>

    @if($canReview)
    <div class="dr-bulk" id="bulk">
      <div style="display:flex;align-items:center;flex-wrap:wrap;gap:6px">
        <b id="n">0</b>&nbsp;selected
        <div class="dr-qs">
          <span data-n="2">Select 2</span>
          <span data-n="4">4</span>
          <span data-n="8">8</span>
          <span data-n="all">All pending</span>
        </div>
      </div>
      <div>
        <button type="button" class="bc" id="bulkClear">Clear</button>
        <button type="button" class="br" id="bulkReject">✕ Reject selected</button>
        <button type="button" class="ba" id="bulkApprove">✓ Approve selected</button>
      </div>
    </div>
    @endif

    <div class="dr-tbox">
      <table class="dr-list">
        <thead>
          <tr>
            @if($canReview)
              <th style="width:36px"><input type="checkbox" class="dr-ck" id="ckall" @disabled($statusTab !== 'pending')></th>
            @endif
            <th>ID</th>
            <th>Module</th>
            <th>Record</th>
            <th>Voucher No</th>
            <th>Requested By</th>
            <th>Requested At</th>
            <th>Status</th>
            <th>Recycle Bin</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="tb">
          @forelse($deleteRequests as $req)
            @php
              $name = $req->requester->name ?? '—';
              $colorIdx = abs(crc32($name)) % count($avatarColors);
              $mc = $moduleColors[$req->module_name] ?? ['#f1f5f9', '#334155'];
              $resourceId = \App\Services\DeleteRequestService::resourceIdentifier($req);
              $isVoucher = $req->module_key === 'vouchers' || stripos((string) $req->module_name, 'voucher') !== false;
              $previewUrl = route('settings-panel.delete-requests.preview', $req);
              $approveUrl = route('settings-panel.delete-requests.approve', $req);
              $rejectUrl = route('settings-panel.delete-requests.reject', $req);
            @endphp
            <tr class="{{ $req->isPending() ? 'pend' : '' }}"
                data-rid="{{ $req->id }}"
                data-preview="{{ $previewUrl }}"
                data-label="{{ $resourceId }}">
              @if($canReview)
                <td>
                  @if($req->isPending())
                    <input type="checkbox" class="dr-ck rck" value="{{ $req->id }}">
                  @endif
                </td>
              @endif
              <td><span class="dr-rid">#{{ $req->id }}</span></td>
              <td><span class="dr-mod" style="background:{{ $mc[0] }};color:{{ $mc[1] }}">{{ $req->module_name }}</span></td>
              <td>
                <button type="button" class="dr-vno open-preview">{{ $req->record_label ?: $resourceId }}</button>
                <span class="dr-vt">ID: {{ $req->deletable_id }}</span>
              </td>
              <td>
                @if($isVoucher)
                  <button type="button" class="dr-vnum open-preview">{{ $resourceId }}</button>
                @else
                  <span class="dr-vnum na">—</span>
                @endif
              </td>
              <td>
                <div class="dr-user">
                  <span class="dr-av" style="background:{{ $avatarColors[$colorIdx] }}">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
                  {{ $name }}
                </div>
              </td>
              <td>
                <div class="dr-dt">
                  <b>{{ $req->created_at?->format('d-m-Y') }}</b>
                  <span>{{ $req->created_at?->format('H:i') }}</span>
                </div>
              </td>
              <td><span class="dr-pill {{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
              <td>
                @if($req->bin_outcome === 'in_recycle_bin')
                  <span class="dr-binp in">In Recycle Bin</span>
                  <span class="dr-bint">{{ $req->moved_to_bin_at?->format('d-m-Y H:i') }}</span>
                @elseif($req->bin_outcome === 'permanently_deleted')
                  <span class="dr-binp pg">Purged</span>
                @elseif($req->bin_outcome === 'payment_reversed')
                  <span class="dr-binp ok">Payment reversed</span>
                @elseif($req->bin_outcome === 'restored')
                  <span class="dr-binp rest">Restored</span>
                @else
                  <span style="color:#94a3b8">—</span>
                @endif
              </td>
              <td class="dr-act">
                <button type="button" class="vw open-preview">{{ $req->isPending() ? 'View →' : 'Details →' }}</button>
                @if($canReview && $req->isPending())
                  <form action="{{ $approveUrl }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="stay" value="1">
                    <button type="submit" class="ap" onclick="return confirm('Approve and move this record to the Recycle Bin?')">✓ Approve</button>
                  </form>
                  <form action="{{ $rejectUrl }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="rj" onclick="return confirm('Reject this delete request?')">✕ Reject</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="{{ $canReview ? 10 : 9 }}" style="color:#94a3b8;height:80px">No requests found</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="dr-pager">
      <div class="info">
        <span id="pinfo">
          @if($total > 0)
            Showing {{ $from }}–{{ $to }} of {{ $total }} requests
          @else
            No requests found
          @endif
        </span>
        <form method="GET" action="{{ route('settings-panel.delete-requests.index') }}" id="perForm">
          @foreach(request()->except(['per_page', 'page']) as $qk => $qv)
            @if(is_array($qv))
              @foreach($qv as $qvv)
                <input type="hidden" name="{{ $qk }}[]" value="{{ $qvv }}">
              @endforeach
            @else
              <input type="hidden" name="{{ $qk }}" value="{{ $qv }}">
            @endif
          @endforeach
          <select name="per_page" id="per" class="no-select2" data-no-select2 onchange="this.form.submit()">
            @foreach([20, 50, 100, 'all'] as $opt)
              <option value="{{ $opt }}" @selected((string) request('per_page', 50) === (string) $opt)>{{ $opt === 'all' ? 'All' : $opt }}</option>
            @endforeach
          </select>
        </form>
      </div>
      <div class="dr-pages" id="pg">
        @if($appended)
          @if($current <= 1)
            <span class="dis">‹ Previous</span>
          @else
            <a href="{{ $appended->previousPageUrl() }}">‹ Previous</a>
          @endif
          @foreach($pageNums as $n)
            @if($n === '…')
              <span class="dots">…</span>
            @elseif($n === $current)
              <span class="on">{{ $n }}</span>
            @else
              <a href="{{ $appended->url($n) }}">{{ $n }}</a>
            @endif
          @endforeach
          @if($current >= $last)
            <span class="dis">Next ›</span>
          @else
            <a href="{{ $appended->nextPageUrl() }}">Next ›</a>
          @endif
        @endif
      </div>
    </div>

  </div>
</div>

<form id="bulkApproveForm" method="POST" action="{{ route('settings-panel.delete-requests.bulk-approve') }}" class="d-none">
  @csrf
  <div id="bulkApproveIds"></div>
</form>
<form id="bulkRejectForm" method="POST" action="{{ route('settings-panel.delete-requests.bulk-reject') }}" class="d-none">
  @csrf
  <div id="bulkRejectIds"></div>
</form>

<div class="dr-ov" id="ov"></div>

<div class="dr-drawer" id="dr">
  <div class="dtop">
    <div>
      <h5 id="dt">Delete Request</h5>
      <small id="ds"></small>
    </div>
    <div class="tb" id="dtb"></div>
  </div>
  <div class="dbody" id="db">
    <div style="color:#64748b;padding:24px;text-align:center">Loading…</div>
  </div>
</div>

<div class="dr-fp" id="fp" data-no-select2>
  <div class="fph">
    <h5>Filter Delete Requests</h5>
    <button type="button" id="fx" aria-label="Close">×</button>
  </div>
  <div class="fpb">
    <form method="GET" action="{{ route('settings-panel.delete-requests.index') }}" id="filterForm" data-no-select2>
      <input type="hidden" name="from_filter" value="1">
      <input type="hidden" name="per_page" value="{{ request('per_page', 50) }}">
      <div class="f">
        <label for="f-id">Request Id</label>
        <input name="request_id" id="f-id" placeholder="Filter By Request ID" value="{{ request('request_id') }}">
      </div>
      <div class="f">
        <label for="f-v">Voucher / Record No.</label>
        <input name="voucher" id="f-v" placeholder="Filter By Voucher No." value="{{ request('voucher') }}">
      </div>
      <div class="f">
        <label for="f-m">Filter by Module</label>
        <select name="module_key" id="f-m" class="no-select2" data-no-select2>
          <option value="">Select</option>
          @foreach($modules as $module)
            <option value="{{ $module->module_key }}" @selected(request('module_key') === $module->module_key)>{{ $module->module_name }}</option>
          @endforeach
        </select>
      </div>
      <div class="f">
        <label for="f-s">Filter by Status</label>
        <select name="status" id="f-s" class="no-select2" data-no-select2>
          <option value="">Select</option>
          <option value="pending" @selected($statusTab === 'pending')>Pending</option>
          <option value="approved" @selected($statusTab === 'approved')>Approved</option>
          <option value="rejected" @selected($statusTab === 'rejected')>Rejected</option>
          <option value="all" @selected($statusTab === 'all')>All</option>
        </select>
      </div>
      <div class="f">
        <label for="f-u">Filter by Requested By</label>
        <select name="requested_by" id="f-u" class="no-select2" data-no-select2>
          <option value="">Select</option>
          @foreach($requesters as $user)
            <option value="{{ $user->id }}" @selected((string) request('requested_by') === (string) $user->id)>{{ $user->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="f">
        <label>Requested Date</label>
        <div class="row2">
          <input type="date" name="date_from" id="f-d1" value="{{ request('date_from') }}">
          <input type="date" name="date_to" id="f-d2" value="{{ request('date_to') }}">
        </div>
      </div>
      <div class="fgo">
        <a class="rs" href="{{ route('settings-panel.delete-requests.index', ['status' => 'pending']) }}">Reset</a>
        <button type="submit" class="go" id="fgo">Filter Data</button>
      </div>
    </form>
  </div>
</div>

<div class="dr-toast" id="toast"></div>
@endsection

@push('third_party_scripts')
<script>
(function () {
  const root = document.getElementById('drRoot');
  if (!root) return;
  const canReview = root.dataset.canReview === '1';
  const ov = document.getElementById('ov');
  const dr = document.getElementById('dr');
  const fp = document.getElementById('fp');
  const db = document.getElementById('db');
  const dt = document.getElementById('dt');
  const ds = document.getElementById('ds');
  const dtb = document.getElementById('dtb');
  const bulk = document.getElementById('bulk');
  const nEl = document.getElementById('n');
  const toastEl = document.getElementById('toast');
  const csrf = root.dataset.csrf;
  const selected = new Set();

  function toast(msg) {
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.style.display = 'block';
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(() => { toastEl.style.display = 'none'; }, 2600);
  }

  function closeAll() {
    [fp, dr, ov].forEach((el) => el && el.classList.remove('open'));
  }

  function updBulk() {
    if (!bulk) return;
    nEl.textContent = String(selected.size);
    bulk.classList.toggle('show', selected.size > 0 && !root.classList.contains('nobulk'));
  }

  function syncRowSel() {
    document.querySelectorAll('#tb tr[data-rid]').forEach((tr) => {
      const id = tr.dataset.rid;
      const ck = tr.querySelector('.rck');
      const on = selected.has(id);
      tr.classList.toggle('sel', on);
      if (ck) ck.checked = on;
    });
    updBulk();
  }

  async function openPreview(url) {
    if (!url) return;
    db.innerHTML = '<div style="color:#64748b;padding:24px;text-align:center">Loading…</div>';
    dt.textContent = 'Delete Request';
    ds.textContent = '';
    dtb.innerHTML = '<button type="button" class="x">✕</button>';
    dr.classList.add('open');
    ov.classList.add('open');
    fp.classList.remove('open');
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } });
      if (!res.ok) throw new Error('Failed to load');
      const html = await res.text();
      db.innerHTML = html;
      db.scrollTop = 0;
      const preview = db.querySelector('.dr-preview');
      if (preview) {
        dt.textContent = preview.dataset.title || 'Delete Request';
        ds.textContent = preview.dataset.sub || '';
        let actions = '';
        if (preview.dataset.canReview === '1') {
          actions += `<form method="POST" action="${preview.dataset.approveUrl}" class="d-inline"><input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="stay" value="1"><button type="submit" class="ap" onclick="return confirm('Approve and move this record to the Recycle Bin?')">✓ Approve</button></form>`;
          actions += `<form method="POST" action="${preview.dataset.rejectUrl}" class="d-inline"><input type="hidden" name="_token" value="${csrf}"><button type="submit" class="rj" onclick="return confirm('Reject this delete request?')">✕ Reject</button></form>`;
        }
        if (preview.dataset.canBin === '1') {
          actions += `<a class="bin" href="${preview.dataset.binUrl}">♻ Recycle Bin</a>`;
        }
        actions += `<a class="full" href="${preview.dataset.fullUrl}" target="_blank" rel="noopener">Open full page ↗</a>`;
        actions += '<button type="button" class="x">✕</button>';
        dtb.innerHTML = actions;
      }
    } catch (e) {
      db.innerHTML = '<div class="banner">Could not load delete request details.</div>';
    }
  }

  function selectedIds() {
    return Array.from(selected);
  }

  function fillBulkForm(formId, wrapId) {
    const wrap = document.getElementById(wrapId);
    wrap.innerHTML = '';
    selectedIds().forEach((id) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'ids[]';
      input.value = id;
      wrap.appendChild(input);
    });
  }

  function restoreNativeFilterSelects() {
    if (!fp || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
    window.jQuery(fp).find('select').each(function () {
      const $el = window.jQuery(this);
      if ($el.hasClass('select2-hidden-accessible')) {
        try { $el.select2('destroy'); } catch (e) {}
      }
      $el.next('.select2-container').remove();
      $el.show().css({ position: '', width: '100%', height: '44px' });
    });
  }

  document.getElementById('fbtn')?.addEventListener('click', () => {
    restoreNativeFilterSelects();
    fp.classList.add('open');
    ov.classList.add('open');
    dr.classList.remove('open');
  });
  restoreNativeFilterSelects();
  document.getElementById('fx')?.addEventListener('click', closeAll);
  ov?.addEventListener('click', closeAll);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(); });

  dtb?.addEventListener('click', (e) => {
    if (e.target.closest('.x')) closeAll();
  });

  document.getElementById('q')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      document.getElementById('drQuickForm')?.submit();
    }
  });

  document.getElementById('tb')?.addEventListener('click', (e) => {
    const btn = e.target.closest('.open-preview, .vw, .dr-vno, .dr-vnum:not(.na)');
    if (!btn) return;
    const tr = btn.closest('tr[data-rid]');
    if (!tr) return;
    e.preventDefault();
    openPreview(tr.dataset.preview);
  });

  document.getElementById('ckall')?.addEventListener('change', (e) => {
    document.querySelectorAll('#tb tr[data-rid] .rck').forEach((ck) => {
      const id = ck.closest('tr').dataset.rid;
      if (e.target.checked) selected.add(id); else selected.delete(id);
    });
    syncRowSel();
  });

  document.getElementById('tb')?.addEventListener('change', (e) => {
    if (!e.target.classList.contains('rck')) return;
    const id = e.target.closest('tr').dataset.rid;
    if (e.target.checked) selected.add(id); else selected.delete(id);
    e.target.closest('tr').classList.toggle('sel', e.target.checked);
    updBulk();
  });

  document.querySelector('.dr-qs')?.addEventListener('click', (e) => {
    const n = e.target.dataset.n;
    if (!n) return;
    selected.clear();
    const rows = Array.from(document.querySelectorAll('#tb tr[data-rid] .rck'));
    rows.forEach((ck, i) => {
      if (n === 'all' || i < Number(n)) selected.add(ck.closest('tr').dataset.rid);
    });
    syncRowSel();
  });

  document.getElementById('bulkClear')?.addEventListener('click', () => {
    selected.clear();
    syncRowSel();
  });

  document.getElementById('bulkApprove')?.addEventListener('click', () => {
    const ids = selectedIds();
    if (!ids.length) return;
    const labels = ids.map((id) => {
      const tr = document.querySelector(`#tb tr[data-rid="${id}"]`);
      return tr?.dataset.label || ('#' + id);
    });
    if (!confirm(`Approve and move to Recycle Bin ${ids.length} request(s)?\n\n` + labels.join(', '))) return;
    fillBulkForm('bulkApproveForm', 'bulkApproveIds');
    document.getElementById('bulkApproveForm').submit();
  });

  document.getElementById('bulkReject')?.addEventListener('click', () => {
    const ids = selectedIds();
    if (!ids.length) return;
    if (!confirm(`Reject ${ids.length} request(s)?`)) return;
    fillBulkForm('bulkRejectForm', 'bulkRejectIds');
    document.getElementById('bulkRejectForm').submit();
  });

  @if(session('success'))
    toast(@json(session('success')));
  @endif
})();
</script>
@endpush
