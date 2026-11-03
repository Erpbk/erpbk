@extends('layouts.app')
@section('title', ucfirst($refType ?? request('ref_type', 'employee')) . ' Attendance Report')

@php
  $refType = $refType ?? request('ref_type', 'employee');
  $reportKpis = $reportKpis ?? ['total'=>0,'present'=>0,'late'=>0,'absent'=>0,'leave'=>0,'half'=>0,'weekend'=>0];
  $personSheets = $personSheets ?? [];
  $monthStart = $monthStart ?? now()->startOfMonth();
  $monthEnd = $monthEnd ?? now()->endOfMonth();
  $billingMonth = $billingMonth ?? ($monthStart instanceof \Carbon\Carbon ? $monthStart->format('Y-m') : now()->format('Y-m'));
  $filterFrom = request('from_date', $monthStart->format('Y-m-d'));
  $filterTo = request('to_date', $monthEnd->format('Y-m-d'));
  $statusLetter = fn (?string $s) => match ($s) {
      'present' => 'P', 'late' => 'L', 'absent' => 'A', 'on leave' => 'V', 'half day' => 'H', 'weekend' => 'W', default => '',
  };
  $sheetDays = [];
  for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
      $sheetDays[] = [
          'date' => $d->format('Y-m-d'),
          'day' => $d->format('j'),
          'dow' => $d->format('D'),
          'weekend' => $d->isWeekend(),
      ];
  }
@endphp

@push('third_party_stylesheets')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/attendance-demo.css') }}?v=20261103s2">
@endpush

@section('content')
@canany(['employees_attendance_view', 'riders_attendance_view'])
<div class="att-demo" id="attReportDemo">

  <div class="tabrow">
    <div class="ptabs">
      <a class="ptab" href="{{ route('attendance.summary', ['user_type' => $refType]) }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        Attendance
      </a>
      <a class="ptab on" href="{{ route('attendance.index', ['ref_type' => $refType]) }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/></svg>
        Attendance Report
      </a>
    </div>
    <div class="actions">
      @canany(['employees_attendance_create', 'riders_attendance_create'])
      <button type="button" class="btn outline" id="attImpBtn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M17 8l-5-5-5 5"></path><path d="M12 3v12"></path></svg>
        Import
      </button>
      @endcanany
      <a class="btn green" href="{{ safe_route('attendance.export', array_filter([
          'ref_type' => $refType,
          'billing_month' => $billingMonth,
          'from_date' => $filterFrom,
          'to_date' => $filterTo,
          'status' => request('status'),
        ]), 'attendance/export') }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>
        Export
      </a>
      <button type="button" class="btn blue" id="attReportFilterBtn">Filter</button>
    </div>
  </div>

  <section class="rk" aria-label="Report summary">
    @php
      $kpiTotal = max(1, (int) ($reportKpis['total'] ?? 0));
      $rkpis = [
        ['n' => (int) ($reportKpis['total'] ?? 0), 'l' => 'Total records', 'c' => '#1F3FD1', 'bg' => '#EEF2FF', 'showPct' => false, 'icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>'],
        ['n' => (int) ($reportKpis['present'] ?? 0), 'l' => 'Present', 'c' => '#1E8E4E', 'bg' => '#E7F6EC', 'showPct' => true, 'icon' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/>'],
        ['n' => (int) ($reportKpis['late'] ?? 0), 'l' => 'Late', 'c' => '#B86E00', 'bg' => '#FFF3DF', 'showPct' => true, 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>'],
        ['n' => (int) ($reportKpis['absent'] ?? 0), 'l' => 'Absent', 'c' => '#C0362C', 'bg' => '#FDECEC', 'showPct' => true, 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/>'],
        ['n' => (int) ($reportKpis['leave'] ?? 0), 'l' => 'On leave', 'c' => '#075985', 'bg' => '#E0F2FE', 'showPct' => true, 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M17 11l2 2 4-4"/>'],
        ['n' => (int) ($reportKpis['weekend'] ?? 0), 'l' => 'Weekend', 'c' => '#5B6478', 'bg' => '#F1F3F8', 'showPct' => true, 'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
      ];
      $pill = function (int $n, string $bg, string $fg): string {
          if ($n < 1) {
              return '<span class="z">0</span>';
          }
          return '<span class="n" style="background:'.$bg.';color:'.$fg.'">'.$n.'</span>';
      };
      $foot = [
        'present' => collect($personSheets)->sum('present'),
        'late' => collect($personSheets)->sum('late'),
        'absent' => collect($personSheets)->sum('absent'),
        'leave' => collect($personSheets)->sum('leave'),
        'half' => collect($personSheets)->sum('half'),
        'weekend' => collect($personSheets)->sum('weekend'),
        'total' => collect($personSheets)->sum('total'),
      ];
    @endphp
    @foreach($rkpis as $k)
      @php $p = $k['showPct'] ? (int) round(($k['n'] / $kpiTotal) * 100) : 100; @endphp
      <div class="kpi" style="--c:{{ $k['c'] }};--bgc:{{ $k['bg'] }}">
        <div class="ic">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $k['icon'] !!}</svg>
        </div>
        <div>
          <div class="num">{{ $k['n'] }}</div>
          <div class="lb">{{ $k['l'] }}</div>
        </div>
        @if($k['showPct'])
          <div class="pc">{{ $p }}%</div>
        @else
          <span></span>
        @endif
        <div class="tr"><i style="--w:{{ $k['showPct'] ? max($p, $k['n'] > 0 ? 2 : 0) : 100 }}%"></i></div>
      </div>
    @endforeach
  </section>

  <section class="rwrap">
    <div class="rhead">
      <h3>Attendance Report</h3>
      <label class="qs">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" id="reportQuickSearch" placeholder="Quick Search..." aria-label="Quick search">
      </label>
    </div>
    <div class="tablewrap" style="max-height:none">
      <table class="ar" id="reportTable">
        <thead>
          <tr>
            <th class="l" style="cursor:default">#</th>
            <th class="l sorting" data-k="name">Employee Name <span class="ar">⇅</span></th>
            <th class="sorting" data-k="present">Present <span class="ar">⇅</span></th>
            <th class="sorting" data-k="late">Late <span class="ar">⇅</span></th>
            <th class="sorting" data-k="absent">Absent <span class="ar">⇅</span></th>
            <th class="sorting" data-k="leave">Leave <span class="ar">⇅</span></th>
            <th class="sorting" data-k="half">Half day <span class="ar">⇅</span></th>
            <th class="sorting" data-k="weekend">Weekend <span class="ar">⇅</span></th>
            <th class="sorting" data-k="total">Total Days <span class="ar">⇅</span></th>
            <th class="sorting" data-k="pct">Attendance % <span class="ar">⇅</span></th>
          </tr>
        </thead>
        <tbody id="reportBody">
          @forelse($personSheets as $i => $row)
            @php
              $initials = collect(explode(' ', (string) $row['name']))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
              $profileUrl = $row['ref_type'] === 'employee'
                ? safe_route('employees.show', ['employee' => $row['ref_id']], 'employees/{id}')
                : safe_route('riders.show', ['rider' => $row['ref_id']], 'riders/{id}');
              $pct = (int) ($row['pct'] ?? 0);
              $pctColor = $pct >= 85 ? '#1E8E4E' : ($pct >= 70 ? '#E59A1F' : '#D64535');
            @endphp
            <tr data-name="{{ strtolower($row['name'].' '.$row['code']) }}"
                data-present="{{ $row['present'] }}"
                data-late="{{ $row['late'] }}"
                data-absent="{{ $row['absent'] }}"
                data-leave="{{ $row['leave'] }}"
                data-half="{{ $row['half'] }}"
                data-weekend="{{ $row['weekend'] }}"
                data-total="{{ $row['total'] }}"
                data-pct="{{ $pct }}">
              <td class="l" style="color:#8A93A6">{{ $i + 1 }}</td>
              <td class="l">
                <div class="who">
                  <a href="{{ $profileUrl }}" title="Open profile">
                    @if(!empty($row['photo']))
                      <img class="rav" src="{{ $row['photo'] }}" alt="">
                    @else
                      <span class="rav">{{ strtoupper($initials) }}</span>
                    @endif
                  </a>
                  <a href="{{ $profileUrl }}">{{ $row['name'] }}</a>
                </div>
              </td>
              <td>{!! $pill((int) $row['present'], '#E7F6EC', '#17663A') !!}</td>
              <td>{!! $pill((int) $row['late'], '#FFF3DF', '#8A4B00') !!}</td>
              <td>{!! $pill((int) $row['absent'], '#FDECEC', '#B42318') !!}</td>
              <td>{!! $pill((int) $row['leave'], '#E0F2FE', '#075985') !!}</td>
              <td>{!! $pill((int) $row['half'], '#E9EEFF', '#1F3FD1') !!}</td>
              <td>{!! $pill((int) $row['weekend'], '#F3F4F7', '#5B6478') !!}</td>
              <td style="font-weight:700">{{ (int) $row['total'] }}</td>
              <td>
                <button type="button" class="pctbtn" data-sheet-idx="{{ $i }}" title="Open monthly attendance sheet">
                  <span class="b"><i style="width:{{ $pct }}%;background:{{ $pctColor }}"></i></span>
                  <span style="color:{{ $pctColor }}">{{ $pct }}%</span>
                </button>
              </td>
            </tr>
          @empty
            <tr><td colspan="10" class="empty">No attendance records for these filters.</td></tr>
          @endforelse
        </tbody>
        @if(count($personSheets))
        <tfoot>
          <tr>
            <td></td>
            <td class="l">Total ({{ count($personSheets) }} {{ \Illuminate\Support\Str::plural($refType === 'rider' ? 'rider' : 'employee', count($personSheets)) }})</td>
            <td>{{ $foot['present'] }}</td>
            <td>{{ $foot['late'] }}</td>
            <td>{{ $foot['absent'] }}</td>
            <td>{{ $foot['leave'] }}</td>
            <td>{{ $foot['half'] }}</td>
            <td>{{ $foot['weekend'] }}</td>
            <td>{{ $foot['total'] }}</td>
            <td></td>
          </tr>
        </tfoot>
        @endif
      </table>
    </div>
  </section>

  {{-- Filter drawer (hidden until Filter button) --}}
  <div class="ov" id="attReportFilterOv"></div>
  <aside class="drawer" id="attReportFilterDrawer" role="dialog" aria-label="Filter Report">
    <div class="hd">
      <h3>Filter Report</h3>
      <button type="button" class="x" id="attReportFilterClose" aria-label="Close">&times;</button>
    </div>
    <form class="bd" method="GET" action="{{ route('attendance.index') }}" id="attReportFilterForm">
      <input type="hidden" name="ref_type" value="{{ $refType }}">
      <div class="fld">
        <label for="rBillingMonth">Billing Month</label>
        <input type="month" id="rBillingMonth" name="billing_month" value="{{ $billingMonth }}">
      </div>
      <div class="two">
        <div class="fld">
          <label for="rFrom">From</label>
          <input type="date" id="rFrom" name="from_date" value="{{ $filterFrom }}">
        </div>
        <div class="fld">
          <label for="rTo">To</label>
          <input type="date" id="rTo" name="to_date" value="{{ $filterTo }}">
        </div>
      </div>
      <div class="fld">
        <label for="rStatus">Status</label>
        <select id="rStatus" name="status" class="att-filter-select" data-placeholder="All statuses">
          <option value="">All</option>
          @foreach(['present'=>'Present','late'=>'Late','absent'=>'Absent','on leave'=>'Leave','half day'=>'Half day','weekend'=>'Weekend'] as $val => $lab)
            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $lab }}</option>
          @endforeach
        </select>
      </div>
      <div class="go">
        <a class="btn reset" href="{{ route('attendance.index', ['ref_type' => $refType]) }}">Reset</a>
        <button type="submit" class="btn blue">Apply</button>
      </div>
    </form>
  </aside>

  {{-- % sheet overlay --}}
  <div class="sheet-ov" id="pctSheetOv">
    <div class="ssw" role="dialog" aria-label="Attendance percentage sheet">
      <div class="ssbar">
        <button type="button" class="btn outline" id="pctSheetWa" title="Share on WhatsApp">WhatsApp</button>
        <button type="button" class="btn outline" id="pctSheetMail" title="Share by email">Email</button>
        <button type="button" class="btn outline" id="pctSheetPrint">Print</button>
        <button type="button" class="btn reset" id="pctSheetClose">Close</button>
      </div>
      <div class="ss" id="pctSheetBody"></div>
    </div>
  </div>

  @include('attendance.partials.import_modal', [
    'refType' => $refType,
    'importPeople' => $importPeople ?? collect(),
    'openImport' => $openImport ?? false,
  ])
</div>

{{-- Keep bulk mark modal (existing behaviour) --}}
@canany(['employees_attendance_create', 'riders_attendance_create'])
<div class="modal fade" id="bulkAttendanceModal" tabindex="-1" aria-labelledby="bulkAttendanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bulkAttendanceModalLabel">Bulk Mark Attendance</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('attendance.bulk-mark') }}" method="POST" id="bulkAttendanceForm">
        @csrf
        <div class="modal-body">
          <div class="row mb-4">
            <div class="col-md-3">
              <label for="bulk_ref_type" class="form-label fw-bold">User Type <span class="text-danger">*</span></label>
              <select class="form-select select2-2" id="bulk_ref_type" name="ref_type" required>
                <option value="">-- Select User Type --</option>
                <option value="employee" {{ $refType === 'employee' ? 'selected' : '' }}>Employees</option>
                <option value="rider" {{ $refType === 'rider' ? 'selected' : '' }}>Riders</option>
              </select>
            </div>
            <div class="col-md-3">
              <label for="userSelect" class="form-label fw-bold">Users</label>
              <select class="form-select select2-3" id="userSelect" style="max-width: 300px;" disabled>
                <option value="">Select user to add</option>
              </select>
            </div>
            <div class="col-md-3">
              <label for="bulk_date" class="form-label fw-bold">Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="bulk_date" name="date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-3">
              <label for="default_status" class="form-label fw-bold">Default Status</label>
              <select class="form-select select2-2" id="default_status">
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="late">Late</option>
                <option value="half-day">Half Day</option>
                <option value="weekend">Weekend</option>
              </select>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label fw-bold mb-0">Selected Users <span class="badge bg-secondary ms-2" id="selectedUsersCount">0</span></label>
            <div>
              <button type="button" class="btn btn-sm btn-outline-success" id="setAllPresent">All Present</button>
              <button type="button" class="btn btn-sm btn-outline-danger" id="setAllAbsent">All Absent</button>
              <button type="button" class="btn btn-sm btn-outline-warning" id="setGeneral">Set 10am - 6pm</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="removeAllUsers">Remove All</button>
            </div>
          </div>
          <div class="table-responsive" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
            <table class="table table-bordered mb-0" id="selectedUsersTable">
              <thead>
                <tr id="bulkAttendanceTableHead">
                  <th style="width:50px">#</th>
                  <th>User</th>
                  <th>Status</th>
                  <th>Check In</th>
                  <th>Check Out</th>
                  <th class="bulk-rider-order-col d-none">Total Orders</th>
                  <th class="bulk-rider-order-col d-none">Working Hours</th>
                  <th class="bulk-rider-order-col d-none">Cancelled</th>
                  <th class="bulk-rider-order-col d-none">Rejected</th>
                  <th>Notes</th>
                  <th style="width:80px">Action</th>
                </tr>
              </thead>
              <tbody id="selectedUsersBody">
                <tr id="noUsersRow">
                  <td colspan="11" class="text-center py-5" id="noUsersRowPlaceholder">
                    <h6 class="text-muted">No users selected</h6>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-end gap-2 pt-3">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="submitBulkBtn" disabled>Save Attendance</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
@endcanany

@php
  $printCompanyName = optional(request()->attributes->get('company'))->name
    ?? optional(auth()->user()->company ?? null)->name
    ?? config('app.name', 'Company');
  $attSheetRangePayload = [
    'from' => $monthStart->format('d M Y'),
    'to' => $monthEnd->format('d M Y'),
    'month' => $monthStart->format('F Y'),
  ];
  $attPrintedAt = now()->format('d M Y, h:i A');
@endphp
@push('page-scripts')
<script>
window.__attPersonSheets = @json($personSheets);
window.__attSheetDays = @json($sheetDays);
window.__attSheetRange = @json($attSheetRangePayload);
window.__attCompanyName = @json($printCompanyName);
window.__attPrintedAt = @json($attPrintedAt);

(function () {
  const sheets = window.__attPersonSheets || [];
  const days = window.__attSheetDays || [];
  const range = window.__attSheetRange || {};
  const companyName = window.__attCompanyName || 'Company';
  const printedAt = window.__attPrintedAt || '';
  const letter = { present:'P', late:'L', absent:'A', 'on leave':'V', 'half day':'H', weekend:'W' };
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  function renumberReportRows() {
    let n = 0;
    document.querySelectorAll('#reportBody tr[data-name]').forEach(tr => {
      if (tr.style.display === 'none') return;
      n += 1;
      const cell = tr.querySelector('td.l');
      if (cell) cell.textContent = String(n);
    });
  }
  document.getElementById('reportQuickSearch')?.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#reportBody tr[data-name]').forEach(tr => {
      tr.style.display = !q || (tr.dataset.name || '').includes(q) ? '' : 'none';
    });
    renumberReportRows();
  });

  (function bindReportSort() {
    const table = document.getElementById('reportTable');
    const tbody = document.getElementById('reportBody');
    if (!table || !tbody) return;
    let sortKey = 'name';
    let sortDir = 1;
    table.querySelectorAll('th.sorting').forEach(th => {
      th.addEventListener('click', () => {
        const key = th.dataset.k || 'name';
        if (sortKey === key) sortDir *= -1;
        else {
          sortKey = key;
          sortDir = key === 'name' ? 1 : -1;
        }
        table.querySelectorAll('th.sorting').forEach(h => h.classList.remove('on'));
        th.classList.add('on');
        th.querySelector('.ar').textContent = sortDir > 0 ? '▲' : '▼';
        const rows = Array.from(tbody.querySelectorAll('tr[data-name]'));
        rows.sort((a, b) => {
          let av = a.dataset[sortKey] ?? '';
          let bv = b.dataset[sortKey] ?? '';
          if (sortKey === 'name') return String(av).localeCompare(String(bv)) * sortDir;
          return ((parseFloat(av) || 0) - (parseFloat(bv) || 0)) * sortDir;
        });
        rows.forEach(r => tbody.appendChild(r));
        renumberReportRows();
      });
    });
  })();

  const filterOv = document.getElementById('attReportFilterOv');
  const filterDrawer = document.getElementById('attReportFilterDrawer');
  const rStatus = document.getElementById('rStatus');
  const $jq = window.jQuery;
  const hasSelect2 = !!( $jq && $jq.fn && $jq.fn.select2 );
  const initReportFilterSelect2 = () => {
    if (!hasSelect2 || !rStatus) return;
    const $el = $jq(rStatus);
    if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
    $el.next('.select2-container').remove();
    $el.select2({
      width: '100%',
      allowClear: true,
      placeholder: $el.data('placeholder') || 'All statuses',
      dropdownParent: $jq(filterDrawer || document.body),
      dropdownCssClass: 'att-filter-select2-dropdown',
      minimumResultsForSearch: 8,
    });
  };
  const openReportFilter = () => {
    filterOv?.classList.add('show');
    filterDrawer?.classList.add('show');
    setTimeout(initReportFilterSelect2, 30);
  };
  const closeReportFilter = () => {
    filterOv?.classList.remove('show');
    filterDrawer?.classList.remove('show');
  };
  document.getElementById('attReportFilterBtn')?.addEventListener('click', openReportFilter);
  document.getElementById('attReportFilterClose')?.addEventListener('click', closeReportFilter);
  filterOv?.addEventListener('click', closeReportFilter);
  initReportFilterSelect2();

  const billingMonthInput = document.getElementById('rBillingMonth');
  const fromInput = document.getElementById('rFrom');
  const toInput = document.getElementById('rTo');
  const syncRangeFromBillingMonth = () => {
    const val = billingMonthInput?.value || '';
    if (!/^\d{4}-\d{2}$/.test(val)) return;
    const [y, m] = val.split('-').map(Number);
    const lastDay = new Date(y, m, 0).getDate();
    const mm = String(m).padStart(2, '0');
    if (fromInput) fromInput.value = `${y}-${mm}-01`;
    if (toInput) toInput.value = `${y}-${mm}-${String(lastDay).padStart(2, '0')}`;
  };
  billingMonthInput?.addEventListener('change', syncRangeFromBillingMonth);

  const sheetOv = document.getElementById('pctSheetOv');
  const sheetBody = document.getElementById('pctSheetBody');

  function buildWeekGrid(row) {
    const byDate = row.by_date || {};
    if (!days.length) return '<p style="text-align:center;color:#5B6478">No dates in range.</p>';

    const first = new Date(days[0].date + 'T00:00:00');
    const last = new Date(days[days.length - 1].date + 'T00:00:00');
    const start = new Date(first);
    start.setDate(start.getDate() - start.getDay()); // Sunday start
    const end = new Date(last);
    end.setDate(end.getDate() + (6 - end.getDay()));

    const dayMap = {};
    days.forEach(d => { dayMap[d.date] = d; });

    let html = '<table class="rpt-cal"><thead><tr>'
      + ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].map(d => `<th>${d}</th>`).join('')
      + '</tr></thead><tbody>';

    for (let cur = new Date(start); cur <= end; ) {
      html += '<tr>';
      for (let i = 0; i < 7; i++) {
        const y = cur.getFullYear();
        const m = String(cur.getMonth() + 1).padStart(2, '0');
        const d = String(cur.getDate()).padStart(2, '0');
        const key = `${y}-${m}-${d}`;
        const inRange = !!dayMap[key];
        const st = byDate[key] || '';
        let L = letter[st] || '';
        if (!L && inRange && dayMap[key].weekend) L = 'W';
        const mute = inRange ? '' : ' mute';
        html += `<td class="${mute}"><span class="dn">${cur.getDate()}</span>`
          + (L ? `<span class="mk ${L}">${L}</span>` : (inRange ? '<span class="mk" style="opacity:.35">–</span>' : ''))
          + '</td>';
        cur.setDate(cur.getDate() + 1);
      }
      html += '</tr>';
    }
    html += '</tbody></table>';
    return html;
  }

  let sheetCur = null;
  function sheetMsg(row) {
    const first = String(row.name || '').split(/\s+/)[0] || 'Employee';
    const period = range.month || ((range.from || '') + ' – ' + (range.to || ''));
    return `Dear ${first},\n\nYour attendance for ${period}:\nPresent: ${row.present}\nLate: ${row.late}\nAbsent: ${row.absent}\nLeave: ${row.leave}\nAttendance: ${row.pct}%\n\nPlease sign your monthly attendance sheet.\n${companyName}`;
  }
  function buildReportHtml(row) {
    sheetCur = row;
    return `
      <article class="rpt">
        <header class="rpt-head">
          <p class="rpt-co">${esc(companyName)}</p>
          <h1 class="rpt-title">Monthly Attendance Sheet</h1>
          <p class="rpt-sub">Period: ${esc(range.from)} – ${esc(range.to)}${range.month ? ' · ' + esc(range.month) : ''}</p>
        </header>
        <section class="rpt-meta">
          <div class="box"><span class="k">Employee</span><span class="v">${esc(row.name)}</span></div>
          <div class="box"><span class="k">ID / Code</span><span class="v">${esc(row.code || '—')}</span></div>
          <div class="box"><span class="k">Designation</span><span class="v">${esc(row.designation || '—')}</span></div>
          <div class="box"><span class="k">Attendance</span><span class="v pct">${esc(row.pct)}%</span></div>
        </section>
        <section class="rpt-stats">
          <div class="st"><b style="color:#17663A">${row.present}</b><span>Present</span></div>
          <div class="st"><b style="color:#8A4B00">${row.late}</b><span>Late</span></div>
          <div class="st"><b style="color:#B42318">${row.absent}</b><span>Absent</span></div>
          <div class="st"><b style="color:#075985">${row.leave}</b><span>Leave</span></div>
          <div class="st"><b style="color:#1F3FD1">${row.half}</b><span>Half day</span></div>
          <div class="st"><b style="color:#5B6478">${row.weekend}</b><span>Weekend</span></div>
        </section>
        ${buildWeekGrid(row)}
        <div class="rpt-leg">
          <span><i class="mk P" style="background:#E7F6EC;color:#17663A">P</i> Present</span>
          <span><i class="mk L" style="background:#FFF3DF;color:#8A4B00">L</i> Late</span>
          <span><i class="mk A" style="background:#FDECEC;color:#B42318">A</i> Absent</span>
          <span><i class="mk V" style="background:#E0F2FE;color:#075985">V</i> Leave</span>
          <span><i class="mk H" style="background:#E9EEFF;color:#1F3FD1">H</i> Half day</span>
          <span><i class="mk W" style="background:#F3F4F7;color:#5B6478">W</i> Weekend</span>
          <span><i style="background:#fff;border:1px dashed #C3CAD8;color:#8A93A6">–</i> Not marked</span>
        </div>
        <p class="rpt-note">I confirm that the attendance recorded above for this period is correct. Attendance % = (Present + Late + Half day) ÷ marked working days (excluding weekends).</p>
        <section class="rpt-sg">
          <div class="ln">Employee Signature<small>Name &amp; date</small></div>
          <div class="ln">Supervisor / HR<small>Name &amp; date</small></div>
          <div class="ln">Date<small>&nbsp;</small></div>
        </section>
        <p class="rpt-foot">Printed on ${esc(printedAt)} · ${esc(companyName)}</p>
      </article>`;
  }

  const printStyles = `
    @page { size: A4 landscape; margin: 10mm; }
    * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    body { margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; color: #0F172A; background: #fff; }
    .no-print { display: none !important; }
    .rpt { width: 100%; }
    .rpt-head { text-align: center; border-bottom: 2.5pt solid #1F3FD1; padding-bottom: 8pt; margin-bottom: 10pt; }
    .rpt-co { font-size: 9pt; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #5B6478; margin: 0 0 3pt; }
    .rpt-title { margin: 0; font-size: 16pt; font-weight: 800; color: #0B1B4A; }
    .rpt-sub { margin: 3pt 0 0; font-size: 10pt; color: #5B6478; }
    .rpt-meta { display: table; width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 6pt; margin: 0 0 8pt -6pt; }
    .rpt-meta .box { display: table-cell; border: 1pt solid #D9DEE8; border-radius: 4pt; padding: 6pt 8pt; background: #F8FAFF; vertical-align: top; }
    .rpt-meta .k { display: block; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #8A93A6; margin-bottom: 2pt; }
    .rpt-meta .v { font-size: 11pt; font-weight: 700; }
    .rpt-meta .pct { color: #1F3FD1; font-size: 16pt; }
    .rpt-stats { display: table; width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 5pt; margin: 0 0 8pt -5pt; }
    .rpt-stats .st { display: table-cell; border: 1pt solid #E4E8F0; border-radius: 4pt; padding: 5pt 3pt; text-align: center; background: #fff; }
    .rpt-stats .st b { display: block; font-size: 13pt; line-height: 1.1; margin-bottom: 1pt; }
    .rpt-stats .st span { font-size: 8pt; color: #5B6478; font-weight: 600; }
    .rpt-cal { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 8pt; }
    .rpt-cal th { background: #EEF2FF !important; color: #1E2A4A; font-size: 8.5pt; font-weight: 700; padding: 5pt 2pt; border: 1pt solid #C9D7F5; text-align: center; }
    .rpt-cal td { border: 1pt solid #D9DEE8; height: 42pt; vertical-align: top; padding: 3pt; background: #fff; }
    .rpt-cal td.mute { background: #F1F5F9 !important; }
    .rpt-cal .dn { font-size: 7.5pt; font-weight: 700; color: #8A93A6; display: block; margin-bottom: 2pt; }
    .rpt-cal .mk { display: inline-block; min-width: 18pt; padding: 1pt 4pt; border-radius: 3pt; font-size: 9pt; font-weight: 800; text-align: center; }
    .rpt-cal .mk.P, i.mk.P { background: #E7F6EC !important; color: #17663A !important; }
    .rpt-cal .mk.L, i.mk.L { background: #FFF3DF !important; color: #8A4B00 !important; }
    .rpt-cal .mk.A, i.mk.A { background: #FDECEC !important; color: #B42318 !important; }
    .rpt-cal .mk.V, i.mk.V { background: #E0F2FE !important; color: #075985 !important; }
    .rpt-cal .mk.H, i.mk.H { background: #E9EEFF !important; color: #1F3FD1 !important; }
    .rpt-cal .mk.W, i.mk.W { background: #F3F4F7 !important; color: #5B6478 !important; }
    .rpt-leg { font-size: 8pt; color: #3A4256; margin: 0 0 8pt; padding: 5pt 6pt; background: #F8FAFF; border: 1pt solid #E4E8F0; border-radius: 4pt; }
    .rpt-leg span { display: inline-block; margin-right: 10pt; }
    .rpt-leg i { display: inline-block; min-width: 14pt; padding: 1pt 3pt; border-radius: 3pt; font-style: normal; font-weight: 800; font-size: 8pt; margin-right: 2pt; text-align: center; }
    .rpt-note { font-size: 8pt; color: #5B6478; margin: 0 0 12pt; line-height: 1.35; }
    .rpt-sg { display: table; width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 18pt; margin: 6pt 0 0 -18pt; }
    .rpt-sg .ln { display: table-cell; border-top: 1pt solid #0F172A; padding-top: 4pt; font-size: 9pt; color: #3A4256; }
    .rpt-sg .ln small { display: block; color: #8A93A6; margin-top: 1pt; font-size: 7.5pt; }
    .rpt-foot { margin-top: 8pt; font-size: 7.5pt; color: #8A93A6; text-align: right; }
  `;

  const openSheet = (idx) => {
    const row = sheets[idx];
    if (!row || !sheetBody) return;
    sheetBody.innerHTML = buildReportHtml(row);
    sheetOv.classList.add('show');
  };
  document.getElementById('reportBody')?.addEventListener('click', (e) => {
    const btn = e.target.closest('.pctbtn');
    if (!btn) return;
    openSheet(parseInt(btn.dataset.sheetIdx, 10));
  });
  document.getElementById('pctSheetClose')?.addEventListener('click', () => sheetOv.classList.remove('show'));
  sheetOv?.addEventListener('click', (e) => { if (e.target === sheetOv) sheetOv.classList.remove('show'); });
  document.getElementById('pctSheetWa')?.addEventListener('click', () => {
    if (!sheetCur) return;
    window.open('https://wa.me/?text=' + encodeURIComponent(sheetMsg(sheetCur)), '_blank', 'noopener');
  });
  document.getElementById('pctSheetMail')?.addEventListener('click', () => {
    if (!sheetCur) return;
    const period = range.month || ((range.from || '') + ' – ' + (range.to || ''));
    location.href = 'mailto:?subject=' + encodeURIComponent('Attendance sheet – ' + period)
      + '&body=' + encodeURIComponent(sheetMsg(sheetCur));
  });
  document.getElementById('pctSheetPrint')?.addEventListener('click', () => {
    const html = sheetBody?.innerHTML || '';
    if (!html) return;

    let frame = document.getElementById('attPrintFrame');
    if (!frame) {
      frame = document.createElement('iframe');
      frame.id = 'attPrintFrame';
      frame.setAttribute('aria-hidden', 'true');
      frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;opacity:0;pointer-events:none;';
      document.body.appendChild(frame);
    }

    const doc = frame.contentWindow.document;
    doc.open();
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Attendance Report — ${esc(companyName)}</title><style>${printStyles}</style></head><body>${html}</body></html>`);
    doc.close();

    const triggerPrint = () => {
      try {
        frame.contentWindow.focus();
        frame.contentWindow.print();
      } catch (e) {}
    };

    // Allow layout/styles to settle before opening the system print dialog
    if (frame.contentWindow.document.readyState === 'complete') {
      setTimeout(triggerPrint, 50);
    } else {
      frame.onload = () => setTimeout(triggerPrint, 50);
    }
  });
})();
</script>

<script>
$(document).ready(function() {
    var allUsers = [];
    var selectedUsers = [];
    var bulkRefType = '';

    function isBulkRiderMode() { return bulkRefType === 'rider'; }
    function toggleBulkRiderOrderColumns() {
        if (isBulkRiderMode()) { $('.bulk-rider-order-col').removeClass('d-none'); }
        else { $('.bulk-rider-order-col').addClass('d-none'); }
    }
    function bulkTableColspan() { return isBulkRiderMode() ? 11 : 7; }
    function riderOrderCellsHtml(user) {
        if (!isBulkRiderMode()) return '';
        return `
            <td class="align-middle bulk-rider-order-col"><input type="number" name="attendances[${user.tempId}][total_orders]" class="form-control form-control-sm total-orders" min="0" value="${user.totalOrders ?? ''}" required></td>
            <td class="align-middle bulk-rider-order-col"><input type="number" name="attendances[${user.tempId}][working_hours]" class="form-control form-control-sm working-hours" min="0" step="0.01" value="${user.workingHours ?? ''}" required></td>
            <td class="align-middle bulk-rider-order-col"><input type="number" name="attendances[${user.tempId}][cancelled_orders]" class="form-control form-control-sm cancelled-orders" min="0" value="${user.cancelledOrders ?? 0}"></td>
            <td class="align-middle bulk-rider-order-col"><input type="number" name="attendances[${user.tempId}][rejected_orders]" class="form-control form-control-sm rejected-orders" min="0" value="${user.rejectedOrders ?? 0}"></td>`;
    }

    if ($('#bulkAttendanceModal').length) {
        $('.select2-2').select2({ dropdownParent: $('#bulkAttendanceModal') });
        $('.select2-3').select2({ dropdownParent: $('#bulkAttendanceModal'), closeOnSelect: false });
    }

    $('#bulk_ref_type').change(function() {
        var refType = $(this).val();
        bulkRefType = refType;
        toggleBulkRiderOrderColumns();
        selectedUsers = [];
        renderSelectedUsers();
        if (refType) {
            $('#userSelect').prop('disabled', false).html('<option value="">Loading users...</option>');
            $.get('{{ route("attendance.users", "") }}/' + refType, function(users) {
                allUsers = users;
                $('#userSelect').html('<option value="">-- Select user to add --</option>');
                $.each(users, function(index, user) {
                    $('#userSelect').append('<option value="' + user.id + '">' + user.name + '</option>');
                });
            });
        } else {
            $('#userSelect').prop('disabled', true).html('<option value="">Select user to add</option>');
            allUsers = [];
        }
    });

    $('#userSelect').change(function() {
        var userId = $('#userSelect').val();
        var refType = $('#bulk_ref_type').val();
        if (!userId || !refType) return;
        if (selectedUsers.some(u => u.id == userId)) return;
        var user = allUsers.find(u => u.id == userId);
        if (!user) return;
        selectedUsers.push({
            id: user.id, name: user.name, email: user.email || '', refType: refType,
            tempId: 'row_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
            status: $('#default_status').val(), checkIn: '', checkOut: '', notes: '',
            totalOrders: '', workingHours: '', cancelledOrders: '', rejectedOrders: ''
        });
        renderSelectedUsers();
        $('#userSelect').val('').trigger('change.select2');
    });

    function renderSelectedUsers() {
        var tbody = $('#selectedUsersBody');
        tbody.empty();
        if (selectedUsers.length === 0) {
            tbody.append(`<tr id="noUsersRow"><td colspan="${bulkTableColspan()}" class="text-center py-5"><h6 class="text-muted">No users selected</h6></td></tr>`);
            $('#selectedUsersCount').text('0');
            $('#submitBulkBtn').prop('disabled', true);
            return;
        }
        $('#submitBulkBtn').prop('disabled', false);
        $.each(selectedUsers, function(index, user) {
            tbody.append(`
                <tr id="${user.tempId}" data-user-id="${user.id}" data-temp-id="${user.tempId}">
                    <td class="text-center align-middle">${index + 1}</td>
                    <td class="align-middle"><strong>${user.name}</strong>
                        <input type="hidden" name="attendances[${user.tempId}][ref_id]" value="${user.id}">
                        <input type="hidden" name="attendances[${user.tempId}][ref_type]" value="${user.refType}">
                    </td>
                    <td class="align-middle">
                        <select name="attendances[${user.tempId}][status]" class="form-select form-select-sm status-select" data-user-temp="${user.tempId}">
                            <option value="present" ${user.status === 'present' ? 'selected' : ''}>Present</option>
                            <option value="absent" ${user.status === 'absent' ? 'selected' : ''}>Absent</option>
                            <option value="late" ${user.status === 'late' ? 'selected' : ''}>Late</option>
                            <option value="half-day" ${user.status === 'half-day' ? 'selected' : ''}>Half Day</option>
                            <option value="weekend" ${user.status === 'weekend' ? 'selected' : ''}>Weekend</option>
                        </select>
                    </td>
                    <td class="align-middle"><input type="time" name="attendances[${user.tempId}][check_in]" class="form-control form-control-sm check-in" value="${user.checkIn}"></td>
                    <td class="align-middle"><input type="time" name="attendances[${user.tempId}][check_out]" class="form-control form-control-sm check-out" value="${user.checkOut}"></td>
                    ${riderOrderCellsHtml(user)}
                    <td class="align-middle"><input type="text" name="attendances[${user.tempId}][notes]" class="form-control form-control-sm notes" value="${user.notes}"></td>
                    <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger remove-user" data-user-temp="${user.tempId}"><i class="fas fa-trash"></i></button></td>
                </tr>`);
        });
        $('#selectedUsersCount').text(selectedUsers.length);
    }

    $(document).on('change', '.status-select', function() {
        var user = selectedUsers.find(u => u.tempId === $(this).data('user-temp'));
        if (user) user.status = $(this).val();
    });
    $(document).on('click', '.remove-user', function() {
        var tempId = $(this).data('user-temp');
        selectedUsers = selectedUsers.filter(u => u.tempId !== tempId);
        renderSelectedUsers();
    });
    $('#setAllPresent').click(function() { selectedUsers.forEach(u => u.status = 'present'); $('.status-select').val('present'); });
    $('#setAllAbsent').click(function() { selectedUsers.forEach(u => u.status = 'absent'); $('.status-select').val('absent'); });
    $('#setGeneral').click(function() {
        selectedUsers.forEach(u => { u.checkIn = '10:00'; u.checkOut = '18:00'; });
        $('.check-in').val('10:00'); $('.check-out').val('18:00');
    });
    $('#removeAllUsers').click(function() { selectedUsers = []; renderSelectedUsers(); });

    $('#bulkAttendanceForm').submit(function(e) {
        e.preventDefault();
        if (selectedUsers.length === 0) return;
        var submitBtn = $('#submitBulkBtn');
        submitBtn.prop('disabled', true).html('Saving...');
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            success: function() { location.reload(); },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html('Save Attendance');
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Save failed');
            }
        });
    });

    $('#bulkAttendanceModal').on('hidden.bs.modal', function() {
        selectedUsers = []; allUsers = []; bulkRefType = '';
        toggleBulkRiderOrderColumns();
        $('#bulk_ref_type').val('');
        $('#userSelect').html('<option value="">Select user to add</option>').prop('disabled', true);
        renderSelectedUsers();
    });
});
</script>
@endpush
@endcanany

@if(!auth()->user()->canany(['employees_attendance_view', 'riders_attendance_view']))
<div class="alert alert-danger mt-4" role="alert">You do not have permission to view attendance records.</div>
@endif
@endsection
