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
@endphp

<div id="raWaOv" hidden></div>
<aside id="raWaDrawer" hidden aria-label="WhatsApp Alerts"
       data-wa-mode="{{ $demoMode }}"
       data-wa-filter-url="{{ route('rider.activities.whatsapp_filter') }}"
       data-wa-send-url="{{ route('rider.activities.whatsapp_send') }}"
       data-wa-csrf="{{ csrf_token() }}">
  <div class="hd">
    <h3>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-12.6 7.3L3 20l1.3-5.1A8.5 8.5 0 1 1 21 11.5z"/></svg>
      <span id="raChTitle">WhatsApp Alerts</span>
    </h3>
    <button type="button" class="x" id="raWaX" aria-label="Close">×</button>
  </div>

  <div class="ra-wa-types" id="raWaTypes">
    @foreach($alertTypes as $i => $type)
      <button type="button" class="{{ $i === 0 ? 'on' : '' }}" data-type="{{ $type['key'] }}" data-fields="{{ $type['fields'] }}">
        {{ $type['label'] }}
        <span data-cnt="{{ $type['key'] }}">0</span>
      </button>
    @endforeach
  </div>

  <div class="ra-wa-set" id="raWaSet">
    <label data-wa-field="project">Project
      <select id="raWaProj" class="no-select2" data-no-select2>
        <option value="">All Projects</option>
        @foreach($projects as $project)
          <option value="{{ $project->id }}">{{ $project->name }}</option>
        @endforeach
      </select>
    </label>
    <label data-wa-field="supervisor">Supervisor
      <select id="raWaSup" class="no-select2" data-no-select2>
        <option value="">All Supervisors</option>
        @foreach($fleetSupervisors as $supervisor)
          <option value="{{ $supervisor }}">{{ $supervisor }}</option>
        @endforeach
      </select>
    </label>
    <label data-wa-field="date">Date
      <input type="date" id="raWaDate"
             value="{{ $waDefaultAlertDate }}"
             max="{{ $waTodayDate }}"
             data-default-yesterday="{{ $waDefaultAlertDate }}"
             data-default-today="{{ $waTodayDate }}">
    </label>
    <label data-wa-field="days">Not working for <input type="number" id="raWaDays" min="1" max="30" value="2"> days or more</label>
    <label data-wa-field="hours">Daily duty <input type="number" id="raWaHrs" min="1" max="16" step="0.5" value="10"> hours</label>
    <label data-wa-field="orders">Daily target <input type="number" id="raWaOrd" min="1" max="60" value="15"> orders</label>
    <button type="button" class="ra-wa-btn primary ra-wa-btn-sm" id="raWaFilter">Filter</button>
  </div>

  <div class="ra-wa-body">
    <div class="ra-wa-list">
      <div class="lh">
        <input type="checkbox" id="raWaAll" checked aria-label="Select all">
        <span id="raWaLh">Hit Filter to load riders</span>
      </div>
      <div id="raWaRows">
        <div class="ra-wa-empty">Set parameters and press Filter.</div>
      </div>
    </div>
    <div class="ra-wa-side">
      <div class="ra-wa-phone" id="raWaPhone">
        <div class="ra-wa-top">
          <div class="av" id="raWaPAv">RA</div>
          <div>
            <b id="raWaPName">—</b>
            <small id="raWaPSub">Rider · WhatsApp</small>
          </div>
        </div>
        <div class="ra-wa-chat" id="raWaChat">
          <span class="ra-wa-day">Today</span>
          <div class="ra-wa-stack" id="raWaMsgStack">
            <div class="ra-wa-msg" id="raWaMsgPreview">
              <div class="txt" id="raWaMsgText">Select a rider after filtering to preview the message.</div>
              <div class="meta"><span>Preview</span></div>
            </div>
          </div>
        </div>
      </div>
      <div class="ra-wa-send-panel" id="raWaSendPanel">
        <label for="raWaFrom">Send from
          <select id="raWaFrom" class="no-select2" data-no-select2>
            @forelse($waNumbers as $num)
              <option value="{{ $num->id }}" @selected($loop->first)>{{ $num->displayLabel() }}</option>
            @empty
              <option value="">{{ __('No WhatsApp number allotted to you') }}</option>
            @endforelse
          </select>
        </label>
        <button type="button" class="ra-wa-btn primary" id="raWaSend" disabled
                @if($waNumbers->isEmpty()) data-wa-send-blocked="1" title="{{ __('No WhatsApp number allotted to you') }}" @endif>Send now</button>
      </div>
    </div>
  </div>

  <div class="ra-wa-ft">
    <span class="note" id="raWaNote">Each selected rider gets the message template on their phone number.</span>
  </div>
</aside>

<div class="ra-toast" id="raToast"></div>
