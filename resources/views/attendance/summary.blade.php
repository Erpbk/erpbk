@extends('layouts.app')
@section('title', 'Attendance - ' . $date->format('F Y'))

@php
  $userType = $userType ?? request('user_type', 'employee');
  $todayKpis = $todayKpis ?? ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0, 'unmarked' => 0];
  $branchTabs = $branchTabs ?? collect();
  $selectedBranchId = $selectedBranchId ?? null;
  $kpiTotal = max(1, (int) ($todayKpis['total'] ?? 1));
  $statusLetter = function (?string $status) {
      return match ($status) {
          'present' => 'P',
          'late' => 'L',
          'absent' => 'A',
          'on leave' => 'V',
          'half day' => 'H',
          'weekend' => 'W',
          default => '',
      };
  };
  $statusLabel = function (?string $status) {
      return match ($status) {
          'present' => 'Present',
          'late' => 'Late in',
          'absent' => 'Absent',
          'on leave' => 'Leave',
          'half day' => 'Half day',
          'weekend' => 'Weekend',
          default => 'Not marked',
      };
  };
  $employmentLabel = function ($user) {
      $st = strtolower((string) ($user->status ?? 'active'));
      return str_contains($st, 'inactive') ? 'Inactive' : 'Active';
  };
  $baseQuery = array_filter([
      'date' => $date->format('Y-m-d'),
      'user_type' => $userType,
      'user_id' => ($usersId ?? 'all') !== 'all' ? $usersId : null,
      'project_id' => $projectId ?: null,
      'fleet_supervisor' => $fleetSupervisor ?: null,
      'status' => $statusFilter ?: null,
      'branch_id' => $selectedBranchId,
      'from_date' => $fromDate ?: null,
      'to_date' => $toDate ?: null,
      'filter_date' => ($filterDate ?? null) ?: null,
  ], fn ($v) => $v !== null && $v !== '');
  $q = fn (array $extra = []) => http_build_query(array_filter(array_merge($baseQuery, $extra), fn ($v) => $v !== null && $v !== ''));
@endphp

@push('third_party_stylesheets')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/attendance-demo.css') }}?v=20261103mark">
@endpush

@section('content')
@canany(['employees_attendance_view', 'riders_attendance_view'])
@php
  $attUpsertUrl = safe_route('attendance.upsertMark', [], 'attendance/upsert-mark');
  $attClearUrl = safe_route('attendance.clearMark', [], 'attendance/clear-mark');
  $attSummaryExportUrl = safe_route('attendance.summary.export', $baseQuery ?? [], 'attendance/summary/export');
@endphp
<div class="att-demo"
     id="attDemo"
     data-upsert="{{ $attUpsertUrl }}"
     data-clear="{{ $attClearUrl }}"
     data-csrf="{{ csrf_token() }}"
     data-user-type="{{ $userType }}">

  <div class="tabrow">
    <div class="ptabs">
      <a class="ptab on" href="{{ route('attendance.summary', ['user_type' => $userType]) }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        Attendance
      </a>
      <a class="ptab" href="{{ route('attendance.index', ['ref_type' => $userType]) }}">
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
      <a class="btn green" href="{{ $attSummaryExportUrl }}">Export</a>
    </div>
  </div>

  <section class="kpis" aria-label="Today's summary">
    @php
      $kpis = [
        ['key' => '', 'label' => 'Total employees', 'n' => $todayKpis['total'], 'c' => '#1F3FD1', 'bg' => '#EEF2FF', 'pct' => 100],
        ['key' => 'present', 'label' => 'Present today', 'n' => $todayKpis['present'], 'c' => '#1E8E4E', 'bg' => '#E7F6EC', 'pct' => round($todayKpis['present'] / $kpiTotal * 100)],
        ['key' => 'late', 'label' => 'Late in today', 'n' => $todayKpis['late'], 'c' => '#E59A1F', 'bg' => '#FFF3DF', 'pct' => round($todayKpis['late'] / $kpiTotal * 100)],
        ['key' => 'absent', 'label' => 'Absent today', 'n' => $todayKpis['absent'], 'c' => '#D64535', 'bg' => '#FDECEC', 'pct' => round($todayKpis['absent'] / $kpiTotal * 100)],
        ['key' => 'unmarked', 'label' => 'Not marked yet', 'n' => $todayKpis['unmarked'], 'c' => '#5B6478', 'bg' => '#F3F4F7', 'pct' => round($todayKpis['unmarked'] / $kpiTotal * 100)],
      ];
    @endphp
    @foreach($kpis as $k)
      @php
        $isOn = ($k['key'] === '' && ! $statusFilter) || ($k['key'] !== '' && $statusFilter === $k['key']);
        if ($k['key'] === '' || $isOn) {
          $href = route('attendance.summary') . '?' . $q(['status' => null]);
        } else {
          $href = route('attendance.summary') . '?' . $q(['status' => $k['key']]);
        }
      @endphp
      <a class="kpi{{ $isOn ? ' on' : '' }}" href="{{ $href }}" style="--c:{{ $k['c'] }};--bgc:{{ $k['bg'] }}">
        <div class="ic">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
        </div>
        <div>
          <div class="num">{{ $k['n'] }}</div>
          <div class="lb">{{ $k['label'] }}</div>
        </div>
        <div class="pc">{{ $k['pct'] }}%</div>
        <div class="tr"><i style="--w:{{ $k['pct'] }}%"></i></div>
      </a>
    @endforeach
  </section>

  <section class="sheet">
    <div class="sheet-top">
      <div class="btabs">
        <a class="btab{{ $selectedBranchId === null ? ' on' : '' }}" href="{{ route('attendance.summary') }}?{{ $q(['branch_id' => null]) }}">
          All Branches<em>{{ $todayKpis['total'] }}</em>
        </a>
        @foreach($branchTabs as $tab)
          <a class="btab{{ (int) $selectedBranchId === (int) $tab['id'] ? ' on' : '' }}"
             href="{{ route('attendance.summary') }}?{{ $q(['branch_id' => $tab['id']]) }}">
            {{ $tab['name'] }}<em>{{ $tab['count'] }}</em>
          </a>
        @endforeach
      </div>
      <div class="tools">
        <label class="qs">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          <input type="search" id="attQuickSearch" placeholder="Quick Search…" value="{{ request('quick_search') }}" aria-label="Quick search">
        </label>
        <button type="button" class="btn blue fbtn" id="attFilterBtn">
          Filter Attendance
          @php $fc = collect([$statusFilter, $selectedBranchId, $usersId !== 'all' ? $usersId : null, $fromDate, $toDate])->filter()->count(); @endphp
          <span class="cnt" @if($fc < 1) hidden @endif>{{ $fc }}</span>
        </button>
      </div>
    </div>

    <div class="viewbar">
      <div class="seg">
        <a class="{{ $viewMode === 'week' ? 'on' : '' }}" href="{{ route('attendance.summary') }}?{{ $q(['view_mode' => 'week', 'view_start' => 1]) }}">1 Week</a>
        <a class="{{ $viewMode === 'ten_days' ? 'on' : '' }}" href="{{ route('attendance.summary') }}?{{ $q(['view_mode' => 'ten_days', 'view_start' => 1]) }}">10 Days</a>
        <a class="{{ $viewMode === 'month' ? 'on' : '' }}" href="{{ route('attendance.summary') }}?{{ $q(['view_mode' => 'month', 'view_start' => 1]) }}">Full Month</a>
      </div>
      <div class="range">
        <a class="{{ ! $hasPrevWindow ? 'disabled' : '' }}" href="{{ route('attendance.summary') }}?{{ $q(['view_mode' => $viewMode, 'view_start' => $prevStart]) }}" aria-label="Previous">&lsaquo;</a>
        <span>
          @if(count($days) > 0 && !empty($isCustomRange))
            {{ \Carbon\Carbon::parse($days[0]['date'])->format('d M') }} – {{ \Carbon\Carbon::parse($days[count($days)-1]['date'])->format('d M') }}
          @elseif(count($days) > 0)
            {{ $days[0]['number'] }} – {{ $days[count($days)-1]['number'] }} of {{ $monthTotalDays }} · {{ $date->format('M Y') }}
          @else
            {{ $date->format('M Y') }}
          @endif
        </span>
        <a class="{{ ! $hasNextWindow ? 'disabled' : '' }}" href="{{ route('attendance.summary') }}?{{ $q(['view_mode' => $viewMode, 'view_start' => $nextStart]) }}" aria-label="Next">&rsaquo;</a>
        <a class="btn outline" href="{{ route('attendance.summary') }}?{{ $q(['date' => $prevMonth, 'view_start' => 1]) }}" title="Previous month">&larr;</a>
        <a class="btn outline" href="{{ route('attendance.summary') }}?{{ $q(['date' => $nextMonth, 'view_start' => 1]) }}" title="Next month">&rarr;</a>
      </div>
    </div>

    <div class="legend">
      <span><i style="background:var(--p-bg);border:1px solid var(--p-bd)"></i>Present</span>
      <span><i style="background:var(--l-bg);border:1px solid var(--l-bd)"></i>Late in</span>
      <span><i style="background:var(--a-bg);border:1px solid var(--a-bd)"></i>Absent</span>
      <span><i style="background:var(--v-bg);border:1px solid var(--v-bd)"></i>Leave</span>
      <span><i style="background:var(--h-bg);border:1px solid var(--h-bd)"></i>Half day</span>
      <span><i style="background:var(--w-bg);border:1px solid var(--w-bd)"></i>Weekend</span>
      <span><i style="background:#fff;border:1px dashed #C3CAD8"></i>Not marked</span>
    </div>

    <div class="tablewrap">
      <table class="att" id="attTable">
        <thead>
          <tr>
            <th class="l stick">Name</th>
            <th>Status</th>
            @foreach($days as $day)
              <th class="{{ $day['is_today'] ? 'today' : '' }}{{ !empty($day['is_weekend']) ? ' we' : '' }}">
                <span class="dn">{{ $day['number'] }}</span>
                <small>{{ $day['day_name'] }}</small>
              </th>
            @endforeach
            <th>Present</th>
          </tr>
        </thead>
        <tbody id="attBody">
          @forelse($users as $user)
            @php
              $initials = collect(explode(' ', (string) $user->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
              $profileUrl = $userType === 'employee'
                ? safe_route('employees.show', ['employee' => $user->id], 'employees/{id}')
                : safe_route('riders.show', ['rider' => $user->id], 'riders/{id}');
              $empStatus = $employmentLabel($user);
              $presentCount = (float) ($user->total_present ?? 0) + (0.5 * (float) ($user->total_halfday ?? 0));
              // half day already counted in total_present in controller for late/half - use total_present as HTML "PRESENT"
              $presentCount = (int) ($user->total_present ?? 0);
            @endphp
            <tr data-name="{{ strtolower($user->name) }}" data-code="{{ strtolower($user->employee_id ?? $user->rider_id ?? '') }}">
              <td class="stick">
                <div class="emp">
                  <a class="av" href="{{ $profileUrl }}" title="Open profile">{{ strtoupper($initials) }}</a>
                  <div>
                    <a class="nm" href="{{ $profileUrl }}">{{ $user->name }}</a>
                    <div class="sub">{{ trim(($user->designation ?? '') . ' · ' . ($user->employee_id ?? $user->rider_id ?? '')) }}</div>
                  </div>
                </div>
              </td>
              <td class="stc">
                <span class="stpill" style="background:{{ $empStatus === 'Active' ? '#E7F6EC' : '#F3F4F7' }};color:{{ $empStatus === 'Active' ? '#17663A' : '#5B6478' }}">
                  <i style="background:{{ $empStatus === 'Active' ? '#2E9E5B' : '#8A93A6' }}"></i>{{ $empStatus }}
                </span>
              </td>
              @foreach($days as $day)
                @php
                  $att = $user->attendance_data[$day['date']] ?? null;
                  $exists = $att && !empty($att['exists']);
                  $letter = $exists ? $statusLetter($att['status'] ?? null) : '';
                  $label = $exists ? $statusLabel($att['status'] ?? null) : 'Not marked';
                  $in = $exists ? ($att['check_in'] ?? null) : null;
                  $out = $exists ? ($att['check_out'] ?? null) : null;
                  $totalOrders = $exists ? ($att['total_orders'] ?? '') : '';
                  $workingHours = $exists ? ($att['working_hours'] ?? '') : '';
                  $cancelledOrders = $exists ? ($att['cancelled_orders'] ?? 0) : 0;
                  $rejectedOrders = $exists ? ($att['rejected_orders'] ?? 0) : 0;
                  $ontimePct = $exists ? ($att['ontime_orders_percentage'] ?? '') : '';
                @endphp
                <td class="{{ !empty($day['is_today']) ? 'today' : '' }}">
                  <button type="button"
                    class="cell {{ $letter }}"
                    data-ref-id="{{ $user->id }}"
                    data-ref-type="{{ $userType }}"
                    data-date="{{ $day['date'] }}"
                    data-name="{{ $user->name }}"
                    data-status="{{ $exists ? ($att['status'] ?? '') : '' }}"
                    data-check-in="{{ $in ? \Carbon\Carbon::parse($day['date'].' '.$in)->format('H:i') : '' }}"
                    data-check-out="{{ $out ? \Carbon\Carbon::parse($day['date'].' '.$out)->format('H:i') : '' }}"
                    data-total-orders="{{ $totalOrders }}"
                    data-working-hours="{{ $workingHours }}"
                    data-cancelled-orders="{{ $cancelledOrders }}"
                    data-rejected-orders="{{ $rejectedOrders }}"
                    data-ontime-pct="{{ $ontimePct }}"
                    title="{{ $label }}{{ $in ? ' · In '.$in : '' }}{{ $out ? ' · Out '.$out : '' }}">
                    @if($letter)
                      <span>{{ $letter }}</span>
                      @if($in || $out)
                        <small>{{ $in ? \Carbon\Carbon::parse($day['date'].' '.$in)->format('H:i') : '' }}{{ $in && $out ? '–' : '' }}{{ $out ? \Carbon\Carbon::parse($day['date'].' '.$out)->format('H:i') : '' }}</small>
                      @endif
                    @else
                      +
                    @endif
                  </button>
                </td>
              @endforeach
              <td class="prc">{{ $presentCount }}</td>
            </tr>
          @empty
            <tr><td colspan="{{ count($days) + 3 }}" class="empty">No {{ $userType }}s match these filters.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="pager">
      <div class="l">
        <span>Showing {{ $totalUsers }} {{ $userType }}{{ $totalUsers === 1 ? '' : 's' }}</span>
      </div>
    </div>
  </section>

  {{-- Filter drawer --}}
  <div class="ov" id="attFilterOv"></div>
  <aside class="drawer" id="attFilterDrawer" role="dialog" aria-label="Filter Attendance">
    <div class="hd"><h3>Filter Attendance</h3><button type="button" class="x" id="attFilterClose" aria-label="Close">&times;</button></div>
    <form class="bd" method="GET" action="{{ route('attendance.summary') }}">
      <input type="hidden" name="user_type" value="{{ $userType }}">
      <input type="hidden" name="view_mode" value="{{ $viewMode }}">
      <input type="hidden" name="view_start" value="{{ $viewStart }}">
      <div class="fld">
        <label for="fMonth">Month</label>
        <input type="month" id="fMonth" name="date" value="{{ $date->format('Y-m') }}">
      </div>
      <div class="fld">
        <label for="fDate">Date</label>
        <input type="date" id="fDate" name="filter_date" value="{{ $filterDate }}">
      </div>
      <div class="two">
        <div class="fld">
          <label for="fFrom">From</label>
          <input type="date" id="fFrom" name="from_date" value="{{ $fromDate }}">
        </div>
        <div class="fld">
          <label for="fTo">To</label>
          <input type="date" id="fTo" name="to_date" value="{{ $toDate }}">
        </div>
      </div>
      <div class="fld">
        <label for="fUser">User</label>
        <select id="fUser" name="user_id">
          <option value="all">All</option>
          @foreach(($filterUsers ?? $users ?? collect()) as $u)
            <option value="{{ $u->id }}" {{ (string) $usersId === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="fld">
        <label for="fStatus">Mark status</label>
        <select id="fStatus" name="status">
          <option value="">All</option>
          @foreach(['present'=>'Present','late'=>'Late','absent'=>'Absent','on leave'=>'Leave','half day'=>'Half day','weekend'=>'Weekend','unmarked'=>'Not marked'] as $val => $lab)
            <option value="{{ $val }}" {{ $statusFilter === $val ? 'selected' : '' }}>{{ $lab }}</option>
          @endforeach
        </select>
      </div>
      @if($userType === 'employee' && $branchTabs->isNotEmpty())
      <div class="fld">
        <label for="fBranch">Branch</label>
        <select id="fBranch" name="branch_id">
          <option value="">All Branches</option>
          @foreach($branchTabs as $tab)
            <option value="{{ $tab['id'] }}" {{ (int) $selectedBranchId === (int) $tab['id'] ? 'selected' : '' }}>{{ $tab['name'] }}</option>
          @endforeach
        </select>
      </div>
      @endif
      <div class="go">
        <a class="btn reset" href="{{ route('attendance.summary', ['user_type' => $userType]) }}">Reset</a>
        <button type="submit" class="btn blue">Apply</button>
      </div>
    </form>
  </aside>

  {{-- Mark modal --}}
  <div class="att-dlg-ov" id="markOv">
    <div class="att-dlg{{ $userType === 'rider' ? ' wide' : '' }}" id="markDlg" role="dialog" aria-labelledby="markTitle">
      <div class="hd"><h3 id="markTitle">Mark Attendance</h3><button type="button" class="x" id="markClose" aria-label="Close">&times;</button></div>
      <div class="bd">
        <div class="mhead" id="markSub"></div>
        <div class="chips" id="markChips">
          @foreach([
            ['present','Present'],
            ['late','Late in'],
            ['absent','Absent'],
            ['on leave','Leave'],
            ['half day','Half day'],
            ['weekend','Weekend'],
          ] as [$val, $lab])
            <button type="button" class="chip" data-status="{{ $val }}">{{ $lab }}</button>
          @endforeach
        </div>
        <div class="two">
          <div class="fld"><label for="markIn">Check In</label><input type="time" id="markIn"></div>
          <div class="fld"><label for="markOut">Check Out</label><input type="time" id="markOut"></div>
        </div>
        <div class="rider-mark-fields" id="markRiderFields" @if($userType !== 'rider') hidden @endif>
          <div class="rider-mark-title">Delivery Orders / Rider Activity</div>
          <div class="two">
            <div class="fld">
              <label for="markTotalOrders">Delivery Orders <span class="req">*</span></label>
              <input type="number" id="markTotalOrders" min="0" step="1" placeholder="0">
            </div>
            <div class="fld">
              <label for="markWorkingHours">Working Hours <span class="req">*</span></label>
              <input type="number" id="markWorkingHours" min="0" step="0.01" placeholder="0">
            </div>
          </div>
          <div class="two">
            <div class="fld">
              <label for="markCancelled">Cancelled Orders</label>
              <input type="number" id="markCancelled" min="0" step="1" value="0" placeholder="0">
            </div>
            <div class="fld">
              <label for="markRejected">Rejected Orders</label>
              <input type="number" id="markRejected" min="0" step="1" value="0" placeholder="0">
            </div>
          </div>
          <div class="fld">
            <label for="markOntime">On-Time Performance (%)</label>
            <input type="number" id="markOntime" min="0" max="100" step="0.01" placeholder="0">
          </div>
        </div>
      </div>
      <div class="ft">
        <button type="button" class="btn reset" id="markClear">Clear</button>
        <button type="button" class="btn blue" id="markSave">Save</button>
      </div>
    </div>
  </div>

  <div class="toast" id="attToast"></div>

  @include('attendance.partials.import_modal', [
    'refType' => $userType,
    'importPeople' => $importPeople ?? $filterUsers ?? $users ?? collect(),
    'openImport' => $openImport ?? false,
  ])
</div>

@push('page-scripts')
<script>
(function () {
  const root = document.getElementById('attDemo');
  if (!root) return;
  const upsertUrl = root.dataset.upsert;
  const clearUrl = root.dataset.clear;
  const csrf = root.dataset.csrf;
  const userType = root.dataset.userType;
  let cur = null;

  const toast = (msg) => {
    const el = document.getElementById('attToast');
    el.textContent = msg;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2200);
  };

  // Quick search
  document.getElementById('attQuickSearch')?.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#attBody tr[data-name]').forEach(tr => {
      const hay = (tr.dataset.name || '') + ' ' + (tr.dataset.code || '');
      tr.style.display = !q || hay.includes(q) ? '' : 'none';
    });
  });

  // Filter drawer
  const ov = document.getElementById('attFilterOv');
  const drawer = document.getElementById('attFilterDrawer');
  const openFilter = () => { ov.classList.add('show'); drawer.classList.add('show'); };
  const closeFilter = () => { ov.classList.remove('show'); drawer.classList.remove('show'); };
  document.getElementById('attFilterBtn')?.addEventListener('click', openFilter);
  document.getElementById('attFilterClose')?.addEventListener('click', closeFilter);
  ov?.addEventListener('click', closeFilter);

  // Mark modal
  const markOv = document.getElementById('markOv');
  const markDlg = document.getElementById('markDlg');
  const markRiderFields = document.getElementById('markRiderFields');
  const markTotalOrders = document.getElementById('markTotalOrders');
  const markWorkingHours = document.getElementById('markWorkingHours');
  const markCancelled = document.getElementById('markCancelled');
  const markRejected = document.getElementById('markRejected');
  const markOntime = document.getElementById('markOntime');

  const isRiderMark = (refType) => (refType || userType) === 'rider';

  const fillRiderFields = (btn) => {
    if (!markRiderFields) return;
    const show = isRiderMark(btn?.dataset?.refType || userType);
    markRiderFields.hidden = !show;
    markDlg?.classList.toggle('wide', show);
    if (!show) return;
    markTotalOrders.value = btn.dataset.totalOrders ?? '';
    markWorkingHours.value = btn.dataset.workingHours ?? '';
    markCancelled.value = btn.dataset.cancelledOrders !== undefined && btn.dataset.cancelledOrders !== ''
      ? btn.dataset.cancelledOrders : '0';
    markRejected.value = btn.dataset.rejectedOrders !== undefined && btn.dataset.rejectedOrders !== ''
      ? btn.dataset.rejectedOrders : '0';
    markOntime.value = btn.dataset.ontimePct ?? '';
  };

  const readRiderMetrics = () => {
    const total = markTotalOrders?.value;
    const hours = markWorkingHours?.value;
    if (total === '' || hours === '') {
      throw new Error('Delivery orders and working hours are required for riders.');
    }
    return {
      total_orders: parseInt(total, 10) || 0,
      working_hours: parseFloat(hours) || 0,
      cancelled_orders: parseInt(markCancelled?.value || '0', 10) || 0,
      rejected_orders: parseInt(markRejected?.value || '0', 10) || 0,
      ontime_orders_percentage: markOntime?.value !== '' ? parseFloat(markOntime.value) : null,
    };
  };

  const openMark = (btn) => {
    cur = {
      btn,
      refId: btn.dataset.refId,
      refType: btn.dataset.refType || userType,
      date: btn.dataset.date,
      name: btn.dataset.name,
      status: btn.dataset.status || 'present',
    };
    document.getElementById('markSub').textContent = cur.name + ' · ' + cur.date;
    document.getElementById('markIn').value = btn.dataset.checkIn || '';
    document.getElementById('markOut').value = btn.dataset.checkOut || '';
    document.querySelectorAll('#markChips .chip').forEach(c => {
      c.classList.toggle('on', c.dataset.status === cur.status);
    });
    fillRiderFields(btn);
    markOv.classList.add('show');
  };
  const closeMark = () => { markOv.classList.remove('show'); cur = null; };
  document.getElementById('markClose')?.addEventListener('click', closeMark);
  markOv?.addEventListener('click', (e) => { if (e.target === markOv) closeMark(); });

  document.getElementById('attBody')?.addEventListener('click', (e) => {
    const btn = e.target.closest('.cell');
    if (!btn) return;
    openMark(btn);
  });

  document.getElementById('markChips')?.addEventListener('click', (e) => {
    const chip = e.target.closest('.chip');
    if (!chip || !cur) return;
    cur.status = chip.dataset.status;
    document.querySelectorAll('#markChips .chip').forEach(c => c.classList.toggle('on', c === chip));
  });

  const letterFor = (status) => ({
    present: 'P', late: 'L', absent: 'A', 'on leave': 'V', 'half day': 'H', weekend: 'W'
  })[status] || '';

  const paintCell = (btn, attendance) => {
    const status = attendance?.status || '';
    const checkIn = attendance?.check_in || '';
    const checkOut = attendance?.check_out || '';
    const letter = letterFor(status);
    btn.dataset.status = status;
    btn.dataset.checkIn = checkIn;
    btn.dataset.checkOut = checkOut;
    if (attendance && Object.prototype.hasOwnProperty.call(attendance, 'total_orders')) {
      btn.dataset.totalOrders = attendance.total_orders ?? '';
      btn.dataset.workingHours = attendance.working_hours ?? '';
      btn.dataset.cancelledOrders = attendance.cancelled_orders ?? 0;
      btn.dataset.rejectedOrders = attendance.rejected_orders ?? 0;
      btn.dataset.ontimePct = attendance.ontime_orders_percentage ?? '';
    }
    if (!status) {
      btn.dataset.totalOrders = '';
      btn.dataset.workingHours = '';
      btn.dataset.cancelledOrders = '0';
      btn.dataset.rejectedOrders = '0';
      btn.dataset.ontimePct = '';
    }
    btn.className = 'cell' + (letter ? ' ' + letter : '');
    if (!status) {
      btn.innerHTML = '+';
      return;
    }
    let html = '<span>' + letter + '</span>';
    if (checkIn || checkOut) {
      html += '<small>' + (checkIn || '') + (checkIn && checkOut ? '–' : '') + (checkOut || '') + '</small>';
    }
    btn.innerHTML = html;
  };

  document.getElementById('markSave')?.addEventListener('click', async () => {
    if (!cur) return;
    const body = {
      ref_type: cur.refType,
      ref_id: cur.refId,
      date: cur.date,
      status: cur.status || 'present',
      check_in: document.getElementById('markIn').value || null,
      check_out: document.getElementById('markOut').value || null,
    };
    try {
      if (isRiderMark(cur.refType)) {
        Object.assign(body, readRiderMetrics());
      }
      const res = await fetch(upsertUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (!res.ok || !data.success) {
        const firstErr = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(firstErr || data.message || 'Save failed');
      }
      paintCell(cur.btn, data.attendance);
      toast('Attendance saved');
      closeMark();
    } catch (err) {
      toast(err.message || 'Error saving');
    }
  });

  document.getElementById('markClear')?.addEventListener('click', async () => {
    if (!cur) return;
    try {
      const res = await fetch(clearUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify({ ref_type: cur.refType, ref_id: cur.refId, date: cur.date }),
      });
      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.message || 'Clear failed');
      paintCell(cur.btn, {});
      toast('Attendance cleared');
      closeMark();
    } catch (err) {
      toast(err.message || 'Error clearing');
    }
  });
})();
</script>
@endpush
@endcanany
@endsection
