@php
  $demoMode = $demoMode ?? 'activities';
  $projects = $projects ?? collect();
  $fleetSupervisors = $fleetSupervisors ?? collect();
  $alertTypes = $demoMode === 'live'
    ? [
        ['key' => 'labs', 'label' => 'Absent today', 'fields' => 'project,supervisor'],
        ['key' => 'lhrs', 'label' => 'Low hours today', 'fields' => 'project,supervisor,date,hours'],
        ['key' => 'lord', 'label' => 'Low orders today', 'fields' => 'project,supervisor,date,orders'],
      ]
    : ($demoMode === 'summary'
      ? [
          ['key' => 'off2', 'label' => 'Not working 2–3 days', 'fields' => 'project,supervisor,date'],
          ['key' => 'off4', 'label' => 'Not working 4–5 days', 'fields' => 'project,supervisor,date'],
          ['key' => 'off6', 'label' => 'Not working 6+ days', 'fields' => 'project,supervisor,date'],
          ['key' => 'hrs', 'label' => 'Less than 10 hours', 'fields' => 'project,supervisor,date,hours'],
          ['key' => 'ord', 'label' => 'Less than 15 orders', 'fields' => 'project,supervisor,date,orders'],
        ]
      : [
          ['key' => 'abs', 'label' => 'Absent', 'fields' => 'project,supervisor,date'],
          ['key' => 'off', 'label' => 'Not working', 'fields' => 'project,supervisor,date,days'],
          ['key' => 'hrs', 'label' => 'Duty hours not completed', 'fields' => 'project,supervisor,date,hours'],
          ['key' => 'ord', 'label' => 'Daily target not met', 'fields' => 'project,supervisor,date,orders'],
        ]);
  $waDefaultAlertDate = now()->subDay()->toDateString();
  $waTodayDate = now()->toDateString();
  $waCompanyId = (int) (\App\Support\CompanyContext::id() ?? 0);
  $waNumbers = ($waCompanyId > 0 && auth()->user())
    ? app(\App\Services\WhatsApp\WhatsAppConnectionService::class)
        ->getConnectionsForUserSend(auth()->user(), $waCompanyId)
    : collect();
  $waPrefsUrl = \Illuminate\Support\Facades\Route::has('whatsapp.ui_prefs.update')
    ? route('whatsapp.ui_prefs.update', absolute: false)
    : '';
  $waUiPrefs = auth()->user()
    ? \App\Support\WhatsApp\WhatsAppUiPreferences::forUser(auth()->user())
    : \App\Support\WhatsApp\WhatsAppUiPreferences::defaults();
@endphp

<link rel="stylesheet" href="{{ asset('css/whatsapp-ui.css') }}?v=20261123-wa-av">

<div id="raWaOv" class="wa-ui-ov" hidden></div>
<aside id="raWaDrawer" class="wa-ui" hidden aria-label="WhatsApp Alerts"
       data-wa-mode="{{ $demoMode }}"
       data-wa-filter-url="{{ route('rider.activities.whatsapp_filter') }}"
       data-wa-send-url="{{ route('rider.activities.whatsapp_send') }}"
       data-wa-prefs-url="{{ $waPrefsUrl }}"
       data-wa-prefs='@json($waUiPrefs)'
       data-wa-csrf="{{ csrf_token() }}">

  {{-- Header --}}
  <div class="wa-hd">
    <h3>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 21l2.1-5.4A8.4 8.4 0 1 1 21 11.5z"/></svg>
      <span id="raChTitle">WhatsApp Alerts</span>
    </h3>
    <button type="button" class="wa-icon-btn" id="raWaGear" title="Customize" aria-label="Customize">&#9881;</button>
    <button type="button" class="wa-icon-btn" id="raWaX" aria-label="Close">&times;</button>
  </div>

  {{-- Toolbar: type tabs + filters --}}
  <div class="wa-bar">
    <div class="wa-seg" id="raWaTypes">
      @foreach($alertTypes as $i => $type)
        <button type="button" class="{{ $i === 0 ? 'on' : '' }}" data-type="{{ $type['key'] }}" data-fields="{{ $type['fields'] }}">
          {{ $type['label'] }}
          <span data-cnt="{{ $type['key'] }}">0</span>
        </button>
      @endforeach
    </div>

    <div class="wa-filters" id="raWaSet">
      <label data-wa-field="project" class="wa-fw">
        <select id="raWaProj" class="wa-fld proj no-select2" data-no-select2 aria-label="Project">
          <option value="">All Projects</option>
          @foreach($projects as $project)
            <option value="{{ $project->id }}">{{ $project->name }}</option>
          @endforeach
        </select>
      </label>
      <label data-wa-field="supervisor" class="wa-fw">
        <select id="raWaSup" class="wa-fld sup no-select2" data-no-select2 aria-label="Supervisor">
          <option value="">All Supervisors</option>
          @foreach($fleetSupervisors as $supervisor)
            <option value="{{ $supervisor }}">{{ $supervisor }}</option>
          @endforeach
        </select>
      </label>
      <label data-wa-field="date" class="wa-fw">
        <input type="date" id="raWaDate" class="wa-fld date" aria-label="Date"
               value="{{ $waDefaultAlertDate }}"
               max="{{ $waTodayDate }}"
               data-default-yesterday="{{ $waDefaultAlertDate }}"
               data-default-today="{{ $waTodayDate }}">
      </label>
      <label data-wa-field="days" class="wa-num">Not working for <input type="number" id="raWaDays" min="1" max="30" value="2"> days or more</label>
      <label data-wa-field="hours" class="wa-num">Daily duty <input type="number" id="raWaHrs" min="1" max="16" step="0.5" value="10"> hours</label>
      <label data-wa-field="orders" class="wa-num">Daily target <input type="number" id="raWaOrd" min="1" max="60" value="15"> orders</label>
      <button type="button" class="wa-go" id="raWaFilter">Filter</button>
    </div>
  </div>

  {{-- Rider list --}}
  <div class="wa-list" id="raWaList">
    <div class="wa-lh">
      <input type="checkbox" id="raWaAll" checked aria-label="Select all">
      <span id="raWaLh">Hit Filter to load riders</span>
    </div>
    <div class="wa-rows" id="raWaRows">
      <div class="wa-empty">Set parameters and press Filter.</div>
    </div>
  </div>

  <div class="wa-ft">
    <span class="note" id="raWaNote">Each selected rider gets the message template on their phone number.</span>
  </div>

  {{-- Chat preview + composer --}}
  <div class="wa-side">
    <div class="wa-phone" id="raWaPhone">
      <div class="wa-ch">
        <div class="wa-av" id="raWaPAv">RA</div>
        <div class="who">
          <b id="raWaPName">—</b>
          <small id="raWaPSub">Rider · WhatsApp</small>
        </div>
        <div class="acts" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="14" height="12" rx="2"/><path d="M16 10l6-4v12l-6-4"/></svg>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        </div>
      </div>
      <div class="wa-msgs" id="raWaChat">
        <span class="wa-day">Today</span>
        <div class="wa-stack" id="raWaMsgStack">
          <div class="wa-bub" id="raWaMsgPreview">
            <div class="txt" id="raWaMsgText">Select a rider after filtering to preview the message.</div>
            <span class="meta"><span>Preview</span> <b>&#10003;&#10003;</b></span>
          </div>
          <div class="wa-preview-warn" id="raWaMsgWarn" hidden></div>
        </div>
      </div>
    </div>
    <div class="wa-comp" id="raWaSendPanel">
      <span class="ib" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 11l-8.5 8.5a5 5 0 0 1-7-7L14 4a3.5 3.5 0 0 1 5 5l-8.5 8.5a2 2 0 0 1-3-3L15 7"/></svg></span>
      <span class="ib" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></svg></span>
      <select id="raWaFrom" class="no-select2" data-no-select2 aria-label="Send from" title="Send from">
        @forelse($waNumbers as $num)
          <option value="{{ $num->id }}" @selected($loop->first)>{{ $num->displayLabel() }}</option>
        @empty
          <option value="">{{ __('No WhatsApp number allotted to you') }}</option>
        @endforelse
      </select>
      <button type="button" class="wa-send" id="raWaSend" disabled aria-label="Send now"
              @if($waNumbers->isEmpty()) data-wa-send-blocked="1" title="{{ __('No WhatsApp number allotted to you') }}" @else title="Send now" @endif>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 20.5V14l9-2-9-2V3.5L22 12z"/></svg>
      </button>
    </div>
  </div>

  {{-- Column resizer (positioned by JS) --}}
  <div class="wa-rsz" id="raWaRsz" title="Drag to resize · double-click to reset"></div>

  {{-- Customize panel (UI only; prefs applied via CSS variables) --}}
  <div class="wa-cust" id="raWaCust" hidden>
    <div class="top"><h2>Customize</h2><button type="button" class="x" id="raWaCustX" aria-label="Close customize">&times;</button></div>
    <div id="raWaCustRows"></div>
    <div class="btns">
      <button type="button" class="reset" id="raWaCustReset">Reset</button>
      <button type="button" class="done" id="raWaCustDone">Done</button>
    </div>
  </div>
</aside>

<div class="ra-toast" id="raToast"></div>
